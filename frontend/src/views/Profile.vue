<template>
  <Navbar />

  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
      <div v-if="errorMessage" class="bg-red-50 text-red-600 rounded-lg p-4 mb-4">
        {{ errorMessage }}
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow overflow-hidden">
        <div class="h-60 w-full relative">
          <img
            :src="coverImage"
            class="w-full h-full object-cover"
          >
        </div>

        <div class="px-6 pb-6 relative">
          <div class="absolute -top-16 left-6">
            <img
              :src="user.profile?.avatar || 'https://i.pravatar.cc/150'"
              class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md"
            >
          </div>

          <div class="pt-20">
            <h1 class="text-2xl font-bold text-gray-800">{{ user.name }}</h1>

            <p class="text-gray-500 mt-1">
              {{ user.profile?.headline || user.profile?.current_job || 'Add your headline' }}
            </p>

            <p class="text-sm text-gray-400 mt-1">
              {{ user.profile?.location || 'No location added' }}
            </p>

            <div class="flex gap-4 mt-4" v-if="isOwnProfile">
              <RouterLink
                to="/profile/edit"
                class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Edit Profile
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">About</h2>
        <p class="text-gray-600 leading-relaxed">
          {{ user.profile?.bio || 'No bio added yet.' }}
        </p>
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow mt-6 p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-semibold text-gray-800">Posts</h2>
          <span class="text-sm text-gray-500">{{ user.posts?.length || 0 }} total</span>
        </div>

        <div v-if="user.posts?.length" class="space-y-4">
          <article v-for="post in user.posts" :key="post.id" class="border rounded-lg p-4">
            <p class="text-gray-800 whitespace-pre-wrap">{{ post.post_content }}</p>
            <p class="text-xs text-gray-500 mt-3">
              {{ formatDate(post.created_at) }} • {{ post.likes_count || 0 }} likes • {{ post.comments_count || 0 }} comments
            </p>
          </article>
        </div>

        <p v-else class="text-gray-500">No posts yet.</p>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'

const route = useRoute()
const user = ref(null)
const errorMessage = ref('')
const loggedInUser = ref(null)

const coverImage = computed(() => {
  return (
    user.value?.profile?.cover ||
    'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=1600&auto=format&fit=crop'
  )
})

const isOwnProfile = computed(() => {
  return loggedInUser.value?.id === user.value?.id
})

const loadLoggedInUser = async () => {
  try {
    const response = await api.get('/me')
    loggedInUser.value = response.data
  } catch {
    loggedInUser.value = null
  }
}

const loadProfile = async (id) => {
  errorMessage.value = ''

  try {
    const response = await api.get(`/profiles/${id}`)
    user.value = response.data.data
  } catch {
    user.value = null
    errorMessage.value = 'Profile not found or failed to load.'
  }
}

const formatDate = (value) => {
  if (!value) return 'Unknown time'
  return new Date(value).toLocaleString()
}

watch(
  () => route.params.id,
  (id) => {
    if (id) loadProfile(id)
  }
)

onMounted(async () => {
  await loadLoggedInUser()
  if (route.params.id) {
    await loadProfile(route.params.id)
  }
})
</script>
