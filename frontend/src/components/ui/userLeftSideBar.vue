<template>
  <div class="col-span-3 space-y-6 sticky top-6 h-[calc(100vh-1.5rem)] overflow-y-auto pr-2">

    <!-- Profile Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-xl transition duration-300">

      <!-- Cover -->
      <div class="relative h-28 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600">
        <img
          :src="user?.profile?.avatar || fallbackAvatar"
          class="w-20 h-20 rounded-full border-4 border-white absolute -bottom-10 left-1/2 -translate-x-1/2 object-cover shadow-md"
        >
      </div>

      <!-- User Info -->
      <div class="pt-12 pb-6 px-6 text-center">
        <h3 class="font-semibold text-lg text-gray-800">
          {{ user?.first_name || 'Guest User' }}
        </h3>

        <p class="text-sm text-gray-500 mt-1">
          {{ user?.profile?.headline || user?.profile?.current_job || 'Welcome to Alumni Media' }}
        </p>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="inline-block mt-4 text-sm font-semibold text-blue-600 hover:text-blue-700 transition"
        >
          View Profile →
        </RouterLink>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-100"></div>

      <!-- Menu -->
      <div class="py-4 px-6 space-y-3 text-sm">

        <RouterLink
          to="/connection"
          class="flex items-center justify-between text-gray-600 hover:text-blue-600 transition"
        >
          <span>Connections</span>
          <span class="text-gray-400">→</span>
        </RouterLink>

        <p class="flex items-center justify-between text-gray-600 hover:text-blue-600 cursor-pointer transition">
          <span>Groups</span>
          <span class="text-gray-400">→</span>
        </p>

        <p class="flex items-center justify-between text-gray-600 hover:text-blue-600 cursor-pointer transition">
          <span>Events</span>
          <span class="text-gray-400">→</span>
        </p>

      </div>

      <!-- Divider -->
      <div class="border-t border-gray-100"></div>

      <!-- Logout -->
      <div class="px-6 py-4">
        <p
          @click="logout"
          class="text-sm text-red-600 font-semibold cursor-pointer hover:text-red-700 transition"
        >
          Logout
        </p>
      </div>

    </div>


    <!-- Quick Links -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-xl transition duration-300">

      <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">
        Quick Links
      </h4>

      <div class="space-y-3 text-sm">

        <RouterLink
          to="/groups"
          class="flex justify-between items-center text-gray-600 hover:text-blue-600 transition"
        >
          Groups
          <span class="text-gray-400">→</span>
        </RouterLink>

        <RouterLink
          to="/events"
          class="flex justify-between items-center text-gray-600 hover:text-blue-600 transition"
        >
          Events
          <span class="text-gray-400">→</span>
        </RouterLink>

        <RouterLink
          to="/connections"
          class="flex justify-between items-center text-gray-600 hover:text-blue-600 transition"
        >
          Connections
          <span class="text-gray-400">→</span>
        </RouterLink>

      </div>

    </div>

  </div>
</template>

<script setup>
import { useRouter } from "vue-router"
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

defineProps({
  user: {
    type: Object,
    default: null,
  },
})
const router = useRouter()
const logout = () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
  router.push("/login"); 
};
</script>
