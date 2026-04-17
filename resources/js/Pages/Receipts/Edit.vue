<script setup>
import { computed, ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({
  receipt: Object,
  customerNames: { type: Array, default: () => [] },
  carModels: { type: Array, default: () => [] },
})

const form = useForm({
  date: props.receipt.date,
  customer_name: props.receipt.customer_name,
  customer_phone: props.receipt.customer_phone ?? '',
  car_model: props.receipt.car_model,
  unit_price: props.receipt.unit_price,
  currency: props.receipt.currency,
  quantity: props.receipt.quantity,
  total_amount: props.receipt.total_amount,
  payment_category: props.receipt.payment_category,
  bank_reference: props.receipt.bank_reference,
  notes: props.receipt.notes ?? '',
})

const totalAmount = computed(() => {
  const price = parseFloat(form.unit_price) || 0
  const qty = parseInt(form.quantity) || 0
  return (price * qty).toFixed(2)
})

function updateTotal() {
  form.total_amount = totalAmount.value
}

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

const paymentCategories = computed(() => [
  { value: 'booking', label: t('booking') },
  { value: 'full_payment', label: t('full_payment') },
  { value: 'down_payment', label: t('down_payment') },
  { value: 'installment', label: t('installment') },
  { value: 'service_payment', label: t('service_payment') },
  { value: 'other', label: t('other') },
])

function submit() {
  form.total_amount = totalAmount.value
  form.put(route('receipts.update', props.receipt.id))
}
</script>

<template>

  <Head :title="t('edit') + ' ' + t('receipts')" />
  <AppLayout>
    <template #title>{{ t('receipts') }} — {{ t('edit') }}</template>

    <div class="max-w-3xl">
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <!-- Receipt number (read-only) -->
        <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
          <span class="text-xs text-blue-600 font-medium">{{ t('receipt_number') }}: </span>
          <span class="font-mono text-blue-800 font-semibold">{{ receipt.receipt_number }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Section: Customer Information -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('customer_name') }}</h3>
            <!-- Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('date') }}</label>
                <DatePicker v-model="formDateObj" dateFormat="yy-mm-dd" showButtonBar class="w-full"
                  inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
                <p v-if="form.errors.date" class="text-red-600 text-xs mt-1">{{ form.errors.date }}</p>
              </div>
            </div>

            <!-- Customer Name + Phone -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_name') }} *</label>
                <AutoComplete v-model="form.customer_name" :suggestions="filteredCustomers" @complete="searchCustomers"
                  class="w-full" inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
                <p v-if="form.errors.customer_name" class="text-red-600 text-xs mt-1">{{ form.errors.customer_name }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_phone') }}</label>
                <input v-model="form.customer_phone" type="text" :placeholder="t('customer_phone')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
              </div>
            </div>

            <!-- Car Model -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('car_model') }} *</label>
              <AutoComplete v-model="form.car_model" :suggestions="filteredCars" @complete="searchCars" class="w-full"
                inputClass="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900" />
              <p v-if="form.errors.car_model" class="text-red-600 text-xs mt-1">{{ form.errors.car_model }}</p>
            </div>
          </div>

          <!-- Section: Pricing -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('price') }}</h3>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400"
                  @input="updateTotal" />
                <p v-if="form.errors.unit_price" class="text-red-600 text-xs mt-1">{{ form.errors.unit_price }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('quantity') }}</label>
                <input v-model="form.quantity" type="number" min="1" placeholder="1"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400"
                  @input="updateTotal" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('total_amount') }}</label>
                <input :value="totalAmount" type="text" readonly
                  class="w-full border border-slate-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-semibold text-blue-700" />
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

              <!-- Bank Reference -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bank_reference') }} *</label>
                <textarea v-model="form.bank_reference" rows="2" :placeholder="t('bank_reference')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                <p v-if="form.errors.bank_reference" class="text-red-600 text-xs mt-1">{{ form.errors.bank_reference }}
                </p>
              </div>

              <!-- Notes -->
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
            <Link :href="route('receipts.index')"
              class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
