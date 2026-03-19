<template>
  <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-5">
      <RouterLink to="/" class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-600 to-blue-700 text-white shadow-sm">
          <i class="fa-solid fa-graduation-cap text-sm"></i>
        </span>
        <div>
          <p class="text-sm font-bold uppercase tracking-wide text-slate-900">Alumni Media</p>
          <p class="text-[11px] font-medium text-slate-500">Professional Network</p>
        </div>
      </RouterLink>

      <div class="hidden items-center gap-2 md:gap-3 sm:flex">
        <RouterLink to="/" :class="navClass('/')">
          <i class="fa-solid fa-house"></i>
          <span>Home</span>
        </RouterLink>

        <RouterLink to="/connection" :class="navClass('/connection')">
          <i class="fa-solid fa-user-group"></i>
          <span>Connection</span>
        </RouterLink>

        <RouterLink to="/message" :class="navClass('/message')" class="relative">
          <i class="fa-solid fa-message"></i>
          <span>Message</span>
          <span
            v-if="unreadCount > 0"
            class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-rose-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
          >
            {{ unreadCount > 99 ? '99+' : unreadCount }}
          </span>
        </RouterLink>

        <RouterLink to="/notification" :class="navClass('/notification')" class="relative">
          <i class="fa-solid fa-bell"></i>
          <span>Notification</span>
          <span
            v-if="notificationUnread > 0"
            class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-amber-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
          >
            {{ notificationUnread > 99 ? '99+' : notificationUnread }}
          </span>
        </RouterLink>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="ml-1 hidden overflow-hidden rounded-xl border border-cyan-200 bg-white p-0.5 shadow-sm sm:inline-flex lg:hidden"
        >
          <img
            :src="user.profile?.avatar || fallbackAvatar"
            alt="User Profile"
            class="h-9 w-9 rounded-lg object-cover"
          >
        </RouterLink>
      </div>

      <button
        type="button"
        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:bg-slate-50 sm:hidden"
        @click="menuOpen = true"
        aria-label="Open menu"
      >
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>

    <div v-if="menuOpen" class="fixed inset-0 z-50 sm:hidden">
      <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          class="absolute inset-0 h-screen w-screen cursor-pointer bg-slate-900/60 backdrop-blur-sm"
          @click="menuOpen = false"
          aria-hidden="true"
        ></div>
      </Transition>

      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-220 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
      >
        <div class="absolute right-0 top-0 h-screen w-[85vw] max-w-sm rounded-l-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="border-b border-slate-100 px-4 py-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-cyan-600 to-blue-700 text-white shadow-sm">
                <i class="fa-solid fa-graduation-cap text-xs"></i>
              </span>
              <div>
                <p class="text-sm font-bold text-slate-900">Alumni Media</p>
                <p class="text-[11px] font-medium text-slate-500">Navigation</p>
              </div>
            </div>
            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50"
              @click="menuOpen = false"
              aria-label="Close menu"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-4 p-4">
          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-center gap-3">
              <img
                :src="user?.profile?.avatar || fallbackAvatar"
                alt="User Profile"
                class="h-10 w-10 rounded-xl object-cover"
              >
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900">{{ user?.name || 'Guest' }}</p>
                <p class="truncate text-xs text-slate-500">{{ user?.profile?.headline || 'Alumni member' }}</p>
              </div>
            </div>
            <RouterLink
              v-if="user"
              :to="{ name: 'Profile', params: { id: user.id } }"
              class="mt-3 inline-flex w-full items-center justify-center rounded-xl border border-cyan-200 bg-white px-3 py-2 text-xs font-semibold text-cyan-700"
              @click="menuOpen = false"
            >
              View Profile
            </RouterLink>
          </div>

          <div class="space-y-2">
            <RouterLink to="/" :class="navClass('/')" @click="menuOpen = false">
              <i class="fa-solid fa-house"></i>
              <span>Home</span>
            </RouterLink>
            <RouterLink to="/connection" :class="navClass('/connection')" @click="menuOpen = false">
              <i class="fa-solid fa-user-group"></i>
              <span>Connection</span>
            </RouterLink>
            <RouterLink to="/message" :class="navClass('/message') + ' relative'" @click="menuOpen = false">
              <i class="fa-solid fa-message"></i>
              <span>Message</span>
              <span
                v-if="unreadCount > 0"
                class="absolute right-2 top-1 min-w-[18px] rounded-full bg-rose-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
              >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
              </span>
            </RouterLink>
            <RouterLink to="/notification" :class="navClass('/notification') + ' relative'" @click="menuOpen = false">
              <i class="fa-solid fa-bell"></i>
              <span>Notification</span>
              <span
                v-if="notificationUnread > 0"
                class="absolute right-2 top-1 min-w-[18px] rounded-full bg-amber-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
              >
                {{ notificationUnread > 99 ? '99+' : notificationUnread }}
              </span>
            </RouterLink>
          </div>
        </div>
      </div>
      </Transition>
    </div>
  </nav>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import api from '@/services/api'
