<template>
  <div class="col-span-12 space-y-5 lg:col-span-6">
    <div class="rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur sm:p-4">
      <div class="flex items-center gap-3">
        <img :src="currentUser?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover shadow-sm">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search posts..."
          class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 transition focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
        >
      </div>

      <div class="mt-4 flex items-center justify-end">
        <RouterLink
          to="/post"
          class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:from-cyan-700 hover:to-blue-700 sm:w-auto"
        >
          Create Post
        </RouterLink>
      </div>
    </div>

    <article
      v-for="post in filteredPosts"
      :key="post.id"
      class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
    >
      <div class="border-b border-slate-100 px-4 pb-4 pt-5 sm:px-5">
        <div class="flex items-center gap-3">
          <img :src="post.user?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover">
          <div>
            <h4 class="font-semibold text-slate-900">{{ post.user?.name || 'Unknown user' }}</h4>
            <p class="text-xs text-slate-500">{{ formatDate(post.created_at) }}</p>
          </div>
        </div>
      </div>

      <div class="space-y-4 px-4 py-4 sm:px-5">
        <template v-if="editingPostId === post.id">
          <input
            v-model="editTitle"
            type="text"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            placeholder="Post title"
          >
          <textarea
            v-model="editContent"
            rows="4"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            placeholder="Post content"
          ></textarea>
          <div class="flex justify-end gap-2">
            <button
              type="button"
              @click="cancelEdit"
              :disabled="isSavingEdit"
              class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="saveEdit(post)"
              :disabled="isSavingEdit"
              class="inline-flex items-center rounded-lg border border-cyan-200 bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
            >
              {{ isSavingEdit ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </template>
        <template v-else>
          <h4 v-if="post.title" class="text-lg font-semibold text-slate-900 break-words">
            {{ post.title }}
          </h4>
          <p
            v-if="post.content"
            class="whitespace-pre-line break-words text-[15px] leading-relaxed text-slate-700"
          >
            {{ post.content }}
          </p>
        </template>

        <div
          v-if="post.media?.length"
          :class="[
            'grid gap-2 overflow-hidden rounded-xl',
            post.media.length === 1 ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2'
          ]"
        >
          <template
            v-for="media in post.media"
            :key="media.id ?? media.file_path ?? media.media_url"
          >
            <img
              v-if="isImageMedia(media)"
              :src="getMediaSrc(media)"
              alt="Post image"
              class="h-full max-h-80 w-full rounded-lg border border-slate-200 object-cover"
            >
            <video
              v-else-if="isVideoMedia(media)"
              :src="getMediaSrc(media)"
              class="h-full max-h-80 w-full rounded-lg border border-slate-200 bg-black object-cover"
              controls
              preload="metadata"
            ></video>
          </template>
        </div>
      </div>

      <div class="space-y-3 border-t border-slate-100 px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <button
              type="button"
              :disabled="likeSubmittingPostId === post.id"
              @click="toggleLike(post)"
              class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-1 disabled:opacity-60"
              :class="isPostLiked(post) ? 'border-pink-200 bg-pink-50 text-pink-700 hover:bg-pink-100 focus-visible:ring-pink-300' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 focus-visible:ring-slate-300'"
              :aria-label="isPostLiked(post) ? 'Unlike post' : 'Like post'"
              :title="isPostLiked(post) ? 'Unlike' : 'Like'"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="currentColor"
                aria-hidden="true"
              >
                <path
                  d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                />
              </svg>
              <span>{{ getLikesCount(post) }}</span>
            </button>

            <button
              type="button"
              :disabled="commentsLoadingByPostId[post.id]"
              @click="toggleComments(post)"
              class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 focus-visible:ring-offset-1 disabled:opacity-60"
              :aria-label="isCommentsOpen(post) ? 'Hide comments' : 'Show comments'"
              :title="isCommentsOpen(post) ? 'Hide comments' : 'Show comments'"
            >
              <svg
                viewBox="0 0 24 24"
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <span>{{ getCommentsCount(post) }}</span>
            </button>
          </div>

          <div class="flex items-center gap-2" v-if="canDelete(post)">
            <button
              @click="startEdit(post)"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 focus-visible:ring-offset-1"
            >
              <span aria-hidden="true">Edit</span>
            </button>
            <button
              @click="deletePost(post.id)"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-1"
            >
              <span aria-hidden="true">Delete</span>
            </button>
          </div>
        </div>

        <p class="text-xs font-medium text-slate-500">
          {{ post.media?.length ? `${post.media.length} media` : 'Text post' }}
        </p>
      </div>

      <div v-if="isCommentsOpen(post)" class="border-t border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-5">
        <form class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start" @submit.prevent="submitComment(post)">
          <textarea
            v-model="commentDraftByPostId[post.id]"
            rows="2"
            placeholder="Write a comment..."
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
          ></textarea>
          <button
            type="submit"
            :disabled="commentSubmittingPostId === post.id"
            class="inline-flex w-full items-center justify-center rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60 sm:w-auto"
          >
            {{ commentSubmittingPostId === post.id ? 'Posting...' : 'Post' }}
          </button>
        </form>

        <p v-if="commentsLoadingByPostId[post.id]" class="text-xs text-slate-500">Loading comments...</p>

        <div v-else-if="!(commentsByPostId[post.id] || []).length" class="text-xs text-slate-500">
          No comments yet.
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="comment in commentsByPostId[post.id]"
            :key="comment.id"
            class="rounded-lg border border-slate-200 bg-white p-3"
          >
            <div class="mb-1 flex items-center justify-between gap-2">
              <p class="text-xs font-semibold text-slate-700">{{ comment.user?.name || 'Unknown user' }}</p>
              <button
                v-if="canDeleteComment(comment)"
                type="button"
                @click="deleteComment(post, comment.id)"
                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700"
                aria-label="Delete comment"
                title="Delete comment"
              >
                <svg
                  viewBox="0 0 24 24"
                  class="h-3.5 w-3.5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  aria-hidden="true"
                >
                  <path d="M3 6h18" />
                  <path d="M8 6V4h8v2" />
                  <path d="M19 6l-1 14H6L5 6" />
                  <path d="M10 11v6" />
                  <path d="M14 11v6" />
                </svg>
              </button>
            </div>
            <p class="whitespace-pre-line break-words text-sm text-slate-700">{{ comment.content }}</p>
          </div>
        </div>
      </div>
    </article>

    <div
      v-if="!filteredPosts.length"
      class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-center sm:p-10"
    >
      <p class="text-sm font-semibold text-slate-700">
        {{ posts.length ? 'No matching posts found.' : 'No posts yet.' }}
      </p>
      <p class="mt-1 text-sm text-slate-500">
        {{ posts.length ? 'Try another keyword in search.' : 'Start the first conversation.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const props = defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: null,
  },
})


