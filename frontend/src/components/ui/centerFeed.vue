<template>
  <div class="col-span-6 space-y-6">

    <!-- Create Post -->
    <div class="bg-white rounded-xl shadow p-4">
      <form @submit.prevent="submitPost">
        <div class="flex items-center gap-3">
          <img
            :src="currentUser?.avatar_url || defaultAvatar"
            class="w-10 h-10 rounded-full object-cover"
          />
          <input
            v-model="postContent"
            type="text"
            placeholder="Share an update with your alumni network..."
            class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none"
          />
        </div>
        <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
        <div class="flex justify-end mt-4">
          <button
            type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
          >
            Post
          </button>
        </div>
      </form>
    </div>

    <!-- Posts -->
    <div v-for="post in posts" :key="post.id" class="bg-white rounded-xl shadow p-4">
      <h4 class="font-semibold">{{ post.title }}</h4>
      <p class="text-gray-700 mt-2">{{ post.content }}</p>
      <p class="text-xs text-gray-500 mt-2">{{ formatDate(post.created_at) }}</p>
    </div>

    <!-- loading -->
    <p v-if="loading" class="text-center text-gray-500">Loading more posts...</p>

    <!-- Infinite scroll trigger -->
    <div ref="loadTrigger" class="h-2"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue"
import api from "@/services/api"
import defaultAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp"

const posts = ref([])
const page = ref(1)
const lastPage = ref(null)
const loading = ref(false)

const postContent = ref("")
const errorMessage = ref("")
const loadTrigger = ref(null)

const props = defineProps({
  currentUser: Object
})

function formatDate(date){
  return new Date(date).toLocaleString()
}

// Load posts
const loadPosts = async () => {
  if(loading.value) return
  if(lastPage.value && page.value > lastPage.value) return

  loading.value = true
  try {
    const res = await api.get(`/posts?page=${page.value}`)
    posts.value.push(...res.data.data)
    lastPage.value = res.data.last_page
    page.value++
  } catch (err) {
    console.error(err)
  }
  loading.value = false
}

// Submit new post
const submitPost = async () => {
  if(!postContent.value.trim()){
    errorMessage.value = "Post cannot be empty"
    return
  }

  try {
    const res = await api.post("/posts", {
      title: postContent.value,
      content: postContent.value
    })
    posts.value.unshift(res.data.post)
    postContent.value = ""
  } catch (err) {
    console.error(err)
    errorMessage.value = "Failed to post"
  }
}

// Infinite scroll
onMounted(async () => {
  await loadPosts()

  nextTick(() => {
    const observer = new IntersectionObserver((entries) => {
      if(entries[0].isIntersecting){
        loadPosts()
      }
    }, { rootMargin: "200px" })

    if(loadTrigger.value){
      observer.observe(loadTrigger.value)
    }
  })
})
</script>