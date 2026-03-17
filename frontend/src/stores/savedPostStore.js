import { defineStore } from "pinia";
import axios from "axios";

export const useSavedPostStore = defineStore("savedPost", {
  state: () => ({
    savedPosts: []
  }),

  actions: {

    async loadSavedPosts() {
      const res = await axios.get("/api/saved-posts");
      this.savedPosts = res.data;
    },

    async savePost(postId) {
      await axios.post(`/api/save-post/${postId}`);

      if (!this.savedPosts.includes(postId)) {
        this.savedPosts.push(postId);
      }
    },

    async unsavePost(postId) {
      await axios.delete(`/api/unsave-post/${postId}`);

      this.savedPosts = this.savedPosts.filter(id => id !== postId);
    }

  }
});