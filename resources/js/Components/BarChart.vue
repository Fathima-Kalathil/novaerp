<script setup>
import { computed } from 'vue'
import { money } from '../utils'

const props = defineProps({ data: { type: Array, required: true } }) // [{label, value}]
const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)))
</script>

<template>
    <div class="flex h-44 items-end gap-3 pt-4">
        <div v-for="(d, i) in data" :key="d.label" class="flex h-full flex-1 flex-col items-center justify-end">
            <span class="mb-1 text-[11px] text-gray-500">{{ d.value ? Math.round(d.value).toLocaleString() : '' }}</span>
            <div
                class="w-full max-w-[42px] rounded-t"
                :class="i === data.length - 1 ? 'bg-indigo-600' : 'bg-indigo-300'"
                :style="{ height: Math.max(2, (d.value / max) * 100) + '%' }"
                :title="money(d.value)"
            />
            <span class="mt-1 text-xs text-gray-500">{{ d.label }}</span>
        </div>
    </div>
</template>
