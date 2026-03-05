<template>
  <div class="col-span-6 space-y-5">
    <div class="bg-white rounded-2xl border border-gray-200/70 shadow-sm p-4 sm:p-5">
      <div class="flex items-center gap-3">
        <img
          :src="currentUser?.avatar_url || defaultAvatar"
          class="w-11 h-11 rounded-full object-cover ring-2 ring-blue-100"
        >
        <div class="flex-1">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search posts..."
            class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 placeholder:text-gray-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
          >
        </div>
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
        <img
          :src="post.user?.avatar_url || currentUser?.avatar_url || defaultAvatar"
          class="w-10 h-10 rounded-full object-cover"
        >
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold text-gray-800">
            {{ post.user?.name || currentUser?.name || 'Alumni Member' }}
          </p>
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
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[11px] font-semibold tracking-wide text-blue-700 transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">✎</span>
          <span>Update</span>
        </button>
        <button
          @clcik = deletePost(id)
          class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-[11px] font-semibold tracking-wide text-red-700 transition hover:-translate-y-0.5 hover:border-red-300 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 focus-visible:ring-offset-1"
        >
          <span aria-hidden="true">🗑</span>
          <span>Delete</span>
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
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

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

const searchQuery = ref('')
const filteredPosts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return props.posts

  return props.posts.filter((post) => {
    const title = (post.title || '').toLowerCase()
    const content = (post.content || '').toLowerCase()
    return title.includes(query) || content.includes(query)
  })
})

function formatDate(date) {
  return new Date(date).toLocaleString()
}
</script>
