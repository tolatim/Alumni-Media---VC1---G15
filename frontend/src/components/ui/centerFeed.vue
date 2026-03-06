<template>
  <div class="col-span-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-4">
      <form @submit.prevent="submitPost">
        <div class="flex items-center gap-3">
          <img :src="currentUser?.avatar_url || defaultAvatar" class="w-10 h-10 rounded-full object-cover">
          <input
            v-model="postContent"
            type="text"
            placeholder="Share an update with your alumni network..."
            class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none"
          >
        </div>

        <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
      </form>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: null,
  },
})
</script>
