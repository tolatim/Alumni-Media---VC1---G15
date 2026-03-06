<template>
  <nav class="flex justify-between items-center px-8 py-4 shadow-md bg-white sticky top-0 z-50">
    <RouterLink to="/" class="text-xl font-bold text-teal-600">
      Alumni Media
    </RouterLink>

    <div class="flex items-center gap-4">
      <RouterLink
        to="/"
        :class="navClass('/')"
      >
        <i class="fa-solid fa-house"></i>
        Home
      </RouterLink>

      <RouterLink
        to="/connection"
        :class="navClass('/connection')"
      >
        <i class="fa-solid fa-user-group"></i>
        Connection
      </RouterLink>

      <RouterLink
        to="/message"
        :class="navClass('/message')"
      >
        <i class="fa-solid fa-message"></i>
        Message
      </RouterLink>
      
      <RouterLink
        to="/notification"
        :class="navClass('/notification')"
      >
        <i class="fa-solid fa-bell"></i>
        Notification
      </RouterLink>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500"
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
import { useRoute } from 'vue-router'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const route = useRoute()
const user = ref(null)

const navClass = (prefix) => {
  const base = 'px-4 py-2 rounded-lg font-medium transition flex flex-col items-center gap-1'
  const isActive = route.path === prefix || route.path.startsWith(`${prefix}/`)
  return isActive
    ? `${base} bg-teal-100 text-teal-700`
    : `${base} text-gray-600 hover:bg-teal-50 hover:text-teal-600`
}

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
