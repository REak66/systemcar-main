<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useConfirm } from 'primevue/useconfirm'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const { t } = useI18n()
const confirm = useConfirm()

const props = defineProps({ users: Object })

const loading = ref(false)

let stopStart, stopFinish
onMounted(() => {
  stopStart = router.on('start', () => { loading.value = true })
  stopFinish = router.on('finish', () => { loading.value = false })
})
onUnmounted(() => { stopStart?.(); stopFinish?.() })

function confirmDelete(event, user) {
  confirm.require({
    target: event.currentTarget,
    message: t('confirm_delete'),
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(route('users.destroy', user.id))
    },
  })
}

function toggleStatus(user) {
  router.post(route('users.toggle-status', user.id))
}

function formatDate(d) {
  if (!d) return '—'
  const parts = String(d).split('T')[0].split('-')
  return `${parts[2]}-${parts[1]}-${parts[0].slice(-2)}`
}
</script>

<template>

  <Head :title="t('users')" />
  <AppLayout>
    <template #title>{{ t('user_management') }}</template>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
        <h2 class="font-semibold text-slate-700">{{ t('users') }}</h2>
        <Link :href="route('users.create')"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
          + {{ t('add') }}
        </Link>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">No.</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('full_name') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('username')
              }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('email') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ t('role')
              }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('status')
              }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('created_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{
                t('actions')
              }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <!-- Skeleton rows -->
            <template v-if="loading">
              <tr v-for="n in 8" :key="'sk-' + n">
                <td v-for="c in 8" :key="c" class="px-4 py-3">
                  <Skeleton height="1rem" />
                </td>
              </tr>
            </template>
            <!-- Data rows -->
            <template v-else>
              <tr v-for="(user, i) in users.data" :key="user.id"
                :class="user.deleted_at ? 'opacity-50 bg-slate-50' : 'table-row-animate hover:bg-slate-50 transition-colors'"
                :style="{ '--row-index': i }">
                <td class="px-4 py-3 text-gray-500">{{ users.from + i }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ user.name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ user.username }}</td>
                <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
                <td class="px-4 py-3">
                  <span :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700',
                  ]">
                    {{ t(user.role) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <ToggleSwitch v-if="!user.deleted_at" :modelValue="!!user.is_active"
                    @update:modelValue="toggleStatus(user)" />
                  <span v-else class="text-xs text-red-500">Deleted</span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ formatDate(user.created_at) }}</td>
                <td class="px-4 py-3">
                  <div v-if="!user.deleted_at" class="flex items-center gap-1.5">
                    <Link :href="route('users.edit', user.id)"
                      class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                      {{ t('edit') }}</Link>
                    <button
                      class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 transition-colors"
                      @click="confirmDelete($event, user)">{{ t('delete') }}</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!users.data || users.data.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="px-4 pb-4">
        <Pagination :paginator="users" />
      </div>
    </div>

    <ConfirmPopup />
  </AppLayout>
</template>
