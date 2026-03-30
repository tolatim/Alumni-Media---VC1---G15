<template>
  <nav class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
      <RouterLink to="/" class="flex items-center gap-3">
        <img
          v-if="appLogoUrl"
          :src="appLogoUrl"
          alt="App logo"
          class="h-10 w-10 rounded-xl border border-slate-200 object-cover shadow-sm"
        >
        <span
          v-else
          class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-600 to-blue-700 text-white shadow-sm"
        >
          <i class="fa-solid fa-graduation-cap text-sm"></i>
        </span>
        <div>
          <p class="text-sm font-bold uppercase tracking-wide text-slate-900">Alumni Media</p>
          <p class="text-[11px] font-medium text-slate-500">Professional Network</p>
        </div>
      </RouterLink>

      <div class="flex items-center gap-2 md:gap-3">
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

        <RouterLink to="/notification" :class="navClass('/notification')">
          <i class="fa-solid fa-bell"></i>
          <span>Notification</span>
        </RouterLink>

        <RouterLink v-if="isAdminUser" to="/admin" :class="adminNavClass">
          <i class="fa-solid fa-shield-halved"></i>
          <span>Admin</span>
        </RouterLink>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="ml-1 overflow-hidden rounded-xl border border-cyan-200 bg-white p-0.5 shadow-sm"
        >
          <img
            :src="user.profile?.avatar || fallbackAvatar"
            alt="User Profile"
            class="h-9 w-9 rounded-lg object-cover"
          >
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import api from '@/services/api'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import { fetchPublicAppearance } from '@/services/appearanceService'
import { useMessageStore } from '@/stores/message'

const route = useRoute()
const messageStore = useMessageStore()
const { unreadCount } = storeToRefs(messageStore)
const user = ref(null)
const appLogoUrl = ref(null)
let unreadSocket = null
let unreadReconnectTimer = null
let shouldReconnectUnreadSocket = true

const navClass = (prefix) => {
  const base = 'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition'
  const isActive = route.path === prefix || route.path.startsWith(`${prefix}/`)
  return isActive
    ? `${base} border-cyan-200 bg-cyan-50 text-cyan-700`
    : `${base} border-transparent bg-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900`
}

const isAdminUser = computed(() => {
  const role = user.value?.role
  const roleName = typeof role === 'string' ? role : role?.name
  return roleName === 'admin'
})

const adminNavClass = computed(() => {
  const base = 'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition'
  const adminPaths = ['/admin', '/users', '/posts', '/reports', '/settings', '/admin/settings']
  const isActive = adminPaths.some((path) => route.path === path || route.path.startsWith(`${path}/`))
  return isActive
    ? `${base} border-cyan-200 bg-cyan-50 text-cyan-700`
    : `${base} border-transparent bg-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900`
})

const fetchMe = async () => {
  try {
    const response = await api.get('/me', {
      headers: {
        'X-Skip-Loading': 'true',
      },
    })
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}

const resolveWsUrl = () => {
  const configured = String(import.meta.env.VITE_WS_URL || '').trim()
  if (!configured) {
    const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws'
    return `${protocol}://${window.location.hostname}:3000/ws`
  }

  try {
    const parsed = new URL(configured)
    const pageHost = window.location.hostname
    const isLocalWsHost = parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1'
    const isRemotePage = pageHost !== 'localhost' && pageHost !== '127.0.0.1'

    if (isLocalWsHost && isRemotePage) {
      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
      parsed.hostname = pageHost
      parsed.protocol = protocol
      return parsed.toString()
    }

    return parsed.toString()
  } catch {
    return configured
  }
}

const scheduleUnreadReconnect = () => {
  if (!shouldReconnectUnreadSocket || unreadReconnectTimer) return
  unreadReconnectTimer = setTimeout(() => {
    unreadReconnectTimer = null
    connectUnreadSocket()
  }, 2000)
}

const handleUnreadSocketPayload = async (payload) => {
  const eventType = String(payload?.type || '')
  if (eventType !== 'direct_message') return

  const myId = Number(user.value?.id || 0)
  const senderId = Number(payload?.data?.sender_id ?? payload?.data?.message?.sender_id ?? 0)
  const receiverId = Number(payload?.data?.receiver_id ?? payload?.data?.message?.receiver_id ?? 0)
  const isIncomingForMe = senderId > 0 && receiverId > 0 && senderId !== myId && receiverId === myId
  if (!isIncomingForMe) return

  const activeChatUserId = Number(route.params.userId || 0)
  const isViewingSenderChat = route.path.startsWith('/message') && activeChatUserId === senderId
  if (!isViewingSenderChat) {
    messageStore.setUnreadCount(unreadCount.value + 1)
  }
}

const connectUnreadSocket = () => {
  const wsUrl = resolveWsUrl()
  const userId = Number(user.value?.id || 0)
  if (!userId || !wsUrl) return

  if (unreadSocket && (unreadSocket.readyState === WebSocket.OPEN || unreadSocket.readyState === WebSocket.CONNECTING)) {
    return
  }

  try {
    unreadSocket = new WebSocket(wsUrl)
  } catch {
    scheduleUnreadReconnect()
    return
  }

  unreadSocket.onopen = () => {
    const role = typeof user.value?.role === 'string' ? user.value.role : user.value?.role?.name
    unreadSocket?.send(JSON.stringify({
      type: 'auth',
      user_id: userId,
      role: role || '',
    }))
  }

  unreadSocket.onmessage = async (event) => {
    try {
      const payload = JSON.parse(event.data)
      await handleUnreadSocketPayload(payload)
    } catch {
      // ignore invalid payload
    }
  }

  unreadSocket.onerror = () => {
    try {
      unreadSocket?.close()
    } catch {
      // ignore close errors
    }
  }

  unreadSocket.onclose = () => {
    unreadSocket = null
    scheduleUnreadReconnect()
  }
}

onMounted(async () => {
  const appearance = await fetchPublicAppearance()
  appLogoUrl.value = appearance.logo_url || null
  await fetchMe()
  connectUnreadSocket()
})

onUnmounted(() => {
  shouldReconnectUnreadSocket = false
  if (unreadReconnectTimer) {
    clearTimeout(unreadReconnectTimer)
    unreadReconnectTimer = null
  }
  if (unreadSocket) {
    try {
      unreadSocket.close()
    } catch {
      // ignore close errors
    }
    unreadSocket = null
  }
})
</script>
