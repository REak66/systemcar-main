<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'

const { t } = useI18n()

const form = useForm({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'cashier',
  is_active: true,
})

function submit() {
  form.post(route('users.store'))
}
</script>

<template>

  <Head :title="t('create') + ' ' + t('user')" />
  <AppLayout>
    <template #title>{{ t('user_management') }} — {{ t('create') }}</template>

    <div class="max-w-2xl">
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form @submit.prevent="submit" class="space-y-6">

          <!-- Section: Account Info -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('full_name') }}</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('full_name') }} *</label>
                <input v-model="form.name" type="text" :placeholder="t('full_name')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                <p v-if="form.errors.name" class="text-red-600 text-xs mt-1">{{ form.errors.name }}</p>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('username') }} *</label>
                  <input v-model="form.username" type="text" autocomplete="off" :placeholder="t('username')"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                  <p v-if="form.errors.username" class="text-red-600 text-xs mt-1">{{ form.errors.username }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('email') }} *</label>
                  <input v-model="form.email" type="email" autocomplete="off" placeholder="user@example.com"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                  <p v-if="form.errors.email" class="text-red-600 text-xs mt-1">{{ form.errors.email }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: Security -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('password') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('password') }} *</label>
                <input v-model="form.password" type="password" autocomplete="new-password" :placeholder="t('password')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                <p v-if="form.errors.password" class="text-red-600 text-xs mt-1">{{ form.errors.password }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('confirm_password') }} *</label>
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                  :placeholder="t('confirm_password')"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
              </div>
            </div>
          </div>

          <!-- Section: Role & Status -->
          <div>
            <h3
              class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
              {{
                t('role') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('role') }} *</label>
                <select v-model="form.role"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="admin">{{ t('admin') }}</option>
                  <option value="cashier">{{ t('cashier') }}</option>
                </select>
                <p v-if="form.errors.role" class="text-red-600 text-xs mt-1">{{ form.errors.role }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('status') }}</label>
                <select v-model="form.is_active"
                  class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option :value="true">{{ t('active') }}</option>
                  <option :value="false">{{ t('inactive') }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-2 border-t border-slate-200">
            <button type="submit" :disabled="form.processing"
              class="bg-blue-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
              {{ form.processing ? '...' : t('save') }}
            </button>
            <Link :href="route('users.index')"
              class="bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
              {{ t('cancel') }}
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
