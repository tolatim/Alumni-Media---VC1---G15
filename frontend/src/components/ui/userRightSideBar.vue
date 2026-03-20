<template>
  <div class="col-span-12 space-y-5 lg:col-span-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-900">Connection Requests</h4>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
          {{ pendingRequests.length }}
        </span>
      </div>

      <div class="space-y-3">
        <article
          v-for="request in pendingRequests"
          :key="request.id"
          class="rounded-xl border border-slate-200 bg-slate-50 p-3"
        >
          <RouterLink :to="{ name: 'Profile', params: { id: request.requester?.id } }" class="flex items-center gap-3 min-w-0">
            <img :src="request.requester?.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-xl object-cover">
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800">{{ request.requester?.name || 'Unknown user' }}</p>
              <p class="truncate text-xs text-slate-500">Sent you a friend request</p>
            </div>
          </RouterLink>

          <div class="mt-3 flex gap-2">
            <button
              @click="$emit('accept-request', request.id)"
              class="inline-flex flex-1 items-center justify-center rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-700"
            >
              Accept
            </button>
            <button
              @click="$emit('reject-request', request.id)"
              class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
            >
              Reject
            </button>
          </div>
        </article>

        <p v-if="!pendingRequests.length" class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">No pending requests.</p>
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h4 class="mb-3 text-sm font-bold text-slate-900">Trending In Alumni</h4>
      <div class="flex flex-wrap gap-2">
        <span class="rounded-full border border-cyan-100 bg-cyan-50 px-2.5 py-1 text-[11px] font-semibold text-cyan-700">#AlumniUpdates</span>
        <span class="rounded-full border border-sky-100 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700">#HiringNow</span>
        <span class="rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700">#CareerGrowth</span>
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h4 class="text-sm font-bold text-slate-900">Suggested Connections</h4>
        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
          {{ suggestions.length }}
        </span>
      </div>

      <div class="space-y-3">
        <div
          v-for="person in suggestions"
          :key="person.id"
          class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-2.5"
        >
          <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex min-w-0 items-center gap-2">
            <img :src="person.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-xl object-cover" >
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800">{{ person.name }}</p>
              <p class="truncate text-xs text-slate-500">{{ person.profile?.headline || 'Alumni member' }}</p>
            </div>
          </RouterLink>
          <button
            @click="$emit('send-request', person.id)"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
          >
            Connect
          </button>
        </div>

        <p v-if="!suggestions.length" class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">No suggestions yet.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
defineProps({
  suggestions: {
    type: Array,
    default: () => [],
  },
  pendingRequests: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['send-request', 'accept-request', 'reject-request'])
</script>
