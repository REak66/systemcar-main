<script setup>
import { computed } from 'vue'

const props = defineProps({
    value: { type: [String, Number], required: true },
    label: { type: String, required: true },
    color: { type: String, default: 'blue' },
})

const borderColor = computed(() => ({
    blue: 'border-l-blue-500',
    green: 'border-l-green-500',
    purple: 'border-l-purple-500',
    red: 'border-l-red-500',
    yellow: 'border-l-amber-500',
    indigo: 'border-l-indigo-500',
}[props.color] ?? 'border-l-blue-500'))

const valueColor = computed(() => ({
    blue: 'text-blue-600',
    green: 'text-green-600',
    purple: 'text-purple-600',
    red: 'text-red-600',
    yellow: 'text-amber-600',
    indigo: 'text-indigo-600',
}[props.color] ?? 'text-blue-600'))
</script>

<template>
    <div
        :class="['stagger-item bg-white rounded-xl border border-slate-200 border-l-4 p-5 flex flex-col gap-2 transition-all duration-200 hover:-translate-y-0.5  active:scale-95', borderColor]">
        <p class="text-xs text-slate-500 font-semibold uppercase tracking-wider">{{ label }}</p>
        <p :class="['text-2xl font-bold leading-none', valueColor]">{{ value }}</p>
    </div>
</template>
