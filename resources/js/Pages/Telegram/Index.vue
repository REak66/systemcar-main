<script setup>
import { ref, reactive } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Components/AppLayout.vue'
import Modal from '@/Components/Modal.vue'

const { t } = useI18n()

const props = defineProps({
    configs: Array,
})

const showAddForm = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)

const addForm = useForm({
    name: '',
    daily_chat_id: '',
    weekly_chat_id: '',
    monthly_chat_id: '',
    document_chat_id: '',
    is_active: true,
})

const editForm = useForm({
    name: '',
    daily_chat_id: '',
    weekly_chat_id: '',
    monthly_chat_id: '',
    document_chat_id: '',
    is_active: true,
})

function openEdit(config) {
    editTarget.value = config
    editForm.name = config.name
    editForm.daily_chat_id = config.daily_chat_id ?? ''
    editForm.weekly_chat_id = config.weekly_chat_id ?? ''
    editForm.monthly_chat_id = config.monthly_chat_id ?? ''
    editForm.document_chat_id = config.document_chat_id ?? ''
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

// ── Schedule (per branch) ─────────────────────────────────────────────────────
const DAY_OPTIONS = [
    { value: 0, label: t('sunday') },
    { value: 1, label: t('monday') },
    { value: 2, label: t('tuesday') },
    { value: 3, label: t('wednesday') },
    { value: 4, label: t('thursday') },
    { value: 5, label: t('friday') },
    { value: 6, label: t('saturday') },
]

const SCHEDULE_META = {
    daily: { labelKey: 'daily_report', icon: '📅' },
    weekly: { labelKey: 'weekly_report', icon: '📆' },
    monthly: { labelKey: 'monthly_report', icon: '🗓️' },
}

// Track which branch schedule sections are open
const openSchedules = reactive({})
function toggleSchedule(configId) {
    openSchedules[configId] = !openSchedules[configId]
}

// Schedule forms keyed by schedule ID
const scheduleForms = reactive({})
const sendingNow = reactive({})

if (props.configs) {
    props.configs.forEach((config) => {
        (config.schedules ?? []).forEach((s) => {
            scheduleForms[s.id] = useForm({
                is_enabled: s.is_enabled,
                time: s.time,
                day_of_week: s.day_of_week,
                day_of_month: s.day_of_month,
            })
            sendingNow[s.id] = false
        })
    })
}

function saveSchedule(schedule) {
    scheduleForms[schedule.id].put(route('telegram.schedules.update', schedule.id))
}

function sendNow(schedule) {
    sendingNow[schedule.id] = true
    router.post(route('telegram.schedules.send-now', schedule.id), {}, {
        onFinish: () => { sendingNow[schedule.id] = false },
    })
}

// Group info for chat ID display
const GROUP_FIELDS = [
    { key: 'daily_chat_id', labelKey: 'daily_report', icon: '📅' },
    { key: 'weekly_chat_id', labelKey: 'weekly_report', icon: '📆' },
    { key: 'monthly_chat_id', labelKey: 'monthly_report', icon: '🗓️' },
    { key: 'document_chat_id', labelKey: 'document_group', icon: '📄' },
]
</script>

<template>

    <Head :title="t('telegram')" />
    <AppLayout>
        <template #title>{{ t('telegram') }}</template>

        <div class="max-w-3xl space-y-6">

            <!-- Bot Token notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 flex items-start gap-3">
                <span class="text-blue-500 text-lg mt-0.5">🤖</span>
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ t('bot_token') }}</p>
                    <p class="text-xs text-blue-600 font-mono mt-0.5">{{ t('bot_token_global_hint') }}</p>
                </div>
            </div>

            <!-- Add button -->
            <div class="flex justify-end">
                <button
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
                    @click="showAddForm = !showAddForm">
                    {{ showAddForm ? t('cancel') : '+ ' + t('add_branch') }}
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showAddForm" class="bg-white rounded-xl border border-slate-200 p-5">
                <h3 class="font-semibold text-gray-700 mb-4">{{ t('add_branch') }}</h3>
                <form @submit.prevent="submitAdd" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('bot_name') }} *</label>
                        <input v-model="addForm.name" type="text" :placeholder="t('branch_name_placeholder')"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                        <p v-if="addForm.errors.name" class="text-red-600 text-xs mt-1">{{ addForm.errors.name }}</p>
                    </div>

                    <!-- 4 group chat IDs -->
                    <div class="grid grid-cols-1 gap-3">
                        <div v-for="g in GROUP_FIELDS" :key="g.key">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ g.icon }} {{ t(g.labelKey) }} — Chat ID
                            </label>
                            <input v-model="addForm[g.key]" type="text" placeholder="-1001234567890"
                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                            <p v-if="addForm.errors[g.key]" class="text-red-600 text-xs mt-1">{{ addForm.errors[g.key]
                            }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input v-model="addForm.is_active" type="checkbox" id="add-active"
                            class="rounded border-slate-300 text-blue-600" />
                        <label for="add-active" class="text-sm text-gray-700">{{ t('active') }}</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="addForm.processing"
                            class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                            {{ addForm.processing ? '...' : t('save') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Branch list -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200">
                    <h2 class="font-semibold text-gray-700">{{ t('branches') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ t('branch_hint') }}</p>
                </div>

                <div v-if="!configs || configs.length === 0" class="px-5 py-8 text-center text-gray-400">
                    {{ t('no_data') }}
                </div>

                <div v-for="(config, i) in configs" :key="config.id"
                    class="table-row-animate border-b border-gray-50 last:border-0" :style="{ '--row-index': i }">

                    <!-- ── View mode ── -->
                    <div v-if="editTarget?.id !== config.id" class="px-5 py-4">

                        <!-- Branch header -->
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800">{{ config.name }}</span>
                                <span
                                    :class="['px-2 py-0.5 rounded-full text-xs', config.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ config.is_active ? t('active') : t('inactive') }}
                                </span>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <button class="text-sm text-blue-600 hover:underline" @click="openEdit(config)">{{
                                    t('edit') }}</button>
                                <button class="text-sm text-red-600 hover:underline" @click="deleteTarget = config">{{
                                    t('delete') }}</button>
                            </div>
                        </div>

                        <!-- Chat ID grid -->
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="g in GROUP_FIELDS" :key="g.key" class="bg-gray-50 rounded-lg px-3 py-2 text-xs">
                                <span class="text-gray-500">{{ g.icon }} {{ t(g.labelKey) }}</span>
                                <p class="font-mono text-gray-700 mt-0.5 truncate">
                                    {{ config[g.key] || '—' }}
                                </p>
                            </div>
                        </div>

                        <!-- ── Report Schedule (per branch) ── -->
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <button
                                class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-blue-600 transition-colors w-full"
                                @click="toggleSchedule(config.id)">
                                <span>📅</span>
                                <span>{{ t('report_schedule') }}</span>
                                <span class="ml-auto text-gray-400 text-xs">
                                    {{ openSchedules[config.id] ? '▲' : '▼' }}
                                </span>
                            </button>

                            <div v-if="openSchedules[config.id]" class="mt-3 space-y-3">
                                <div v-for="schedule in config.schedules" :key="schedule.id"
                                    class="bg-gray-50 rounded-xl border border-gray-100 px-4 py-3">
                                    <form @submit.prevent="saveSchedule(schedule)" class="space-y-3">

                                        <!-- Label + toggle -->
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-800">
                                                {{ SCHEDULE_META[schedule.report_type]?.icon }}
                                                {{ t(SCHEDULE_META[schedule.report_type]?.labelKey) }}
                                            </span>
                                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                                <input v-model="scheduleForms[schedule.id].is_enabled" type="checkbox"
                                                    class="sr-only peer" />
                                                <div
                                                    class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5">
                                                </div>
                                                <span class="ml-2 text-xs"
                                                    :class="scheduleForms[schedule.id].is_enabled ? 'text-blue-600 font-medium' : 'text-gray-400'">
                                                    {{ scheduleForms[schedule.id].is_enabled ? t('enabled') :
                                                    t('disabled') }}
                                                </span>
                                            </label>
                                        </div>

                                        <!-- Time + day selectors -->
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                                    t('send_time')
                                                    }}</label>
                                                <input v-model="scheduleForms[schedule.id].time" type="time"
                                                    :class="['w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500', !scheduleForms[schedule.id].time ? 'text-gray-400' : 'text-gray-900']" />
                                                <p v-if="scheduleForms[schedule.id].errors.time"
                                                    class="text-red-600 text-xs mt-1">
                                                    {{ scheduleForms[schedule.id].errors.time }}
                                                </p>
                                            </div>

                                            <!-- Day of week (weekly only) -->
                                            <div v-if="schedule.report_type === 'weekly'">
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                                    t('day_of_week')
                                                    }}</label>
                                                <select v-model="scheduleForms[schedule.id].day_of_week"
                                                    :class="['w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500', !scheduleForms[schedule.id].day_of_week ? 'text-gray-400' : 'text-gray-900']">
                                                    <option value="" disabled class="text-gray-400">{{ t('day_of_week')
                                                        }}
                                                    </option>
                                                    <option v-for="day in DAY_OPTIONS" :key="day.value"
                                                        :value="day.value">
                                                        {{ day.label }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- Day of month (monthly only) -->
                                            <div v-if="schedule.report_type === 'monthly'">
                                                <label class="block text-xs font-medium text-gray-600 mb-1">{{
                                                    t('day_of_month')
                                                    }}</label>
                                                <input v-model.number="scheduleForms[schedule.id].day_of_month"
                                                    type="number" min="1" max="31" placeholder="1"
                                                    :class="['w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400', !scheduleForms[schedule.id].day_of_month ? 'text-gray-400' : 'text-gray-900']" />
                                                <p v-if="scheduleForms[schedule.id].errors.day_of_month"
                                                    class="text-red-600 text-xs mt-1">
                                                    {{ scheduleForms[schedule.id].errors.day_of_month }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <button type="button" :disabled="sendingNow[schedule.id]"
                                                class="bg-gray-100 text-gray-700 px-4 py-1.5 rounded-lg text-xs hover:bg-gray-200 disabled:opacity-50 transition-colors"
                                                @click="sendNow(schedule)">
                                                {{ sendingNow[schedule.id] ? '...' : t('send_now') }}
                                            </button>
                                            <button type="submit" :disabled="scheduleForms[schedule.id].processing"
                                                class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-xs hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                                {{ scheduleForms[schedule.id].processing ? '...' : t('save') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Edit mode ── -->
                    <div v-else class="px-5 py-4 bg-blue-50">
                        <form @submit.prevent="submitEdit" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">{{ t('bot_name') }}</label>
                                <input v-model="editForm.name" type="text" :placeholder="t('branch_name_placeholder')"
                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                            </div>

                            <!-- 4 group chat IDs -->
                            <div class="grid grid-cols-1 gap-3">
                                <div v-for="g in GROUP_FIELDS" :key="g.key">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        {{ g.icon }} {{ t(g.labelKey) }} — Chat ID
                                    </label>
                                    <input v-model="editForm[g.key]" type="text" placeholder="-1001234567890"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-400" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input v-model="editForm.is_active" type="checkbox" :id="'edit-active-' + config.id"
                                    class="rounded border-slate-300 text-blue-600" />
                                <label :for="'edit-active-' + config.id" class="text-sm text-gray-700">{{ t('active')
                                }}</label>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="editForm.processing"
                                    class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                                    {{ editForm.processing ? '...' : t('save') }}
                                </button>
                                <button type="button"
                                    class="bg-gray-200 text-gray-700 px-4 py-1.5 rounded-lg text-sm hover:bg-gray-300"
                                    @click="editTarget = null">
                                    {{ t('cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <Modal :show="!!deleteTarget" :title="t('delete')" :message="t('confirm_delete')" :confirm-text="t('yes')"
            :cancel-text="t('cancel')" @confirm="doDelete" @cancel="deleteTarget = null" />
    </AppLayout>
</template>
