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
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition relative"
        :class="{'after:absolute after:-bottom-2 after:left-0 after:w-full after:h-1 after:bg-teal-600 after:rounded-full': activeLink === 'home'}"
      >
        Home
      </RouterLink>

      <RouterLink
        to="/connection"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Connection
      </RouterLink>

      <RouterLink
        to="/message"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Message
      </RouterLink>

      <RouterLink
        to="/notification"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Notification
      </RouterLink>

      <RouterLink
        v-if="user?.role === 'admin'"
        to="/admin"
        class="px-4 py-2 rounded-lg text-white bg-teal-600 hover:bg-teal-700 font-medium transition"
      >
        Admin
      </RouterLink>
    </div>

    <!-- Profile + Logout -->
    <div class="flex items-center gap-3">
      <button
        @click="logout"
        class="px-3 py-1.5 rounded-lg text-red-500 hover:bg-red-50 font-medium transition"
      >
        Logout
      </button>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500 hover:ring-2 hover:ring-teal-400 transition"
      >
        <img
          :src="user.profile?.avatar || defaultAvatar"
          alt="User Profile"
          class="w-full h-full object-cover"
        >
      </RouterLink>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getUser } from '@/services/authService'
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const router = useRouter()
const user = ref(null)
const activeLink = ref('home') // optional: highlight active link

const fetchMe = async () => {
  try {
    const user_id = JSON.parse(localStorage.getItem('user')?.id || null)
    if (!user_id) return
    const response = await getUser(user_id)
    user.value = response.data
    localStorage.setItem('user', JSON.stringify(response.data))
  } catch {
    user.value = null
  }
}

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

onMounted(fetchMe)
</script>