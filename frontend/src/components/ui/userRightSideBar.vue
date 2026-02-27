<template>
  <div class="col-span-3 space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
      <h4 class="font-semibold mb-4">Trending in Alumni</h4>

      <div class="space-y-3 text-sm">
        <p class="font-medium hover:text-blue-600 cursor-pointer">#AlumniUpdates</p>
        <p class="font-medium hover:text-blue-600 cursor-pointer">#HiringNow</p>
        <p class="font-medium hover:text-blue-600 cursor-pointer">#CareerGrowth</p>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h4 class="font-semibold mb-4">Suggested Connections</h4>

      <div class="space-y-4">
        <div
          v-for="person in suggestions"
          :key="person.id"
          class="flex items-center justify-between"
        >
          <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex items-center gap-2 min-w-0">
            <img :src="person.profile?.avatar || 'https://i.pravatar.cc/60'" class="w-8 h-8 rounded-full object-cover">
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ person.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ person.profile?.headline || 'Alumni member' }}</p>
            </div>
          </RouterLink>
          <button class="text-xs px-3 py-1 border rounded-full hover:bg-gray-100">
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
