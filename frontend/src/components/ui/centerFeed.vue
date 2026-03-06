<template>
  <div class="col-span-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-4">
      <div class="flex items-center gap-3">
        <img :src="currentUser?.profile?.avatar || fallbackAvatar" class="w-10 h-10 rounded-full object-cover">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search posts..."
          class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder:text-gray-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
        >
      </div>

      <div class="mt-4 flex items-center justify-end">
        <RouterLink
          to="/post"
          class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
        >
          Create Post
        </RouterLink>
      </div>
    </div>

    <article
      v-for="post in filteredPosts"
      :key="post.id"
      class="bg-white rounded-2xl border border-gray-200/70 shadow-sm p-5"
    >
      <div class="flex items-center gap-3">
        <img :src="post.user?.profile?.avatar || fallbackAvatar" class="w-10 h-10 rounded-full object-cover">
        <div>
          <h4 class="font-semibold">{{ post.user?.name || 'Unknown user' }}</h4>
          <p class="text-xs text-gray-500">{{ formatDate(post.created_at) }}</p>
        </div>
      </div>

      <h4 class="mt-4 text-lg font-semibold text-gray-900 break-words">
        {{ post.title }}
      </h4>
      <p class="mt-2 whitespace-pre-line break-words text-gray-700 leading-relaxed">
        {{ post.content }}
      </p>

      <div class="mt-5 flex items-center justify-end gap-2">
        <button
          v-if="canDelete(post)"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-semibold tracking-wide text-blue-700 transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">Edit</span>
        </button>
        <button
          v-if="canDelete(post)"
          @click="deletePost(post.id)"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-[11px] font-semibold tracking-wide text-red-700 transition hover:-translate-y-0.5 hover:border-red-300 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">Delete</span>
        </button>
      </div>
    </article>

    <div
      v-if="!filteredPosts.length"
      class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-gray-500"
    >
      {{ posts.length ? 'No matching posts found.' : 'No posts yet. Start the first conversation.' }}
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
</script>
