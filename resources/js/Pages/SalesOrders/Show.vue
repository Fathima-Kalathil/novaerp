<script setup>
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { money, date } from '../../utils'

const props = defineProps({ order: Object })

const act = (action, question) => {
    if (question && !confirm(question)) return
    router.post(`/sales-orders/${props.order.id}/${action}`, {}, { preserveScroll: true })
}
</script>

<template>
    <AppLayout :title="order.order_number">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/sales-orders" class="text-sm text-indigo-600 hover:underline">&larr; Sales orders</Link>
                <h1 class="mt-1 flex items-center gap-3 text-xl font-semibold text-gray-900">
                    {{ order.order_number }} <StatusBadge :value="order.status" />
                </h1>
            </div>
            <div class="flex flex-wrap gap-2">
                <button v-if="order.status === 'draft'" class="btn-primary" @click="act('confirm', 'Confirm this order? Stock will be deducted and an invoice issued.')">Confirm order</button>
                <button v-if="order.status === 'confirmed'" class="btn-primary" @click="act('ship')">Mark as shipped</button>
                <button v-if="['draft', 'confirmed'].includes(order.status)" class="btn-danger" @click="act('cancel', 'Cancel this order?' + (order.status === 'confirmed' ? ' Stock will be returned.' : ''))">Cancel order</button>
                <Link v-if="order.invoice" :href="`/invoices/${order.invoice.id}`" class="btn-secondary">View invoice</Link>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="card overflow-hidden lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50"><tr><th class="th">Product</th><th class="th text-right">Qty</th><th class="th text-right">Unit price</th><th class="th text-right">Line total</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="item in order.items" :key="item.id">
                                <td class="td"><p class="font-medium text-gray-900">{{ item.product?.name }}</p><p class="text-xs text-gray-500">{{ item.product?.sku }}</p></td>
                                <td class="td text-right">{{ item.quantity }}</td>
                                <td class="td text-right">{{ money(item.unit_price) }}</td>
                                <td class="td text-right font-medium">{{ money(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="space-y-1 border-t border-gray-200 p-4 text-sm">
                    <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>{{ money(order.subtotal) }}</span></div>
                    <div class="flex justify-between text-gray-600"><span>VAT</span><span>{{ money(order.tax) }}</span></div>
                    <div class="flex justify-between text-base font-semibold text-gray-900"><span>Total</span><span>{{ money(order.total) }}</span></div>
                </div>
            </div>

            <div class="card h-fit space-y-3 p-5 text-sm">
                <div><p class="text-xs uppercase tracking-wide text-gray-500">Customer</p><p class="font-medium text-gray-900">{{ order.customer?.name }}</p><p class="text-gray-500">{{ order.customer?.email }}</p></div>
                <div><p class="text-xs uppercase tracking-wide text-gray-500">Order date</p><p>{{ date(order.ordered_at) }}</p></div>
                <div><p class="text-xs uppercase tracking-wide text-gray-500">Created by</p><p>{{ order.creator?.name ?? '-' }}</p></div>
                <div v-if="order.invoice"><p class="text-xs uppercase tracking-wide text-gray-500">Invoice</p><p class="flex items-center gap-2">{{ order.invoice.invoice_number }} <StatusBadge :value="order.invoice.status" /></p></div>
                <div v-if="order.notes"><p class="text-xs uppercase tracking-wide text-gray-500">Notes</p><p>{{ order.notes }}</p></div>
            </div>
        </div>
    </AppLayout>
</template>
