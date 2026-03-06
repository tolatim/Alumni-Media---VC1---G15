<template>
  <nav class="flex justify-between items-center px-8 py-4 shadow-md bg-white sticky top-0 z-50">
    <RouterLink to="/" class="text-xl font-bold text-teal-600">
      Alumni Media
    </RouterLink>

    <div class="flex items-center gap-4">
      <RouterLink
        to="/"
        :class="navClass('/')"
      >
        <i class="fa-solid fa-house"></i>
        Home
      </RouterLink>

      <RouterLink
        to="/connection"
        :class="navClass('/connection')"
      >
        <i class="fa-solid fa-user-group"></i>
        Connection
      </RouterLink>

      <RouterLink
        to="/message"
        :class="navClass('/message')"
        class="relative"
      >
        <i class="fa-solid fa-message"></i>
        Message
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] leading-[18px] text-center"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </RouterLink>
      
      <RouterLink
        to="/notification"
        :class="navClass('/notification')"
      >
        <i class="fa-solid fa-bell"></i>
        Notification
      </RouterLink>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500"
      >
        <img
          :src="user.profile?.avatar || fallbackAvatar"
          alt="User Profile"
          class="w-full h-full object-cover"
        >
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import api from '@/services/api'
import { useRoute } from 'vue-router'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const route = useRoute()
const user = ref(null)
const unreadCount = ref(0)
let unreadTimer = null
const handleMessagesUpdated = () => {
  fetchUnreadCount()
}

const navClass = (prefix) => {
  const base = 'px-4 py-2 rounded-lg font-medium transition flex flex-col items-center gap-1'
  const isActive = route.path === prefix || route.path.startsWith(`${prefix}/`)
  return isActive
    ? `${base} bg-teal-100 text-teal-700`
    : `${base} text-gray-600 hover:bg-teal-50 hover:text-teal-600`
}

const fetchMe = async () => {
  try {
    const response = await api.get('/me')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}

const fetchUnreadCount = async () => {
  try {
    const response = await api.get('/messages/unread-count')
    unreadCount.value = response.data?.data?.count || 0
  } catch {
    unreadCount.value = 0
  }
}

watch(
  () => route.fullPath,
  async () => {
    await fetchUnreadCount()
  }
)

onMounted(async () => {
  await fetchMe()
  await fetchUnreadCount()
  unreadTimer = setInterval(fetchUnreadCount, 15000)
  window.addEventListener('messages:updated', handleMessagesUpdated)
})

onUnmounted(() => {
  if (unreadTimer) {
    clearInterval(unreadTimer)
  }
  window.removeEventListener('messages:updated', handleMessagesUpdated)
})
</script>
