<script setup>
import { ref, computed } from 'vue'
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

const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash)

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
    <div class="min-h-screen bg-gray-100 flex">
        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" class="fixed inset-0 z-20 bg-black/50 lg:hidden" @click="sidebarOpen = false" />

        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-30 w-64 flex flex-col bg-blue-900 transition-transform duration-200',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]">
            <!-- Brand -->
            <div class="flex items-center gap-2 h-16 px-4 bg-blue-950 flex-shrink-0">
                <span class="font-bold text-white text-lg">{{ t('app_name') }}</span>
                <button class="ml-auto text-blue-300 hover:text-white lg:hidden" @click="sidebarOpen = false">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto py-4 px-2">
                <Link v-for="item in navItems" :key="item.match" :href="item.href" :class="[
                    'flex items-center gap-3 px-3 py-2.5 rounded-lg mb-1 text-sm font-medium transition-colors',
                    isActive(item.match)
                        ? 'bg-blue-700 text-white'
                        : 'text-blue-200 hover:bg-blue-800 hover:text-white',
                ]" @click="sidebarOpen = false">
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    {{ item.name }}
                </Link>
            </nav>

            <!-- User info -->
            <div class="p-3 border-t border-blue-800 flex-shrink-0">
                <div class="flex items-center gap-3 px-2 py-1">
                    <div
                        class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        {{ user?.name?.charAt(0)?.toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-white text-sm font-medium truncate">{{ user?.name }}</p>
                        <p class="text-blue-300 text-xs capitalize">{{ user?.role }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col lg:ml-64 min-w-0">
            <!-- Top header -->
            <header class="sticky top-0 z-10 h-16 bg-white shadow-sm flex items-center gap-4 px-4 lg:px-6">
                <button class="text-gray-500 hover:text-gray-700 lg:hidden" @click="sidebarOpen = true">
                    <Bars3Icon class="w-6 h-6" />
                </button>

                <h1 class="flex-1 font-semibold text-gray-800 text-lg truncate">
                    <slot name="title" />
                </h1>

                <!-- Language switcher -->
                <div class="relative">
                    <button class="flex items-center gap-1 text-gray-600 hover:text-gray-900 text-sm px-2 py-1 rounded"
                        @click="langMenuOpen = !langMenuOpen">
                        <GlobeAltIcon class="w-4 h-4" />
                        <span class="hidden sm:block uppercase font-medium">{{ locale }}</span>
                        <ChevronDownIcon class="w-3 h-3" />
                    </button>
                    <div v-if="langMenuOpen"
                        class="absolute right-0 mt-1 w-32 bg-white shadow-lg rounded-lg border py-1 z-50">
                        <button v-for="l in locales" :key="l.code"
                            :class="['w-full text-left px-3 py-2 text-sm hover:bg-gray-100', locale === l.code ? 'font-semibold text-blue-600' : 'text-gray-700']"
                            @click="switchLocale(l.code)">
                            {{ l.label }}
                        </button>
                    </div>
                </div>

                <!-- Logout -->
                <Link :href="route('logout')" method="post" as="button"
                    class="flex items-center gap-1 text-gray-600 hover:text-red-600 text-sm px-2 py-1 rounded transition-colors">
                    <ArrowRightOnRectangleIcon class="w-5 h-5" />
                    <span class="hidden sm:block">{{ t('logout') }}</span>
                </Link>
            </header>

            <!-- Flash messages -->
            <div class="mx-4 lg:mx-6 mt-4 space-y-2">
                <Transition name="flash">
                    <div v-if="flash?.success"
                        class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                        ✓ {{ flash.success }}
                    </div>
                </Transition>
                <Transition name="flash">
                    <div v-if="flash?.error"
                        class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                        ✗ {{ flash.error }}
                    </div>
                </Transition>
            </div>

            <!-- Page content -->
            <main class="flex-1 p-4 lg:p-6 animate-fade-up">
                <slot />
            </main>
        </div>
    </div>
</template>
