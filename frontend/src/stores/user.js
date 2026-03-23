import { defineStore } from "pinia";
import api from "@/services/api";
import { useSavedPostStore } from "@/stores/savedPostStore"; // ADD

export const useUserStore = defineStore("currentUser", {
  state: () => ({
    currentUser: null
  }),

  actions: {
    async fetchUser() {
      try {
        const response = await api.get("/me")
        this.currentUser = response.data

        // ADD THIS — start WebSocket + load saved posts once user is known
        const savedPostStore = useSavedPostStore()
        savedPostStore.init(this.currentUser.id)

      } catch (error) {
        console.log(error)
      }
    },

    // ADD THIS — call when user logs out
    logout() {
      const savedPostStore = useSavedPostStore()
      savedPostStore.teardown()

      this.currentUser = null
      localStorage.removeItem("token")
    }
  }
})