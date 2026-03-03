<template>
  <div class="col-span-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-4">
      <form @submit.prevent="submitPost">
        <div class="flex items-center gap-3">
          <img :src="currentUser?.profile?.avatar || 'https://i.pravatar.cc/150'" class="w-10 h-10 rounded-full object-cover">
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
            type="submit"
            :disabled="isPosting"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-60"
          >
            {{ isPosting ? 'Posting...' : 'Post' }}
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
        <img :src="post.user?.profile?.avatar || 'https://i.pravatar.cc/51'" class="w-10 h-10 rounded-full object-cover">
        <div>
          <h4 class="font-semibold">{{ post.user?.name || 'Unknown user' }}</h4>
          <p class="text-xs text-gray-500">{{ formatDate(post.created_at) }} • Public</p>
        </div>
      </div>

      <p class="mt-3 text-gray-700 whitespace-pre-wrap">{{ post.post_content }}</p>

      <img
        v-if="post.media?.length"
        :src="post.media[0].file_path"
        class="rounded-lg mt-4 w-full h-72 object-cover"
      >

      <div class="flex justify-between text-sm text-gray-500 mt-4">
        <span>{{ post.likes_count || 0 }} likes</span>
        <span>{{ post.comments_count || 0 }} comments</span>
      </div>
    </div>

    <p v-if="!posts.length" class="text-center text-gray-500">No posts yet. Create the first post.</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const props = defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['post-created'])

const postContent = ref('')
const isPosting = ref(false)
const errorMessage = ref('')

const submitPost = async () => {
  errorMessage.value = ''

  if (!postContent.value.trim()) {
    errorMessage.value = 'Post content is required.'
    return
  }

  isPosting.value = true
  try {
    const response = await api.post('/posts', {
      post_content: postContent.value.trim(),
    })

    emit('post-created', response.data.data)
    postContent.value = ''
  } catch {
    errorMessage.value = 'Failed to create post. Please try again.'
  } finally {
    isPosting.value = false
  }
}

const formatDate = (value) => {
  if (!value) return 'Unknown time'
  return new Date(value).toLocaleString()
}
</script>
