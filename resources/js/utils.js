import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'

export const money = (v) =>
    'AED ' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

export const date = (v) =>
    v ? new Date(v).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-'

export const cap = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : '')

/** Reactive list filters that reload the page (debounced) when they change. */
export function useListFilters(url, initial) {
    const filters = reactive({ ...initial })
    let timer
    watch(filters, () => {
        clearTimeout(timer)
        timer = setTimeout(() => {
            const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v != null))
            router.get(url, query, { preserveState: true, replace: true })
        }, 300)
    })
    return filters
}
