<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const { t } = useI18n()
const page = usePage()
const confirm = useConfirm()

const props = defineProps({
    receipts: Object,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
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

// ── Import state (step: 'pick' | 'preview' | 'result') ───────────────────────
const showImportModal = ref(false)
const importStep = ref('pick')
const importFiles = ref([])          // multiple files
const importFileInput = ref(null)
const previewRows = ref([])
const previewLoading = ref(false)
const previewError = ref('')
const importLoading = ref(false)
const importResult = ref(null)       // { success, failed, errors }

const hasPreviewErrors = computed(() => previewRows.value.some(r => r.error || r.duplicate))
const importableRows = computed(() => previewRows.value.filter(r => !r.error && !r.duplicate))

function openImportModal() {
    importStep.value = 'pick'
    importFiles.value = []
    previewRows.value = []
    previewError.value = ''
    importResult.value = null
    showImportModal.value = true
}

function closeImport() {
    showImportModal.value = false
}

function onImportFileChange(e) {
    importFiles.value = Array.from(e.target.files)
    previewRows.value = []
    previewError.value = ''
}

function removeFile(index) {
    importFiles.value = importFiles.value.filter((_, i) => i !== index)
    previewRows.value = []
    previewError.value = ''
}

async function loadPreview() {
    if (!importFiles.value.length) return
    previewLoading.value = true
    previewError.value = ''
    previewRows.value = []

    const form = new FormData()
    importFiles.value.forEach(f => form.append('files[]', f))

    try {
        const res = await fetch(route('receipts.import.preview'), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: form,
        })
        if (!res.ok) {
            const json = await res.json().catch(() => ({}))
            previewError.value = json?.message ?? t('preview_error')
        } else {
            previewRows.value = await res.json()
            importStep.value = 'preview'
        }
    } catch {
        previewError.value = t('preview_error')
    } finally {
        previewLoading.value = false
    }
}

function submitImport() {
    if (!importFiles.value.length) return
    importLoading.value = true
    const form = new FormData()
    importFiles.value.forEach(f => form.append('files[]', f))
    router.post(route('receipts.import'), form, {
        forceFormData: true,
        onSuccess: (page) => {
            const flash = page.props.flash ?? {}
            const failed = flash.import_failed ?? 0
            const success = flash.import_success ?? 0
            const errors = flash.import_errors ?? []
            importFiles.value = []
            // Auto-close when no failures; show result step on partial failure
            if (failed === 0) {
                showImportModal.value = false
                importStep.value = 'pick'
                previewRows.value = []
            } else {
                importResult.value = { success, failed, errors }
                importStep.value = 'result'
            }
        },
        onFinish: () => { importLoading.value = false },
    })
}

function fmtAmount(n) {
    return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
    if (!d) return ''
    const parts = String(d).split('T')[0].split('-')
    return `${parts[2]}-${parts[1]}-${parts[0].slice(-2)}`
}

function applyFilter() {
    router.get(route('receipts.index'), {
        search: search.value || undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilter() {
    search.value = ''
    fromDate.value = ''
    toDate.value = ''
    router.get(route('receipts.index'))
}

function openConfirmDelete(event, r) {
    confirm.require({
        target: event.currentTarget,
        message: t('confirm_delete'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => { router.delete(route('receipts.destroy', r.id)) },
    })
}

function buildExportUrl(format) {
    const base = format === 'excel' ? route('receipts.export.excel') : route('receipts.export.pdf')
    const params = new URLSearchParams()
    if (fromDate.value) params.append('from_date', fromDate.value)
    if (toDate.value) params.append('to_date', toDate.value)
    if (search.value) params.append('search', search.value)
    const q = params.toString()
    return base + (q ? '?' + q : '')
}

function paymentCategoryClass(cat) {
    const map = {
        full_payment: 'bg-teal-100 text-teal-700',
        booking: 'bg-amber-100 text-amber-700',
        down_payment: 'bg-indigo-100 text-indigo-700',
        installment: 'bg-orange-100 text-orange-700',
        service_payment: 'bg-violet-100 text-violet-700',
        other: 'bg-slate-100 text-slate-500',
    }
    return map[cat] ?? 'bg-blue-100 text-blue-700'
}
</script>

<template>

    <Head :title="t('receipts')" />
    <AppLayout>
        <template #title>{{ t('receipts') }}</template>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <!-- Toolbar -->
            <div class="p-4 border-b border-slate-200 space-y-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-slate-500 mb-1">{{ t('from_date') }}</label>
                        <DatePicker v-model="fromDateObj" dateFormat="yy-mm-dd" showButtonBar
                            :placeholder="t('from_date')" class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400" />
                    </div>
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-slate-500 mb-1">{{ t('to_date') }}</label>
                        <DatePicker v-model="toDateObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('to_date')"
                            class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400" />
                    </div>
                    <div class="w-full sm:flex-[2] sm:min-w-[200px]">
                        <label class="block text-xs text-slate-500 mb-1">{{ t('search') }}</label>
                        <input v-model="search" type="text" :placeholder="t('search') + '...'"
                            class="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @keyup.enter="applyFilter" />
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

                <div class="flex flex-wrap gap-2">
                    <Link :href="route('receipts.create')"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        + {{ t('create') }}
                    </Link>
                    <button @click="openImportModal"
                        class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-violet-700 transition-colors">
                        ↑ {{ t('import_excel') }}
                    </button>
                    <a :href="buildExportUrl('excel')"
                        class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
                        ↓ {{ t('export_excel') }}
                    </a>
                    <a :href="buildExportUrl('pdf')"
                        class="bg-rose-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-700 transition-colors">
                        ↓ {{ t('export_pdf') }}
                    </a>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                No.
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('receipt_number') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('date')
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('customer_name') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('car_model') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('quantity')
                                }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('currency')
                                }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('total_amount') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('payment_category') }}
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{
                                    t('actions')
                                }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Skeleton rows -->
                        <template v-if="loading">
                            <tr v-for="n in 8" :key="'sk-' + n">
                                <td v-for="c in 10" :key="c" class="px-4 py-3">
                                    <Skeleton height="1rem" />
                                </td>
                            </tr>
                        </template>
                        <!-- Data rows -->
                        <template v-else>
                            <tr v-for="(r, i) in receipts.data" :key="r.id"
                                class="table-row-animate hover:bg-slate-50 transition-colors"
                                :style="{ '--row-index': i }">
                                <td class="px-4 py-3 text-gray-500">{{ receipts.from + i }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ r.receipt_number }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ fmtDate(r.date) }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ r.customer_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.car_model }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.quantity }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ r.currency }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{
                                    Number(r.total_amount).toLocaleString(undefined,
                                        { minimumFractionDigits: 2 }) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="['px-2 py-0.5 rounded-full text-xs font-medium', paymentCategoryClass(r.payment_category)]">{{
                                            t(r.payment_category) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <Link :href="route('receipts.show', r.id)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
                                            {{ t('view') }}</Link>
                                        <Link :href="route('receipts.edit', r.id)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            {{ t('edit') }}</Link>
                                        <button
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                                            @click="openConfirmDelete($event, r)">{{ t('delete') }}</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!receipts.data || receipts.data.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                <Pagination :paginator="receipts" />
            </div>
        </div>

        <ConfirmPopup />

        <!-- Import Modal -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-xl w-full flex flex-col"
                :class="importStep === 'preview' ? 'max-w-4xl max-h-[90vh]' : 'max-w-md'">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                    <h2 class="text-base font-semibold text-gray-800">{{ t('import_excel') }}</h2>
                    <button @click="closeImport"
                        class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <!-- ── STEP: PICK ──────────────────────────────────────────────────── -->
                <div v-if="importStep === 'pick'" class="p-6 space-y-4">
                    <p class="text-xs text-gray-500">{{ t('import_template_hint') }}</p>

                    <div>
                        <span class="block text-xs font-medium text-gray-600 mb-1">{{ t('select_file') }}</span>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <span
                                class="px-3 py-1.5 rounded border border-purple-300 bg-purple-50 text-purple-700 text-sm hover:bg-purple-100 shrink-0">{{
                                    t('choose_files') }}</span>
                            <span class="text-xs text-gray-500">{{ importFiles.length ? t('files_selected', {
                                n:
                                    importFiles.length
                            })
                                :
                                t('no_file_chosen') }}</span>
                            <input ref="importFileInput" type="file" accept=".xlsx,.xls" multiple class="sr-only"
                                @change="onImportFileChange" />
                        </label>

                        <!-- File list chips -->
                        <ul v-if="importFiles.length" class="mt-2 space-y-1">
                            <li v-for="(f, i) in importFiles" :key="i"
                                class="flex items-center justify-between bg-gray-50 border border-slate-200 rounded px-3 py-1.5 text-xs">
                                <span class="text-gray-700 truncate max-w-xs">{{ f.name }}</span>
                                <button @click="removeFile(i)"
                                    class="ml-3 text-gray-400 hover:text-red-500 leading-none text-base shrink-0">&times;</button>
                            </li>
                        </ul>
                    </div>

                    <p v-if="previewError"
                        class="text-xs text-red-600 bg-red-50 border border-red-200 rounded px-3 py-2">{{
                            previewError }}</p>

                    <div class="flex justify-end gap-3 pt-1">
                        <button @click="closeImport"
                            class="px-4 py-2 rounded-lg text-sm bg-gray-100 text-gray-700 hover:bg-gray-200">{{
                                t('cancel') }}</button>
                        <button @click="loadPreview" :disabled="!importFiles.length || previewLoading"
                            class="px-4 py-2 rounded-lg text-sm bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="previewLoading">{{ t('loading') }}...</span>
                            <span v-else>{{ t('preview') }} →</span>
                        </button>
                    </div>
                </div>

                <!-- ── STEP: PREVIEW ───────────────────────────────────────────────── -->
                <div v-else-if="importStep === 'preview'" class="flex flex-col min-h-0">
                    <!-- Summary badge -->
                    <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap gap-3 text-xs shrink-0">
                        <span class="bg-green-100 text-green-800 rounded-full px-2.5 py-0.5 font-medium">
                            {{ importableRows.length }} {{ t('preview_ready') }}
                        </span>
                        <span v-if="hasPreviewErrors"
                            class="bg-red-100 text-red-800 rounded-full px-2.5 py-0.5 font-medium">
                            {{ previewRows.length - importableRows.length }} {{ t('preview_skipped') }}
                        </span>
                        <span class="text-gray-500 ml-auto">{{ t('preview_review_hint') }}</span>
                    </div>

                    <!-- Scrollable table -->
                    <div class="overflow-auto flex-1">
                        <table class="w-full text-xs whitespace-nowrap">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{
                                        t('receipt_number')
                                    }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{
                                        t('customer_name') }}
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{ t('date') }}
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{
                                        t('car_model') }}
                                    </th>
                                    <th class="px-3 py-2 text-right font-medium text-gray-500 uppercase">{{
                                        t('total_amount') }}
                                    </th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{
                                        t('payment_category')
                                    }}</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500 uppercase">{{ t('status')
                                    }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(row, i) in previewRows" :key="i"
                                    :class="row.error ? 'bg-red-50' : row.duplicate ? 'bg-yellow-50' : 'bg-white'">
                                    <td class="px-3 py-2 text-gray-400">{{ i + 1 }}</td>
                                    <td class="px-3 py-2 font-mono font-medium text-gray-800">{{ row.receipt_number ||
                                        '—' }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ row.customer_name }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ fmtDate(row.date) }}</td>
                                    <td class="px-3 py-2 text-gray-700">{{ row.car_model }}</td>
                                    <td class="px-3 py-2 text-right text-gray-800 font-medium">
                                        {{ row.currency === 'KHR' ? '៛' : '$' }} {{ fmtAmount(row.total_amount) }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-600 capitalize">{{ row.payment_category }}</td>
                                    <td class="px-3 py-2">
                                        <span v-if="row.error"
                                            class="inline-flex items-center gap-1 text-red-700 bg-red-100 px-2 py-0.5 rounded-full text-xs"
                                            :title="row.error">
                                            ✕ {{ t('status_error') }}
                                        </span>
                                        <span v-else-if="row.duplicate"
                                            class="inline-flex items-center gap-1 text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded-full text-xs">
                                            ⚠ {{ t('status_duplicate') }}
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center gap-1 text-green-700 bg-green-100 px-2 py-0.5 rounded-full text-xs">
                                            ✓ {{ t('status_ready') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer actions -->
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center shrink-0">
                        <button @click="importStep = 'pick'" class="text-sm text-gray-500 hover:text-gray-700">← {{
                            t('back')
                        }}</button>
                        <div class="flex gap-3">
                            <button @click="closeImport"
                                class="px-4 py-2 rounded-lg text-sm bg-gray-100 text-gray-700 hover:bg-gray-200">{{
                                    t('cancel')
                                }}</button>
                            <button @click="submitImport" :disabled="importableRows.length === 0 || importLoading"
                                class="px-4 py-2 rounded-lg text-sm bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span v-if="importLoading">{{ t('loading') }}...</span>
                                <span v-else>{{ t('confirm_import') }} ({{ importableRows.length }})</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── STEP: RESULT ────────────────────────────────────────────────── -->
                <div v-else-if="importStep === 'result'" class="p-6 space-y-3">
                    <div v-if="importResult.success > 0"
                        class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-lg p-4">
                        <span class="text-green-600 text-xl mt-0.5">✓</span>
                        <div>
                            <p class="text-sm font-semibold text-green-800">{{ t('import_done') }}</p>
                            <p class="text-xs text-green-700 mt-0.5">{{ importResult.success }} {{
                                t('import_success_count') }}
                            </p>
                        </div>
                    </div>
                    <div v-if="importResult.failed > 0"
                        class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-lg p-4">
                        <span class="text-red-600 text-xl mt-0.5">✕</span>
                        <div>
                            <p class="text-sm font-semibold text-red-800">{{ importResult.failed }} {{
                                t('import_failed_count')
                            }}</p>
                            <ul v-if="importResult.errors.length" class="mt-1 space-y-0.5">
                                <li v-for="(err, i) in importResult.errors" :key="i" class="text-xs text-red-600">{{ err
                                }}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button @click="closeImport"
                            class="px-4 py-2 rounded-lg text-sm bg-gray-800 text-white hover:bg-gray-900">{{
                                t('close') }}</button>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
