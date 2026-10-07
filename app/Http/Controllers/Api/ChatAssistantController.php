<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyTier;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Support\Storefront;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Customer chat assistant (chat.html), answered by Google Gemini. The API key never reaches the browser:
 * the page sends the conversation here and this controller adds the shop facts, catalog and the
 * customer's own orders before asking Gemini.
 */
class ChatAssistantController extends Controller
{
    public function reply(Request $request): JsonResponse
    {
        $key = config('services.gemini.key');
        if (! $key) {
            return response()->json(['message' => 'The AI assistant is not set up yet.'], 503);
        }

        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:20'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.text' => ['required', 'string', 'max:1500'],
        ]);

        $contents = collect($data['messages'])
            ->map(fn ($m) => ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['text']]]])
            ->values()->all();

        if (end($contents)['role'] !== 'user') {
            return response()->json(['message' => 'Send a question first.'], 422);
        }

        // Try the main model, then the backup model when the main one is rate-limited or overloaded
        // (each model has its own free-tier allowance).
        $models = array_values(array_unique(array_filter([config('services.gemini.model'), config('services.gemini.fallback_model')])));
        $response = null;

        foreach ($models as $i => $model) {
            try {
                // Gemini sometimes answers "high demand, try again" (503) or a brief 500; retry those twice.
                $response = Http::timeout(30)
                    ->retry(3, 1200, fn ($e) => $e instanceof RequestException && in_array($e->response->status(), [500, 502, 503], true), throw: false)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                        'system_instruction' => ['parts' => [['text' => $this->instructions($request)]]],
                        'contents' => $contents,
                        // The cap includes the model's hidden "thinking" tokens, so it's well above the length of a short answer.
                        'generationConfig' => ['temperature' => 0.5, 'maxOutputTokens' => 4096],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('Gemini request failed', ['model' => $model, 'error' => $e->getMessage()]);
                $response = null;
                continue;
            }

            if ($response->successful()) {
                break;
            }
            Log::warning('Gemini returned an error', ['model' => $model, 'status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            if (! in_array($response->status(), [404, 429, 500, 502, 503], true)) {
                break; // a request problem the backup model won't fix
            }
        }

        if (! $response || $response->failed()) {
            $busy = ! $response || in_array($response->status(), [429, 500, 502, 503], true);

            // "busy" tells the page to answer with its built-in basics instead of a dead end
            return response()->json([
                'busy' => $busy,
                'message' => $busy
                    ? 'The assistant is busy right now. Please try again in a minute.'
                    : 'The assistant could not answer right now. Please try again, or message us on Telegram.',
            ], 502);
        }

        $text = collect($response->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');

        return response()->json([
            'reply' => trim($text) ?: "Sorry, I couldn't come up with an answer to that. You can ask us directly on Telegram: https://t.me/psaonline_support",
        ]);
    }

    /** Everything the assistant is allowed to know, and how it should behave. */
    private function instructions(Request $request): string
    {
        $catalog = Storefront::catalog(false);
        $usd = fn ($v) => '$'.number_format((float) $v, 2);
        $money = fn ($v) => '$'.rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $percent = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.').'%';
        $date = fn ($iso) => $iso ? Carbon::parse($iso)->timezone(config('app.timezone'))->format('j M Y') : null;

        $products = collect($catalog['products'])->map(function ($p) use ($usd, $percent, $date) {
            $price = $usd($p['priceUSD']);
            if ($p['onSale']) {
                $ends = $date($p['discountEndsAt']);
                $price .= sprintf(' (on sale, %s off, normally %s%s)', $percent($p['discountPercent']), $usd($p['originalPriceUSD']), $ends ? ", sale ends {$ends}" : '');
            }
            $details = collect((array) $p['specifications'])->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');

            return sprintf('- %s | %s | %s | %s | %s%s%s%s | link: product-detail.html?id=%s',
                $p['id'], $p['title'], $p['categoryLabel'], $price,
                $p['inStock'] ? 'in stock' : 'sold out',
                $p['reviewsCount'] ? sprintf(' | rated %s/5 (%d reviews)', $p['rating'], $p['reviewsCount']) : '',
                $p['badge'] ? ' | '.$p['badge'] : '',
                ! empty($p['freeDelivery']) ? ' | FREE DELIVERY item' : '',
                $p['id'],
            ).($p['description'] ? "\n  About: ".$p['description'] : '').($details ? "\n  Details: ".$details : '');
        })->implode("\n");

        $categories = collect($catalog['categories'])->where('productCount', '>', 0)
            ->map(fn ($c) => "{$c['name']} ({$c['productCount']})")->implode(', ');

        $methods = collect($catalog['paymentMethods'])
            ->filter(fn ($m) => $m['isActive'])
            ->map(fn ($m) => '- '.$m['name'].($m['description'] ? ': '.$m['description'] : ''))
            ->implode("\n");

        // Only the codes staff chose to show customers under "My coupons"; other codes stay secret.
        $coupons = collect(Storefront::coupons())
            ->map(fn ($c) => sprintf('- %s: %s%s%s%s', $c['code'], $c['label'],
                $c['minOrderUSD'] ? ' on items worth '.$money($c['minOrderUSD']).' or more' : '',
                $c['endsAt'] ? ', until '.$date($c['endsAt']) : '',
                $c['description'] ? ' ('.$c['description'].')' : ''))
            ->implode("\n") ?: '- None right now.';

        $tiers = LoyaltyTier::ordered()
            ->map(fn (LoyaltyTier $t) => (float) $t->min_spend_usd > 0
                ? "  - {$t->name}: after spending {$money($t->min_spend_usd)} on delivered orders, {$percent($t->discount_percent)} off items"
                : "  - {$t->name}: every customer starts here (no discount)")
            ->implode("\n");
        $lapseDays = LoyaltyTier::lapseDays();

        $user = $request->user();
        $customer = $user ? "The customer is signed in as {$user->name}." : 'The customer is not signed in.';
        if ($user && ! $user->isStaff()) {
            $l = Storefront::loyalty($user);
            $customer .= sprintf(' Their membership: %s (%s off items), %s spent toward it.', $l['tier'], $percent($l['discountPercent']), $money($l['spendUSD']))
                .($l['nextTier'] ? sprintf(' %s more to reach %s.', $money($l['spendToNextUSD']), $l['nextTier']) : ' This is the top tier.')
                .($l['expiresAt'] ? ' It ends on '.$date($l['expiresAt']).' unless they order again before then.' : '');
        }

        $orders = $user
            ? Order::with(Order::PAGE_RELATIONS)->ofUser($user->id)->latest()->limit(5)->get()
                ->map(fn ($o) => sprintf('- %s placed %s: %s, total $%s, paid by %s, items: %s, link: order-detail.html?order=%s', $o->order_number,
                    optional($o->created_at)->format('d M Y'), Storefront::statusLabel($o), number_format((float) $o->total_usd, 2), Storefront::paymentName($o->payment_method),
                    $o->items->map(fn ($i) => $i->quantity.' x '.($i->product?->title ?? 'item'))->implode(', '), $o->order_number))
                ->implode("\n")
            : '';

        // Delivery, contact details, social accounts and chat clean-up come from the Website page (site settings).
        $delivery = '$'.number_format(SiteSetting::number('shop.delivery_fee_usd'), 2);
        $freeFrom = $money(SiteSetting::number('shop.free_delivery_from_usd'));
        $telegramUrl = SiteSetting::get('social.telegram') ?: 'https://t.me/psaonline_support';
        $telegram = SiteSetting::socialName('telegram', $telegramUrl);
        $chatDays = (int) SiteSetting::number('chat.auto_delete_days');
        $contact = collect(['Phone' => 'footer.phone', 'Email' => 'footer.email', 'Address' => 'footer.address', 'Opening hours' => 'footer.hours'])
            ->map(fn ($key) => SiteSetting::get($key))->filter()
            ->map(fn ($value, $label) => "- {$label}: {$value}")->implode("\n");
        $social = collect(SiteSetting::SOCIAL_PLATFORMS)
            ->map(fn ($p) => ($url = SiteSetting::get("social.{$p}")) ? SiteSetting::FIELDS["social.{$p}"][1].' '.$url : null)->filter()->implode(', ');

        return <<<PROMPT
You are the friendly shopping assistant for PsaOnline, an online shop in Phnom Penh, Cambodia, for cute gifts and everyday finds (bag and phone charms, gift sets, hair clips, socks, tees, shirts, bags and watches).

How to answer:
- Reply in the customer's language (Khmer or English). Keep answers short and warm: 1 to 4 sentences, or a short list.
- Answer from the shop facts, catalog and account details below. They cover the products, prices, sales, delivery, payment, coupons, membership, orders, returns and how every part of the website works, so most questions can be answered from them. Combine facts when needed (for example, work out whether a bag gets free delivery, or which products fit a budget).
- For general questions that aren't about this shop's own rules (gift ideas, what suits someone, how to scan a KHQR code with a banking app, how to care for a fabric), answer helpfully from common knowledge, and suggest matching products from the catalog when it fits.
- Never invent products, prices, discounts, promo codes, delivery times, stock or policies that aren't below. Only when a question needs a shop fact that isn't here, say you're not sure and suggest messaging the shop on Telegram ({$telegramUrl}) or from their order page.
- When you suggest a product, give its name, price in USD, and its link exactly as written in the catalog (for example product-detail.html?id=genz-25). Suggest at most 3.
- You can't change or cancel orders, take payments or see payment slips. For anything about a specific order beyond what is listed below, tell them to use "Messages with the shop" on their order page.
- Never ask for card numbers, passwords or banking PINs.
- To point to a page, write its link in place of the page name and the chat shows it as a button with the right label: dashboard-buyer.html (My account), products.html (the shop), products.html?q=gift (gift ideas), products.html?max=10 (gifts under $10), cart.html (your bag), checkout.html (checkout), wishlist.html (saved items), setting.html (settings), about.html (about us), order-detail.html?order=ORDER_NUMBER (one order). For example: "Go to dashboard-buyer.html and tap your order." Never put links in brackets.

Shop facts:
- Delivery: Phnom Penh only. {$delivery}, free on orders of {$freeFrom} or more, and free for the whole order when the bag has any item marked FREE DELIVERY. Orders are usually sent out within 24 to 48 hours after the payment is checked (cash on delivery: after the shop confirms the address and phone). Rain or traffic can sometimes delay a delivery. Keep your phone on so the courier can reach you; a second delivery attempt may cost extra.
- All prices are in US dollars (USD) only. The checkout and order pages also show the riel amount (4,000 riel to $1) for paying by KHQR.
- Browsing: the shop is products.html. You can search, filter by category or price, and sort. Products on sale show the old price crossed out. Tap the heart to save an item to wishlist.html; items you looked at recently are under "Viewed history" in My account. The bag, saved items and recently viewed items stay on the customer's own device.
- Accounts: anyone can browse and fill a bag without an account. To place an order, sign in or register at checkout (name, email, phone number and a password of at least 6 characters). In setting.html customers change their name, phone, profile photo, password, saved delivery addresses and light or dark theme.
- To order: add items to the bag (cart.html), go to checkout.html, sign in, enter the delivery address in Phnom Penh (you can drop a map pin so the courier finds you), add a coupon code if you have one, choose a payment method and place the order.
- Paying by KHQR / bank: after ordering, the payment page shows the shop's QR code. Pay with any Cambodian banking app (ABA, ACLEDA, Canadia, Wing and others), then upload a screenshot of the transfer slip on the order page. Staff check the slip, then pack and send the order.
- Cash on delivery: pay the courier the exact amount when the parcel arrives.
- Order steps, as shown on the order page: Payment Pending (waiting for payment or slip), Slip Uploaded (staff are checking it), Payment Verified, Processing (being packed), Out for delivery, Delivered. An order can also be Cancelled, or show Payment Failed if the slip could not be confirmed.
- Track orders from My account dashboard-buyer.html, then tap the order. Each order page has "Messages with the shop" to chat with staff about that order. A chat with no new messages for {$chatDays} days is cleared automatically.
- Reviews: after an order is delivered, customers can rate and review its items from the order page.
- Coupons (promo codes): one code per order, typed at checkout or picked with "Use" under My coupons in My account. A code takes money off the items, not the delivery fee. Each code may need a minimum amount, have an end date, or be usable only a limited number of times. If an order is cancelled, the code can be used again.
- Membership (Plus, Pro, Max): the more a customer spends on delivered orders, the bigger their automatic discount on items at checkout. It works together with a coupon: the member discount comes off first, then the coupon. If a customer places no order for {$lapseDays} days, their membership goes back to Plus and their spending starts again from $0. My account shows their current tier and how much more they need for the next one.
{$tiers}
- Cancelling: an order can be cancelled for free before it is sent out. Ask the shop through "Messages with the shop" on the order page or on Telegram. Once the courier has it, it can't be cancelled.
- Damaged, broken or wrong items: message the shop on Telegram soon after delivery with the order number and clear photos, and the shop will arrange a replacement or a full refund. Earrings, ear cuffs, pierced jewelry and hair accessories that have been worn can't be returned for a change of mind once unpacked. If an item turns out to be out of stock or priced by mistake, the shop tells you and refunds anything you paid.
- Privacy: the shop never asks for card numbers or banking PINs, doesn't sell customer details, and only uses them to deliver orders.
- Human help: Telegram {$telegram} ({$telegramUrl}), every day.

Contact details:
{$contact}
- Social media: {$social}

Product categories: {$categories}

Payment methods currently available:
{$methods}

Coupons customers can use right now:
{$coupons}

Catalog (id | name | category | price | stock | rating | tag | link), with a short description and details under each:
{$products}

{$customer}
{$this->ordersBlock($orders)}
PROMPT;
    }

    private function ordersBlock(string $orders): string
    {
        return $orders === '' ? '' : "Their recent orders:\n{$orders}";
    }
}
