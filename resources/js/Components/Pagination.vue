<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  links: { type: Array, required: true },
})
</script>

<template>
  <div v-if="links && links.length > 3" class="flex items-center justify-center gap-1 mt-4 flex-wrap">
    <template v-for="link in links" :key="link.label">
      <Link
        v-if="link.url"
        :href="link.url"
        preserve-scroll
        :class="[
          'px-3 py-1.5 text-sm rounded border transition-colors',
          link.active
            ? 'bg-blue-600 text-white border-blue-600'
            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
        ]"
        v-html="link.label"
      />
      <span
        v-else
        class="px-3 py-1.5 text-sm rounded border bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed"
        v-html="link.label"
      />
    </template>
  </div>
</template>
