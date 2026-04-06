<script setup>
defineProps({
  value: { type: [String, Number], required: true },
  label: { type: String, required: true },
  color: { type: String, default: 'blue' },
})
</script>

<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-1 animate-fade-up transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <p class="text-sm text-gray-500 font-medium">{{ label }}</p>
    <p class="text-2xl font-bold mt-1 text-blue-600">{{ value }}</p>
  </div>
</template>
