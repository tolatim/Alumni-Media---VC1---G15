<script setup lang="ts">
const props = withDefaults(defineProps<{
  adminName?: string
  adminEmail?: string
  adminAvatar?: string | null
  pendingReports?: number
}>(), {
  adminName: 'Admin',
  adminEmail: '',
  adminAvatar: null,
  pendingReports: 0,
})

const getInitial = (name: string) => {
  const value = (name || '').trim()
  if (!value) return 'A'
  return value.charAt(0).toUpperCase()
}
</script>

<template>
  <header class="flex flex-col gap-3 border-b border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-4">
    <div class="flex w-full items-center gap-4 sm:w-auto">
      <input
        type="text"
        placeholder="Search alumni..."
        class="w-full rounded-lg border border-gray-300 bg-gray-100 px-4 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 sm:w-72 lg:w-96"
      >
    </div>

    <div class="flex w-full items-center justify-between gap-4 sm:w-auto sm:justify-end sm:gap-5">
      <button
        type="button"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700"
        title="Pending reports"
      >
        <span
          v-if="props.pendingReports > 0"
          class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-red-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
        >
          {{ props.pendingReports > 99 ? '99+' : props.pendingReports }}
        </span>
        <svg
          viewBox="0 0 24 24"
          class="h-4 w-4"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          aria-hidden="true"
        >
          <path d="M5 4v16" />
          <path d="M5 5h11l-2.5 4L16 13H5" />
        </svg>
      </button>

      <div class="flex min-w-0 items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5">
        <img
          v-if="props.adminAvatar"
          :src="props.adminAvatar"
          class="h-9 w-9 rounded-full object-cover"
          alt="Admin profile"
        >
        <span
          v-else
          class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-sm font-semibold text-white"
        >
          {{ getInitial(props.adminName) }}
        </span>
        <div class="min-w-0 leading-tight">
          <p class="text-sm font-semibold text-gray-900">{{ props.adminName }}</p>
          <p class="truncate text-xs text-gray-500">{{ props.adminEmail || 'Administrator' }}</p>
        </div>
      </div>
    </div>
  </header>
</template>
