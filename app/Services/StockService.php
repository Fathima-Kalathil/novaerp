<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Change stock by a signed quantity and record the movement.
     * Must be called inside a DB transaction when part of a bigger operation.
     */
    public function move(Product $product, int $quantity, string $type, ?Model $reference = null, ?string $note = null, ?int $userId = null): StockMovement
    {
        $newStock = $product->stock_on_hand + $quantity;

        if ($newStock < 0) {
            throw new DomainException(sprintf(
                'Stock for "%s" cannot go below zero (current %d, change %d).',
                $product->name, $product->stock_on_hand, $quantity
            ));
        }

        $product->update(['stock_on_hand' => $newStock]);

        return StockMovement::create([
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->getKey(),
            'created_by' => $userId,
        ]);
    }

    /** Manual stock correction (stock take, damage, opening balance). */
    public function adjust(Product $product, int $quantity, ?string $note, int $userId): StockMovement
    {
        return DB::transaction(function () use ($product, $quantity, $note, $userId) {
            $locked = Product::lockForUpdate()->findOrFail($product->id);

            return $this->move($locked, $quantity, 'adjustment', null, $note ?: 'Manual adjustment', $userId);
        });
    }
}
