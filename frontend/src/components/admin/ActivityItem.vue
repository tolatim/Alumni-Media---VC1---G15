<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
  time?: string
  icon?: string
}>()
</script>

<template>
  <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <div
      class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-700"
      aria-hidden="true"
    >
      {{ icon || '•' }}
    </div>

    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-semibold text-slate-900">{{ title || 'Activity' }}</p>
      <p v-if="description" class="mt-0.5 line-clamp-2 text-xs text-slate-600">{{ description }}</p>
    </div>

    <div v-if="time" class="shrink-0 text-[11px] font-semibold text-slate-500">
      {{ time }}
    </div>
  </div>
</template>
