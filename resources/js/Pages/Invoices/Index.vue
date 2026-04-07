<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
  invoices: Object,
  filters: Object,
})

const search = ref(props.filters?.search ?? '')
const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')
const typeFilter = ref(props.filters?.type ?? '')
const deleteTarget = ref(null)
const loading = ref(false)

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

function doDelete() {
  router.delete(route('invoices.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
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
</script>

<template>
  <Head :title="t('invoices')" />
  <AppLayout>
    <template #title>{{ t('invoices') }}</template>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 space-y-3">
        <div class="flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('from_date') }}</label>
            <input v-model="fromDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('to_date') }}</label>
            <input v-model="toDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('invoice_type') }}</label>
            <select v-model="typeFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">{{ t('all') }}</option>
              <option value="car_sale">{{ t('car_sale') }}</option>
              <option value="service">{{ t('service') }}</option>
            </select>
          </div>
          <div class="flex-1 min-w-40">
            <label class="block text-xs text-gray-500 mb-1">{{ t('search') }}</label>
            <input v-model="search" type="text" :placeholder="t('search') + '...'" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @keyup.enter="applyFilter" />
          </div>
          <button @click="applyFilter" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">{{ t('filter') }}</button>
          <button @click="resetFilter" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition-colors">{{ t('all') }}</button>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link :href="route('invoices.create') + '?type=car_sale'" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
            + {{ t('car_sale') }}
          </Link>
          <Link :href="route('invoices.create') + '?type=service'" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition-colors">
            + {{ t('service') }}
          </Link>
          <a :href="buildExportUrl('excel')" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
            ↓ {{ t('export_excel') }}
          </a>
          <a :href="buildExportUrl('pdf')" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition-colors">
            ↓ {{ t('export_pdf') }}
          </a>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No.</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_type') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer_name') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('car_model') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('currency') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('grand_total') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('vat') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('payment_category') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <!-- Skeleton rows -->
            <template v-if="loading">
              <tr v-for="n in 8" :key="'sk-' + n" class="animate-pulse [animation-duration:5s]">
                <td v-for="c in 11" :key="c" class="px-4 py-3"><div class="h-4 bg-gray-200 rounded"></div></td>
              </tr>
            </template>
            <!-- Data rows -->
            <template v-else>
            <tr v-for="(inv, i) in invoices.data" :key="inv.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500">{{ invoices.from + i }}</td>
              <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ inv.invoice_number }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 rounded-full text-xs', inv.invoice_type === 'car_sale' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700']">
                  {{ t(inv.invoice_type) }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ fmtDate(inv.date) }}</td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ inv.customer_name }}</td>
              <td class="px-4 py-3 text-gray-600">{{ inv.car_model }}</td>
              <td class="px-4 py-3 text-gray-600">{{ inv.currency }}</td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ Number(inv.grand_total).toLocaleString(undefined, {minimumFractionDigits:2}) }}</td>
              <td class="px-4 py-3">
                <span :class="inv.with_vat ? 'text-green-600' : 'text-gray-400'" class="text-xs">{{ inv.with_vat ? t('with_vat') : t('without_vat') }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">{{ t(inv.payment_category) }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <Link :href="route('invoices.show', inv.id)" class="text-xs text-gray-600 hover:underline">{{ t('view') }}</Link>
                  <Link :href="route('invoices.edit', inv.id)" class="text-xs text-blue-600 hover:underline">{{ t('edit') }}</Link>
                  <button class="text-xs text-red-600 hover:underline" @click="deleteTarget = inv">{{ t('delete') }}</button>
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
        <Pagination :links="invoices.links" />
      </div>
    </div>

    <Modal
      :show="!!deleteTarget"
      :title="t('delete')"
      :message="t('confirm_delete')"
      :confirm-text="t('yes')"
      :cancel-text="t('cancel')"
      @confirm="doDelete"
      @cancel="deleteTarget = null"
    />
  </AppLayout>
</template>
