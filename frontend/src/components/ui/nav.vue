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
      
<<<<<<< HEAD
      <!-- Notifications Dropdown -->
      <div class="relative">
        <button
          @click="showNotificationMenu = !showNotificationMenu"
          class="relative px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition flex items-center gap-2"
        >
          🔔
          <span
            v-if="unreadNotificationCount > 0"
            class="absolute -top-1 right-2 min-w-[20px] h-[20px] px-1 rounded-full bg-red-500 text-white text-[11px] leading-[20px] text-center font-bold"
          >
            {{ unreadNotificationCount }}
          </span>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="showNotificationMenu"
          class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-40 max-h-96 overflow-y-auto"
        >
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-gray-200 p-4 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Notifications</h3>
            <button
              v-if="unreadNotificationCount > 0"
              @click="markAllAsRead"
              class="text-xs text-teal-600 hover:text-teal-700 font-semibold"
            >
              Mark all read
            </button>
          </div>

          <!-- Notifications List -->
          <div v-if="recentNotifications.length === 0" class="p-4 text-center text-gray-500">
            No notifications yet
          </div>

          <div v-else class="divide-y divide-gray-200">
            <div
              v-for="notification in recentNotifications"
              :key="notification.id"
              class="p-4 hover:bg-gray-50 transition cursor-pointer"
              :class="{ 'bg-blue-50': !notification.read_at }"
              @click="goToNotifications"
            >
              <div class="flex items-start gap-3">
                <!-- Icon -->
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center text-white flex-shrink-0"
                  :class="getIconClass(notification.type)"
                >
                  {{ getIcon(notification.type) }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">
                    {{ getTitle(notification) }}
                  </p>
                  <p class="text-xs text-gray-600 mt-1">{{ timeAgo(notification.created_at) }}</p>
                </div>

                <!-- Unread indicator -->
                <div
                  v-if="!notification.read_at"
                  class="w-2 h-2 rounded-full bg-teal-500 flex-shrink-0 mt-1"
                ></div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="sticky bottom-0 bg-white border-t border-gray-200 p-3">
            <RouterLink
              to="/notification"
              class="block text-center text-sm text-teal-600 hover:text-teal-700 font-semibold py-2"
            >
              View all notifications →
            </RouterLink>
          </div>
        </div>
      </div>
=======
      <RouterLink
        to="/notification"
        :class="navClass('/notification')"
      >
        <i class="fa-solid fa-bell"></i>
        Notification
      </RouterLink>
>>>>>>> 9eb295f1b2b0f25d84cd2398ff970217a8515370

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
<<<<<<< HEAD

import { onMounted, ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
=======
import { onMounted, onUnmounted, ref, watch } from 'vue'
import api from '@/services/api'
import { useRoute } from 'vue-router'
>>>>>>> 9eb295f1b2b0f25d84cd2398ff970217a8515370
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const route = useRoute()
const user = ref(null)
<<<<<<< HEAD
const notifications = ref([])
const showNotificationMenu = ref(false)
let notificationInterval = null

const unreadNotificationCount = computed(() => {
  return notifications.value.filter(n => !n.read_at).length
})

const recentNotifications = computed(() => {
  return notifications.value.slice(0, 5)
})
=======
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
>>>>>>> 9eb295f1b2b0f25d84cd2398ff970217a8515370

const fetchMe = async () => {
  try {
    const response = await api.get('/me')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}

<<<<<<< HEAD
const fetchNotifications = async () => {
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/mark-all-read')
    await fetchNotifications()
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const goToNotifications = () => {
  showNotificationMenu.value = false
  router.push('/notification')
}

const getIcon = (type) => {
  const iconMap = {
    'like': '👍',
    'comment': '💬',
    'follow': '👤',
    'post': '📝',
    'message': '📨',
    'new_post': '✨',
  }
  return iconMap[type] || '🔔'
}

const getIconClass = (type) => {
  const classMap = {
    'like': 'bg-red-500',
    'comment': 'bg-blue-500',
    'follow': 'bg-purple-500',
    'post': 'bg-orange-500',
    'message': 'bg-green-500',
    'new_post': 'bg-teal-500',
  }
  return classMap[type] || 'bg-gray-500'
}

const getTitle = (notification) => {
  const { type, data } = notification
  
  const titleMap = {
    'like': `${data?.user_name || 'Someone'} liked your post`,
    'comment': `${data?.user_name || 'Someone'} commented on your post`,
    'follow': `${data?.user_name || 'Someone'} started following you`,
    'post': `${data?.user_name || 'Someone'} posted something`,
    'message': `New message from ${data?.user_name || 'someone'}`,
    'new_post': `${data?.user_name || 'Someone'} posted something new`,
  }
  
  return titleMap[type] || data?.title || type
}

const timeAgo = (date) => {
  const seconds = Math.floor((new Date() - new Date(date)) / 1000)
  
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  if (days < 7) return `${days}d ago`
  
  return new Date(date).toLocaleDateString()
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  const notificationDiv = document.querySelector('.relative')
  if (notificationDiv && !notificationDiv.contains(event.target)) {
    showNotificationMenu.value = false
  }
}

onMounted(() => {
  fetchMe()
  fetchNotifications()
  // Poll for new notifications every 10 seconds
  notificationInterval = setInterval(fetchNotifications, 10000)
  // Close dropdown on outside click
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  if (notificationInterval) {
    clearInterval(notificationInterval)
  }
  document.removeEventListener('click', handleClickOutside)
=======
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
>>>>>>> 9eb295f1b2b0f25d84cd2398ff970217a8515370
})
</script>

<style scoped>
/* Smooth dropdown animation */
.relative > div:not(.w-11) {
  animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
