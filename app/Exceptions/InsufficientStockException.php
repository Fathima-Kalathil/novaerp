<?php

namespace App\Exceptions;

use App\Models\Product;
use DomainException;

class InsufficientStockException extends DomainException
{
    public function __construct(Product $product, int $requested)
    {
        parent::__construct(sprintf(
            'Not enough stock for "%s": %d requested, %d available.',
            $product->name,
            $requested,
            $product->stock_on_hand
        ));
    }
}
