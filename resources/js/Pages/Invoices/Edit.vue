<script setup>
import { computed, watch, ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()
const props = defineProps({
  invoice: Object,
  customerNames: { type: Array, default: () => [] },
  carModels: { type: Array, default: () => [] },
})

const form = useForm({
  invoice_type: props.invoice.invoice_type,
  date: props.invoice.date,
  customer_name: props.invoice.customer_name,
  customer_address: props.invoice.customer_address ?? '',
  customer_phone: props.invoice.customer_phone ?? '',
  car_model: props.invoice.car_model,
  unit_price: props.invoice.unit_price,
  currency: props.invoice.currency,
  quantity: props.invoice.quantity,
  sub_total: props.invoice.sub_total,
  with_vat: props.invoice.with_vat,
  vat_rate: props.invoice.vat_rate,
  vat_amount: props.invoice.vat_amount,
  grand_total: props.invoice.grand_total,
  payment_category: props.invoice.payment_category,
  bank_reference: props.invoice.bank_reference,
  notes: props.invoice.notes ?? '',
})

const subTotal = computed(() => {
  const price = parseFloat(form.unit_price) || 0
  const qty = parseInt(form.quantity) || 0
  return price * qty
})
const vatAmount = computed(() => form.with_vat ? subTotal.value * (form.vat_rate / 100) : 0)
const grandTotal = computed(() => (subTotal.value + vatAmount.value).toFixed(2))

watch([() => form.unit_price, () => form.quantity, () => form.with_vat, () => form.vat_rate], () => {
  form.sub_total = subTotal.value.toFixed(2)
  form.vat_amount = vatAmount.value.toFixed(2)
  form.grand_total = grandTotal.value
})

const paymentCategories = computed(() => [
  { value: 'booking', label: t('booking') },
  { value: 'full_payment', label: t('full_payment') },
  { value: 'down_payment', label: t('down_payment') },
  { value: 'installment', label: t('installment') },
  { value: 'service_payment', label: t('service_payment') },
  { value: 'other', label: t('other') },
])

const filteredCustomers = ref([])
const filteredCars = ref([])

function searchCustomers(event) {
  const q = event.query.toLowerCase()
  filteredCustomers.value = q
    ? props.customerNames.filter(n => n.toLowerCase().includes(q))
    : [...props.customerNames]
}

function searchCars(event) {
  const q = event.query.toLowerCase()
  filteredCars.value = q
    ? props.carModels.filter(m => m.toLowerCase().includes(q))
    : [...props.carModels]
}

const formDateObj = computed({
  get: () => form.date ? new Date(form.date + 'T00:00:00') : null,
  set: (val) => { form.date = val ? val.toISOString().split('T')[0] : '' },
})

function submit() {
  form.sub_total = subTotal.value.toFixed(2)
  form.vat_amount = vatAmount.value.toFixed(2)
  form.grand_total = grandTotal.value
  form.put(route('invoices.update', props.invoice.id))
}
</script>

<template>

  <Head :title="t('edit') + ' ' + t('invoices')" />
  <AppLayout>
    <template #title>{{ t('invoices') }} — {{ t('edit') }}</template>

    <div class="max-w-3xl">
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
          <span class="text-xs text-blue-600 font-medium">{{ t('invoice_number') }}: </span>
          <span class="font-mono text-blue-800 font-semibold">{{ invoice.invoice_number }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-6">

          <!-- Section: Document Info -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('invoice_number') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('invoice_type') }}</label>
                <select v-model="form.invoice_type"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="car_sale">{{ t('car_sale') }}</option>
                  <option value="service">{{ t('service') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('date') }}</label>
                <DatePicker v-model="formDateObj" dateFormat="yy-mm-dd" showButtonBar class="w-full"
                  inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
              </div>
            </div>
          </div>

          <!-- Section: Customer Information -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('customer_name') }}</h3>
            <div class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_name') }} *</label>
                  <AutoComplete v-model="form.customer_name" :suggestions="filteredCustomers"
                    @complete="searchCustomers" class="w-full"
                    inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
                  <p v-if="form.errors.customer_name" class="text-red-600 text-xs mt-1">{{ form.errors.customer_name }}
                  </p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_phone') }}</label>
                  <input v-model="form.customer_phone" type="text" :placeholder="t('customer_phone')"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_address') }}</label>
                <input v-model="form.customer_address" type="text" :placeholder="t('customer_address')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('car_model') }} *</label>
                <AutoComplete v-model="form.car_model" :suggestions="filteredCars" @complete="searchCars" class="w-full"
                  inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
                <p v-if="form.errors.car_model" class="text-red-600 text-xs mt-1">{{ form.errors.car_model }}</p>
              </div>
            </div>
          </div>

          <!-- Section: Pricing & VAT -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('price') }}</h3>
            <div class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('currency') }}</label>
                  <select v-model="form.currency"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="USD">USD</option>
                    <option value="KHR">KHR</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('unit_price') }}</label>
                  <input v-model="form.unit_price" type="number" min="0" step="0.01" placeholder="0.00"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('quantity') }}</label>
                  <input v-model="form.quantity" type="number" min="1" placeholder="1"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                </div>
              </div>

              <!-- VAT + Totals -->
              <div class="bg-slate-50 rounded-lg p-4 space-y-3">
                <div class="flex items-center gap-3">
                  <ToggleButton v-model="form.with_vat" :onLabel="t('with_vat')" :offLabel="t('without_vat')"
                    onIcon="pi pi-check" offIcon="pi pi-times" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                  <div>
                    <span class="text-slate-500">{{ t('sub_total') }}</span>
                    <p class="font-semibold text-slate-800">{{ subTotal.toFixed(2) }}</p>
                  </div>
                  <div>
                    <span class="text-slate-500">{{ t('vat_amount') }} ({{ form.with_vat ? form.vat_rate + '%' : '0%'
                    }})</span>
                    <p class="font-semibold text-slate-800">{{ vatAmount.toFixed(2) }}</p>
                  </div>
                  <div>
                    <span class="text-slate-500">{{ t('grand_total') }}</span>
                    <p class="font-bold text-blue-700 text-base">{{ form.currency }} {{ grandTotal }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: Payment Details -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('payment_category') }}</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('payment_category') }}</label>
                <select v-model="form.payment_category"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option v-for="c in paymentCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bank_reference') }} *</label>
                <textarea v-model="form.bank_reference" rows="2" :placeholder="t('bank_reference')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                <p v-if="form.errors.bank_reference" class="text-red-600 text-xs mt-1">{{ form.errors.bank_reference }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('notes') }}</label>
                <textarea v-model="form.notes" rows="2" :placeholder="t('notes')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-2 border-t border-slate-200">
            <button type="submit" :disabled="form.processing"
              class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors flex items-center gap-2">
              <ProgressSpinner v-if="form.processing" style="width:16px;height:16px" strokeWidth="8" />
              <span v-else>{{ t('save') }}</span>
            </button>
            <Link :href="route('invoices.index')"
              class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
