<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
        // Expected shape: [{ value: '', label: '' }, ...]
    },
    placeholder: {
        type: String,
        default: 'Select...',
    },
    label: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const container = ref(null)

const selectedOption = computed(() => props.options.find(o => o.value === props.modelValue) ?? null)

function select(value) {
    emit('update:modelValue', value)
    open.value = false
}

function handleClickOutside(e) {
    if (container.value && !container.value.contains(e.target)) {
        open.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<template>
    <div ref="container" class="relative">
        <!-- Label -->
        <label v-if="label" class="block text-xs font-medium text-slate-500 mb-1.5">{{ label }}</label>

        <!-- Trigger button -->
        <button type="button" @click="open = !open" :class="[
            'w-full h-[38px] flex items-center justify-between gap-2 rounded-lg px-3 text-sm bg-white transition-all duration-150',
            open
                ? 'border border-blue-500 ring-2 ring-blue-500/20'
                : 'border border-slate-300',
            selectedOption ? 'text-gray-800' : 'text-gray-400',
        ]">
            <!-- Selected indicator dot -->
            <span class="flex items-center gap-2 min-w-0">
                <span v-if="selectedOption" class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                <span class="truncate">{{ selectedOption ? selectedOption.label : placeholder }}</span>
            </span>

            <!-- Chevron -->
            <span :class="['shrink-0 transition-transform duration-200', open ? 'rotate-180' : '']">
                <svg class="w-4 h-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <!-- Dropdown panel -->
        <Transition enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1">
            <div v-if="open"
                class="absolute z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-xl overflow-hidden">
                <!-- Placeholder / clear option -->
                <button type="button" @click="select('')" :class="[
                    'w-full flex items-center justify-between px-3 py-2.5 text-sm transition-colors',
                    !modelValue
                        ? 'bg-blue-50 text-blue-600'
                        : 'text-gray-400 hover:bg-slate-50 hover:text-gray-600',
                ]">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                            :class="!modelValue ? 'bg-blue-400' : 'bg-transparent'"></span>
                        {{ placeholder }}
                    </span>
                    <svg v-if="!modelValue" class="w-3.5 h-3.5 text-blue-500 shrink-0" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                            clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Divider -->
                <div class="h-px bg-slate-200 mx-2"></div>

                <!-- Options list -->
                <ul class="py-1 max-h-52 overflow-y-auto">
                    <li v-for="option in options" :key="option.value">
                        <button type="button" @click="select(option.value)" :class="[
                            'w-full flex items-center justify-between px-3 py-2.5 text-sm transition-colors',
                            modelValue === option.value
                                ? 'bg-blue-50 text-blue-600 font-medium'
                                : 'text-gray-700 hover:bg-slate-50',
                        ]">
                            <span class="flex items-center gap-2 min-w-0">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 transition-colors"
                                    :class="modelValue === option.value ? 'bg-blue-500' : 'bg-gray-200'"></span>
                                <span class="truncate">{{ option.label }}</span>
                            </span>
                            <svg v-if="modelValue === option.value" class="w-3.5 h-3.5 text-blue-500 shrink-0"
                                viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>
