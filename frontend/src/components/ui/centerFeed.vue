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

    <PostCard
      v-for="post in filteredPosts"
      :key="post.id"
      :post="post"
      :current-user="currentUser"
      @deleted="handlePostDeleted"
      @refresh-posts="emit('refreshPosts')"
    />

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
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import PostCard from '@/components/ui/PostCard.vue'

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

const handlePostDeleted = (postId) => {
  deletedPostIds.value = [...deletedPostIds.value, postId]
}
</script>
