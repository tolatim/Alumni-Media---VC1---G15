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
      const items = res.data?.data || res.data || [];
      this.savedPosts = items.map(item => Number(item.post_id)).filter(Boolean);
    },

    async savePost(postId) {
      const id = Number(postId);
      if (this.isSaved(id)) return;

      // optimistic update
      this.savedPosts.push(id);

      try {
        await axios.post(`/api/saved-posts`, { post_id: id });
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
        await axios.delete(`/api/saved-posts/${id}`); // backend expects postId in URL
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
        .listen(".post.saved", ({ saved_post }) => {
          const pid = Number(saved_post?.post_id);
          if (pid && !this.savedPosts.includes(pid)) {
            this.savedPosts.push(pid);
          }
        })

        // fired when unsave() broadcasts PostUnsaved event
        .listen(".post.unsaved", ({ post_id }) => {
          const pid = Number(post_id);
          this.savedPosts = this.savedPosts.filter(id => id !== pid);
        });
    },

    unsubscribeFromSavedPosts(userId) {
      const echo = createEcho();
      if (!echo) return;

      echo.leave(`user.${userId}.saved-posts`);
      this.wsStatus = "disconnected";
    },

    // call this once when user logs in
    async init(userId) {
      await this.loadSavedPosts();
      this.subscribeToSavedPosts(userId);
    },

    teardown(userId) {
      this.unsubscribeFromSavedPosts(userId);
      this.savedPosts = [];
    },
  },
});