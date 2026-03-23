import { defineStore } from "pinia";
import axios from "axios";
import { createEcho } from "@/services/realtime"; // already exists!

export const useSavedPostStore = defineStore("savedPost", {
  state: () => ({
    savedPosts: [],   // array of post_ids (matches your existing loadSavedPosts)
    wsStatus: "disconnected",
  }),

  getters: {
    // check if a post is saved — use in your button
    isSaved: (state) => (postId) => state.savedPosts.includes(Number(postId)),
  },

  actions: {

    // ── existing (keep as-is, just fix the URL to match your routes) ──
    async loadSavedPosts() {
      const res = await axios.get("/api/saved-posts");
      this.savedPosts = res.data; // your index() returns array of post_ids
    },

    async savePost(postId) {
      const id = Number(postId);
      if (this.isSaved(id)) return;

      // optimistic update
      this.savedPosts.push(id);

      try {
        await axios.post(`/api/saved-posts/${id}`); // fixed URL to match your routes
      } catch (err) {
        // rollback on failure
        this.savedPosts = this.savedPosts.filter(i => i !== id);
      }
    },

    async unsavePost(postId) {
      const id = Number(postId);
      if (!this.isSaved(id)) return;

      // optimistic update
      this.savedPosts = this.savedPosts.filter(i => i !== id);

      try {
        await axios.delete(`/api/saved-posts/${id}`); // fixed URL to match your routes
      } catch (err) {
        // rollback on failure
        await this.loadSavedPosts();
      }
    },

    async toggleSave(postId) {
      this.isSaved(postId)
        ? await this.unsavePost(postId)
        : await this.savePost(postId);
    },

    // ── NEW: WebSocket subscription using your existing realtime.js ──
    subscribeToSavedPosts(userId) {
      const echo = createEcho(); // uses your existing createEcho()
      if (!echo) return;

      this.wsStatus = "connecting";

      echo
        .private(`user.${userId}.saved-posts`)

        .subscribed(() => {
          this.wsStatus = "connected";
        })

        .error(() => {
          this.wsStatus = "disconnected";
        })

        // fired when save() broadcasts PostSaved event
        .listen(".post.saved", ({ post_id }) => {
          if (!this.savedPosts.includes(post_id)) {
            this.savedPosts.push(post_id);
          }
        })

        // fired when unsave() broadcasts PostUnsaved event
        .listen(".post.unsaved", ({ post_id }) => {
          this.savedPosts = this.savedPosts.filter(id => id !== post_id);
        });
    },

    unsubscribeFromSavedPosts() {
      const echo = createEcho();
      if (!echo) return;

      echo.leave("user.*.saved-posts");
      this.wsStatus = "disconnected";
    },

    // call this once when user logs in
    async init(userId) {
      await this.loadSavedPosts();
      this.subscribeToSavedPosts(userId);
    },

    teardown() {
      this.unsubscribeFromSavedPosts();
      this.savedPosts = [];
    },
  },
});