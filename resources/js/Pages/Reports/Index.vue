<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const { t } = useI18n()

const props = defineProps({
  receipts: Object,
  invoices: Object,
  summary: Object,
  type: String,
  start: String,
  end: String,
})

const activeTab = ref(props.type ?? 'daily')
const dateParam = ref(props.start)
const fromDate = ref(props.start)
const toDate = ref(props.end)

const dateParamObj = computed({
  get: () => dateParam.value ? new Date(dateParam.value + 'T00:00:00') : null,
  set: (val) => {
    dateParam.value = val ? val.toISOString().split('T')[0] : ''
    applyFilter()
  },
})
const fromDateObj = computed({
  get: () => fromDate.value ? new Date(fromDate.value + 'T00:00:00') : null,
  set: (val) => { fromDate.value = val ? val.toISOString().split('T')[0] : '' },
})
const toDateObj = computed({
  get: () => toDate.value ? new Date(toDate.value + 'T00:00:00') : null,
  set: (val) => { toDate.value = val ? val.toISOString().split('T')[0] : '' },
})

const tabs = [
  { key: 'daily', label: () => t('daily_report') },
  { key: 'weekly', label: () => t('weekly_report') },
  { key: 'monthly', label: () => t('monthly_report') },
  { key: 'custom', label: () => t('custom_report') },
]

function applyFilter() {
  const params = { type: activeTab.value }
  if (activeTab.value === 'daily') params.date = dateParam.value
  if (activeTab.value === 'custom') {
    params.from_date = fromDate.value
    params.to_date = toDate.value
  }
  router.get(route('reports.index'), params)
}

function switchTab(key) {
  activeTab.value = key
  applyFilter()
}