const emit = defineEmits(['refreshPosts'])
const searchQuery = ref('')
const deletedPostIds = ref([])
const editingPostId = ref(null)
const editTitle = ref('')
const editContent = ref('')
const isSavingEdit = ref(false)
const likeSubmittingPostId = ref(null)
const commentSubmittingPostId = ref(null)
const commentDraftByPostId = ref({})
const commentsByPostId = ref({})
const commentsOpenByPostId = ref({})
const commentsLoadingByPostId = ref({})
const likesCountByPostId = ref({})
const commentsCountByPostId = ref({})
const likedByPostId = ref({})
const filteredPosts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const visiblePosts = props.posts.filter((post) => !deletedPostIds.value.includes(post.id))
  if (!query) return visiblePosts

  return visiblePosts.filter((post) => {
    const title = (post?.title || '').toLowerCase()
    const content = (post?.content || '').toLowerCase()
    return title.includes(query) || content.includes(query)
  })
})

const deletePost = async (id) => {
  if (!confirm('Are you sure you want to delete this post?')) return

  try {
    await api.delete(`/posts/${id}`)
    deletedPostIds.value = [...deletedPostIds.value, id]
    emit('refreshPosts')
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to delete post.')
  }
}

const canDelete = (post) => {
  const currentUserId = Number(props.currentUser?.id)
  const ownerId = Number(post?.user_id ?? post?.user?.id)
  return Number.isFinite(currentUserId) && Number.isFinite(ownerId) && currentUserId === ownerId
}

const canDeleteComment = (comment) => {
  const currentUserId = Number(props.currentUser?.id)
  return Number.isFinite(currentUserId) && currentUserId === Number(comment?.user_id)
}

const formatDate = (value) => {
  if (!value) return 'Unknown time'
  return new Date(value).toLocaleString()
}

const startEdit = (post) => {
  editingPostId.value = post.id
  editTitle.value = post?.title || ''
  editContent.value = post?.content || ''
}

const cancelEdit = () => {
  editingPostId.value = null
  editTitle.value = ''
  editContent.value = ''
}

