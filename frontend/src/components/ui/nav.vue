<template>
  <nav class="flex justify-between items-center px-8 py-4 shadow-md bg-white sticky top-0 z-50">
    <RouterLink to="/" class="text-xl font-bold text-teal-600">
      Alumni Media
    </RouterLink>

    <div class="flex items-center gap-4">
      <RouterLink
        to="/"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Home
      </RouterLink>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="px-4 py-2 rounded-lg text-gray-600 hover:bg-teal-50 hover:text-teal-600 font-medium transition"
      >
        Profile
      </RouterLink>

      <button
        @click="logout"
        class="px-4 py-2 rounded-lg text-red-500 hover:bg-red-50 font-medium transition"
      >
        Logout
      </button>

      <RouterLink
        v-if="user"
        :to="{ name: 'Profile', params: { id: user.id } }"
        class="w-11 h-11 rounded-full overflow-hidden border-2 border-teal-500"
      >
        <img
          :src="user.profile?.avatar || 'https://i.pravatar.cc/150'"
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

const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

onMounted(fetchMe)
</script>
