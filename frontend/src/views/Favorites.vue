<template>
  <Navbar />
  <main class="min-h-screen bg-transparent py-6 md:py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-5">
      <section class="mb-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <h1 class="text-lg font-semibold text-slate-900">My Favorites</h1>
        <p class="mt-1 text-sm text-slate-500">
          Posts you saved to read again.
        </p>
      </section>

      <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">
        Loading favorites...
      </div>

      <div v-else-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-700">
        {{ errorMessage }}
      </div>

      <div v-else-if="!posts.length" class="rounded-2xl border border-slate-200 bg-white px-5 py-10 text-center">
        <p class="text-sm font-medium text-slate-700">No favorite posts yet.</p>
        <p class="mt-1 text-xs text-slate-500">Use the bookmark button on a post to add it here.</p>
      </div>

      <div v-else class="space-y-4">
        <PostCard
          v-for="post in posts"
          :key="post.id"
          :post="post"
          :current-user="currentUser"
          @deleted="handlePostDeleted"
          @favorite-changed="handleFavoriteChanged"
          @refresh-posts="loadFavorites"
        />
      </div>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Navbar from '@/components/ui/nav.vue'
import PostCard from '@/components/ui/PostCard.vue'
import api from '@/services/api'

const currentUser = ref(null)
const posts = ref([])
const loading = ref(false)
const errorMessage = ref('')

const loadCurrentUser = async () => {
  try {
    const response = await api.get('/me', {
      headers: { 'X-Skip-Loading': 'true' },
    })
    currentUser.value = response.data || null
  } catch {
    currentUser.value = null
  }
}

const loadFavorites = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await api.get('/favorites')
    posts.value = response.data?.data || []
  } catch (error) {
    posts.value = []
    errorMessage.value = error?.response?.data?.message || 'Failed to load favorites.'
  } finally {
    loading.value = false
  }
}

const handlePostDeleted = (postId) => {
  posts.value = posts.value.filter((post) => String(post.id) !== String(postId))
}

const handleFavoriteChanged = ({ postId, favorited, favoritesCount }) => {
  if (!favorited) {
    posts.value = posts.value.filter((post) => String(post.id) !== String(postId))
    return
  }

  posts.value = posts.value.map((post) => {
    if (String(post.id) !== String(postId)) return post
    return {
      ...post,
      favorited_by_me: true,
      favorites_count: Number(favoritesCount) || Number(post.favorites_count) || 0,
    }
  })
}

onMounted(async () => {
  await Promise.all([loadCurrentUser(), loadFavorites()])
})
</script>
