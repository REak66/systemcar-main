<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import {
    HomeIcon,
    UsersIcon,
    DocumentTextIcon,
    DocumentDuplicateIcon,
    ChartBarIcon,
    PaperAirplaneIcon,
    ClipboardDocumentListIcon,
    Bars3Icon,
    XMarkIcon,
    GlobeAltIcon,
    ChevronDownIcon,
    ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const { t, locale } = useI18n()
const page = usePage()

const sidebarOpen = ref(false)
const langMenuOpen = ref(false)
const langMenuRef = ref(null)

const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash)

// Auto-dismiss flash messages
const showSuccess = ref(false)
const showError = ref(false)
let successTimer = null
let errorTimer = null

watch(
    () => flash.value?.success,
    (val) => {
        clearTimeout(successTimer)
        showSuccess.value = !!val
        if (val) successTimer = setTimeout(() => { showSuccess.value = false }, 4000)
    },
    { immediate: true },
)

watch(
    () => flash.value?.error,
    (val) => {
        clearTimeout(errorTimer)
        showError.value = !!val
        if (val) errorTimer = setTimeout(() => { showError.value = false }, 5000)
    },
    { immediate: true },
)

const locales = [
    { code: 'en', label: 'English' },
    { code: 'km', label: 'ខ្មែរ' },
    { code: 'zh', label: '中文' },
]

function switchLocale(code) {
    locale.value = code
    localStorage.setItem('locale', code)
    langMenuOpen.value = false
}

function handleClickOutside(e) {
    if (langMenuRef.value && !langMenuRef.value.contains(e.target)) {
        langMenuOpen.value = false
    }
}

onMounted(() => {
    const saved = localStorage.getItem('locale')
    if (saved) locale.value = saved
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    clearTimeout(successTimer)
    clearTimeout(errorTimer)
})

const navItems = computed(() => {
    const items = [
        { name: t('dashboard'), href: route('dashboard'), match: '/dashboard', icon: HomeIcon },
        { name: t('receipts'), href: route('receipts.index'), match: '/receipts', icon: DocumentTextIcon },
        { name: t('invoices'), href: route('invoices.index'), match: '/invoices', icon: DocumentDuplicateIcon },
        { name: t('reports'), href: route('reports.index'), match: '/reports', icon: ChartBarIcon },
    ]
    if (user.value?.role === 'admin') {
        items.push({ name: t('telegram'), href: route('telegram.index'), match: '/telegram', icon: PaperAirplaneIcon })
        items.push({ name: t('users'), href: route('users.index'), match: '/users', icon: UsersIcon })
        items.push({ name: t('audit_logs'), href: route('audit-logs.index'), match: '/audit-logs', icon: ClipboardDocumentListIcon })
    }
    return items
})

function isActive(match) {
    return page.url.startsWith(match)
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-black/50 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 w-64 flex flex-col bg-blue-950 transition-transform duration-300',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <!-- Brand -->
            <div class="flex items-center gap-3 h-16 px-5 bg-black/20 border-b border-white/10 flex-shrink-0">
                <span class="font-bold text-white tracking-wide">{{ t('app_name') }}</span>
                <button class="ml-auto text-white/60 hover:text-white lg:hidden" aria-label="Close sidebar"
                    @click="sidebarOpen = false">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-0.5">
                <Link v-for="item in navItems" :key="item.match" :href="item.href"
                    :aria-current="isActive(item.match) ? 'page' : undefined" :class="[
                        'sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200',
                        isActive(item.match)
                            ? 'bg-blue-600 text-white'
                            : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                    ]" @click="sidebarOpen = false">
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User info -->
            <div class="p-4 border-t border-white/10 flex-shrink-0 bg-black/15">
                <div class="flex items-center gap-3 px-1">
                    <div
                        class="w-9 h-9 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-sm font-semibold truncate">{{ user?.name }}</p>
                        <p class="text-blue-300/80 text-xs capitalize">{{ user?.role }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
            <!-- Top header -->
            <header
                class="sticky top-0 z-10 h-16 bg-white border-b border-slate-200 flex items-center gap-4 px-4 lg:px-6">
                <button class="text-slate-500 hover:text-slate-700 lg:hidden" aria-label="Open sidebar"
                    @click="sidebarOpen = true">
                    <Bars3Icon class="w-6 h-6" />
                </button>

                <h1 class="flex-1 font-semibold text-slate-800 text-base truncate">
                    <slot name="title" />
                </h1>

                <!-- Language switcher -->
                <div ref="langMenuRef" class="relative">
                    <button
                        class="flex items-center gap-1 text-slate-600 hover:text-slate-900 text-sm px-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors"
                        :aria-expanded="langMenuOpen" aria-haspopup="listbox" aria-label="Switch language"
                        @click="langMenuOpen = !langMenuOpen">
                        <GlobeAltIcon class="w-4 h-4" />
                        <span class="hidden sm:block uppercase font-semibold text-xs">{{ locale }}</span>
                        <ChevronDownIcon class="w-3 h-3" />
                    </button>
                    <div v-if="langMenuOpen" role="listbox"
                        class="absolute right-0 mt-1 w-32 bg-white rounded-xl border border-slate-200 py-1 z-50">
                        <button v-for="l in locales" :key="l.code" role="option" :aria-selected="locale === l.code"
                            :class="['w-full text-left px-3 py-2 text-sm hover:bg-slate-50 transition-colors', locale === l.code ? 'font-semibold text-blue-600' : 'text-slate-700']"
                            @click="switchLocale(l.code)">
                            {{ l.label }}
                        </button>
                    </div>
                </div>

                <!-- Logout -->
                <Link :href="route('logout')" method="post" as="button"
                    class="flex items-center gap-1.5 text-slate-600 hover:text-red-600 text-sm px-2 py-1.5 rounded-lg hover:bg-red-50 transition-all duration-150">
                    <ArrowRightOnRectangleIcon class="w-5 h-5" />
                    <span class="hidden sm:block">{{ t('logout') }}</span>
                </Link>
            </header>

            <!-- Flash messages -->
            <div class="px-4 lg:px-6 mt-4 space-y-2">
                <Transition name="flash">
                    <div v-if="showSuccess && flash?.success" role="status"
                        class="flex items-center justify-between bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl text-sm">
                        <span>✓ {{ flash.success }}</span>
                        <button class="ml-4 text-green-500 hover:text-green-800 transition-colors" aria-label="Dismiss"
                            @click="showSuccess = false">✕</button>
                    </div>
                </Transition>
                <Transition name="flash">
                    <div v-if="showError && flash?.error" role="alert"
                        class="flex items-center justify-between bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-sm">
                        <span>✗ {{ flash.error }}</span>
                        <button class="ml-4 text-red-500 hover:text-red-800 transition-colors" aria-label="Dismiss"
                            @click="showError = false">✕</button>
                    </div>
                </Transition>
            </div>

            <!-- Page content -->
            <Transition name="page" mode="out-in">
                <main :key="page.component" class="flex-1 p-4 lg:p-6">
                    <slot />
                </main>
            </Transition>
        </div>
    </div>
</template>
