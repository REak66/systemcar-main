<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    paginator: { type: Object, default: null },
    links: { type: Array, default: null },
})

const PER_PAGE_OPTIONS = [10, 25, 50]

const paginatorLinks = computed(() => props.paginator?.links ?? props.links ?? [])

const meta = computed(() =>
    props.paginator
        ? {
            from: props.paginator.from,
            to: props.paginator.to,
            total: props.paginator.total,
            per_page: props.paginator.per_page,
        }
        : null
)

const hasLinks = computed(() => paginatorLinks.value.length > 3)

// ── Custom dropdown state ─────────────────────────────────────────────────────
const dropdownOpen = ref(false)
const dropdownRef = ref(null)

function toggleDropdown() { dropdownOpen.value = !dropdownOpen.value }

function selectPerPage(val) {
    dropdownOpen.value = false
    if (val === meta.value?.per_page) return
    const url = new URL(window.location.href)
    url.searchParams.set('per_page', val)
    url.searchParams.delete('page')
    router.visit(url.toString(), { replace: true })
}

function onClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        dropdownOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
    <div v-if="meta?.total || hasLinks" class="mt-4 space-y-3">

        <!-- Info row: showing X–Y of Z + per-page selector -->
        <div v-if="meta?.total" class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-slate-500">
                Showing
                <span class="font-semibold text-slate-700">{{ meta.from ?? 0 }}</span>
                –
                <span class="font-semibold text-slate-700">{{ meta.to ?? 0 }}</span>
                of
                <span class="font-semibold text-slate-700">{{ meta.total }}</span>
                records
            </p>

            <!-- Custom per-page dropdown -->
            <div ref="dropdownRef" class="relative flex items-center gap-2">
                <span class="text-sm text-slate-500 select-none">Per page</span>

                <button type="button" @click="toggleDropdown"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ meta.per_page }}
                    <!-- chevron -->
                    <svg :class="['w-4 h-4 text-slate-400 transition-transform duration-150', dropdownOpen ? 'rotate-180' : '']"
                        xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Dropdown panel -->
                <transition enter-active-class="transition ease-out duration-100" enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100" leave-active-class="transition ease-in duration-75"
                    leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
                    <div v-if="dropdownOpen"
                        class="absolute right-0 bottom-9 z-20 w-24 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <button v-for="n in PER_PAGE_OPTIONS" :key="n" type="button" @click="selectPerPage(n)" :class="[
                            'w-full px-4 py-2 text-left text-sm transition-colors',
                            n === meta.per_page
                                ? 'bg-blue-50 text-blue-600 font-semibold'
                                : 'text-slate-700 hover:bg-slate-50',
                        ]">
                            {{ n }}
                            <span v-if="n === meta.per_page" class="float-right text-blue-500">✓</span>
                        </button>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Page links -->
        <div v-if="hasLinks" class="flex items-center justify-center gap-1 flex-wrap">
            <template v-for="link in paginatorLinks" :key="link.label">
                <Link v-if="link.url" :href="link.url" preserve-scroll :class="[
                    'px-3.5 py-1.5 text-sm rounded-lg border font-medium transition-all duration-150',
                    link.active
                        ? 'bg-blue-600 text-white border-blue-600'
                        : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50 hover:border-slate-400',
                ]" v-html="link.label" />
                <span v-else
                    class="px-3.5 py-1.5 text-sm rounded-lg border bg-slate-50 text-slate-400 border-slate-200 cursor-not-allowed"
                    v-html="link.label" />
            </template>
        </div>

    </div>
</template>
