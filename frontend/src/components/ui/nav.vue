<template>
  <nav class="flex justify-between items-center px-6 py-3 shadow-md bg-white sticky top-0 z-50 transition-all">
    <!-- Logo -->
    <RouterLink to="/" class="text-2xl font-bold text-teal-600 hover:text-teal-700 transition">
      Alumni Media
    </RouterLink>

    <!-- Navigation Links -->
    <div class="flex items-center gap-2 md:gap-4">
      <RouterLink
        to="/"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-house"></i>
        <span class="font-sm">Home</span>
      </RouterLink>

      <RouterLink
        to="/connection"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-people-arrows"></i>
        <span class="font-sm">Connection</span>
      </RouterLink>

      <RouterLink
        to="/message"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-message"></i>
        <span class="font-sm">Message</span>
      </RouterLink>

      <RouterLink
        to="/notification"
        class="flex flex-col items-center px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        <i class="fa-solid fa-bell"></i>
        <span class="font-sm">Notification</span>
      </RouterLink>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500 hover:ring-2 hover:ring-teal-400 transition"
      >
        <img
          :src="user.profile?.avatar || fallbackAvatar"
          alt="User Profile"
          class="w-full h-full object-cover"
        >
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const user = ref(null)
const activeLink = ref('home') // optional: highlight active link

const fetchMe = async () => {
  try {
    const response = await api.get('/me')
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}



onMounted(fetchMe)
</script>