<template>
  <Navbar />
  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-12 gap-6">
      <userLeftSideBar :user="currentUser" />

      <centerFeed
        :posts="posts"
        :current-user="currentUser"
        @post-created="prependPost"
      />

      <userRightSideBar :suggestions="suggestions" />
    </div>

    <p v-if="errorMessage" class="text-center text-red-500 mt-4">{{ errorMessage }}</p>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Navbar from '@/components/ui/nav.vue'
import userLeftSideBar from '@/components/ui/userLeftSideBar.vue'
import centerFeed from '@/components/ui/centerFeed.vue'
import userRightSideBar from '@/components/ui/userRightSideBar.vue'
import api from '@/services/api'

const currentUser = ref(null)
const posts = ref([])
const suggestions = ref([])
const errorMessage = ref('')

const loadHomeData = async () => {
  errorMessage.value = ''

  const [meRes, feedRes, suggestionRes] = await Promise.allSettled([
    api.get('/me'),
    api.get('/feed'),
    api.get('/users/suggestions'),
  ])

  if (meRes.status === 'fulfilled') {
    currentUser.value = meRes.value.data
    localStorage.setItem('user', JSON.stringify(meRes.value.data))
  } else {
    currentUser.value = null
    errorMessage.value = meRes.reason?.response?.data?.message || 'Failed to load your account.'
    return
  }

  if (feedRes.status === 'fulfilled') {
    posts.value = feedRes.value.data?.data || []
  } else {
    posts.value = []
  }

  if (suggestionRes.status === 'fulfilled') {
    suggestions.value = suggestionRes.value.data?.data || []
  } else {
    suggestions.value = []
  }

  if (feedRes.status === 'rejected' || suggestionRes.status === 'rejected') {
    errorMessage.value = 'Some home sections failed to load.'
  }
}

const prependPost = (newPost) => {
  posts.value = [newPost, ...posts.value]
}

onMounted(loadHomeData)
</script>
