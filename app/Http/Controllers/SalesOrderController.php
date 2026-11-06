<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $term = mb_strtolower(trim((string) $request->input('search')));

        $orders = SalesOrder::with('customer:id,name')
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(order_number) LIKE ?', [$like])
                        ->orWhereHas('customer', fn ($c) => $c->whereRaw('LOWER(name) LIKE ?', [$like]));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('SalesOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('SalesOrders/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')
                ->get(['id', 'sku', 'name', 'unit_price', 'stock_on_hand']),
            'vatRate' => config('erp.vat_rate'),
        ]);
    }

    public function store(Request $request, SalesOrderService $service): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        $order = $service->create($data, $request->user()->id);

        return redirect('/sales-orders/'.$order->id)->with('success', 'Draft order created. Confirm it to reserve stock and issue the invoice.');
    }

    public function show(SalesOrder $salesOrder): Response
    {
        return Inertia::render('SalesOrders/Show', [
            'order' => $salesOrder->load(['customer', 'items.product', 'invoice', 'creator:id,name']),
        ]);
    }

    public function confirm(Request $request, SalesOrder $salesOrder, SalesOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->confirm($salesOrder, $request->user()->id), 'Order confirmed, stock deducted and invoice issued.');
    }

    public function ship(SalesOrder $salesOrder, SalesOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->ship($salesOrder), 'Order marked as shipped.');
    }

    public function cancel(Request $request, SalesOrder $salesOrder, SalesOrderService $service): RedirectResponse
    {
        return $this->run(fn () => $service->cancel($salesOrder, $request->user()->id), 'Order cancelled.');
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
