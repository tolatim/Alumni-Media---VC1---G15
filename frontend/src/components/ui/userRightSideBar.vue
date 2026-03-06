<template>
  <div class="col-span-3 space-y-6">
    <div class="bg-white rounded-xl shadow p-6">
      <h4 class="font-semibold mb-4">Connection Requests</h4>

      <div class="space-y-4">
        <div
          v-for="request in pendingRequests"
          :key="request.id"
          class="border rounded-lg p-3"
        >
          <RouterLink :to="{ name: 'Profile', params: { id: request.requester?.id } }" class="flex items-center gap-2 min-w-0">
            <img :src="request.requester?.profile?.avatar || 'https://i.pravatar.cc/60'" class="w-8 h-8 rounded-full object-cover">
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ request.requester?.name || 'Unknown user' }}</p>
              <p class="text-xs text-gray-500 truncate">Sent you a friend request</p>
            </div>
          </RouterLink>

          <div class="flex gap-2 mt-3">
            <button
              @click="$emit('accept-request', request.id)"
              class="text-xs px-3 py-1 rounded-full bg-teal-600 text-white hover:bg-teal-700"
            >
              Accept
            </button>
            <button
              @click="$emit('reject-request', request.id)"
              class="text-xs px-3 py-1 rounded-full border hover:bg-gray-100"
            >
              Reject
            </button>
          </div>
        </div>

        <p v-if="!pendingRequests.length" class="text-sm text-gray-500">No pending requests.</p>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
      <h4 class="font-semibold mb-4">Trending in Alumni</h4>

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
            @click="$emit('send-request', person.id)"
            class="text-xs px-3 py-1 border rounded-full hover:bg-gray-100"
          >
            Connect
          </button>
        </div>

        <p v-if="!suggestions.length" class="text-sm text-gray-500">No suggestions yet.</p>
      </div>
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
  pendingRequests: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['send-request', 'accept-request', 'reject-request'])
</script>
