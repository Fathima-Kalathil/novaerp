<script setup>
import AppLayout from '../../Layouts/AppLayout.vue'
import ListToolbar from '../../Components/ListToolbar.vue'
import Pagination from '../../Components/Pagination.vue'

defineProps({ logs: Object })

const when = (v) => new Date(v).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })
const summary = (values) => (values ? Object.entries(values).map(([k, v]) => `${k}: ${v ?? 'null'}`).join(', ') : '-')
const tone = { created: 'text-green-700 bg-green-50', updated: 'text-blue-700 bg-blue-50', deleted: 'text-red-700 bg-red-50' }
</script>

<template>
    <AppLayout title="Audit log">
        <ListToolbar title="Audit log" subtitle="Who changed what, and when (admin only)" />

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="th">When</th><th class="th">User</th><th class="th">Action</th><th class="th">Record</th><th class="th">Old values</th><th class="th">New values</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="l in logs.data" :key="l.id" class="align-top">
                            <td class="td whitespace-nowrap">{{ when(l.created_at) }}</td>
                            <td class="td">{{ l.user?.name ?? 'System' }}</td>
                            <td class="td"><span class="rounded px-2 py-0.5 text-xs font-medium capitalize" :class="tone[l.action]">{{ l.action }}</span></td>
                            <td class="td whitespace-nowrap">{{ l.auditable_type }} #{{ l.auditable_id }}</td>
                            <td class="td max-w-xs break-words text-xs text-gray-500">{{ summary(l.old_values) }}</td>
                            <td class="td max-w-xs break-words text-xs text-gray-500">{{ summary(l.new_values) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="logs" />
        </div>
    </AppLayout>
</template>
