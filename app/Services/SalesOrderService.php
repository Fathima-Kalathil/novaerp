<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderService
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * Create a draft order. Prices are read from the product table on the
     * server (never trusted from the browser) and copied onto the order lines.
     *
     * @param array{customer_id:int, notes?:?string, ordered_at?:mixed, items:array<int,array{product_id:int,quantity:int}>} $data
     */
    public function create(array $data, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

            $subtotal = 0.0;
            $lines = [];

            foreach ($data['items'] as $row) {
                $product = $products[$row['product_id']];
                $qty = (int) $row['quantity'];
                $price = (float) $product->unit_price;
                $line = round($price * $qty, 2);
                $subtotal += $line;

                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $line,
                ];
            }

            $subtotal = round($subtotal, 2);
            $tax = round($subtotal * config('erp.vat_rate'), 2);

            $order = SalesOrder::create([
                'order_number' => 'TMP-'.Str::uuid(),
                'customer_id' => $data['customer_id'],
                'status' => 'draft',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => round($subtotal + $tax, 2),
                'notes' => $data['notes'] ?? null,
                'ordered_at' => $data['ordered_at'] ?? now(),
                'created_by' => $userId,
            ]);

            $order->update(['order_number' => sprintf('SO-%s-%05d', date('Y'), $order->id)]);
            $order->items()->createMany($lines);

            return $order->load('items');
        });
    }

    /**
     * Confirm a draft order: check stock, deduct it, write movements, create the invoice.
     * Everything happens in ONE transaction with row locks, so two people
     * confirming at the same time can never oversell the same stock.
     */
    public function confirm(SalesOrder $order, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);

            if ($order->status !== 'draft') {
                throw new DomainException('Only draft orders can be confirmed.');
            }

            $items = $order->items()->get();

            // Lock products in a fixed order (by id) to avoid deadlocks.
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products[$item->product_id];

                if ($product->stock_on_hand < $item->quantity) {
                    throw new InsufficientStockException($product, $item->quantity);
                }
            }

            foreach ($items as $item) {
                $this->stock->move($products[$item->product_id], -$item->quantity, 'sale', $order, 'Sales order '.$order->order_number, $userId);
            }

            $order->update(['status' => 'confirmed']);

            Invoice::create([
                'sales_order_id' => $order->id,
                'invoice_number' => sprintf('INV-%s-%05d', date('Y'), $order->id),
                'status' => 'unpaid',
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
            ]);

            return $order->fresh(['items', 'invoice']);
        });
    }

    public function ship(SalesOrder $order): SalesOrder
    {
        if ($order->status !== 'confirmed') {
            throw new DomainException('Only confirmed orders can be marked as shipped.');
        }

        $order->update(['status' => 'shipped']);

        return $order;
    }

    /** Draft orders are simply cancelled; confirmed orders also return their stock. */
    public function cancel(SalesOrder $order, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = SalesOrder::lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, ['draft', 'confirmed'], true)) {
                throw new DomainException('Only draft or confirmed orders can be cancelled.');
            }

            if ($order->status === 'confirmed') {
                $items = $order->items()->get();
                $products = Product::whereIn('id', $items->pluck('product_id'))
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($items as $item) {
                    $this->stock->move($products[$item->product_id], $item->quantity, 'return', $order, 'Cancelled '.$order->order_number, $userId);
                }

                $order->invoice()->update(['status' => 'void']);
            }

            $order->update(['status' => 'cancelled']);

            return $order;
        });
    }
}
