<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { cap } from '../utils'

defineProps({ title: String })

const page = usePage()
const user = computed(() => page.props.auth.user)
const flash = computed(() => page.props.flash)
const menuOpen = ref(false)

const ALL = ['admin', 'sales', 'warehouse']
const menu = [
    { label: 'Dashboard', href: '/dashboard', roles: ALL },
    { label: 'Products', href: '/products', roles: ALL },
    { label: 'Categories', href: '/categories', roles: ['warehouse'] },
    { label: 'Customers', href: '/customers', roles: ['sales'] },
    { label: 'Sales orders', href: '/sales-orders', roles: ['sales'] },
    { label: 'Invoices', href: '/invoices', roles: ['sales'] },
    { label: 'Suppliers', href: '/suppliers', roles: ['warehouse'] },
    { label: 'Purchase orders', href: '/purchase-orders', roles: ['warehouse'] },
    { label: 'Audit log', href: '/audit-logs', roles: ['admin'] },
]
const items = computed(() => menu.filter((m) => user.value.role === 'admin' || m.roles.includes(user.value.role)))
const isActive = (href) => page.url === href || page.url.startsWith(href + '/') || page.url.startsWith(href + '?')
const logout = () => router.post('/logout')
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen lg:flex">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-30 w-60 transform bg-indigo-950 text-indigo-100 transition-transform print:hidden lg:static lg:translate-x-0"
            :class="menuOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-14 items-center px-5 text-lg font-bold tracking-tight text-white">NovaERP</div>
            <nav class="space-y-1 px-3 py-2">
                <Link
                    v-for="m in items"
                    :key="m.href"
                    :href="m.href"
                    class="block rounded-md px-3 py-2 text-sm font-medium"
                    :class="isActive(m.href) ? 'bg-indigo-600 text-white' : 'text-indigo-200 hover:bg-indigo-900'"
                    @click="menuOpen = false"
                >
                    {{ m.label }}
                </Link>
            </nav>
        </aside>
        <div v-if="menuOpen" class="fixed inset-0 z-20 bg-black/40 lg:hidden" @click="menuOpen = false" />

        <!-- Main -->
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 print:hidden sm:px-6">
                <button class="rounded p-2 text-gray-600 hover:bg-gray-100 lg:hidden" @click="menuOpen = true" aria-label="Open menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <span class="hidden text-sm text-gray-500 lg:block">{{ title }}</span>
                <div class="flex items-center gap-3">
                    <div class="text-right leading-tight">
                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                        <p class="text-xs text-gray-500">{{ cap(user.role) }}</p>
                    </div>
                    <button class="btn-secondary btn-sm" @click="logout">Log out</button>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <div v-if="flash.success" class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 print:hidden">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 print:hidden">
                    {{ flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
