<script setup>
import { computed, watch } from 'vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({
  invoiceNumber: String,
  type: { type: String, default: 'car_sale' },
})

const today = new Date().toISOString().split('T')[0]

const form = useForm({
  invoice_number: props.invoiceNumber,
  invoice_type: props.type,
  date: today,
  customer_name: '',
  customer_address: '',
  customer_phone: '',
  car_model: '',
  unit_price: '',
  currency: 'USD',
  quantity: 1,
  sub_total: '',
  with_vat: false,
  vat_rate: 10,
  vat_amount: '0.00',
  grand_total: '',
  payment_category: 'full_payment',
  bank_reference: '',
  notes: '',
})

const subTotal = computed(() => {
  const price = parseFloat(form.unit_price) || 0
  const qty = parseInt(form.quantity) || 0
  return price * qty
})

const vatAmount = computed(() => {
  return form.with_vat ? subTotal.value * (form.vat_rate / 100) : 0
})

const grandTotal = computed(() => (subTotal.value + vatAmount.value).toFixed(2))

function recalc() {
  form.sub_total = subTotal.value.toFixed(2)
  form.vat_amount = vatAmount.value.toFixed(2)
  form.grand_total = grandTotal.value
}

watch([() => form.unit_price, () => form.quantity, () => form.with_vat, () => form.vat_rate], recalc)

function onTypeChange() {
  router.get(route('invoices.create'), { type: form.invoice_type }, { preserveState: false })
}

const paymentCategories = computed(() => [
  { value: 'booking', label: t('booking') },
  { value: 'full_payment', label: t('full_payment') },
  { value: 'down_payment', label: t('down_payment') },
  { value: 'installment', label: t('installment') },
  { value: 'service_payment', label: t('service_payment') },
  { value: 'other', label: t('other') },
])

function submit() {
  form.sub_total = subTotal.value.toFixed(2)
  form.vat_amount = vatAmount.value.toFixed(2)
  form.grand_total = grandTotal.value
  form.post(route('invoices.store'))
}
</script>

<template>
  <Head :title="t('create') + ' ' + t('invoices')" />
  <AppLayout>
    <template #title>{{ t('invoices') }} — {{ t('create') }}</template>

    <div class="max-w-3xl">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form @submit.prevent="submit" class="space-y-4">

          <!-- Invoice Number + Type + Date -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('invoice_number') }}</label>
              <input :value="form.invoice_number" type="text" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-mono text-blue-700" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('invoice_type') }} *</label>
              <select v-model="form.invoice_type" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @change="onTypeChange">
                <option value="car_sale">{{ t('car_sale') }}</option>
                <option value="service">{{ t('service') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('date') }}</label>
              <input v-model="form.date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="form.errors.date" class="text-red-600 text-xs mt-1">{{ form.errors.date }}</p>
            </div>
          </div>

          <!-- Customer fields -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_name') }} *</label>
              <input v-model="form.customer_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="form.errors.customer_name" class="text-red-600 text-xs mt-1">{{ form.errors.customer_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_phone') }}</label>
              <input v-model="form.customer_phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('customer_address') }}</label>
            <input v-model="form.customer_address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <!-- Car Model -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('car_model') }} *</label>
            <input v-model="form.car_model" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.car_model" class="text-red-600 text-xs mt-1">{{ form.errors.car_model }}</p>
          </div>

          <!-- Pricing grid -->
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('currency') }}</label>
              <select v-model="form.currency" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="USD">USD</option>
                <option value="KHR">KHR</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('unit_price') }} *</label>
              <input v-model="form.unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="form.errors.unit_price" class="text-red-600 text-xs mt-1">{{ form.errors.unit_price }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('quantity') }} *</label>
              <input v-model="form.quantity" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <!-- VAT toggle + totals -->
          <div class="bg-gray-50 rounded-lg p-4 space-y-3">
            <div class="flex items-center gap-3">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.with_vat" type="checkbox" class="rounded border-gray-300 text-blue-600 w-4 h-4" />
                <span class="text-sm font-medium text-gray-700">{{ t('with_vat') }}</span>
              </label>
            </div>

            <div class="grid grid-cols-3 gap-3 text-sm">
              <div>
                <span class="text-gray-500">{{ t('sub_total') }}</span>
                <p class="font-semibold text-gray-800">{{ subTotal.toFixed(2) }}</p>
              </div>
              <div>
                <span class="text-gray-500">{{ t('vat_amount') }} ({{ form.with_vat ? form.vat_rate + '%' : '0%' }})</span>
                <p class="font-semibold text-gray-800">{{ vatAmount.toFixed(2) }}</p>
              </div>
              <div>
                <span class="text-gray-500">{{ t('grand_total') }}</span>
                <p class="font-bold text-blue-700 text-base">{{ form.currency }} {{ grandTotal }}</p>
              </div>
            </div>
          </div>

          <!-- Payment Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('payment_category') }} *</label>
            <select v-model="form.payment_category" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="c in paymentCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
          </div>

          <!-- Bank Reference -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bank_reference') }} *</label>
            <textarea v-model="form.bank_reference" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.bank_reference" class="text-red-600 text-xs mt-1">{{ form.errors.bank_reference }}</p>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('notes') }}</label>
            <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ form.processing ? '...' : t('save') }}
            </button>
            <Link :href="route('invoices.index')" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
