import { defineStore } from 'pinia'
import api from '@/services/api'

export const useFeedStore = defineStore('feed', {
  state: () => ({
    posts: [],
    feedPage: 1,
    feedLastPage: 1,
    hasMorePosts: true
  }),

  actions: {
    async loadFeedPage(page = 1, append = false) {
      const response = await api.get('/feed', {
        params: { page }
      })

      const items = response.data?.data || []
      const pagination = response.data?.pagination || {}

      this.feedPage = pagination.current_page
      this.feedLastPage = pagination.last_page
      this.hasMorePosts = this.feedPage < this.feedLastPage

      this.posts = append ? [...this.posts, ...items] : items

     }
  }
})