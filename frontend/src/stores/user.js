import { defineStore } from "pinia";
import api from "@/services/api";

export const useUserStore = defineStore("currentUser", {
  state: () => ({
    currentUser: null
  }),

  actions: {
    async fetchUser() {
      try {
        const response = await api.get("/me")
        this.currentUser = response.data
      } catch (error) {
        console.log(error)
      }
    }
  }
})