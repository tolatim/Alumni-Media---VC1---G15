<script setup>
import { ref } from 'vue'
import CreatePostModal from '@/components/CreatePostModal.vue'

const showModal = ref(false)

const openModal = () => { showModal.value = true }
const handleClose = () => { showModal.value = false }
const handlePost = (data) => {
  console.log('New post:', data)
  // → send to API, add to feed, etc.
  showModal.value = false
}
</script>

<template>
  <button @click="openModal" class="btn">Create Post</button>

  <CreatePostModal
    v-model:isOpen="showModal"
    @close="handleClose"
    @post="handlePost"
  />
</template>