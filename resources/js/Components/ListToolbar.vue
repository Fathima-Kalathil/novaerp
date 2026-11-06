<script setup>
// Title + optional action slot, used at the top of every list page.
defineProps({ title: String, subtitle: String })
</script>

<template>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-gray-500">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2"><slot /></div>
    </div>
</template>
