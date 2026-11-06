<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '../Layouts/AppLayout.vue'
import StatCard from '../Components/StatCard.vue'
import BarChart from '../Components/BarChart.vue'
import StatusBadge from '../Components/StatusBadge.vue'
import { money, date } from '../utils'

defineProps({ stats: Object, chart: Array, lowStock: Array, recentOrders: Array })
</script>

<template>
    <AppLayout title="Dashboard">
        <h1 class="mb-4 text-xl font-semibold text-gray-900">Dashboard</h1>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Revenue this month" :value="money(stats.revenue_month)" accent="green" />
            <StatCard label="Open orders" :value="stats.open_orders" accent="indigo" />
            <StatCard label="Low stock items" :value="stats.low_stock" accent="amber" />
            <StatCard label="Unpaid invoices" :value="stats.unpaid_invoices" accent="red" />
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="card p-4 lg:col-span-2">
                <h2 class="font-semibold text-gray-900">Sales - last 6 months</h2>
                <p class="text-xs text-gray-500">Confirmed and shipped orders, AED incl. VAT</p>
                <BarChart :data="chart" />
            </div>

            <div class="card p-4">
                <h2 class="mb-3 font-semibold text-gray-900">Low stock alerts</h2>
                <p v-if="!lowStock.length" class="text-sm text-gray-500">Everything is well stocked.</p>
                <ul class="divide-y divide-gray-100">
                    <li v-for="p in lowStock" :key="p.id" class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <p class="font-medium text-gray-800">{{ p.name }}</p>
                            <p class="text-xs text-gray-500">{{ p.sku }} - reorder at {{ p.reorder_level }}</p>
                        </div>
                        <StatusBadge :value="p.stock_on_hand <= Math.floor(p.reorder_level / 2) ? 'critical' : 'low'" />
                        <span class="ml-3 w-14 text-right font-semibold" :class="p.stock_on_hand <= Math.floor(p.reorder_level / 2) ? 'text-red-600' : 'text-amber-600'">
                            {{ p.stock_on_hand }} left
                        </span>
                    </li>
                </ul>
                <Link href="/products?status=low" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:underline">View all low stock</Link>
            </div>
        </div>

        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-900">Recent sales orders</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="th">Order</th><th class="th">Customer</th><th class="th">Date</th><th class="th">Status</th><th class="th text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="o in recentOrders" :key="o.id">
                            <td class="td"><Link :href="`/sales-orders/${o.id}`" class="font-medium text-indigo-600 hover:underline">{{ o.order_number }}</Link></td>
                            <td class="td">{{ o.customer?.name }}</td>
                            <td class="td">{{ date(o.ordered_at) }}</td>
                            <td class="td"><StatusBadge :value="o.status" /></td>
                            <td class="td text-right font-medium">{{ money(o.total) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
