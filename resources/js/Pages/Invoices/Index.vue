<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'
import Pagination from '../../Components/Pagination.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { money, date, useListFilters } from '../../utils'

const props = defineProps({ invoices: Object, filters: Object })
const filters = useListFilters('/invoices', { search: props.filters.search ?? '', status: props.filters.status ?? '' })
</script>

<template>
    <AppLayout title="Invoices">
        <ListToolbar title="Invoices" subtitle="Issued automatically when a sales order is confirmed" />

        <div class="card overflow-hidden">
            <div class="grid gap-3 border-b border-gray-200 p-4 sm:grid-cols-3">
                <input v-model="filters.search" class="input" placeholder="Search invoice number" />
                <select v-model="filters.status" class="input">
                    <option value="">All statuses</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="void">Void</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="th">Invoice</th><th class="th">Customer</th><th class="th">Issued</th><th class="th">Due</th><th class="th">Status</th><th class="th text-right">Amount</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="inv in invoices.data" :key="inv.id" class="hover:bg-gray-50">
                            <td class="td"><Link :href="`/invoices/${inv.id}`" class="font-medium text-indigo-600 hover:underline">{{ inv.invoice_number }}</Link></td>
                            <td class="td">{{ inv.order?.customer?.name }}</td>
                            <td class="td">{{ date(inv.issued_at) }}</td>
                            <td class="td">{{ date(inv.due_at) }}</td>
                            <td class="td"><StatusBadge :value="inv.status" /></td>
                            <td class="td text-right font-medium">{{ money(inv.order?.total) }}</td>
                        </tr>
                        <tr v-if="!invoices.data.length"><td colspan="6" class="td py-10 text-center text-gray-500">No invoices found.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="invoices" />
        </div>
    </AppLayout>
</template>