const saveEdit = async (post) => {
  if (!editContent.value.trim()) {
    alert('Post content is required.')
    return
  }

  isSavingEdit.value = true
  try {
    await api.put(`/posts/${post.id}`, {
      title: editTitle.value.trim(),
      content: editContent.value.trim(),
    })

    post.title = editTitle.value.trim()
    post.content = editContent.value.trim()
    cancelEdit()
    emit('refreshPosts')
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to update post.')
  } finally {
    isSavingEdit.value = false
  }
}

const isPostLiked = (post) => {
  if (Object.prototype.hasOwnProperty.call(likedByPostId.value, post.id)) {
    return Boolean(likedByPostId.value[post.id])
  }
  return Boolean(post?.liked_by_me)
}

const getLikesCount = (post) => {
  if (Object.prototype.hasOwnProperty.call(likesCountByPostId.value, post.id)) {
    return Number(likesCountByPostId.value[post.id]) || 0
  }
  return Number(post?.likes_count) || 0
}

const getCommentsCount = (post) => {
  if (Object.prototype.hasOwnProperty.call(commentsCountByPostId.value, post.id)) {
    return Number(commentsCountByPostId.value[post.id]) || 0
  }
  return Number(post?.comments_count) || 0
}

const toggleLike = async (post) => {
  likeSubmittingPostId.value = post.id
  try {
    const response = await api.post(`/posts/${post.id}/like`)
    likedByPostId.value[post.id] = Boolean(response.data?.liked)
    likesCountByPostId.value[post.id] = Number(response.data?.likes_count || 0)
    post.likes_count = likesCountByPostId.value[post.id]
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to toggle like.')
  } finally {
    likeSubmittingPostId.value = null
  }
}

const isCommentsOpen = (post) => Boolean(commentsOpenByPostId.value[post.id])

const loadComments = async (post) => {
  commentsLoadingByPostId.value[post.id] = true
  try {
    const response = await api.get(`/posts/${post.id}/comments`)
    commentsByPostId.value[post.id] = response.data?.data || []
    commentsCountByPostId.value[post.id] = Number(response.data?.comments_count || commentsByPostId.value[post.id].length)
    post.comments_count = commentsCountByPostId.value[post.id]
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to load comments.')
  } finally {
    commentsLoadingByPostId.value[post.id] = false
  }
}

const toggleComments = async (post) => {
  const currentlyOpen = Boolean(commentsOpenByPostId.value[post.id])
  commentsOpenByPostId.value[post.id] = !currentlyOpen
  if (currentlyOpen) return
  if (Array.isArray(commentsByPostId.value[post.id])) return
  await loadComments(post)
}

const submitComment = async (post) => {
  const content = String(commentDraftByPostId.value[post.id] || '').trim()
  if (!content) return

  commentSubmittingPostId.value = post.id
  try {
    const response = await api.post(`/posts/${post.id}/comments`, { content })
    const createdComment = response.data?.comment

    if (!Array.isArray(commentsByPostId.value[post.id])) {
      commentsByPostId.value[post.id] = []
    }

    if (createdComment) {
      commentsByPostId.value[post.id] = [createdComment, ...commentsByPostId.value[post.id]]
    }

    commentDraftByPostId.value[post.id] = ''
    commentsCountByPostId.value[post.id] = Number(response.data?.comments_count || commentsByPostId.value[post.id].length)
    post.comments_count = commentsCountByPostId.value[post.id]
    commentsOpenByPostId.value[post.id] = true
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to add comment.')
  } finally {
    commentSubmittingPostId.value = null
  }
}

const deleteComment = async (post, commentId) => {
  try {
    const response = await api.delete(`/comments/${commentId}`)
    commentsByPostId.value[post.id] = (commentsByPostId.value[post.id] || []).filter((comment) => comment.id !== commentId)
    commentsCountByPostId.value[post.id] = Number(response.data?.comments_count || commentsByPostId.value[post.id].length)
    post.comments_count = commentsCountByPostId.value[post.id]
  } catch (error) {
    console.error(error.response?.data || error)
    alert(error.response?.data?.message || 'Failed to delete comment.')
  }
}

const getMediaSrc = (media) => media?.media_url || media?.file_path || ''

const getMediaType = (media) => {
  const explicitType = String(media?.type || '').toLowerCase()
  if (explicitType === 'image' || explicitType === 'video') return explicitType

  const src = getMediaSrc(media).toLowerCase()
  if (/\.(mp4|mov|avi|webm|mkv)(\?|#|$)/.test(src)) return 'video'
  if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|#|$)/.test(src)) return 'image'
  return ''
}

const isImageMedia = (media) => getMediaType(media) === 'image'
const isVideoMedia = (media) => getMediaType(media) === 'video'
</script>
