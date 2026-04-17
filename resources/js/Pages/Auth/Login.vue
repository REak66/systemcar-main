<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const form = useForm({
  username: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>

  <Head :title="t('login')" />

  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-blue-950 to-blue-800 p-4">
    <div class="bg-white rounded-2xl ring-1 ring-white/10 w-full max-w-md p-8 animate-scale-in">
      <!-- Logo -->
      <div class="text-center mb-8 animate-fade-up" style="animation-delay: 0.1s">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
          <span class="text-3xl">🚗</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">{{ t('app_name') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ t('sign_in') }}</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- Username -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('username') }}</label>
          <input v-model="form.username" type="text" autocomplete="username"
            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="t('username')" />
          <p v-if="form.errors.username" class="text-red-600 text-xs mt-1">{{ form.errors.username }}</p>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('password') }}</label>
          <input v-model="form.password" type="password" autocomplete="current-password"
            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="t('password')" />
          <p v-if="form.errors.password" class="text-red-600 text-xs mt-1">{{ form.errors.password }}</p>
        </div>

        <!-- Remember me + Forgot -->
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-blue-600" />
            {{ t('remember_me') }}
          </label>
          <a :href="route('password.request')" class="text-sm text-blue-600 hover:underline">
            {{ t('forgot_password') }}
          </a>
        </div>

        <!-- Submit -->
        <button type="submit" :disabled="form.processing"
          class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ form.processing ? '...' : t('sign_in') }}
        </button>
      </form>
    </div>
  </div>
</template>
