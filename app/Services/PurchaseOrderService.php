<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderService
{
    public function __construct(private StockService $stock)
    {
    }

    /**
     * @param array{supplier_id:int, notes?:?string, expected_at?:mixed, items:array<int,array{product_id:int,quantity:int,unit_cost:numeric}>} $data
     */
    public function create(array $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $total = 0.0;
            $lines = [];

            foreach ($data['items'] as $row) {
                $qty = (int) $row['quantity'];
                $cost = (float) $row['unit_cost'];
                $line = round($qty * $cost, 2);
                $total += $line;

                $lines[] = [
                    'product_id' => $row['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => $line,
                ];
            }

            $po = PurchaseOrder::create([
                'po_number' => 'TMP-'.Str::uuid(),
                'supplier_id' => $data['supplier_id'],
                'status' => 'draft',
                'total' => round($total, 2),
                'notes' => $data['notes'] ?? null,
                'expected_at' => $data['expected_at'] ?? null,
                'created_by' => $userId,
            ]);

            $po->update(['po_number' => sprintf('PO-%s-%05d', date('Y'), $po->id)]);
            $po->items()->createMany($lines);

            return $po->load('items');
        });
    }

    public function markOrdered(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new DomainException('Only draft purchase orders can be sent to the supplier.');
        }

        $po->update(['status' => 'ordered']);

        return $po;
    }

    /** Receiving goods increases stock and records one movement per line. */
    public function receive(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $userId) {
            $po = PurchaseOrder::lockForUpdate()->findOrFail($po->id);

            if ($po->status !== 'ordered') {
                throw new DomainException('Only purchase orders with status "ordered" can be received.');
            }

            $items = $po->items()->get();
            $products = Product::whereIn('id', $items->pluck('product_id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $this->stock->move($product, $item->quantity, 'purchase', $po, 'Received '.$po->po_number, $userId);
                $product->update(['cost_price' => $item->unit_cost]);
            }

            $po->update(['status' => 'received', 'received_at' => now()]);

            return $po;
        });
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (! in_array($po->status, ['draft', 'ordered'], true)) {
            throw new DomainException('Only draft or ordered purchase orders can be cancelled.');
        }

        $po->update(['status' => 'cancelled']);

        return $po;
    }
}
