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

      <userRightSideBar
        :suggestions="suggestions"
        :pending-requests="pendingRequests"
        @send-request="sendConnectionRequest"
        @accept-request="acceptConnectionRequest"
        @reject-request="rejectConnectionRequest"
      />
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
const pendingRequests = ref([])
const errorMessage = ref('')

const loadHomeData = async () => {
  errorMessage.value = ''

  const [meRes, feedRes, suggestionRes, pendingRes] = await Promise.allSettled([
    api.get('/me'),
    api.get('/feed'),
    api.get('/users/suggestions'),
    api.get('/connections/pending'),
  ])

  if (meRes.status === 'fulfilled') {
    currentUser.value = meRes.value.data
    localStorage.setItem('user', JSON.stringify(meRes.value.data))
  } else {
    currentUser.value = null
    errorMessage.value = meRes.reason?.response?.data?.message || 'Failed to load your account.'
    return
  }

  posts.value = feedRes.status === 'fulfilled' ? (feedRes.value.data?.data || []) : []
  suggestions.value = suggestionRes.status === 'fulfilled' ? (suggestionRes.value.data?.data || []) : []
  pendingRequests.value = pendingRes.status === 'fulfilled' ? (pendingRes.value.data?.data || []) : []

  if (feedRes.status === 'rejected' || suggestionRes.status === 'rejected' || pendingRes.status === 'rejected') {
    errorMessage.value = 'Some home sections failed to load.'
  }
}

const prependPost = (newPost) => {
  posts.value = [newPost, ...posts.value]
}

const sendConnectionRequest = async (userId) => {
  try {
    await api.post('/connections/request', { user_id: userId })
    suggestions.value = suggestions.value.filter((person) => person.id !== userId)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send connection request.'
  }
}

const acceptConnectionRequest = async (requestId) => {
  try {
    await api.post(`/connections/${requestId}/accept`)
    pendingRequests.value = pendingRequests.value.filter((request) => request.id !== requestId)
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to accept request.'
  }
}

const rejectConnectionRequest = async (requestId) => {
  try {
    await api.post(`/connections/${requestId}/reject`)
    pendingRequests.value = pendingRequests.value.filter((request) => request.id !== requestId)
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to reject request.'
  }
}

const refreshSuggestions = async () => {
  try {
    const response = await api.get('/users/suggestions')
    suggestions.value = response.data?.data || []
  } catch {
    suggestions.value = []
  }
}

onMounted(loadHomeData)
</script>
