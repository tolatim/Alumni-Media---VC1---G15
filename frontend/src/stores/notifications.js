import { defineStore } from "pinia";
import api from "@/services/api";

export const useNotificationStore = defineStore("notifications", {
  state: () => ({
    items: [],
    loading: false,
    error: "",
    unreadCount: 0,
    channelName: null,
    subscribed: false
  }),

  getters: {
    totalCount: (state) => state.items.length,
    unreadItems: (state) => state.items.filter((item) => !item.read_at)
  },

  actions: {
    async fetchNotifications() {
      this.loading = true;
      this.error = "";
      try {
        const response = await api.get("/notifications");
        this.items = response.data?.data || [];
        this.unreadCount = this.items.filter((item) => !item.read_at).length;
      } catch (error) {
        this.error =
          error.response?.data?.message || "Failed to load notifications.";
      } finally {
        this.loading = false;
      }
    },

    async refreshUnreadCount() {
      try {
        const response = await api.get("/notifications/unread-count", {
          headers: { "X-Skip-Loading": "true" }
        });
        this.unreadCount = response.data?.data?.count || 0;
      } catch {
        this.unreadCount = 0;
      }
    },

    async markAsRead(notificationId) {
      const target = this.items.find((item) => item.id === notificationId);
      if (!target || target.read_at) return;
      try {
        await api.post(`/notifications/${notificationId}/read`);
        target.read_at = new Date().toISOString();
        if (this.unreadCount > 0) this.unreadCount -= 1;
      } catch (error) {
        this.error =
          error.response?.data?.message || "Failed to mark notification as read.";
      }
    },

    connect(userId) {
      if (!userId || !window.Echo) return;
      if (this.subscribed && this.channelName === `App.Models.User.${userId}`) {
        return;
      }
      if (this.channelName) this.disconnect();

      this.channelName = `App.Models.User.${userId}`;
      this.subscribed = true;

      window.Echo.private(this.channelName).notification((notification) => {
        const id = notification.id ?? `rt-${Date.now()}`;
        const exists = this.items.some((item) => item.id === id);
        if (exists) return;

        this.items.unshift({
          id,
          data: notification,
          read_at: null,
          created_at: new Date().toISOString()
        });
        this.unreadCount += 1;
      });
    },

    disconnect() {
      if (!this.channelName || !window.Echo) return;
      window.Echo.leave(`private-${this.channelName}`);
      this.channelName = null;
      this.subscribed = false;
    }
  }
});


