<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'
import Pagination from '../../Components/Pagination.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { money, date, useListFilters } from '../../utils'

const props = defineProps({ orders: Object, filters: Object })
const filters = useListFilters('/sales-orders', { search: props.filters.search ?? '', status: props.filters.status ?? '' })
</script>

<template>
    <AppLayout title="Sales orders">
        <ListToolbar title="Sales orders" subtitle="Draft, confirm and ship customer orders">
            <Link href="/sales-orders/create" class="btn-primary">+ New order</Link>
        </ListToolbar>

        <div class="card overflow-hidden">
            <div class="grid gap-3 border-b border-gray-200 p-4 sm:grid-cols-3">
                <input v-model="filters.search" class="input" placeholder="Search order no. or customer" />
                <select v-model="filters.status" class="input">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="shipped">Shipped</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="th">Order</th><th class="th">Customer</th><th class="th">Date</th><th class="th">Status</th><th class="th text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="o in orders.data" :key="o.id" class="hover:bg-gray-50">
                            <td class="td"><Link :href="`/sales-orders/${o.id}`" class="font-medium text-indigo-600 hover:underline">{{ o.order_number }}</Link></td>
                            <td class="td">{{ o.customer?.name }}</td>
                            <td class="td">{{ date(o.ordered_at) }}</td>
                            <td class="td"><StatusBadge :value="o.status" /></td>
                            <td class="td text-right font-medium">{{ money(o.total) }}</td>
                        </tr>
                        <tr v-if="!orders.data.length"><td colspan="5" class="td py-10 text-center text-gray-500">No orders found.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="orders" />
        </div>
    </AppLayout>
</template>
