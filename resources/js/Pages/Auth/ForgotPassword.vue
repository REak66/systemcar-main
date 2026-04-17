<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const { t } = useI18n()
const page = usePage()
const status = computed(() => page.props.flash?.status)

const form = useForm({ email: '' })

function submit() {
  form.post(route('password.email'))
}
</script>

<template>
  <Head :title="t('forgot_password')" />

  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 to-blue-700 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-8">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 mb-4">
          <span class="text-3xl">🚗</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">{{ t('reset_password') }}</h1>
        <p class="text-gray-500 text-sm mt-1">{{ t('forgot_password') }}</p>
      </div>

      <div v-if="status" class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
        {{ status }}
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('email') }}</label>
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            :placeholder="t('email')"
          />
          <p v-if="form.errors.email" class="text-red-600 text-xs mt-1">{{ form.errors.email }}</p>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? '...' : t('send_reset_link') }}
        </button>

        <div class="text-center">
          <a :href="route('login')" class="text-sm text-blue-600 hover:underline">
            {{ t('back_to_login') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</template>
