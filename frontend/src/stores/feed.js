import { defineStore } from 'pinia'
import api from '@/services/api'

const FEED_PER_PAGE = 8

export const useFeedStore = defineStore('feed', {
  state: () => ({
    posts: [],
    page: 1,
    lastPage: 1,
  }),

  actions: {
    async load(page = 1, append = false) {
      const response = await api.get('/feed', {
        params: { page, per_page: FEED_PER_PAGE },
      })

      const items = response.data?.data || []
      const pagination = response.data?.pagination || {}
      const currentPage = Number(pagination.current_page || page)
      const lastPage = Number(pagination.last_page || currentPage)

      this.page = currentPage
      this.lastPage = lastPage

      if (append) {
        this.posts = [...this.posts, ...items]
      } else {
        this.posts = items
      }
    },

    addPost(post) {
      if (!post?.id) return
      const exists = this.posts.some((entry) => entry.id === post.id)
      if (exists) {
        this.posts = this.posts.map((entry) => (entry.id === post.id ? post : entry))
        return
      }
      this.posts = [post, ...this.posts]
    },

    replacePost(post) {
      if (!post?.id) return
      this.posts = this.posts.map((entry) => (entry.id === post.id ? post : entry))
    },

    removePost(postId) {
      if (!postId) return
      this.posts = this.posts.filter((entry) => entry.id !== postId)
    },
  },
})
