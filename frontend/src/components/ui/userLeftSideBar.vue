<template>
  <div class="col-span-3 space-y-6">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      <div class="h-20 bg-blue-800 relative">
        <img
          :src="user?.profile?.avatar || 'https://i.pravatar.cc/150'"
          class="w-20 h-20 rounded-full border-4 border-white absolute -bottom-10 left-1/2 -translate-x-1/2 object-cover"
        >
      </div>

      <div class="pt-12 pb-6 px-6 text-center">
        <h3 class="font-semibold text-lg text-gray-800">{{ user?.name || 'Guest User' }}</h3>

        <p class="text-sm text-gray-500">
          {{ user?.profile?.headline || user?.profile?.current_job || 'Welcome to Alumni Media' }}
        </p>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="inline-block mt-4 text-sm text-blue-600 hover:underline"
        >
          View profile
        </RouterLink>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
      <h4 class="font-semibold text-gray-700">Quick Links</h4>

      <div class="space-y-2 text-gray-600 text-sm">
        <p class="hover:text-blue-600 cursor-pointer transition">Groups</p>
        <p class="hover:text-blue-600 cursor-pointer transition">Events</p>
        <p class="hover:text-blue-600 cursor-pointer transition">Connections</p>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  user: {
    type: Object,
    default: null,
  },
})
</script>
