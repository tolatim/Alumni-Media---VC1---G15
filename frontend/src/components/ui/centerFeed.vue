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

        <div class="flex justify-end mt-4">
          <button
            @click="post"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-60"
          >
            Post
          </button>
        </div>
      </form>
    </div>

    <div
      v-for="post in posts"
      :key="post.id"
      class="bg-white rounded-xl shadow p-4"
    >
      <div class="flex items-center gap-3">
        <img :src="user?.data?.avatar_url || defaultAvatar" class="w-10 h-10 rounded-full object-cover">
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'
import CreatePostModal from '@/components/CreatePostModal.vue'
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import router from '@/router'
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
const post = () => {
  router.push('/post')
}
</script>
