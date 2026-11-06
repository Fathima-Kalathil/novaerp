<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderService;
use App\Services\SalesOrderService;
use App\Services\StockService;
use DomainException;
use Illuminate\Database\Seeder;

/**
 * Realistic demo data. Safe to run twice: it does nothing if users already exist.
 *
 * Logins (password for all: "password"):
 *   admin@demo.com      - Admin (everything)
 *   sales@demo.com      - Sales (customers, sales orders, invoices)
 *   warehouse@demo.com  - Warehouse (products, stock, purchasing)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            $this->command?->info('Database already seeded - skipping.');

            return;
        }

        mt_srand(2026); // same demo data every time

        $stock = app(StockService::class);
        $sales = app(SalesOrderService::class);
        $purchasing = app(PurchaseOrderService::class);

        // ---- users -------------------------------------------------------
        User::create(['name' => 'Demo Admin', 'email' => 'admin@demo.com', 'password' => 'password', 'role' => 'admin']);
        $salesUser = User::create(['name' => 'Sara Sales', 'email' => 'sales@demo.com', 'password' => 'password', 'role' => 'sales']);
        $whUser = User::create(['name' => 'Wael Warehouse', 'email' => 'warehouse@demo.com', 'password' => 'password', 'role' => 'warehouse']);

        // ---- categories + products --------------------------------------
        $cats = [];
        foreach (['Electronics', 'Office Supplies', 'Furniture', 'Accessories'] as $name) {
            $cats[$name] = Category::create(['name' => $name]);
        }

        // [sku, name, category, price, cost, reorder, opening stock]
        $catalog = [
            ['EL-1001', 'Wireless Mouse', 'Electronics', 45, 28, 15, 7],
            ['EL-1002', 'USB-C Cable 1m', 'Electronics', 18, 8, 20, 4],
            ['EL-1003', 'Mechanical Keyboard', 'Electronics', 189, 120, 10, 64],
            ['EL-1004', '24" Monitor', 'Electronics', 649, 480, 8, 31],
            ['EL-1005', 'HDMI Adapter', 'Electronics', 29, 14, 15, 9],
            ['EL-1006', 'Webcam 1080p', 'Electronics', 139, 85, 10, 48],
            ['EL-1007', 'Bluetooth Headset', 'Electronics', 219, 140, 10, 55],
            ['EL-1008', 'Power Bank 20000mAh', 'Electronics', 119, 70, 12, 72],
            ['OF-2001', 'A4 Paper Box (5 reams)', 'Office Supplies', 62, 44, 25, 180],
            ['OF-2002', 'Ballpoint Pens (50 pack)', 'Office Supplies', 38, 22, 20, 140],
            ['OF-2003', 'Sticky Notes (12 pack)', 'Office Supplies', 24, 12, 20, 95],
            ['OF-2004', 'Stapler Heavy Duty', 'Office Supplies', 41, 25, 10, 38],
            ['OF-2005', 'Desk Organizer', 'Office Supplies', 35, 19, 12, 58],
            ['OF-2006', 'Whiteboard Markers (10)', 'Office Supplies', 27, 14, 15, 6],
            ['FU-3001', 'Ergonomic Office Chair', 'Furniture', 579, 390, 6, 22],
            ['FU-3002', 'Standing Desk', 'Furniture', 1290, 920, 4, 14],
            ['FU-3003', 'Filing Cabinet 3-Drawer', 'Furniture', 345, 230, 5, 17],
            ['FU-3004', 'Bookshelf 5-Tier', 'Furniture', 299, 190, 5, 3],
            ['AC-4001', 'Laptop Stand', 'Accessories', 79, 42, 15, 10],
            ['AC-4002', 'Laptop Sleeve 15"', 'Accessories', 59, 30, 15, 66],
            ['AC-4003', 'Monitor Arm', 'Accessories', 159, 95, 8, 29],
            ['AC-4004', 'Cable Management Kit', 'Accessories', 22, 9, 20, 110],
            ['AC-4005', 'Mouse Pad XL', 'Accessories', 32, 15, 15, 84],
            ['AC-4006', 'Desk Lamp LED', 'Accessories', 89, 48, 10, 41],
        ];

        $products = collect();
        foreach ($catalog as [$sku, $name, $cat, $price, $cost, $reorder, $opening]) {
            $p = Product::create([
                'category_id' => $cats[$cat]->id,
                'sku' => $sku,
                'name' => $name,
                'description' => $name.' - demo catalogue item.',
                'unit_price' => $price,
                'cost_price' => $cost,
                'reorder_level' => $reorder,
                'stock_on_hand' => 0,
            ]);
            $stock->adjust($p, $opening, 'Opening stock', $whUser->id);
            $products->push($p->fresh());
        }

        // products that are NOT low on stock - used for random demo sales
        $sellable = $products->filter(fn ($p) => $p->stock_on_hand > $p->reorder_level)->values();

        // ---- customers + suppliers --------------------------------------
        $customerNames = [
            'Al Noor Trading LLC', 'Gulf Star Contracting', 'Desert Rose Interiors', 'Emirates Tech Solutions',
            'Falcon Logistics', 'Marina Business Centre', 'Oasis Medical Clinic', 'Pearl Hospitality Group',
        ];
        $customers = collect($customerNames)->map(fn ($n, $i) => Customer::create([
            'name' => $n,
            'email' => 'accounts@'.strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $n)[0])).'.example',
            'phone' => '+971 50 '.str_pad((string) (1000000 + $i * 137), 7, '0', STR_PAD_LEFT),
            'address' => 'Abu Dhabi, UAE',
        ]));

        $supplierNames = ['Techno Wholesale FZE', 'Prime Office Distribution', 'Furnish Direct Trading', 'Digital Parts Hub', 'ProSupply Middle East'];
        $suppliers = collect($supplierNames)->map(fn ($n, $i) => Supplier::create([
            'name' => $n,
            'email' => 'sales@'.strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $n)[0])).'.example',
            'phone' => '+971 4 '.str_pad((string) (3000000 + $i * 211), 7, '0', STR_PAD_LEFT),
            'address' => 'Dubai, UAE',
        ]));

        // ---- purchasing history -----------------------------------------
        // Two received orders, one sent (waiting for goods), one draft.
        foreach ([
            [0, ['EL-1003' => 20, 'EL-1004' => 10], 'receive'],
            [1, ['OF-2001' => 60, 'OF-2002' => 40], 'receive'],
            [3, ['EL-1002' => 100, 'EL-1005' => 60, 'EL-1001' => 50], 'order'],
            [2, ['FU-3004' => 10, 'FU-3002' => 6], 'draft'],
        ] as [$supplierIndex, $lines, $final]) {
            $items = [];
            foreach ($lines as $sku => $qty) {
                $p = $products->firstWhere('sku', $sku);
                $items[] = ['product_id' => $p->id, 'quantity' => $qty, 'unit_cost' => $p->cost_price];
            }
            $po = $purchasing->create([
                'supplier_id' => $suppliers[$supplierIndex]->id,
                'expected_at' => now()->addDays(7)->toDateString(),
                'items' => $items,
            ], $whUser->id);

            if ($final !== 'draft') {
                $purchasing->markOrdered($po);
            }
            if ($final === 'receive') {
                $purchasing->receive($po, $whUser->id);
            }
        }

        // ---- sales history (spread over ~6 months for the dashboard chart) ----
        for ($i = 0; $i < 28; $i++) {
            $daysAgo = $i < 6 ? mt_rand(0, 6) : mt_rand(7, 170);
            $orderedAt = now()->subDays($daysAgo)->setTime(mt_rand(9, 17), mt_rand(0, 59));

            $items = [];
            foreach ($sellable->shuffle()->take(mt_rand(1, 4)) as $p) {
                $items[] = ['product_id' => $p->id, 'quantity' => mt_rand(1, 6)];
            }

            $order = $sales->create([
                'customer_id' => $customers->random()->id,
                'ordered_at' => $orderedAt,
                'items' => $items,
            ], $salesUser->id);

            if ($i % 7 === 6) {
                continue; // leave as draft
            }

            try {
                $sales->confirm($order, $salesUser->id);
            } catch (DomainException) {
                continue;
            }

            $invoice = $order->invoice()->first();
            $invoice->update(['issued_at' => $orderedAt, 'due_at' => $orderedAt->copy()->addDays(30)]);

            if ($i % 9 === 8) {
                $sales->cancel($order->fresh(), $salesUser->id);

                continue;
            }

            if ($daysAgo > 5) {
                $sales->ship($order->fresh());
            }
            if ($daysAgo > 20 && mt_rand(0, 3) > 0) {
                $invoice->update(['status' => 'paid', 'paid_at' => $orderedAt->copy()->addDays(mt_rand(3, 25))]);
            }
        }
    }
}
