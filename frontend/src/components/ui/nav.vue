<template>
  <nav class="flex justify-between items-center px-8 py-4 shadow-md bg-white sticky top-0 z-50">
    <RouterLink to="/" class="text-xl font-bold text-teal-600">
      Alumni Media
    </RouterLink>

    <div class="flex items-center gap-4">
      <RouterLink
        to="/"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-house"></i>
        <span class="font-sm">
          Home
        </span>
      </RouterLink>

      <RouterLink
        to="/connection"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-people-arrows"></i>
        <span class="font-sm">
          Connection
        </span>
      </RouterLink>

      <RouterLink
        to="/message"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-message"></i>
        <span class="font-sm">
          Message
        </span>
      </RouterLink>
      
      <!-- Notification -->
      <RouterLink
        to="/notification"
        class="relative flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-bell"></i>
        <span class="text-sm">Notification</span>

        <!-- Badge -->
        <span
          v-if="notifications.length > 0"
          class="absolute top-0 right-1 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full"
        >
          {{ notifications.length }}
        </span>
      </RouterLink>


      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500"
      >
        <img
          :src="user.profile?.avatar"
          alt="User Profile"
          class="w-full h-full object-cover"
        >
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const user = ref(null)

const fetchMe = async () => {
  try {
    const response = await api.get('/me')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}
// State
const notifications = ref([])

// Fetch notifications
const fetchNotification = async () => {
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  }
}

onMounted(() => {
  fetchMe()
  fetchNotification()
})
</script>
