<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { money } from '../../utils'

const props = defineProps({ suppliers: Array, products: Array })

const form = useForm({
    supplier_id: '',
    expected_at: '',
    notes: '',
    items: [{ product_id: '', quantity: 1, unit_cost: 0 }],
})

const productOf = (id) => props.products.find((p) => p.id === id)
const total = computed(() => form.items.reduce((s, r) => s + (Number(r.quantity) || 0) * (Number(r.unit_cost) || 0), 0))

const pickProduct = (row) => {
    const p = productOf(row.product_id)
    if (p) row.unit_cost = p.cost_price
}
const addRow = () => form.items.push({ product_id: '', quantity: 1, unit_cost: 0 })
const removeRow = (i) => form.items.splice(i, 1)
const submit = () => form.transform((d) => ({ ...d, expected_at: d.expected_at || null })).post('/purchase-orders')
</script>

<template>
    <AppLayout title="New purchase order">
        <h1 class="mb-4 text-xl font-semibold text-gray-900">New purchase order</h1>

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="card space-y-4 p-5 lg:col-span-2">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Supplier</label>
                        <select v-model="form.supplier_id" class="input" required>
                            <option value="" disabled>Select a supplier</option>
                            <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <p v-if="form.errors.supplier_id" class="err">{{ form.errors.supplier_id }}</p>
                    </div>
                    <div>
                        <label class="label">Expected delivery</label>
                        <input v-model="form.expected_at" type="date" class="input" />
                    </div>
                </div>

                <div>
                    <label class="label">Items</label>
                    <p v-if="form.errors.items" class="err mb-2">{{ form.errors.items }}</p>
                    <div class="space-y-3">
                        <div v-for="(row, i) in form.items" :key="i" class="rounded-md border border-gray-200 p-3">
                            <div class="grid gap-3 sm:grid-cols-12">
                                <div class="sm:col-span-5">
                                    <select v-model="row.product_id" class="input" required @change="pickProduct(row)">
                                        <option value="" disabled>Select product</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.sku }} - {{ p.name }} ({{ p.stock_on_hand }} in stock)</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2"><input v-model="row.quantity" type="number" min="1" class="input" placeholder="Qty" required /></div>
                                <div class="sm:col-span-3"><input v-model="row.unit_cost" type="number" min="0" step="0.01" class="input" placeholder="Unit cost" required /></div>
                                <div class="flex items-center justify-between sm:col-span-2">
                                    <span class="text-sm font-medium">{{ money((Number(row.quantity) || 0) * (Number(row.unit_cost) || 0)) }}</span>
                                    <button v-if="form.items.length > 1" type="button" class="text-sm text-red-600 hover:underline" @click="removeRow(i)">x</button>
                                </div>
                            </div>
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
                <div class="flex justify-between border-t border-gray-200 pt-2 text-lg font-semibold text-gray-900"><span>Total</span><span>{{ money(total) }}</span></div>
                <button class="btn-primary mt-3 w-full" :disabled="form.processing">Save as draft</button>
                <Link href="/purchase-orders" class="btn-secondary w-full">Cancel</Link>
            </div>
        </form>
    </AppLayout>
</template>
