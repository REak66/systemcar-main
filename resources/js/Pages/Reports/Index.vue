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
    <div class="flex gap-1 mb-4 bg-white rounded-xl shadow-sm border border-gray-100 p-1 w-fit">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.key ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100',
        ]"
        @click="switchTab(tab.key)"
      >
        {{ tab.label() }}
      </button>
    </div>

    <!-- Date pickers -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
      <div class="flex flex-wrap gap-3 items-end">
        <div v-if="activeTab === 'daily'">
          <label class="block text-xs text-gray-500 mb-1">{{ t('date') }}</label>
          <input v-model="dateParam" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @change="applyFilter" />
        </div>
        <template v-if="activeTab === 'custom'">
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('from_date') }}</label>
            <input v-model="fromDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('to_date') }}</label>
            <input v-model="toDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <button @click="applyFilter" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">{{ t('filter') }}</button>
        </template>

        <div class="ml-auto flex gap-2">
          <a :href="exportUrl('excel')" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">↓ {{ t('export_excel') }}</a>
          <a :href="exportUrl('pdf')" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700">↓ {{ t('export_pdf') }}</a>
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-2">{{ t('report_period') }}: {{ fmtDate(start) }} → {{ fmtDate(end) }}</p>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500">{{ t('total_receipts') }}</p>
        <p class="text-xl font-bold text-blue-600">{{ summary.total_receipts }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500">{{ t('total_invoices') }}</p>
        <p class="text-xl font-bold text-blue-600">{{ summary.total_invoices }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500">{{ t('receipts') }}</p>
        <p class="text-lg font-bold text-green-600">{{ fmt(summary.receipt_amount) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500">{{ t('invoices') }}</p>
        <p class="text-lg font-bold text-green-600">{{ fmt(summary.invoice_amount) }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs text-gray-500">{{ t('total') }}</p>
        <p class="text-lg font-bold text-blue-700">{{ fmt(summary.total_amount) }}</p>
      </div>
    </div>

    <!-- Receipts table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
      <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700">{{ t('receipts') }}</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs text-gray-500">No.</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('receipt_number') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('date') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('customer_name') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('car_model') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('quantity') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('currency') }}</th>
              <th class="px-4 py-2 text-right text-xs text-gray-500">{{ t('total_amount') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('payment_category') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="(r, i) in receipts.data" :key="r.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-500">{{ receipts.from + i }}</td>
              <td class="px-4 py-2 font-mono text-xs text-blue-700">{{ r.receipt_number }}</td>
              <td class="px-4 py-2 text-gray-600">{{ fmtDate(r.date) }}</td>
              <td class="px-4 py-2">{{ r.customer_name }}</td>
              <td class="px-4 py-2">{{ r.car_model }}</td>
              <td class="px-4 py-2">{{ r.quantity }}</td>
              <td class="px-4 py-2">{{ r.currency }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ fmt(r.total_amount) }}</td>
              <td class="px-4 py-2 text-xs">{{ t(r.payment_category) }}</td>
            </tr>
            <tr v-if="!receipts.data || receipts.data.length === 0">
              <td colspan="9" class="px-4 py-6 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 pb-4">
        <Pagination :links="receipts.links" />
      </div>
    </div>

    <!-- Invoices table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700">{{ t('invoices') }}</div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs text-gray-500">No.</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('invoice_number') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('invoice_type') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('date') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('customer_name') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('car_model') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('currency') }}</th>
              <th class="px-4 py-2 text-right text-xs text-gray-500">{{ t('grand_total') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('vat') }}</th>
              <th class="px-4 py-2 text-left text-xs text-gray-500">{{ t('payment_category') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="(inv, i) in invoices.data" :key="inv.id" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-gray-500">{{ invoices.from + i }}</td>
              <td class="px-4 py-2 font-mono text-xs text-blue-700">{{ inv.invoice_number }}</td>
              <td class="px-4 py-2 text-xs">{{ t(inv.invoice_type) }}</td>
              <td class="px-4 py-2 text-gray-600">{{ fmtDate(inv.date) }}</td>
              <td class="px-4 py-2">{{ inv.customer_name }}</td>
              <td class="px-4 py-2">{{ inv.car_model }}</td>
              <td class="px-4 py-2">{{ inv.currency }}</td>
              <td class="px-4 py-2 text-right font-medium">{{ fmt(inv.grand_total) }}</td>
              <td class="px-4 py-2 text-xs">{{ inv.with_vat ? t('with_vat') : t('without_vat') }}</td>
              <td class="px-4 py-2 text-xs">{{ t(inv.payment_category) }}</td>
            </tr>
            <tr v-if="!invoices.data || invoices.data.length === 0">
              <td colspan="10" class="px-4 py-6 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="px-4 pb-4">
        <Pagination :links="invoices.links" />
      </div>
    </div>
  </AppLayout>
</template>