import { useRoute } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import { createEcho } from '@/services/realtime'

const route = useRoute()
const notificationStore = useNotificationStore()
const user = ref(null)
const unreadCount = ref(0)
const menuOpen = ref(false)
const notificationUnread = computed(() => notificationStore.unreadCount)
let unreadTimer = null
let notificationTimer = null
let realtimeRefreshTimer = null
let realtimeChannel = null
const handleMessagesUpdated = () => {
  fetchUnreadCount()
}

const navClass = (prefix) => {
  const base = 'inline-flex w-full items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition'
  const isActive = route.path === prefix || route.path.startsWith(`${prefix}/`)
  return isActive
    ? `${base} border-cyan-200 bg-cyan-50 text-cyan-700`
    : `${base} border-transparent bg-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900`
}

const fetchMe = async () => {
  try {
    const response = await api.get('/me', {
      headers: {
        'X-Skip-Loading': 'true',
      },
    })
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
    createEcho()
    notificationStore.connect(user.value?.id)
    await notificationStore.fetchNotifications()
  } catch {
    user.value = null
  }
}

const fetchUnreadCount = async () => {
  try {
    const response = await api.get('/messages/unread-count', {
      headers: {
        'X-Skip-Loading': 'true',
      },
    })
    unreadCount.value = response.data?.data?.count || 0
  } catch {
    unreadCount.value = 0
  }
}

const scheduleUnreadRefresh = () => {
  if (realtimeRefreshTimer) {
    clearTimeout(realtimeRefreshTimer)
  }
  realtimeRefreshTimer = setTimeout(() => {
    realtimeRefreshTimer = null
    fetchUnreadCount()
  }, 300)
}

const attachRealtime = () => {
  const echo = createEcho()
  if (!echo || !user.value?.id) return

  realtimeChannel = echo.private(`user.${user.value.id}`)
  realtimeChannel.listen('.MessageCreated', () => {
    scheduleUnreadRefresh()
  })
  realtimeChannel.listen('.MessageUpdated', () => {
    scheduleUnreadRefresh()
  })
  realtimeChannel.listen('.MessageDeleted', () => {
    scheduleUnreadRefresh()
  })
}

const detachRealtime = () => {
  if (realtimeChannel) {
    realtimeChannel.stopListening('.MessageCreated')
    realtimeChannel.stopListening('.MessageUpdated')
    realtimeChannel.stopListening('.MessageDeleted')
    realtimeChannel = null
  }
  if (typeof window !== 'undefined' && window.Echo && user.value?.id) {
    window.Echo.leave(`user.${user.value.id}`)
  }
  if (realtimeRefreshTimer) {
    clearTimeout(realtimeRefreshTimer)
    realtimeRefreshTimer = null
  }
}

watch(
  () => route.fullPath,
  async () => {
    menuOpen.value = false
    await fetchUnreadCount()
    await notificationStore.refreshUnreadCount()
  }
)

onMounted(async () => {
  await fetchMe()
  await fetchUnreadCount()
  await notificationStore.refreshUnreadCount()
  attachRealtime()
  unreadTimer = setInterval(fetchUnreadCount, 15000)
  notificationTimer = setInterval(() => notificationStore.refreshUnreadCount(), 15000)
  window.addEventListener('messages:updated', handleMessagesUpdated)
})

onUnmounted(() => {
  if (unreadTimer) {
    clearInterval(unreadTimer)
  }
  if (notificationTimer) {
    clearInterval(notificationTimer)
  }
  notificationStore.disconnect()
  detachRealtime()
  window.removeEventListener('messages:updated', handleMessagesUpdated)
})
</script>
