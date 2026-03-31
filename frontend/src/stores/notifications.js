import { defineStore } from 'pinia'
import api from '@/services/api'

const NOTIFICATIONS_PER_PAGE = 20

const defaultPagination = () => ({
  current_page: 1,
  last_page: 1,
  per_page: NOTIFICATIONS_PER_PAGE,
  total: 0,
})

export const useNotificationStore = defineStore('notificationStore', {
  state: () => ({
    notifications: [],
    unreadCount: 0,
    pagination: defaultPagination(),
  }),

  actions: {
    async loadNotifications(page = 1) {
      const response = await api.get('/notifications', {
        params: { page, per_page: NOTIFICATIONS_PER_PAGE },
      })

      this.notifications = Array.isArray(response.data?.data) ? response.data.data : []
      this.pagination = response.data?.pagination || defaultPagination()
    },

    async loadUnreadCount() {
      try {
        const response = await api.get('/notifications/unread-count', {
          headers: {
            'X-Skip-Loading': 'true',
          },
        })
        const count = Number(response.data?.data?.count || 0)
        this.unreadCount = Number.isFinite(count) && count > 0 ? count : 0
      } catch {
        this.unreadCount = 0
      }
    },

    pushRealtimeNotification(notification) {
      const normalized = notification || null
      const id = Number(normalized?.id || 0)
      if (!id) return

      const existingIndex = this.notifications.findIndex((item) => Number(item?.id) === id)
      if (existingIndex >= 0) {
        this.notifications.splice(existingIndex, 1, normalized)
        return
      }

      this.notifications.unshift(normalized)
      if (this.notifications.length > NOTIFICATIONS_PER_PAGE) {
        this.notifications = this.notifications.slice(0, NOTIFICATIONS_PER_PAGE)
      }

      const isSeen = Boolean(normalized?.seen)
      if (!isSeen) {
        this.unreadCount += 1
      }
    },

    async markSeen(notificationId) {
      await api.post(`/notifications/${notificationId}/seen`)
      this.notifications = this.notifications.map((item) => {
        if (Number(item?.id) !== Number(notificationId)) return item
        if (item.seen) return item
        return { ...item, seen: true }
      })
      this.unreadCount = Math.max(0, this.unreadCount - 1)
    },

    async markAllSeen() {
      await api.post('/notifications/mark-all-seen')
      this.notifications = this.notifications.map((item) => ({ ...item, seen: true }))
      this.unreadCount = 0
    },
  },
})
