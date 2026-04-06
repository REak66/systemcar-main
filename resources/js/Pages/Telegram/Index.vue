<script setup>
import { ref, reactive } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
  configs: Array,
  schedules: Array,
})

const showAddForm = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)

const addForm = useForm({
  name: '',
  bot_token: '',
  chat_id: '',
  is_active: true,
})

const editForm = useForm({
  name: '',
  bot_token: '',
  chat_id: '',
  is_active: true,
})

function openEdit(config) {
  editTarget.value = config
  editForm.name = config.name
  editForm.bot_token = config.bot_token
  editForm.chat_id = config.chat_id
  editForm.is_active = config.is_active
}

function submitAdd() {
  addForm.post(route('telegram.store'), {
    onSuccess: () => {
      addForm.reset()
      showAddForm.value = false
    },
  })
}

function submitEdit() {
  editForm.put(route('telegram.update', editTarget.value.id), {
    onSuccess: () => { editTarget.value = null },
  })
}

function doDelete() {
  router.delete(route('telegram.destroy', deleteTarget.value.id), {
    onFinish: () => { deleteTarget.value = null },
  })
}

// ── Schedule ─────────────────────────────────────────────────────────────────
const DAY_OPTIONS = [
  { value: 0, label: t('sunday') },
  { value: 1, label: t('monday') },
  { value: 2, label: t('tuesday') },
  { value: 3, label: t('wednesday') },
  { value: 4, label: t('thursday') },
  { value: 5, label: t('friday') },
  { value: 6, label: t('saturday') },
]

const SCHEDULE_LABELS = {
  daily:   'daily_report',
  weekly:  'weekly_report',
  monthly: 'monthly_report',
}

const scheduleForms = reactive({})
if (props.schedules) {
  props.schedules.forEach((s) => {
    scheduleForms[s.id] = useForm({
      is_enabled:   s.is_enabled,
      time:         s.time,
      day_of_week:  s.day_of_week,
      day_of_month: s.day_of_month,
    })
  })
}

function saveSchedule(schedule) {
  scheduleForms[schedule.id].put(route('telegram.schedules.update', schedule.id))
}
</script>