function fmt(n) {
  return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function fmtDate(d) {
  if (!d) return ''
  const parts = String(d).split('T')[0].split('-')
  return `${parts[2]}-${parts[1]}-${parts[0].slice(-2)}`
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

function exportUrl(format) {
  const base = format === 'excel' ? route('reports.export.excel') : route('reports.export.pdf')
  const params = new URLSearchParams({ from_date: props.start, to_date: props.end })
  return base + '?' + params.toString()
}
</script>

<template>

  <Head :title="t('reports')" />
  <AppLayout>
    <template #title>{{ t('reports') }}</template>

    <!-- Tabs -->
    <div class="flex flex-wrap gap-1 mb-4 bg-white rounded-xl border border-slate-200 p-1.5">
      <button v-for="tab in tabs" :key="tab.key" :class="[
        'flex-1 sm:flex-none px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150',
        activeTab === tab.key ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100',
      ]" @click="switchTab(tab.key)">
        {{ tab.label() }}
      </button>
    </div>

    <!-- Date pickers -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4">
      <div class="flex flex-wrap gap-3 items-end">
        <div v-if="activeTab === 'daily'" class="w-full sm:w-auto sm:min-w-[180px]">
          <label class="block text-xs text-gray-500 mb-1">{{ t('date') }}</label>
          <DatePicker v-model="dateParamObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('date')" class="w-full"
            inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm placeholder-gray-400" />
        </div>
        <template v-if="activeTab === 'custom'">
          <div class="w-full sm:w-auto sm:min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1">{{ t('from_date') }}</label>
            <DatePicker v-model="fromDateObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('from_date')"
              class="w-full"
              inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm placeholder-gray-400" />
          </div>
          <div class="w-full sm:w-auto sm:min-w-[180px]">
            <label class="block text-xs text-gray-500 mb-1">{{ t('to_date') }}</label>
            <DatePicker v-model="toDateObj" dateFormat="yy-mm-dd" showButtonBar :placeholder="t('to_date')"
              class="w-full"
              inputClass="w-full h-[38px] border border-slate-300 rounded-lg px-3 py-2 text-sm placeholder-gray-400" />
          </div>
          <button @click="applyFilter" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">{{
            t('filter') }}</button>
        </template>

        <div class="ml-auto flex gap-2">
          <a :href="exportUrl('excel')"
            class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-700">↓ {{
              t('export_excel') }}</a>
          <a :href="exportUrl('pdf')"
            class="bg-rose-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-700">↓ {{
              t('export_pdf')
            }}</a>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-2">{{ t('report_period') }}: {{ fmtDate(start) }} → {{ fmtDate(end) }}</p>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ t('total_receipts') }}</p>
        <p class="text-2xl font-bold text-blue-600 leading-none mt-2">{{ summary.total_receipts }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ t('total_invoices') }}</p>
        <p class="text-2xl font-bold text-blue-600 leading-none mt-2">{{ summary.total_invoices }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ t('receipts') }}</p>
        <p class="text-lg font-bold text-emerald-600 leading-none mt-2">{{ fmt(summary.receipt_amount) }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ t('invoices') }}</p>
        <p class="text-lg font-bold text-emerald-600 leading-none mt-2">{{ fmt(summary.invoice_amount) }}</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center border-l-4 border-l-blue-500">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ t('total') }}</p>
        <p class="text-lg font-bold text-blue-700 leading-none mt-2">{{ fmt(summary.total_amount) }}</p>
      </div>
    </div>

    <!-- Receipts table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
      <div class="px-4 py-3 border-b border-slate-200 font-semibold text-slate-700">{{ t('receipts') }}</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">No.</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('receipt_number') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('date')
                }}
              </th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('customer_name') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('car_model') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('quantity')
                }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('currency')
                }}</th>
              <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('total_amount') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('payment_category') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(r, i) in receipts.data" :key="r.id"
              class="table-row-animate hover:bg-slate-50 transition-colors" :style="{ '--row-index': i }">
              <td class="px-4 py-2 text-gray-500">{{ receipts.from + i }}</td>
              <td class="px-4 py-2 font-mono text-xs text-blue-700">{{ r.receipt_number }}</td>
              <td class="px-4 py-2 text-gray-600">{{ fmtDate(r.date) }}</td>
              <td class="px-4 py-2">{{ r.customer_name }}</td>
              <td class="px-4 py-2">{{ r.car_model }}</td>
              <td class="px-4 py-2">{{ r.quantity }}</td>
              <td class="px-4 py-2">{{ r.currency }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ fmt(r.total_amount) }}</td>
              <td class="px-4 py-2">
                <span
                  :class="['px-2 py-0.5 rounded-full text-xs font-medium', paymentCategoryClass(r.payment_category)]">{{
                    t(r.payment_category) }}</span>
              </td>
            </tr>
            <tr v-if="!receipts.data || receipts.data.length === 0">
              <td colspan="9" class="px-4 py-6 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 pb-4">
        <Pagination :paginator="receipts" />
      </div>
    </div>

    <!-- Invoices table -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 font-semibold text-slate-700">{{ t('invoices') }}</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">No.</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('invoice_number') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('invoice_type') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('date')
                }}
              </th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('customer_name') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('car_model') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('currency')
                }}</th>
              <th class="px-4 py-2 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('grand_total') }}</th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('vat')
                }}
              </th>
              <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('payment_category') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="(inv, i) in invoices.data" :key="inv.id"
              class="table-row-animate hover:bg-slate-50 transition-colors" :style="{ '--row-index': i }">
              <td class="px-4 py-2 text-gray-500">{{ invoices.from + i }}</td>
              <td class="px-4 py-2 font-mono text-xs text-blue-700">{{ inv.invoice_number }}</td>
              <td class="px-4 py-2">
                <span
                  :class="['px-2 py-0.5 rounded-full text-xs font-medium', inv.invoice_type === 'car_sale' ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700']">{{
                    t(inv.invoice_type) }}</span>
              </td>
              <td class="px-4 py-2 text-gray-600">{{ fmtDate(inv.date) }}</td>
              <td class="px-4 py-2">{{ inv.customer_name }}</td>
              <td class="px-4 py-2">{{ inv.car_model }}</td>
              <td class="px-4 py-2">{{ inv.currency }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ fmt(inv.grand_total) }}</td>
              <td class="px-4 py-2">
                <span :class="inv.with_vat ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap">{{ inv.with_vat ? t('with_vat')
                    :
                    t('without_vat') }}</span>
              </td>
              <td class="px-4 py-2">
                <span
                  :class="['px-2 py-0.5 rounded-full text-xs font-medium', paymentCategoryClass(inv.payment_category)]">{{
                    t(inv.payment_category) }}</span>
              </td>
            </tr>
            <tr v-if="!invoices.data || invoices.data.length === 0">
              <td colspan="10" class="px-4 py-6 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 pb-4">
        <Pagination :paginator="invoices" />
      </div>
    </div>
  </AppLayout>
</template>
