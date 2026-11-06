<script setup>
import { computed } from 'vue'

const props = defineProps({ value: { type: String, required: true } })

const styles = {
    draft: 'bg-gray-100 text-gray-700',
    confirmed: 'bg-blue-100 text-blue-700',
    ordered: 'bg-blue-100 text-blue-700',
    shipped: 'bg-green-100 text-green-700',
    received: 'bg-green-100 text-green-700',
    paid: 'bg-green-100 text-green-700',
    ok: 'bg-green-100 text-green-700',
    unpaid: 'bg-amber-100 text-amber-700',
    low: 'bg-amber-100 text-amber-700',
    cancelled: 'bg-red-100 text-red-700',
    void: 'bg-red-100 text-red-700',
    critical: 'bg-red-100 text-red-700',
    inactive: 'bg-gray-100 text-gray-500',
}
const cls = computed(() => styles[props.value] ?? 'bg-gray-100 text-gray-700')
const text = computed(() => props.value.charAt(0).toUpperCase() + props.value.slice(1))
</script>

<template>
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium" :class="cls">{{ text }}</span>
</template>
