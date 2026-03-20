<template>
  <div class="col-span-12 space-y-5 lg:col-span-3 lg:sticky lg:top-24 lg:h-[calc(100vh-6.5rem)] lg:overflow-y-auto lg:pr-2">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="h-20 bg-gradient-to-r from-cyan-700 via-sky-700 to-blue-700"></div>
      <div class="-mt-9 px-5 pb-5">
        <img
          :src="user?.profile?.avatar || fallbackAvatar"
          class="h-20 w-20 mx-auto rounded-2xl border-4 border-white object-cover shadow-md"
        >
        <div class="mt-3 text-center">
          <h3 class="text-base font-bold text-slate-900">{{ displayName }}</h3>
          <p class="mt-1 line-clamp-2 text-xs text-slate-500">
            {{ user?.profile?.headline || user?.profile?.current_job || 'Welcome to Alumni Media' }}
          </p>
        </div>
        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="mt-4 inline-flex w-full items-center justify-center rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"
        >
          View Profile
        </RouterLink>
      </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <h4 class="mb-3 text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500">Navigation</h4>
      <div class="space-y-2 text-sm">
        <RouterLink to="/" class="flex items-center justify-between rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-50">
          <span class="font-medium">Home</span>
          <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
        </RouterLink>
        <RouterLink to="/connection" class="flex items-center justify-between rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-50">
          <span class="font-medium">Connections</span>
          <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
        </RouterLink>
        <RouterLink to="/message" class="flex items-center justify-between rounded-lg px-3 py-2 text-slate-700 transition hover:bg-slate-50">
          <span class="font-medium">Messages</span>
          <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
        </RouterLink>
      </div>
      <button
        type="button"
        @click="logout"
        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100"
      >
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
      </button>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const router = useRouter()

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
})

const displayName = computed(() => {
  const firstName = props.user?.first_name || ''
  const lastName = props.user?.last_name || ''
  const joined = `${firstName} ${lastName}`.trim()
  return joined || props.user?.name || 'Guest User'
})

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  localStorage.removeItem('user_id')
  router.replace('/login')
}
</script>
