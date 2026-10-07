<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Inventory;
use App\Support\Storefront;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Buying stock (Drops & Stock > Suppliers, Buying stock). A purchase order goes
 * draft -> ordered -> received (or cancelled). Receiving it adds each line to stock as a
 * "purchase" stock movement; the cost stays on the purchase order line.
 */
class StockApiController extends Controller
{
    /* ---------- Suppliers ---------- */

    public function suppliers(Request $request): JsonResponse
    {
        $this->staff($request, 'manage_suppliers');

        return response()->json(['suppliers' => $this->supplierList()]);
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $this->staff($request, 'manage_suppliers');
        $supplier = Supplier::create($this->validateSupplier($request) + ['is_active' => true]);

        return response()->json(['supplier' => $this->supplierJson($supplier->loadCount('purchaseOrders')), 'suppliers' => $this->supplierList()], 201);
    }

    public function updateSupplier(Request $request, Supplier $supplier): JsonResponse
    {
        $this->staff($request, 'manage_suppliers');
        $supplier->update($this->validateSupplier($request, $supplier));

        return response()->json(['supplier' => $this->supplierJson($supplier->loadCount('purchaseOrders')), 'suppliers' => $this->supplierList()]);
    }

    /** A supplier with purchase orders is hidden instead of deleted, so its orders keep it. */
    public function destroySupplier(Request $request, Supplier $supplier): JsonResponse
    {
        $this->staff($request, 'manage_suppliers');
        $this->staff($request, 'delete_products');

        $archived = $supplier->purchaseOrders()->exists();
        $archived ? $supplier->update(['is_active' => false]) : $supplier->delete();

        return response()->json(['ok' => true, 'archived' => $archived, 'suppliers' => $this->supplierList()]);
    }

    /* ---------- Purchase orders ---------- */

    public function purchaseOrders(Request $request): JsonResponse
    {
        $this->staff($request, 'manage_stock');

        return response()->json(['purchaseOrders' => $this->orderList()]);
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $user = $this->staff($request, 'manage_stock');
        $data = $this->validateOrder($request);

        $po = DB::transaction(function () use ($data, $user) {
            $po = PurchaseOrder::create([
                'po_number' => $this->newNumber(),
                'supplier_id' => $data['supplierId'],
                'ordered_by' => $user->id,
                'status' => $data['place'] ? 'ordered' : 'draft',
                'ordered_at' => $data['place'] ? now() : null,
            ]);
            $this->saveLines($po, $data['lines']);

            return $po;
        });

        return response()->json(['purchaseOrder' => $this->orderJson($po->fresh()), 'purchaseOrders' => $this->orderList()], 201);
    }

    /**
     * Edit a draft (supplier and lines), or move it on:
     * action = order (draft -> ordered), receive (ordered -> received, adds the stock), cancel.
     */
    public function updatePurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $user = $this->staff($request, 'manage_stock');
        $action = $request->validate(['action' => ['sometimes', Rule::in(['order', 'receive', 'cancel'])]])['action'] ?? null;
        $po = $purchaseOrder;

        if ($action === null) {
            abort_unless($po->status === 'draft', 422, 'Only a draft can be edited. Cancel it and make a new one instead.');
            $data = $this->validateOrder($request);
            DB::transaction(function () use ($po, $data) {
                $po->update(['supplier_id' => $data['supplierId']]);
                $po->items()->delete();
                $this->saveLines($po, $data['lines']);
                if ($data['place']) {
                    $po->update(['status' => 'ordered', 'ordered_at' => now()]);
                }
            });
        } else {
            $allowed = ['order' => ['draft'], 'receive' => ['ordered'], 'cancel' => ['draft', 'ordered']][$action];
            abort_unless(in_array($po->status, $allowed, true), 422, "This purchase order is {$po->status}, so it can't be {$action}ed now.");

            DB::transaction(function () use ($po, $action, $user) {
                match ($action) {
                    'order' => $po->update(['status' => 'ordered', 'ordered_at' => now()]),
                    'cancel' => $po->update(['status' => 'cancelled']),
                    'receive' => (function () use ($po, $user) {
                        foreach ($po->items()->with('product')->get() as $line) {
                            Inventory::receive($line->product, $line->quantity, $user, $line->id);
                        }
                        $po->update(['status' => 'received', 'received_at' => now()]);
                    })(),
                };
            });
            if ($action === 'receive') {
                Storefront::forgetCatalog(); // stock changed
            }
        }

        $products = $action === 'receive'
            ? Product::whereIn('id', $po->items()->pluck('product_id'))->get()->map(fn ($p) => Storefront::product($p))->values()
            : [];

