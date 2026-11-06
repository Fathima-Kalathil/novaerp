<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'
import Pagination from '../../Components/Pagination.vue'
import { useListFilters } from '../../utils'

// Shared screen for Customers and Suppliers (same fields, different endpoint).
const props = defineProps({ title: String, baseUrl: String, items: Object, filters: Object })
const filters = useListFilters(props.baseUrl, { search: props.filters.search ?? '' })

const showForm = ref(false)
const editingId = ref(null)
const form = useForm({ name: '', email: '', phone: '', address: '' })

const openNew = () => {
    editingId.value = null
    form.defaults({ name: '', email: '', phone: '', address: '' })
    form.reset()
    form.clearErrors()
    showForm.value = true
}
const openEdit = (row) => {
    editingId.value = row.id
    form.defaults({ name: row.name, email: row.email ?? '', phone: row.phone ?? '', address: row.address ?? '' })
    form.reset()
    form.clearErrors()
    showForm.value = true
}
const close = () => {
    showForm.value = false
    editingId.value = null
}
const submit = () => {
    const options = { preserveScroll: true, onSuccess: close }
    editingId.value ? form.put(`${props.baseUrl}/${editingId.value}`, options) : form.post(props.baseUrl, options)
}
const remove = (row) => {
    if (confirm(`Delete "${row.name}"? Existing orders keep their history.`)) router.delete(`${props.baseUrl}/${row.id}`, { preserveScroll: true })
}
</script>

<template>
    <AppLayout :title="title">
        <ListToolbar :title="title">
            <button class="btn-primary" @click="openNew">+ Add {{ title.toLowerCase().replace(/s$/, '') }}</button>
        </ListToolbar>

        <form v-if="showForm" class="card mb-4 space-y-4 p-5" @submit.prevent="submit">
            <h2 class="font-semibold text-gray-900">{{ editingId ? 'Edit' : 'New' }} {{ title.toLowerCase().replace(/s$/, '') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label">Name</label>
                    <input v-model="form.name" class="input" required />
                    <p v-if="form.errors.name" class="err">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="label">Email</label>
                    <input v-model="form.email" type="email" class="input" />
                    <p v-if="form.errors.email" class="err">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input v-model="form.phone" class="input" />
                </div>
                <div>
                    <label class="label">Address</label>
                    <input v-model="form.address" class="input" />
                </div>
            </div>
            <div class="flex gap-2">
                <button class="btn-primary" :disabled="form.processing">Save</button>
                <button type="button" class="btn-secondary" @click="close">Cancel</button>
            </div>
        </form>

        <div class="card overflow-hidden">
            <div class="border-b border-gray-200 p-4">
                <input v-model="filters.search" class="input sm:max-w-xs" placeholder="Search name, email or phone" />
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="th">Name</th><th class="th">Email</th><th class="th">Phone</th><th class="th">Address</th><th class="th"></th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in items.data" :key="row.id" class="hover:bg-gray-50">
                            <td class="td font-medium text-gray-900">{{ row.name }}</td>
                            <td class="td">{{ row.email ?? '-' }}</td>
                            <td class="td">{{ row.phone ?? '-' }}</td>
                            <td class="td">{{ row.address ?? '-' }}</td>
                            <td class="td whitespace-nowrap text-right">
                                <button class="mr-3 text-indigo-600 hover:underline" @click="openEdit(row)">Edit</button>
                                <button class="text-red-600 hover:underline" @click="remove(row)">Delete</button>
                            </td>
                        </tr>
                        <tr v-if="!items.data.length"><td colspan="5" class="td py-10 text-center text-gray-500">Nothing found.</td></tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="items" />
        </div>
    </AppLayout>
</template>
