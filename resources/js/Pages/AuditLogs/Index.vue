<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const { t } = useI18n()

const props = defineProps({
  logs: Object,
  filters: Object,
})

const actionFilter = ref(props.filters?.action ?? '')
const fromDate = ref(props.filters?.from_date ?? '')
const toDate = ref(props.filters?.to_date ?? '')
const loading = ref(false)

let stopStart, stopFinish
onMounted(() => {
  stopStart = router.on('start', () => { loading.value = true })
  stopFinish = router.on('finish', () => { loading.value = false })
})
onUnmounted(() => { stopStart?.(); stopFinish?.() })

function applyFilter() {
  router.get(route('audit-logs.index'), {
    action: actionFilter.value || undefined,
    from_date: fromDate.value || undefined,
    to_date: toDate.value || undefined,
  }, { preserveState: true, replace: true })
}

function resetFilter() {
  actionFilter.value = ''
  fromDate.value = ''
  toDate.value = ''
  router.get(route('audit-logs.index'))
}

function formatDate(d) {
  if (!d) return '—'
  const parts = String(d).split('T')
  const dp = parts[0].split('-')
  const time = parts[1] ? parts[1].substring(0, 5) : ''
  return `${dp[2]}-${dp[1]}-${dp[0].slice(-2)}${time ? ' ' + time : ''}`
}

function formatJson(obj) {
  if (!obj) return '—'
  const keys = Object.keys(obj).filter(k => !['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'].includes(k))
  return keys.map(k => `${k}: ${obj[k]}`).join(', ')
}
</script>

<template>
  <Head :title="t('audit_logs')" />
  <AppLayout>
    <template #title>{{ t('audit_logs') }}</template>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Filters -->
      <div class="p-4 border-b border-gray-100">
        <div class="flex flex-wrap gap-3 items-end">
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('action') }}</label>
            <select v-model="actionFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option value="">{{ t('all') }}</option>
              <option value="create">create</option>
              <option value="edit">edit</option>
              <option value="delete">delete</option>
            </select>
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('from_date') }}</label>
            <input v-model="fromDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ t('to_date') }}</label>
            <input v-model="toDate" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <button @click="applyFilter" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">{{ t('filter') }}</button>
          <button @click="resetFilter" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">{{ t('all') }}</button>
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('timestamp') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('user') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('action') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('model') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('ip_address') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('old_values') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('new_values') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <!-- Skeleton rows -->
            <template v-if="loading">
              <tr v-for="n in 8" :key="'sk-' + n" class="animate-pulse [animation-duration:5s]">
                <td v-for="c in 8" :key="c" class="px-4 py-3"><div class="h-4 bg-gray-200 rounded"></div></td>
              </tr>
            </template>
            <!-- Data rows -->
            <template v-else>
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
              <td class="px-4 py-3 text-gray-700">{{ log.user?.name ?? '—' }}</td>
              <td class="px-4 py-3">
                <span :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium',
                  log.action === 'create' ? 'bg-green-100 text-green-700' :
                  log.action === 'edit' ? 'bg-blue-100 text-blue-700' :
                  'bg-red-100 text-red-700'
                ]">{{ log.action }}</span>
              </td>
              <td class="px-4 py-3 text-gray-600">{{ log.model_type }}</td>
              <td class="px-4 py-3 text-gray-500">{{ log.model_id }}</td>
              <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ log.ip_address }}</td>
              <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate" :title="formatJson(log.old_values)">{{ formatJson(log.old_values) }}</td>
              <td class="px-4 py-3 text-xs text-gray-500 max-w-xs truncate" :title="formatJson(log.new_values)">{{ formatJson(log.new_values) }}</td>
            </tr>
            <tr v-if="!logs.data || logs.data.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="px-4 pb-4">
        <Pagination :links="logs.links" />
      </div>
    </div>
  </AppLayout>
</template>
