<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()

const props = defineProps({ user: Object })

const form = useForm({
  name: props.user.name,
  username: props.user.username,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  role: props.user.role,
  is_active: props.user.is_active,
})

function submit() {
  form.put(route('users.update', props.user.id))
}
</script>

<template>
  <Head :title="t('edit') + ' ' + t('user')" />
  <AppLayout>
    <template #title>{{ t('user_management') }} — {{ t('edit') }}</template>

    <div class="max-w-2xl">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('full_name') }} *</label>
            <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.name" class="text-red-600 text-xs mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Username -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('username') }} *</label>
            <input v-model="form.username" type="text" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.username" class="text-red-600 text-xs mt-1">{{ form.errors.username }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('email') }} *</label>
            <input v-model="form.email" type="email" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="form.errors.email" class="text-red-600 text-xs mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password (optional on edit) -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('new_password') }}</label>
              <input v-model="form.password" type="password" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" :placeholder="t('password') + ' (optional)'" />
              <p v-if="form.errors.password" class="text-red-600 text-xs mt-1">{{ form.errors.password }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('confirm_password') }}</label>
              <input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <!-- Role + Status -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('role') }} *</label>
              <select v-model="form.role" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="admin">{{ t('admin') }}</option>
                <option value="cashier">{{ t('cashier') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('status') }}</label>
              <select v-model="form.is_active" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option :value="true">{{ t('active') }}</option>
                <option :value="false">{{ t('inactive') }}</option>
              </select>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ form.processing ? '...' : t('save') }}
            </button>
            <Link :href="route('users.index')" class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