        return response()->json(['purchaseOrder' => $this->orderJson($po->fresh()), 'purchaseOrders' => $this->orderList(), 'products' => $products]);
    }

    /** Only drafts can be deleted; anything that was ordered stays as a record. */
    public function destroyPurchaseOrder(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->staff($request, 'manage_stock');
        abort_unless($purchaseOrder->status === 'draft', 422, 'Only a draft can be deleted. Cancel an ordered purchase instead.');
        $purchaseOrder->delete();

        return response()->json(['ok' => true, 'purchaseOrders' => $this->orderList()]);
    }

    /* ---------- Helpers ---------- */

    private function staff(Request $request, string $permission): User
    {
        $user = $request->user();
        abort_unless($user, 401, 'Please log in first.');
        abort_unless($user->isStaff() && $user->status !== 'Suspended' && $user->hasPermission($permission), 403, 'Your role does not allow this.');

        return $user;
    }

    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        $req = $supplier ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$req, 'string', 'min:2', 'max:100'],
            'contactName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^[+0-9\s\-()]+$/'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'isActive' => ['sometimes', 'boolean'],
        ], [
            'name.required' => 'Give the supplier a name.',
            'phone.regex' => 'Please enter a valid phone number.',
        ]);
        $map = ['name' => 'name', 'contactName' => 'contact_name', 'phone' => 'phone', 'email' => 'email', 'address' => 'address', 'isActive' => 'is_active'];
        $out = [];
        foreach ($map as $in => $column) {
            if (array_key_exists($in, $data)) {
                $out[$column] = is_string($data[$in]) ? trim($data[$in]) : $data[$in];
            }
        }

        return $out;
    }

    /** @return array{supplierId:int, place:bool, lines:array<int, array{product:Product, quantity:int, unitCost:float}>} */
    private function validateOrder(Request $request): array
    {
        $data = $request->validate([
            'supplierId' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'place' => ['sometimes', 'boolean'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product' => ['required', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'lines.*.unitCost' => ['required', 'numeric', 'min:0', 'max:100000'],
        ], [
            'supplierId.required' => 'Choose the supplier.',
            'supplierId.exists' => 'Choose an active supplier.',
            'lines.required' => 'Add at least one product.',
            'lines.*.quantity.min' => 'Each quantity must be at least 1.',
        ]);

        $lines = [];
        foreach ($data['lines'] as $i => $line) {
            $product = Product::findByKey($line['product']) ?? throw ValidationException::withMessages(["lines.{$i}.product" => "Product {$line['product']} was not found."]);
            if (isset($lines[$product->id])) {
                throw ValidationException::withMessages(["lines.{$i}.product" => "“{$product->title}” is in the list twice; change its quantity instead."]);
            }
            $lines[$product->id] = ['product' => $product, 'quantity' => (int) $line['quantity'], 'unitCost' => round((float) $line['unitCost'], 2)];
        }

        return ['supplierId' => (int) $data['supplierId'], 'place' => (bool) ($data['place'] ?? false), 'lines' => array_values($lines)];
    }

    private function saveLines(PurchaseOrder $po, array $lines): void
    {
        foreach ($lines as $line) {
            $po->items()->create(['product_id' => $line['product']->id, 'quantity' => $line['quantity'], 'unit_cost_usd' => $line['unitCost']]);
        }
    }

    private function newNumber(): string
    {
        do {
            $number = 'PO-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        } while (PurchaseOrder::where('po_number', $number)->exists());

        return $number;
    }

    private function supplierList(): array
    {
        return Supplier::withCount('purchaseOrders')->orderByDesc('is_active')->orderBy('name')->get()->map(fn ($s) => $this->supplierJson($s))->all();
    }

    private function supplierJson(Supplier $s): array
    {
        return [
            'id' => $s->id, 'name' => $s->name, 'contactName' => $s->contact_name, 'phone' => $s->phone,
            'email' => $s->email, 'address' => $s->address, 'isActive' => (bool) $s->is_active,
            'purchaseOrders' => (int) ($s->purchase_orders_count ?? 0),
        ];
    }

    private function orderList(): array
    {
        return PurchaseOrder::with(['supplier:id,name', 'orderedBy:id,name', 'items.product:id,sku,title'])->latest('id')->limit(200)->get()
            ->map(fn ($po) => $this->orderJson($po))->all();
    }

    private function orderJson(PurchaseOrder $po): array
    {
        $po->loadMissing(['supplier:id,name', 'orderedBy:id,name', 'items.product:id,sku,title']);
        $lines = $po->items->map(fn ($i) => [
            'product' => $i->product?->sku ?? 'db-'.$i->product_id,
            'title' => $i->product?->title,
            'quantity' => (int) $i->quantity,
            'unitCost' => (float) $i->unit_cost_usd,
            'lineTotal' => round((float) $i->unit_cost_usd * $i->quantity, 2),
        ])->values();

        return [
            'id' => $po->id,
            'number' => $po->po_number,
            'status' => $po->status,
            'supplierId' => $po->supplier_id,
            'supplier' => $po->supplier?->name,
            'orderedBy' => $po->orderedBy?->name,
            'orderedAt' => optional($po->ordered_at)->toIso8601String(),
            'receivedAt' => optional($po->received_at)->toIso8601String(),
            'createdAt' => optional($po->created_at)->toIso8601String(),
            'lines' => $lines,
            'totalUSD' => round($lines->sum('lineTotal'), 2), // calculated from the lines, not stored
        ];
    }
}
