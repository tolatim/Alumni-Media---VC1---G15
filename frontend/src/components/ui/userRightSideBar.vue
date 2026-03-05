<template>
  <div class="col-span-3 space-y-6">

    <!-- Trending Section -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 shadow-sm p-5 hover:shadow-lg transition duration-300">
      <h4 class="font-semibold text-gray-800 mb-3 text-sm uppercase tracking-wide">Trending in Alumni</h4>

      <div class="flex flex-col gap-2 text-sm">
        <p class="font-medium text-gray-700 hover:text-blue-600 cursor-pointer transition duration-200">#AlumniUpdates</p>
        <p class="font-medium text-gray-700 hover:text-blue-600 cursor-pointer transition duration-200">#HiringNow</p>
        <p class="font-medium text-gray-700 hover:text-blue-600 cursor-pointer transition duration-200">#CareerGrowth</p>
      </div>
    </div>

    <!-- Suggested Connections -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl border border-gray-200 shadow-sm p-5 hover:shadow-lg transition duration-300">
      <h4 class="font-semibold text-gray-800 mb-4 text-sm uppercase tracking-wide">Suggested Connections</h4>

      <div class="flex flex-col gap-4">

        <div
          v-for="person in suggestions"
          :key="person.id"
          class="flex items-center justify-between"
        >
          <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex items-center gap-3 min-w-0 hover:bg-gray-50 rounded-lg transition px-2 py-1">
            <img
              :src="person.profile?.avatar || 'https://i.pravatar.cc/60'"
              class="w-10 h-10 rounded-full object-cover ring-1 ring-gray-200 shadow-sm"
            >
            <div class="min-w-0">
              <p class="text-sm font-semibold truncate text-gray-900">{{ person.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ person.profile?.headline || 'Alumni member' }}</p>
            </div>
          </RouterLink>
          <button
            class="text-xs px-3 py-1 border border-gray-300 rounded-full hover:bg-blue-50 hover:border-blue-200 hover:text-blue-600 transition font-medium"
          >
            Connect
          </button>
        </div>

        <p v-if="!suggestions.length" class="text-sm text-gray-500">No suggestions yet.</p>
      </div>
    </div>

  </div>
</template>

<script setup>
defineProps({
  suggestions: {
    type: Array,
    default: () => [],
  },
})
</script>