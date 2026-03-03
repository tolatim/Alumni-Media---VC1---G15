<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useHomeStore } from '@/stores/home'   // ← we'll create this next

const router = useRouter()
const homeStore = useHomeStore()   // or use provide/inject / event bus if no Pinia

const content = ref('')
const imageFile = ref(null)
// ... your form logic, image preview, etc.

const submitPost = async () => {
  if (!content.value.trim()) {
    alert('Post content is required')
    return
  }

  // 1. Prepare data (you would normally send to backend here)
  const newPost = {
    id: Date.now().toString(),           // fake id
    content: content.value,
    image: imageFile.value ? 'fake-url-for-preview' : null,
    createdAt: new Date().toISOString(),
    user: { name: 'Current User' }        // get from auth
    // avatar, likesCount: 0, commentsCount: 0, etc.
  }

  try {
    // Real version: await api.post('/posts', formData)
    // For now – simulate success
    await new Promise(r => setTimeout(r, 800))

    // 2. Add to home feed immediately (optimistic update)
    homeStore.prependPost(newPost)

    // 3. Go back to home
    router.push('/')
    // or router.back() if you prefer
  } catch (err) {
    alert('Failed to post')
  }
}
</script>