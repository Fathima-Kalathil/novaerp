<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ paginator: { type: Object, required: true } })
</script>

<template>
    <div class="flex flex-col items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 sm:flex-row">
        <p class="text-sm text-gray-500">
            <template v-if="paginator.total">Showing {{ paginator.from }}-{{ paginator.to }} of {{ paginator.total }}</template>
            <template v-else>No results</template>
        </p>
        <nav v-if="paginator.last_page > 1" class="flex flex-wrap gap-1">
            <template v-for="(link, i) in paginator.links" :key="i">
                <span v-if="!link.url" class="rounded px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded px-3 py-1 text-sm"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
