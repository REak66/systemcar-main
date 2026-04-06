<script setup>
import { computed } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({
  receiptNumber: String,
})

const today = new Date().toISOString().split('T')[0]

const form = useForm({
  receipt_number: props.receiptNumber,
  date: today,
  customer_name: '',
  customer_phone: '',
  car_model: '',
  unit_price: '',
  currency: 'USD',
  quantity: 1,
  total_amount: '',
  payment_category: 'full_payment',
  bank_reference: '',
  notes: '',
})

const totalAmount = computed(() => {
  const price = parseFloat(form.unit_price) || 0
  const qty = parseInt(form.quantity) || 0
  return (price * qty).toFixed(2)
})

function updateTotal() {
  form.total_amount = totalAmount.value
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
  form.total_amount = totalAmount.value
  form.post(route('receipts.store'))
}
</script>

<template>
  <Head :title="t('create') + ' ' + t('receipts')" />
  <AppLayout>
    <template #title>{{ t('receipts') }} — {{ t('create') }}</template>

    <div class="max-w-3xl">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form @submit.prevent="submit" class="space-y-4">

          <!-- Receipt Number + Date -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('receipt_number') }}</label>
              <input :value="form.receipt_number" type="text" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-mono text-blue-700" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('date') }}</label>
              <input v-model="form.date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="form.errors.date" class="text-red-600 text-xs mt-1">{{ form.errors.date }}</p>
            </div>
          </div>

          <!-- Customer Name + Phone -->
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

          <!-- Car Model -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('car_model') }} *</label>
            <input v-model="form.car_model" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.car_model" class="text-red-600 text-xs mt-1">{{ form.errors.car_model }}</p>
          </div>

          <!-- Unit Price + Currency + Qty + Total -->
          <div class="grid grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('currency') }} *</label>
              <select v-model="form.currency" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="USD">USD</option>
                <option value="KHR">KHR</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('unit_price') }} *</label>
              <input v-model="form.unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @input="updateTotal" />
              <p v-if="form.errors.unit_price" class="text-red-600 text-xs mt-1">{{ form.errors.unit_price }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('quantity') }} *</label>
              <input v-model="form.quantity" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" @input="updateTotal" />
              <p v-if="form.errors.quantity" class="text-red-600 text-xs mt-1">{{ form.errors.quantity }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('total_amount') }}</label>
              <input :value="totalAmount" type="text" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-sm font-medium text-blue-700" />
            </div>
          </div>

          <!-- Payment Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('payment_category') }} *</label>
            <select v-model="form.payment_category" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="c in paymentCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <p v-if="form.errors.payment_category" class="text-red-600 text-xs mt-1">{{ form.errors.payment_category }}</p>
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

          <!-- Actions -->
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ form.processing ? '...' : t('save') }}
            </button>
            <Link :href="route('receipts.index')" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
