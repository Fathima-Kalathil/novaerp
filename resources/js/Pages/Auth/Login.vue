<script setup>
import { Head, useForm } from '@inertiajs/vue3'

const form = useForm({ email: '', password: '', remember: false })
const submit = () => form.post('/login', { onFinish: () => form.reset('password') })
const fill = (email) => {
    form.email = email
    form.password = 'password'
}
const demo = [
    ['Admin', 'admin@demo.com', 'Everything'],
    ['Sales', 'sales@demo.com', 'Customers, orders, invoices'],
    ['Warehouse', 'warehouse@demo.com', 'Products, stock, purchasing'],
]
</script>

<template>
    <Head title="Log in" />
    <div class="flex min-h-screen items-center justify-center bg-indigo-950 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-bold tracking-tight text-white">NovaERP</h1>
                <p class="mt-1 text-sm text-indigo-200">Inventory, sales and purchasing in one place</p>
            </div>

            <form class="card space-y-4 p-6" @submit.prevent="submit">
                <div>
                    <label class="label" for="email">Email</label>
                    <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" required autofocus />
                    <p v-if="form.errors.email" class="err">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="label" for="password">Password</label>
                    <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300" /> Remember me
                </label>
                <button class="btn-primary w-full" :disabled="form.processing">Log in</button>
            </form>

            <div class="mt-4 rounded-lg bg-white/10 p-4 text-sm text-indigo-100">
                <p class="mb-2 font-semibold text-white">Demo accounts (click to fill, password: password)</p>
                <button
                    v-for="[role, email, desc] in demo"
                    :key="email"
                    type="button"
                    class="mb-1 flex w-full items-center justify-between rounded px-2 py-1.5 text-left hover:bg-white/10"
                    @click="fill(email)"
                >
                    <span><b>{{ role }}</b> - {{ email }}</span>
                    <span class="hidden text-xs text-indigo-300 sm:inline">{{ desc }}</span>
                </button>
            </div>
        </div>
    </div>
</template>
