<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $monthStart = now()->startOfMonth();
        $chartStart = now()->startOfMonth()->subMonths(5);

        $sold = SalesOrder::whereIn('status', ['confirmed', 'shipped'])
            ->where('ordered_at', '>=', $chartStart)
            ->get(['id', 'total', 'ordered_at']);

        // Group in PHP so the same code works on MySQL, Postgres and SQLite.
        $chart = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();
            $chart[] = [
                'label' => $start->format('M'),
                'value' => round((float) $sold->filter(fn ($o) => $o->ordered_at->between($start, $end))->sum('total'), 2),
            ];
        }

        $lowStock = Product::where('is_active', true)
            ->whereColumn('stock_on_hand', '<=', 'reorder_level');

        return Inertia::render('Dashboard', [
            'stats' => [
                'revenue_month' => round((float) $sold->where('ordered_at', '>=', $monthStart)->sum('total'), 2),
                'open_orders' => SalesOrder::whereIn('status', ['draft', 'confirmed'])->count(),
                'low_stock' => (clone $lowStock)->count(),
                'unpaid_invoices' => Invoice::where('status', 'unpaid')->count(),
            ],
            'chart' => $chart,
            'lowStock' => (clone $lowStock)->orderBy('stock_on_hand')->limit(6)
                ->get(['id', 'sku', 'name', 'stock_on_hand', 'reorder_level']),
            'recentOrders' => SalesOrder::with('customer:id,name')->latest('id')->limit(6)
                ->get(['id', 'order_number', 'customer_id', 'status', 'total', 'ordered_at']),
        ]);
    }
}
