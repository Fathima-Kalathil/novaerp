<script setup>
defineProps({
    label: String,
    value: [String, Number],
    accent: { type: String, default: 'indigo' },
})
const bars = { indigo: 'bg-indigo-500', green: 'bg-green-500', amber: 'bg-amber-500', red: 'bg-red-500' }
</script>

<template>
    <div class="card relative overflow-hidden p-4 pl-5">
        <div class="absolute inset-y-0 left-0 w-1" :class="bars[accent]" />
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ value }}</p>
    </div>
</template>
