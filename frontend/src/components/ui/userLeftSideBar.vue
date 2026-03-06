<template>
  <div class="col-span-3 space-y-6 sticky top-6 h-[calc(100vh-1.5rem)] overflow-y-auto pr-2">

    <!-- Profile Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition hover:shadow-lg">

      <!-- Cover -->
      <div class="relative h-24 bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-600">
        <img
          v-if="user?.cover_url"
          :src="user.cover_url"
          class="absolute inset-0 w-full h-full object-cover opacity-40"
        />
      </div>

      <!-- Avatar -->
      <div class="flex justify-center relative">
        <div class="-mt-12">
          <img
            :src="user?.avatar_url || defaultAvatar"
            class="w-24 h-24 rounded-full border-4 border-white shadow-lg object-cover bg-white ring-1 ring-gray-200"
          />
        </div>
      </div>

      <!-- Info -->
      <div class="px-6 pb-6 text-center">
        <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ fullName }}</h3>
        <p class="text-sm text-gray-600 mt-1">{{ user?.headline || user?.current_job || 'Alumni Member' }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ user?.location || 'Phnom Penh, Cambodia' }}</p>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="inline-block mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 transition"
        >
          View profile
        </RouterLink>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-100"></div>

      <!-- Stats -->
      <div class="px-6 py-4 text-sm space-y-2">
        <div class="flex justify-between items-center py-2 hover:bg-gray-50 px-2 rounded-lg transition cursor-pointer">
          <span class="text-gray-600">Connections</span>
          <span class="font-semibold text-blue-600">{{ user?.connections_count ?? 0 }}</span>
        </div>
        <div class="flex justify-between items-center py-2 hover:bg-gray-50 px-2 rounded-lg transition cursor-pointer">
          <span class="text-gray-600">Profile Views</span>
          <span class="font-semibold text-blue-600">{{ user?.profile_views ?? 0 }}</span>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 transition hover:shadow-lg">
      <h4 class="text-sm font-semibold text-gray-800 uppercase tracking-wide mb-4">Quick Links</h4>
      <div class="space-y-3 text-sm">
        <RouterLink to="/groups" class="block text-gray-600 hover:text-blue-600 transition">Groups</RouterLink>
        <RouterLink to="/events" class="block text-gray-600 hover:text-blue-600 transition">Events</RouterLink>
        <RouterLink to="/connections" class="block text-gray-600 hover:text-blue-600 transition">Connections</RouterLink>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const props = defineProps({
  user: { type: Object, default: null },
})

const fullName = computed(() => {
  if (!props.user) return 'Guest User'
  return `${props.user.first_name || ''} ${props.user.last_name || ''}`.trim()
})
</script>