<script setup>
import { Head } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'

const { t } = useI18n()

const props = defineProps({
  stats: Object,
})

function fmt(n) {
  return Number(n ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>

<template>
  <Head :title="t('dashboard')" />
  <AppLayout>
    <template #title>{{ t('dashboard') }}</template>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
      <StatCard :value="stats.total_receipts" :label="t('total_receipts')" />
      <StatCard :value="stats.total_invoices" :label="t('total_invoices')" />
      <StatCard :value="stats.today_receipts" :label="t('today_receipts')" />
      <StatCard :value="stats.today_invoices" :label="t('today_invoices')" />
      <StatCard :value="fmt(stats.total_amount_today) + ' USD'" :label="t('total_today')" />
      <StatCard :value="stats.monthly_receipts" :label="t('monthly_receipts')" />
      <StatCard :value="stats.monthly_invoices" :label="t('monthly_invoices')" />
    </div>
  </AppLayout>
</template>
