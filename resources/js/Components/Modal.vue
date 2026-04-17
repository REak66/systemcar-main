<script setup>
defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Confirm' },
  message: { type: String, default: 'Are you sure?' },
  confirmText: { type: String, default: 'Yes' },
  cancelText: { type: String, default: 'Cancel' },
  danger: { type: Boolean, default: true },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      >
        <div class="modal-dialog bg-white rounded-xl max-w-sm w-full p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ title }}</h3>
          <p class="text-gray-600 text-sm mb-6">{{ message }}</p>
          <div class="flex gap-3 justify-end">
            <button
              class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
              @click="emit('cancel')"
            >
              {{ cancelText }}
            </button>
            <button
              :class="[
                'px-4 py-2 text-sm text-white rounded-lg transition-colors',
                danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700',
              ]"
              @click="emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
