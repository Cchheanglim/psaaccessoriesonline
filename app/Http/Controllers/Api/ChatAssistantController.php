<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Storefront;
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
        $products = collect(Storefront::catalog(false)['products'])
            ->map(fn ($p) => sprintf(
                '- %s | %s | %s | $%.2f (%s riel) | %s | link: product-detail.html?id=%s',
                $p['id'], $p['title'], $p['categoryLabel'], $p['priceUSD'], number_format($p['priceKHR']),
                $p['inStock'] ? 'in stock' : 'sold out', $p['id'],
            ))->implode("\n");

        $methods = collect(Storefront::catalog(false)['paymentMethods'])
            ->filter(fn ($m) => $m['isActive'])
            ->map(fn ($m) => '- '.$m['name'].($m['description'] ? ': '.$m['description'] : ''))
            ->implode("\n");

        $user = $request->user();
        $orders = $user
            ? Order::where('user_id', $user->id)->latest()->limit(5)->get()
                ->map(fn ($o) => sprintf('- %s placed %s: %s, total $%s, paid by %s', $o->order_number,
                    optional($o->created_at)->format('d M Y'), Storefront::statusLabel($o), number_format((float) $o->total_usd, 2), Storefront::paymentName($o->payment_method)))
                ->implode("\n")
            : '';

        $customer = $user ? "The customer is signed in as {$user->name}." : 'The customer is not signed in.';

        return <<<PROMPT
You are the friendly shopping assistant for PsaOnline, an online shop in Phnom Penh, Cambodia, for cute gifts and everyday finds (bag and phone charms, gift sets, hair clips, socks, tees, shirts, bags and watches).

How to answer:
- Reply in the customer's language (Khmer or English). Keep answers short and warm: 1 to 4 sentences, or a short list.
- Only use the facts below. Never invent products, prices, discounts, promo codes, delivery times, stock, or policies. If you don't know, say so and suggest messaging the shop on Telegram (https://t.me/psaonline_support) or from their order page.
- When you suggest a product, give its name, price in USD and riel, and its link exactly as written in the catalog (for example product-detail.html?id=genz-25). Suggest at most 3.
- You can't change orders, take payments or see payment slips. For anything about a specific order beyond its status, tell them to use "Messages with the shop" on their order page.
- Never ask for card numbers, passwords or banking PINs.
- To point to a page, write its link in place of the page name and the chat shows it as a button with the right label: dashboard-buyer.html (My account), products.html (the shop), products.html?q=gift (gift ideas), products.html?max=10 (gifts under $10), cart.html (your bag). For example: "Go to dashboard-buyer.html and tap your order." Never put links in brackets.

Shop facts:
- Delivery: Phnom Penh only. $1.50, free on orders of $15 (61,500 riel) or more.
- Prices are shown in USD and riel at a fixed 1 USD = 4,100 riel.
- To order: add items to the bag, sign in at checkout, enter the delivery address, choose a payment method.
- Paying by KHQR / bank: after ordering, the payment page shows the shop's QR code. Pay with any Cambodian banking app (ABA, ACLEDA, Wing and others), then upload the transfer slip. Staff check the slip, then pack and send the order.
- Cash on delivery: pay the courier when the parcel arrives.
- Track orders from My account dashboard-buyer.html, then tap the order. Customers can review items after delivery.
- Human help: Telegram @psaonline_support.

Payment methods currently available:
{$methods}

Catalog (id | name | category | price | stock | link):
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
