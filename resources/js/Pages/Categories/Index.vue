<script setup>
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'

defineProps({ categories: Array })

const form = useForm({ name: '' })
const add = () => form.post('/categories', { preserveScroll: true, onSuccess: () => form.reset() })
const remove = (c) => {
    if (confirm(`Delete category "${c.name}"? Its products will become uncategorised.`)) router.delete(`/categories/${c.id}`, { preserveScroll: true })
}
</script>

<template>
    <AppLayout title="Categories">
        <ListToolbar title="Categories" subtitle="Group products for filtering and reports" />

        <form class="card mb-4 flex flex-wrap items-start gap-3 p-4" @submit.prevent="add">
            <div class="min-w-[220px] flex-1">
                <input v-model="form.name" class="input" placeholder="New category name" required />
                <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
            </div>
            <button class="btn-primary" :disabled="form.processing">Add category</button>
        </form>

        <div class="card overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr><th class="th">Name</th><th class="th text-right">Products</th><th class="th"></th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="c in categories" :key="c.id">
                        <td class="td font-medium text-gray-900">{{ c.name }}</td>
                        <td class="td text-right">{{ c.products_count }}</td>
                        <td class="td text-right"><button class="text-red-600 hover:underline" @click="remove(c)">Delete</button></td>
                    </tr>
                    <tr v-if="!categories.length"><td colspan="3" class="td py-8 text-center text-gray-500">No categories yet.</td></tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