<template>
  <Head :title="t('telegram')" />
  <AppLayout>
    <template #title>{{ t('telegram') }}</template>

    <div class="max-w-3xl space-y-6">
      <!-- Add button -->
      <div class="flex justify-end">
        <button
          class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors"
          @click="showAddForm = !showAddForm"
        >
          {{ showAddForm ? t('cancel') : '+ ' + t('add_config') }}
        </button>
      </div>

      <!-- Add form -->
      <div v-if="showAddForm" class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">{{ t('add_config') }}</h3>
        <form @submit.prevent="submitAdd" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bot_name') }} *</label>
              <input v-model="addForm.name" type="text" :placeholder="'e.g. Branch 1'" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="addForm.errors.name" class="text-red-600 text-xs mt-1">{{ addForm.errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('chat_id') }} *</label>
              <input v-model="addForm.chat_id" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <p v-if="addForm.errors.chat_id" class="text-red-600 text-xs mt-1">{{ addForm.errors.chat_id }}</p>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bot_token') }} *</label>
            <input v-model="addForm.bot_token" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
            <p v-if="addForm.errors.bot_token" class="text-red-600 text-xs mt-1">{{ addForm.errors.bot_token }}</p>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="addForm.is_active" type="checkbox" id="add-active" class="rounded border-gray-300 text-blue-600" />
            <label for="add-active" class="text-sm text-gray-700">{{ t('active') }}</label>
          </div>
          <div class="flex gap-2">
            <button type="submit" :disabled="addForm.processing" class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
              {{ addForm.processing ? '...' : t('save') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Config list -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700">{{ t('telegram') }} {{ t('name') }}</h2>
        </div>

        <div v-if="!configs || configs.length === 0" class="px-5 py-8 text-center text-gray-400">{{ t('no_data') }}</div>

        <div v-for="config in configs" :key="config.id" class="border-b border-gray-50 last:border-0">
          <!-- View mode -->
          <div v-if="editTarget?.id !== config.id" class="px-5 py-4 flex items-center gap-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-medium text-gray-800">{{ config.name }}</span>
                <span :class="['px-2 py-0.5 rounded-full text-xs', config.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                  {{ config.is_active ? t('active') : t('inactive') }}
                </span>
              </div>
              <p class="text-xs text-gray-500">Chat ID: {{ config.chat_id }}</p>
              <p class="text-xs text-gray-400 font-mono truncate">Token: {{ config.bot_token?.slice(0, 20) }}...</p>
            </div>
            <div class="flex gap-2 flex-shrink-0">
              <button class="text-sm text-blue-600 hover:underline" @click="openEdit(config)">{{ t('edit') }}</button>
              <button class="text-sm text-red-600 hover:underline" @click="deleteTarget = config">{{ t('delete') }}</button>
            </div>
          </div>

          <!-- Edit mode -->
          <div v-else class="px-5 py-4 bg-blue-50">
            <form @submit.prevent="submitEdit" class="space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ t('bot_name') }}</label>
                  <input v-model="editForm.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">{{ t('chat_id') }}</label>
                  <input v-model="editForm.chat_id" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ t('bot_token') }}</label>
                <input v-model="editForm.bot_token" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
              <div class="flex items-center gap-2">
                <input v-model="editForm.is_active" type="checkbox" :id="'edit-active-' + config.id" class="rounded border-gray-300 text-blue-600" />
                <label :for="'edit-active-' + config.id" class="text-sm text-gray-700">{{ t('active') }}</label>
              </div>
              <div class="flex gap-2">
                <button type="submit" :disabled="editForm.processing" class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                  {{ editForm.processing ? '...' : t('save') }}
                </button>
                <button type="button" class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg text-sm hover:bg-gray-300" @click="editTarget = null">
                  {{ t('cancel') }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- ── Report Schedule ────────────────────────────────────────────── -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700">{{ t('report_schedule') }}</h2>
          <p class="text-xs text-gray-400 mt-0.5">{{ t('report_schedule_hint') }}</p>
        </div>

        <div v-if="!schedules || schedules.length === 0" class="px-5 py-8 text-center text-gray-400">{{ t('no_data') }}</div>

        <div v-for="schedule in schedules" :key="schedule.id" class="border-b border-gray-50 last:border-0 px-5 py-4">
          <form @submit.prevent="saveSchedule(schedule)" class="space-y-3">
            <!-- Header row: label + toggle switch -->
            <div class="flex items-center justify-between">
              <span class="font-medium text-gray-800">{{ t(SCHEDULE_LABELS[schedule.report_type]) }}</span>
              <label class="relative inline-flex items-center cursor-pointer select-none">
                <input
                  v-model="scheduleForms[schedule.id].is_enabled"
                  type="checkbox"
                  class="sr-only peer"
                />
                <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
                <span class="ml-2 text-sm" :class="scheduleForms[schedule.id].is_enabled ? 'text-blue-600 font-medium' : 'text-gray-400'">
                  {{ scheduleForms[schedule.id].is_enabled ? t('enabled') : t('disabled') }}
                </span>
              </label>
            </div>

            <!-- Time + day selectors -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('send_time') }}</label>
                <input
                  v-model="scheduleForms[schedule.id].time"
                  type="time"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="scheduleForms[schedule.id].errors.time" class="text-red-600 text-xs mt-1">{{ scheduleForms[schedule.id].errors.time }}</p>
              </div>

              <!-- Day of week (weekly only) -->
              <div v-if="schedule.report_type === 'weekly'">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('day_of_week') }}</label>
                <select
                  v-model="scheduleForms[schedule.id].day_of_week"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option v-for="day in DAY_OPTIONS" :key="day.value" :value="day.value">{{ day.label }}</option>
                </select>
              </div>

              <!-- Day of month (monthly only) -->
              <div v-if="schedule.report_type === 'monthly'">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('day_of_month') }}</label>
                <input
                  v-model.number="scheduleForms[schedule.id].day_of_month"
                  type="number"
                  min="1"
                  max="31"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="scheduleForms[schedule.id].errors.day_of_month" class="text-red-600 text-xs mt-1">{{ scheduleForms[schedule.id].errors.day_of_month }}</p>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="scheduleForms[schedule.id].processing"
                class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                {{ scheduleForms[schedule.id].processing ? '...' : t('save') }}
              </button>
            </div>
          </form>
        </div>
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
