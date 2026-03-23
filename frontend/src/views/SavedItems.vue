<template>
  <Navbar />
  <main class="min-h-screen bg-slate-50/60 py-6 md:py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-5 space-y-5">
      <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <div class="flex items-center gap-3">
          <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-900 text-white shadow-sm">
            <i class="fa-solid fa-bookmark"></i>
          </span>
          <div>
            <p class="text-sm font-semibold text-slate-900">Saved items</p>
            <p class="text-xs text-slate-500">Posts you bookmarked to revisit later.</p>
          </div>
        </div>
        <span v-if="savedPosts.length" class="rounded-lg bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700">
          {{ savedPosts.length }} saved
        </span>
      </div>

      <div v-if="loading" class="grid gap-4">
        <div v-for="n in 3" :key="n" class="animate-pulse overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="h-20 bg-slate-200/60"></div>
          <div class="space-y-3 p-4">
            <div class="h-4 w-1/2 rounded bg-slate-200"></div>
            <div class="h-3 w-5/6 rounded bg-slate-200"></div>
            <div class="h-3 w-2/3 rounded bg-slate-200"></div>
          </div>
        </div>
      </div>

      <p
        v-else-if="!savedPosts.length"
        class="rounded-2xl border border-slate-200 bg-white px-4 py-6 text-center text-sm font-medium text-slate-600 shadow-sm"
      >
        You have no saved posts yet.
        <RouterLink to="/" class="ml-1 font-semibold text-cyan-700 hover:underline">Browse feed</RouterLink>
      </p>

      <div v-else class="space-y-4">
        <PostCard
          v-for="post in savedPosts"
          :key="post.id"
          :post="post"
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

const savedPosts = ref([])
const loading = ref(false)

const fetchSaved = async () => {
  loading.value = true
  try {
    const response = await api.get('/saved-posts')
    savedPosts.value = response.data?.data || response.data || []
  } catch (e) {
    savedPosts.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchSaved)
</script>
