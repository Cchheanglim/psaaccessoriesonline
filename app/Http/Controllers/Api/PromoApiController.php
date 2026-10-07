<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Staff portal > Promo codes (needs the "Promo codes" permission). */
class PromoApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->staff($request);

        return response()->json(['promoCodes' => $this->list()]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->staff($request);
        $promo = PromoCode::create($this->validated($request) + ['is_active' => true, 'show_to_customers' => false]);

        return response()->json(['promoCode' => $this->json($promo), 'promoCodes' => $this->list()], 201);
    }

    /** Once a code has been used, its code and discount stay as they were, so old orders still make sense. */
    public function update(Request $request, PromoCode $promoCode): JsonResponse
    {
        $this->staff($request);
        $data = $this->validated($request, $promoCode);
        if ($promoCode->orders()->exists()) {
            foreach (['code', 'discount_type', 'discount_value'] as $locked) {
                if (array_key_exists($locked, $data) && (string) $data[$locked] !== (string) $promoCode->{$locked}) {
                    throw ValidationException::withMessages([$locked => 'Customers have already used this code, so its code and discount can\'t change. Turn it off and create a new one.']);
                }
            }
        }
        $promoCode->update($data);

        return response()->json(['promoCode' => $this->json($promoCode->fresh()), 'promoCodes' => $this->list()]);
    }

    /** Used codes are turned off instead of deleted, so the orders that used them keep them. */
    public function destroy(Request $request, PromoCode $promoCode): JsonResponse
    {
        $this->staff($request);
        $used = $promoCode->orders()->exists();
        $used ? $promoCode->update(['is_active' => false]) : $promoCode->delete();

        return response()->json(['ok' => true, 'turnedOff' => $used, 'promoCodes' => $this->list()]);
    }

    private function validated(Request $request, ?PromoCode $promo = null): array
    {
        $req = $promo ? 'sometimes' : 'required';
        $request->merge(array_filter([
            'code' => $request->has('code') ? PromoCode::normalize($request->input('code')) : null,
        ], fn ($v) => $v !== null));

        $data = $request->validate([
            'code' => [$req, 'string', 'min:3', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('promo_codes', 'code')->ignore($promo?->id)],
            'description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'discountType' => [$req, Rule::in(['percent', 'fixed'])],
            'discountValue' => [$req, 'numeric', 'gt:0', 'max:10000'],
            'minOrderUSD' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'maxUses' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'maxUsesPerCustomer' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'startsAt' => ['sometimes', 'nullable', 'date'],
            'endsAt' => ['sometimes', 'nullable', 'date'],
            'isActive' => ['sometimes', 'boolean'],
            'showToCustomers' => ['sometimes', 'boolean'],
        ], [
            'code.required' => 'Give the code a name customers will type, like SUMMER10.',
            'code.min' => 'Codes need at least 3 characters.',
            'code.regex' => 'Use letters, numbers, - or _ only (no spaces).',
            'code.unique' => 'There is already a code with that name.',
            'discountValue.required' => 'How much should it take off?',
            'discountValue.gt' => 'The discount must be more than 0.',
        ]);

        $type = $data['discountType'] ?? $promo?->discount_type;
        $value = (float) ($data['discountValue'] ?? $promo?->discount_value);
        if ($type === 'percent' && $value > 100) {
            throw ValidationException::withMessages(['discountValue' => 'A percentage discount can be 100% at most.']);
        }
        $starts = array_key_exists('startsAt', $data) ? $data['startsAt'] : $promo?->starts_at;
        $ends = array_key_exists('endsAt', $data) ? $data['endsAt'] : $promo?->ends_at;
        if ($starts && $ends && Carbon::parse($ends)->lte(Carbon::parse($starts))) {
            throw ValidationException::withMessages(['endsAt' => 'The end date must be after the start date.']);
        }

        $map = [
            'code' => 'code', 'description' => 'description', 'discountType' => 'discount_type', 'discountValue' => 'discount_value',
            'minOrderUSD' => 'min_order_usd', 'maxUses' => 'max_uses', 'maxUsesPerCustomer' => 'max_uses_per_customer',
            'startsAt' => 'starts_at', 'endsAt' => 'ends_at', 'isActive' => 'is_active', 'showToCustomers' => 'show_to_customers',
        ];
        $out = [];
        foreach ($map as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out[$column] = is_string($data[$in]) && $in === 'description' ? (trim($data[$in]) ?: null) : $data[$in];
            }
        }
        // An end date with no time means "until the end of that day".
        if (! empty($out['ends_at']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $out['ends_at'])) {
            $out['ends_at'] = Carbon::parse($out['ends_at'])->endOfDay();
        }

        return $out;
    }

    private function list(): array
    {
        return PromoCode::withCount(['orders as uses_count' => fn ($q) => $q->whereDoesntHave('currentStatus', fn ($s) => $s->where('order_status_id', \App\Models\OrderStatus::idFor('cancelled')))])
            ->orderByDesc('id')->get()->map(fn ($p) => $this->json($p))->values()->all();
    }

    private function json(PromoCode $p): array
    {
        $uses = $p->uses_count ?? $p->usedOrders()->count();

        return [
            'id' => $p->id,
            'code' => $p->code,
            'description' => $p->description,
            'label' => $p->label(),
            'discountType' => $p->discount_type,
            'discountValue' => (float) $p->discount_value,
            'minOrderUSD' => $p->min_order_usd !== null ? (float) $p->min_order_usd : null,
            'maxUses' => $p->max_uses,
            'maxUsesPerCustomer' => $p->max_uses_per_customer,
            'startsAt' => optional($p->starts_at)->toIso8601String(),
            'endsAt' => optional($p->ends_at)->toIso8601String(),
            'isActive' => $p->is_active,
            'showToCustomers' => $p->show_to_customers,
            'state' => $p->state($uses),
            'uses' => $uses,
            'discountGivenUSD' => number_format($this->discountGiven($p), 2, '.', ''),
            'used' => $p->orders()->exists(),
        ];
    }

    /** What the code took off its (not cancelled) orders, worked out from each order's items. */
    private function discountGiven(PromoCode $p): float
    {
        return round($p->usedOrders()->with('items')->get()->each->setRelation('promoCode', $p)
            ->sum(fn ($o) => $o->promo_discount_usd), 2);
    }

    private function staff(Request $request): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($user->isStaff() && $user->status !== 'Suspended' && $user->hasPermission('manage_promotions'), 403,
            'Your role does not have the "Promo codes" permission. Ask an Admin to tick it under Staff & roles.');

        return $user;
    }
}
