<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(int $stock = 10): Product
    {
        return Product::create([
            'sku' => 'T-'.uniqid(), 'name' => 'Test product', 'unit_price' => 100,
            'cost_price' => 60, 'reorder_level' => 2, 'stock_on_hand' => $stock,
        ]);
    }

    private function makeDraft(Product $product, int $qty): array
    {
        $user = User::create(['name' => 'T', 'email' => 't'.uniqid().'@x.test', 'password' => 'secret123', 'role' => 'sales']);
        $customer = Customer::create(['name' => 'Acme']);

        $order = app(SalesOrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => $qty]],
        ], $user->id);

        return [$order, $user];
    }

    public function test_draft_order_calculates_totals_with_vat(): void
    {
        [$order] = $this->makeDraft($this->makeProduct(), 3);

        $this->assertSame('300.00', $order->subtotal);
        $this->assertSame('15.00', $order->tax);
        $this->assertSame('315.00', $order->total);
        $this->assertStringStartsWith('SO-', $order->order_number);
    }

    public function test_confirming_deducts_stock_and_creates_invoice(): void
    {
        $product = $this->makeProduct(10);
        [$order, $user] = $this->makeDraft($product, 4);

        $confirmed = app(SalesOrderService::class)->confirm($order, $user->id);

        $this->assertSame('confirmed', $confirmed->status);
        $this->assertSame(6, $product->fresh()->stock_on_hand);
        $this->assertNotNull($confirmed->invoice);
        $this->assertSame(-4, StockMovement::where('product_id', $product->id)->sum('quantity'));
    }

    public function test_confirming_is_blocked_when_stock_is_insufficient(): void
    {
        $product = $this->makeProduct(2);
        [$order, $user] = $this->makeDraft($product, 5);

        $this->expectException(InsufficientStockException::class);

        try {
            app(SalesOrderService::class)->confirm($order, $user->id);
        } finally {
            $this->assertSame(2, $product->fresh()->stock_on_hand); // untouched
            $this->assertSame('draft', $order->fresh()->status);
        }
    }

    public function test_cancelling_a_confirmed_order_returns_stock(): void
    {
        $product = $this->makeProduct(10);
        [$order, $user] = $this->makeDraft($product, 4);
        $service = app(SalesOrderService::class);

        $service->confirm($order, $user->id);
        $service->cancel($order->fresh(), $user->id);

        $this->assertSame(10, $product->fresh()->stock_on_hand);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_receiving_a_purchase_order_increases_stock(): void
    {
        $product = $this->makeProduct(1);
        $user = User::create(['name' => 'W', 'email' => 'w@x.test', 'password' => 'secret123', 'role' => 'warehouse']);
        $supplier = Supplier::create(['name' => 'Supplier']);
        $service = app(PurchaseOrderService::class);

        $po = $service->create([
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 25, 'unit_cost' => 55]],
        ], $user->id);
        $service->markOrdered($po);
        $service->receive($po, $user->id);

        $this->assertSame(26, $product->fresh()->stock_on_hand);
    }

    public function test_guests_are_redirected_and_roles_are_enforced(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');

        $sales = User::create(['name' => 'S', 'email' => 's@x.test', 'password' => 'secret123', 'role' => 'sales']);
        $this->actingAs($sales)->get('/suppliers')->assertForbidden();
        $this->actingAs($sales)->get('/customers')->assertOk();
    }
}
