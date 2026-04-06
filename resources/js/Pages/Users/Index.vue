<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({ users: Object })

const deleteTarget = ref(null)

function confirmDelete(user) {
  deleteTarget.value = user
}

function doDelete() {
  router.delete(route('users.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
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

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-700">{{ t('users') }}</h2>
        <Link
          :href="route('users.create')"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors"
        >
          + {{ t('add') }}
        </Link>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('full_name') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('username') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('email') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('role') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('status') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('created_date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ t('actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="(user, i) in users.data" :key="user.id" :class="user.deleted_at ? 'opacity-50 bg-gray-50' : 'hover:bg-gray-50'">
              <td class="px-4 py-3 text-gray-500">{{ users.from + i }}</td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ user.name }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.username }}</td>
              <td class="px-4 py-3 text-gray-600">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    user.role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700',
                  ]"
                >
                  {{ t(user.role) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <button
                  v-if="!user.deleted_at"
                  :class="[
                    'px-2 py-0.5 rounded-full text-xs font-medium cursor-pointer',
                    user.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500',
                  ]"
                  @click="toggleStatus(user)"
                >
                  {{ user.is_active ? t('active') : t('inactive') }}
                </button>
                <span v-else class="text-xs text-red-500">Deleted</span>
              </td>
              <td class="px-4 py-3 text-gray-500">{{ formatDate(user.created_at) }}</td>
              <td class="px-4 py-3">
                <div v-if="!user.deleted_at" class="flex gap-2">
                  <Link
                    :href="route('users.edit', user.id)"
                    class="text-xs text-blue-600 hover:underline"
                  >
                    {{ t('edit') }}
                  </Link>
                  <button
                    class="text-xs text-red-600 hover:underline"
                    @click="confirmDelete(user)"
                  >
                    {{ t('delete') }}
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!users.data || users.data.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ t('no_data') }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-4 pb-4">
        <Pagination :links="users.links" />
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
