<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $term = mb_strtolower(trim((string) $request->input('search')));

        $orders = PurchaseOrder::with('supplier:id,name')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(po_number) LIKE ?', [$like])
                        ->orWhereHas('supplier', fn ($s) => $s->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('PurchaseOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PurchaseOrders/Create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')
                ->get(['id', 'sku', 'name', 'cost_price', 'stock_on_hand']),
        ]);
    }

    public function store(Request $request, PurchaseOrderService $service): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $po = $service->create($data, $request->user()->id);

        return redirect('/purchase-orders/'.$po->id)->with('success', 'Draft purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        return Inertia::render('PurchaseOrders/Show', [
            'order' => $purchaseOrder->load(['supplier', 'items.product', 'creator:id,name']),
        ]);
    }

    public function order(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->markOrdered($purchaseOrder), 'Purchase order sent to supplier.');
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->receive($purchaseOrder, $request->user()->id), 'Goods received and stock increased.');
    }

    public function cancel(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->cancel($purchaseOrder), 'Purchase order cancelled.');
    }

    private function run(callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
