<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'
import Pagination from '../../Components/Pagination.vue'
import StatusBadge from '../../Components/StatusBadge.vue'
import { money, useListFilters } from '../../utils'

const props = defineProps({ products: Object, categories: Array, filters: Object })
const filters = useListFilters('/products', {
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
    status: props.filters.status ?? '',
})

const role = computed(() => usePage().props.auth.user.role)
const canEdit = computed(() => ['admin', 'warehouse'].includes(role.value))

const stockStatus = (p) => {
    if (!p.is_active) return 'inactive'
    if (p.stock_on_hand <= Math.floor(p.reorder_level / 2)) return 'critical'
    if (p.stock_on_hand <= p.reorder_level) return 'low'
    return 'ok'
}
const label = { ok: 'ok', low: 'low', critical: 'critical', inactive: 'inactive' }

const remove = (p) => {
    if (confirm(`Delete "${p.name}"? Past orders keep its history.`)) router.delete(`/products/${p.id}`)
}
</script>

<template>
    <AppLayout title="Products">
        <ListToolbar title="Products" subtitle="Catalogue and live stock levels">
            <Link v-if="canEdit" href="/products/create" class="btn-primary">+ New product</Link>
        </ListToolbar>

        <div class="card overflow-hidden">
            <div class="grid gap-3 border-b border-gray-200 p-4 sm:grid-cols-3">
                <input v-model="filters.search" class="input" placeholder="Search name or SKU" />
                <select v-model="filters.category" class="input">
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select v-model="filters.status" class="input">
                    <option value="">All products</option>
                    <option value="low">Low stock only</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="th">SKU</th><th class="th">Name</th><th class="th">Category</th>
                            <th class="th text-right">Price</th><th class="th text-right">Stock</th><th class="th">Status</th>
                            <th v-if="canEdit" class="th"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50">
                            <td class="td font-mono text-xs">{{ p.sku }}</td>
                            <td class="td font-medium text-gray-900">{{ p.name }}</td>
                            <td class="td">{{ p.category?.name ?? '-' }}</td>
                            <td class="td text-right">{{ money(p.unit_price) }}</td>
                            <td class="td text-right font-semibold">{{ p.stock_on_hand }}</td>
                            <td class="td"><StatusBadge :value="label[stockStatus(p)]" /></td>
                            <td v-if="canEdit" class="td whitespace-nowrap text-right">
                                <Link :href="`/products/${p.id}/edit`" class="mr-3 text-indigo-600 hover:underline">Edit</Link>
                                <button class="text-red-600 hover:underline" @click="remove(p)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!products.data.length"><td colspan="7" class="td py-10 text-center text-gray-500">No products match your filters.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="products" />
        </div>
    </AppLayout>
</template>
