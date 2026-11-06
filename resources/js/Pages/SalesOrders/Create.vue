<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { money } from '../../utils'

const props = defineProps({ customers: Array, products: Array, vatRate: Number })

const form = useForm({
    customer_id: '',
    notes: '',
    items: [{ product_id: '', quantity: 1 }],
})

const productOf = (id) => props.products.find((p) => p.id === id)
const lineTotal = (row) => (productOf(row.product_id)?.unit_price ?? 0) * (Number(row.quantity) || 0)
const subtotal = computed(() => form.items.reduce((sum, r) => sum + lineTotal(r), 0))
const tax = computed(() => subtotal.value * props.vatRate)
const total = computed(() => subtotal.value + tax.value)

const addRow = () => form.items.push({ product_id: '', quantity: 1 })
const removeRow = (i) => form.items.splice(i, 1)
const lowStock = (row) => {
    const p = productOf(row.product_id)
    return p && Number(row.quantity) > p.stock_on_hand
}
const submit = () => form.post('/sales-orders')
</script>

<template>
    <AppLayout title="New sales order">
        <h1 class="mb-4 text-xl font-semibold text-gray-900">New sales order</h1>

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="card space-y-4 p-5 lg:col-span-2">
                <div>
                    <label class="label">Customer</label>
                    <select v-model="form.customer_id" class="input" required>
                        <option value="" disabled>Select a customer</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="form.errors.customer_id" class="err">{{ form.errors.customer_id }}</p>
                </div>

                <div>
                    <label class="label">Items</label>
                    <p v-if="form.errors.items" class="err mb-2">{{ form.errors.items }}</p>
                    <div class="space-y-3">
                        <div v-for="(row, i) in form.items" :key="i" class="rounded-md border border-gray-200 p-3">
                            <div class="grid gap-3 sm:grid-cols-12">
                                <div class="sm:col-span-6">
                                    <select v-model="row.product_id" class="input" required>
                                        <option value="" disabled>Select product</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }} ({{ p.stock_on_hand }} in stock)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <input v-model="row.quantity" type="number" min="1" class="input" required />
                                </div>
                                <div class="flex items-center justify-between sm:col-span-4">
                                    <span class="text-sm font-medium text-gray-800">{{ money(lineTotal(row)) }}</span>
                                    <button v-if="form.items.length > 1" type="button" class="text-sm text-red-600 hover:underline" @click="removeRow(i)">Remove</button>
                                </div>
                            </div>
                            <p v-if="lowStock(row)" class="mt-2 text-xs text-amber-600">Only {{ productOf(row.product_id).stock_on_hand }} in stock - the order can be saved as a draft but cannot be confirmed yet.</p>
                            <p v-if="form.errors[`items.${i}.product_id`]" class="err">{{ form.errors[`items.${i}.product_id`] }}</p>
                            <p v-if="form.errors[`items.${i}.quantity`]" class="err">{{ form.errors[`items.${i}.quantity`] }}</p>
                        </div>
                    </div>
                    <button type="button" class="mt-3 text-sm font-medium text-indigo-600 hover:underline" @click="addRow">+ Add line</button>
                </div>

                <div>
                    <label class="label">Notes</label>
                    <textarea v-model="form.notes" rows="2" class="input" />
                </div>
            </div>

            <div class="card h-fit space-y-2 p-5">
                <h2 class="font-semibold text-gray-900">Summary</h2>
                <div class="flex justify-between text-sm text-gray-600"><span>Subtotal</span><span>{{ money(subtotal) }}</span></div>
                <div class="flex justify-between text-sm text-gray-600"><span>VAT ({{ vatRate * 100 }}%)</span><span>{{ money(tax) }}</span></div>
                <div class="flex justify-between border-t border-gray-200 pt-2 text-lg font-semibold text-gray-900"><span>Total</span><span>{{ money(total) }}</span></div>
                <button class="btn-primary mt-3 w-full" :disabled="form.processing">Save as draft</button>
                <Link href="/sales-orders" class="btn-secondary w-full">Cancel</Link>
                <p class="pt-1 text-xs text-gray-500">Prices are taken from the product catalogue when the order is saved.</p>
            </div>
        </form>
    </AppLayout>
</template>
