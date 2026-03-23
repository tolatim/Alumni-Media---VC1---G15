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
      this.posts = this.posts.map((entry) => {
        if (entry.id === post.id) {
          return post
        }

        if (entry.shared_post?.id === post.id) {
          return {
            ...entry,
            shared_post: post,
          }
        }

        return entry
      })
    },

    updatePostLike(postId, payload = {}, currentUserId = null) {
      const normalizedPostId = Number(postId)
      if (!Number.isFinite(normalizedPostId)) return

      const normalizedLikesCount = Number(payload.likes_count ?? payload.likesCount)
      const actorUserId = Number(payload.actor_user_id ?? payload.actorUserId)
      const actorLiked = payload.liked

      const applyLikeUpdate = (post) => {
        if (!post) return post

        const nextPost = { ...post }

        if (Number.isFinite(normalizedLikesCount)) {
          nextPost.likes_count = normalizedLikesCount
        }

        if (Number.isFinite(actorUserId) && Number(currentUserId) === actorUserId && typeof actorLiked === 'boolean') {
          nextPost.liked_by_me = actorLiked
        }

        return nextPost
      }

      this.posts = this.posts.map((entry) => {
        if (Number(entry.id) === normalizedPostId) {
          return applyLikeUpdate(entry)
        }

        if (Number(entry.shared_post?.id) === normalizedPostId) {
          return {
            ...entry,
            shared_post: applyLikeUpdate(entry.shared_post),
          }
        }

        return entry
      })
    },

    updatePostComments(postId, payload = {}) {
      const normalizedPostId = Number(postId)
      if (!Number.isFinite(normalizedPostId)) return

      const normalizedCommentsCount = Number(payload.comments_count ?? payload.commentsCount)

      const applyCommentUpdate = (post) => {
        if (!post) return post

        const nextPost = { ...post }

        if (Number.isFinite(normalizedCommentsCount)) {
          nextPost.comments_count = normalizedCommentsCount
        }

        return nextPost
      }

      this.posts = this.posts.map((entry) => {
        if (Number(entry.id) === normalizedPostId) {
          return applyCommentUpdate(entry)
        }

        if (Number(entry.shared_post?.id) === normalizedPostId) {
          return {
            ...entry,
            shared_post: applyCommentUpdate(entry.shared_post),
          }
        }

        return entry
      })
    },

    removePost(postId) {
      if (!postId) return
      this.posts = this.posts
        .filter((entry) => entry.id !== postId)
        .map((entry) => {
          if (entry.shared_post?.id === postId) {
            return {
              ...entry,
              shared_post: null,
            }
          }

          return entry
        })
    },
  },
})
