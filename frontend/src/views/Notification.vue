<template>
  <div class="min-h-screen bg-gray-50 p-4">
    <div class="max-w-2xl mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Notifications</h1>
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="text-sm px-4 py-2 bg-teal-500 text-white rounded hover:bg-teal-600"
        >
          Mark all as read
        </button>
      </div>

      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-600">Loading notifications...</p>
      </div>

      <div v-else-if="notifications.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
        <p class="text-gray-600">No notifications yet</p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          class="bg-white rounded-lg shadow p-4 hover:shadow-md transition flex items-start gap-4"
          :class="{ 'bg-blue-50 border-l-4 border-blue-500': !notification.read_at }"
        >
          <!-- Icon based on type -->
          <div class="flex-shrink-0 mt-1">
            <div
              class="w-10 h-10 rounded-full flex items-center justify-center text-white text-lg"
              :class="getNotificationIconClass(notification.type)"
            >
              {{ getNotificationIcon(notification.type) }}
            </div>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <p class="text-gray-800 font-medium">{{ getNotificationTitle(notification) }}</p>
            <p class="text-gray-600 text-sm mt-1">{{ notification.data?.message }}</p>
            <p class="text-gray-400 text-xs mt-2">{{ timeAgo(notification.created_at) }}</p>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 flex-shrink-0">
            <button
              v-if="!notification.read_at"
              @click="markAsRead(notification.id)"
              class="text-sm px-2 py-1 bg-teal-500 text-white rounded hover:bg-teal-600"
              title="Mark as read"
            >
              ✓
            </button>
            <button
              @click="deleteNotification(notification.id)"
              class="text-sm px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
              title="Delete"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '@/services/api'

const notifications = ref([])
const loading = ref(true)

const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length)

const fetchNotifications = async () => {
  try {
    loading.value = true
    const response = await api.get('/notifications')
    notifications.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
    notifications.value = []
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId) => {
  try {
    await api.patch(`/notifications/${notificationId}/read`)
    await fetchNotifications()
  } catch (error) {
    console.error('Failed to mark notification as read:', error)
  }
}

const markAllAsRead = async () => {
  try {
    const unreadNotifications = notifications.value.filter(n => !n.read_at)
    await Promise.all(unreadNotifications.map(n => api.patch(`/notifications/${n.id}/read`)))
    await fetchNotifications()
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const deleteNotification = async (notificationId) => {
  try {
    await api.delete(`/notifications/${notificationId}`)
    await fetchNotifications()
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const getNotificationIcon = (type) => {
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

const getNotificationIconClass = (type) => {
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

const getNotificationTitle = (notification) => {
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

// Fetch notifications on mount and set up polling
onMounted(() => {
  fetchNotifications()
  // Poll for new notifications every 10 seconds
  setInterval(fetchNotifications, 10000)
})
</script>
