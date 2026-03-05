<template>
  <div class="col-span-6 space-y-5">

    <!-- Create Post -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-3 hover:shadow-md transition">
      <div class="flex items-center gap-3">
        <img
          :src="currentUser?.avatar_url || defaultAvatar"
          class="w-10 h-10 rounded-full object-cover"
        >
        <input
          v-model="postContent"
          type="text"
          placeholder="Share an update..."
          class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
        >
      </div>

      <p v-if="errorMessage" class="text-red-500 text-xs">{{ errorMessage }}</p>

      <div class="flex justify-end">
        <button
          @click="post"
          class="bg-blue-600 text-white px-4 py-1.5 rounded-full hover:bg-blue-700 transition text-sm font-medium"
        >
          Post
        </button>
      </div>
    </div>

    <!-- Mock Feed Posts -->
    <div
      v-for="post in mockPosts"
      :key="post.id"
      class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition"
    >
      <!-- Post Header -->
      <div class="flex items-center gap-3 mb-2">
        <img
          :src="post.user.avatar"
          class="w-10 h-10 rounded-full object-cover"
        >
        <div class="flex-1">
          <h4 class="text-gray-900 font-semibold text-sm">{{ post.user.name }}</h4>
          <p class="text-gray-400 text-xs">{{ post.time }}</p>
        </div>
        <button class="text-gray-400 hover:text-gray-600 transition">•••</button>
      </div>

      <!-- Post Content -->
      <p class="text-gray-700 text-sm mb-3">{{ post.content }}</p>

      <!-- Optional Post Image -->
      <img
        v-if="post.image"
        :src="post.image"
        class="w-full rounded-xl object-cover mb-3 max-h-80"
      >

      <!-- Post Actions -->
      <div class="flex justify-between text-gray-500 text-sm border-t border-gray-100 pt-2">
        <button class="flex items-center gap-1 hover:text-blue-600 transition text-xs">
          👍 Like
        </button>
        <button class="flex items-center gap-1 hover:text-blue-600 transition text-xs">
          💬 Comment
        </button>
        <button class="flex items-center gap-1 hover:text-blue-600 transition text-xs">
          🔗 Share
        </button>
        <button class="flex items-center gap-1 hover:text-red-600 transition text-xs">
          🚩 Report
        </button>
        <button class="flex items-center gap-1 hover:text-green-600 transition text-xs">
          💾 Save
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import defaultAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const currentUser = {
  avatar_url: 'https://i.pravatar.cc/100?img=12',
  name: 'Tola Dev',
}

const postContent = ref('')
const errorMessage = ref('')

// Frontend-only post handler
const post = () => {
  if (!postContent.value.trim()) {
    errorMessage.value = 'Post cannot be empty'
    return
  }
  mockPosts.unshift({
    id: mockPosts.length + 1,
    user: currentUser,
    content: postContent.value,
    time: 'Just now',
    image: null,
  })
  postContent.value = ''
  errorMessage.value = ''
}

// Mock posts data
const mockPosts = ref([
  {
    id: 1,
    user: { name: 'Jane Doe', avatar: 'https://i.pravatar.cc/100?img=3' },
    content: 'Excited to share that I just started a new role at TechCorp!',
    time: '2 hours ago',
    image: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=800&q=80',
  },
  {
    id: 2,
    user: { name: 'John Smith', avatar: 'https://i.pravatar.cc/100?img=5' },
    content: 'Had an amazing alumni meetup yesterday! #Networking',
    time: '5 hours ago',
    image: null,
  },
  {
    id: 3,
    user: { name: 'Alice Kim', avatar: 'https://i.pravatar.cc/100?img=7' },
    content: 'Check out my latest blog post on career growth!',
    time: '1 day ago',
    image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
  },
])
</script>