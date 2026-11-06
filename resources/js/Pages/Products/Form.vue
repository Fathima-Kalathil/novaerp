<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import { date } from '../../utils'

const props = defineProps({ product: Object, categories: Array, movements: Array })
const editing = !!props.product

const form = useForm({
    sku: props.product?.sku ?? '',
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? '',
    description: props.product?.description ?? '',
    unit_price: props.product?.unit_price ?? '',
    cost_price: props.product?.cost_price ?? 0,
    reorder_level: props.product?.reorder_level ?? 5,
    is_active: props.product?.is_active ?? true,
    initial_stock: 0,
})

const submit = () => {
    const payload = form.transform((d) => ({ ...d, category_id: d.category_id === '' ? null : d.category_id }))
    editing ? payload.put(`/products/${props.product.id}`) : payload.post('/products')
}

const adjust = useForm({ quantity: '', note: '' })
const submitAdjust = () =>
    adjust.post(`/products/${props.product.id}/adjust`, { preserveScroll: true, onSuccess: () => adjust.reset() })
</script>

<template>
    <AppLayout :title="editing ? 'Edit product' : 'New product'">
        <h1 class="mb-4 text-xl font-semibold text-gray-900">{{ editing ? 'Edit product' : 'New product' }}</h1>

        <div class="grid gap-6 lg:grid-cols-3">
            <form class="card space-y-4 p-5 lg:col-span-2" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">SKU</label>
                        <input v-model="form.sku" class="input" required />
                        <p v-if="form.errors.sku" class="err">{{ form.errors.sku }}</p>
                    </div>
                    <div>
                        <label class="label">Category</label>
                        <select v-model="form.category_id" class="input">
                            <option value="">No category</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label">Name</label>
                    <input v-model="form.name" class="input" required />
                    <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="label">Selling price (AED)</label>
                        <input v-model="form.unit_price" type="number" step="0.01" min="0" class="input" required />
                        <p v-if="form.errors.unit_price" class="err">{{ form.errors.unit_price }}</p>
                    </div>
                    <div>
                        <label class="label">Cost price (AED)</label>
                        <input v-model="form.cost_price" type="number" step="0.01" min="0" class="input" required />
                    </div>
                    <div>
                        <label class="label">Reorder level</label>
                        <input v-model="form.reorder_level" type="number" min="0" class="input" required />
                    </div>
                </div>
                <div v-if="!editing">
                    <label class="label">Opening stock</label>
                    <input v-model="form.initial_stock" type="number" min="0" class="input sm:w-1/3" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300" /> Active (can be sold and purchased)
                </label>

                <div class="flex gap-2 pt-2">
                    <button class="btn-primary" :disabled="form.processing">{{ editing ? 'Save changes' : 'Create product' }}</button>
                    <Link href="/products" class="btn-secondary">Cancel</Link>
                </div>
            </form>

            <div v-if="editing" class="space-y-6">
                <div class="card p-5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Stock on hand</p>
                    <p class="text-3xl font-semibold text-gray-900">{{ product.stock_on_hand }}</p>

                    <form class="mt-4 space-y-3 border-t border-gray-100 pt-4" @submit.prevent="submitAdjust">
                        <p class="text-sm font-medium text-gray-700">Adjust stock</p>
                        <input v-model="adjust.quantity" type="number" class="input" placeholder="+10 or -3" required />
                        <p v-if="adjust.errors.quantity" class="err">{{ adjust.errors.quantity }}</p>
                        <input v-model="adjust.note" class="input" placeholder="Reason (stock take, damaged...)" />
                        <button class="btn-secondary w-full" :disabled="adjust.processing">Apply adjustment</button>
                    </form>
                </div>

                <div class="card p-5">
                    <p class="mb-3 text-sm font-semibold text-gray-900">Recent stock movements</p>
                    <p v-if="!movements.length" class="text-sm text-gray-500">No movements yet.</p>
                    <ul class="divide-y divide-gray-100 text-sm">
                        <li v-for="m in movements" :key="m.id" class="flex items-center justify-between py-2">
                            <div>
                                <p class="capitalize text-gray-800">{{ m.type }}</p>
                                <p class="text-xs text-gray-500">{{ date(m.created_at) }}<span v-if="m.creator"> - {{ m.creator.name }}</span></p>
                            </div>
                            <span class="font-semibold" :class="m.quantity > 0 ? 'text-green-600' : 'text-red-600'">{{ m.quantity > 0 ? '+' : '' }}{{ m.quantity }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
