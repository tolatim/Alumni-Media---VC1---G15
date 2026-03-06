<template>
  <div class="col-span-6 space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur">
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
          class="inline-flex items-center rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:from-cyan-700 hover:to-blue-700"
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
      <div class="border-b border-slate-100 px-5 pt-5 pb-4">
        <div class="flex items-center gap-3">
          <img :src="post.user?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover">
          <div>
            <h4 class="font-semibold text-slate-900">{{ post.user?.name || 'Unknown user' }}</h4>
            <p class="text-xs text-slate-500">{{ formatDate(post.created_at) }}</p>
          </div>
        </div>
      </div>

      <div class="space-y-4 px-5 py-4">
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
            post.media.length === 1 ? 'grid-cols-1' : 'grid-cols-2'
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

      <div class="flex items-center justify-between border-t border-slate-100 px-5 py-3">
        <p class="text-xs font-medium text-slate-500">
          {{ post.media?.length ? `${post.media.length} media` : 'Text post' }}
        </p>
        <button
          v-if="canDelete(post)"
          @click="startEdit(post)"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">Edit</span>
        </button>
        <button
          v-if="canDelete(post)"
          @click="deletePost(post.id)"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold tracking-wide text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">Delete</span>
        </button>
      </div>
    </article>

    <div
      v-if="!filteredPosts.length"
      class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"
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
