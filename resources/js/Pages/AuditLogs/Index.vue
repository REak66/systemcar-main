<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import SelectDropdown from '@/Components/SelectDropdown.vue'

const { t } = useI18n()

const props = defineProps({
    logs: Object,
    filters: Object,
})

const actionFilter = ref(props.filters?.action ?? '')
const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')
const loading = ref(false)

const fromDateObj = computed({
    get: () => fromDate.value ? new Date(fromDate.value + 'T00:00:00') : null,
    set: (val) => { fromDate.value = val ? val.toISOString().split('T')[0] : '' },
})
const toDateObj = computed({
    get: () => toDate.value ? new Date(toDate.value + 'T00:00:00') : null,
    set: (val) => { toDate.value = val ? val.toISOString().split('T')[0] : '' },
})

let stopStart, stopFinish
onMounted(() => {
    stopStart = router.on('start', () => { loading.value = true })
    stopFinish = router.on('finish', () => { loading.value = false })
})
onUnmounted(() => { stopStart?.(); stopFinish?.() })

function applyFilter() {
    router.get(route('audit-logs.index'), {
        action: actionFilter.value || undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilter() {
    actionFilter.value = ''
    fromDate.value = ''
    toDate.value = ''
    router.get(route('audit-logs.index'))
}

function formatDate(d) {
    if (!d) return '—'
    const parts = String(d).split('T')
    const dp = parts[0].split('-')
    const time = parts[1] ? parts[1].substring(0, 5) : ''
    return `${dp[2]}-${dp[1]}-${dp[0].slice(-2)}${time ? ' ' + time : ''}`
}

function formatJson(obj) {
    if (!obj) return '—'
    const keys = Object.keys(obj).filter(k => !['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'].includes(k))
    return keys.map(k => `${k}: ${obj[k]}`).join(', ')
}
</script>

<template>

    <Head :title="t('audit_logs')" />
    <AppLayout>
        <template #title>{{ t('audit_logs') }}</template>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <!-- Filters -->
            <div class="p-4 border-b border-slate-200">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <SelectDropdown v-model="actionFilter" :label="t('action')" :placeholder="t('action')" :options="[
                            { value: 'create', label: 'create' },
                            { value: 'edit', label: 'edit' },
                            { value: 'delete', label: 'delete' },
                        ]" />
                    </div>
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-slate-500 mb-1">{{ t('from_date') }}</label>
                        <DatePicker v-model="fromDateObj" dateFormat="yy-mm-dd" showButtonBar
                            :placeholder="t('from_date')" class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm placeholder-gray-400" />
                    </div>
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-slate-500 mb-1">{{ t('to_date') }}</label>
                        <DatePicker v-model="toDateObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('to_date')"
                            class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm placeholder-gray-400" />
                    </div>
                    <div class="flex gap-2 items-end">
                        <button @click="applyFilter"
                            class="h-[38px] bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors whitespace-nowrap">{{
                                t('filter') }}</button>
                        <button @click="resetFilter"
                            class="h-[38px] border border-slate-300 text-slate-600 px-5 py-2 rounded-lg text-sm font-medium hover:bg-slate-100 transition-colors whitespace-nowrap">{{
                                t('all') }}</button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('timestamp') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('user') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('action') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('model') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('ip_address') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('old_values') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('new_values') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Skeleton rows -->
                        <template v-if="loading">
                            <tr v-for="n in 8" :key="'sk-' + n">
                                <td v-for="c in 8" :key="c" class="px-4 py-3">
                                    <Skeleton height="1rem" />
                                </td>
                            </tr>
                        </template>
                        <!-- Data rows -->
                        <template v-else>
                            <tr v-for="(log, i) in logs.data" :key="log.id"
                                class="table-row-animate hover:bg-slate-50 transition-colors"
                                :style="{ '--row-index': i }">
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{
                                    formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ log.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'px-2 py-0.5 rounded-full text-xs font-medium',
                                        log.action === 'create' ? 'bg-green-100 text-green-700' :
                                            log.action === 'edit' ? 'bg-blue-100 text-blue-700' :
                                                'bg-red-100 text-red-700'
                                    ]">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ log.model_type }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ log.model_id }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ log.ip_address }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate"
                                    :title="formatJson(log.old_values)">{{ formatJson(log.old_values) }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate"
                                    :title="formatJson(log.new_values)">{{ formatJson(log.new_values) }}</td>
                            </tr>
                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                <Pagination :paginator="logs" />
            </div>
        </div>
    </AppLayout>
</template>
