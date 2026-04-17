<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import SelectDropdown from '@/Components/SelectDropdown.vue'

const { t } = useI18n()
const confirm = useConfirm()

const props = defineProps({
    invoices: Object,
    filters: Object,
})

const search = ref(props.filters?.search ?? '')
const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')
const typeFilter = ref(props.filters?.type ?? '')
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
    router.get(route('invoices.index'), {
        search: search.value || undefined,
        from_date: fromDate.value || undefined,
        to_date: toDate.value || undefined,
        type: typeFilter.value || undefined,
    }, { preserveState: true, replace: true })
}

function resetFilter() {
    search.value = ''
    fromDate.value = ''
    toDate.value = ''
    typeFilter.value = ''
    router.get(route('invoices.index'))
}

function openConfirmDelete(event, inv) {
    confirm.require({
        target: event.currentTarget,
        message: t('confirm_delete'),
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => { router.delete(route('invoices.destroy', inv.id)) },
    })
}

function fmtDate(d) {
    if (!d) return ''
    const parts = String(d).split('T')[0].split('-')
    return `${parts[2]}-${parts[1]}-${parts[0].slice(-2)}`
}

function buildExportUrl(type) {
    const base = type === 'excel' ? route('invoices.export.excel') : route('invoices.export.pdf')
    const params = new URLSearchParams()
    if (fromDate.value) params.append('from_date', fromDate.value)
    if (toDate.value) params.append('to_date', toDate.value)
    if (typeFilter.value) params.append('type', typeFilter.value)
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
        other: 'bg-slate-100 text-gray-900',
    }
    return map[cat] ?? 'bg-blue-100 text-blue-700'
}
</script>

<template>

    <Head :title="t('invoices')" />
    <AppLayout>
        <template #title>{{ t('invoices') }}</template>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <!-- Toolbar -->
            <div class="p-4 border-b border-slate-200 space-y-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-gray-900 mb-1">{{ t('from_date') }}</label>
                        <DatePicker v-model="fromDateObj" dateFormat="yy-mm-dd" showButtonBar
                            :placeholder="t('from_date')" class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400" />
                    </div>
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <label class="block text-xs text-gray-900 mb-1">{{ t('to_date') }}</label>
                        <DatePicker v-model="toDateObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('to_date')"
                            class="w-full"
                            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400" />
                    </div>
                    <div class="w-full sm:flex-1 sm:min-w-[150px]">
                        <SelectDropdown v-model="typeFilter" :label="t('invoice_type')" :placeholder="t('invoice_type')"
                            :options="[
                                { value: 'car_sale', label: t('car_sale') },
                                { value: 'service', label: t('service') },
                            ]" />
                    </div>
                    <div class="w-full sm:flex-[2] sm:min-w-[200px]">
                        <label class="block text-xs text-gray-900 mb-1">{{ t('search') }}</label>
                        <input v-model="search" type="text" :placeholder="t('search') + '...'"
                            class="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @keyup.enter="applyFilter" />
                    </div>
                    <div class="flex gap-2 items-end">
                        <button @click="applyFilter"
                            class="h-[38px] bg-blue-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors whitespace-nowrap">{{
                                t('filter') }}</button>
                        <button @click="resetFilter"
                            class="h-[38px] border border-slate-300 text-gray-900 px-5 py-2 rounded-lg text-sm font-medium hover:bg-slate-100 transition-colors whitespace-nowrap">{{
                                t('all') }}</button>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link :href="route('invoices.create') + '?type=car_sale'"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                        + {{ t('car_sale') }}
                    </Link>
                    <Link :href="route('invoices.create') + '?type=service'"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors">
                        + {{ t('service') }}
                    </Link>
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
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                No.
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('invoice_number') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('invoice_type') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('date')
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('customer_name') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('car_model') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('currency')
                                }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('grand_total') }}</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('vat')
                                }}
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('payment_category') }}
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-900 uppercase tracking-wider">
                                {{
                                    t('actions')
                                }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <!-- Skeleton rows -->
                        <template v-if="loading">
                            <tr v-for="n in 8" :key="'sk-' + n">
                                <td v-for="c in 11" :key="c" class="px-4 py-3">
                                    <Skeleton height="1rem" />
                                </td>
                            </tr>
                        </template>
                        <!-- Data rows -->
                        <template v-else>
                            <tr v-for="(inv, i) in invoices.data" :key="inv.id"
                                class="table-row-animate hover:bg-slate-50 transition-colors"
                                :style="{ '--row-index': i }">
                                <td class="px-4 py-3 text-gray-500">{{ invoices.from + i }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ inv.invoice_number }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="['px-2 py-0.5 rounded-full text-xs font-medium', inv.invoice_type === 'car_sale' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700']">
                                        {{ t(inv.invoice_type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ fmtDate(inv.date) }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ inv.customer_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ inv.car_model }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ inv.currency }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{
                                    Number(inv.grand_total).toLocaleString(undefined,
                                        { minimumFractionDigits: 2 }) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="inv.with_vat ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                        class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{
                                            inv.with_vat ?
                                                t('with_vat') : t('without_vat') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="['px-2 py-0.5 rounded-full text-xs font-medium', paymentCategoryClass(inv.payment_category)]">{{
                                            t(inv.payment_category) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <Link :href="route('invoices.show', inv.id)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-gray-900 bg-slate-100 hover:bg-slate-200 transition-colors">
                                            {{ t('view') }}</Link>
                                        <Link :href="route('invoices.edit', inv.id)"
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                                            {{ t('edit') }}</Link>
                                        <button
                                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                                            @click="openConfirmDelete($event, inv)">{{ t('delete') }}</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="11" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                <Pagination :paginator="invoices" />
            </div>
        </div>

        <ConfirmPopup />
    </AppLayout>
</template>
