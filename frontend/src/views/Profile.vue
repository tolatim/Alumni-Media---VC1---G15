<template>
  <Navbar />

  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
      <div v-if="errorMessage" class="bg-red-50 text-red-600 rounded-lg p-4 mb-4">
        {{ errorMessage }}
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow overflow-hidden">
        <div class="h-60 w-full relative">
          <img :src="coverImage" class="w-full h-full object-cover">
        </div>

        <div class="px-6 pb-6 relative">
          <div class="absolute -top-16 left-6">
            <img :src="user.profile?.avatar || fallbackAvatar" class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md">
          </div>

          <div class="pt-20">
            <h1 class="text-2xl font-bold text-gray-800">{{ user.name }}</h1>

            <p class="text-gray-500 mt-1">
              {{ user.profile?.headline || user.profile?.current_job || 'Add your headline' }}
            </p>

            <p class="text-sm text-gray-400 mt-1">
              {{ user.profile?.location || 'No location added' }}
            </p>

            <div class="flex gap-4 mt-4" v-if="isOwnProfile">
              <RouterLink
                to="/profile/edit"
                class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Edit Profile
              </RouterLink>
            </div>
            <div class="flex gap-4 mt-4" v-else>
              <RouterLink
                v-if="connectionStatus === 'accepted'"
                :to="`/message/${user.id}`"
                class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Message
              </RouterLink>
              <button
                v-else-if="connectionStatus === 'none'"
                @click="sendConnectionRequest"
                class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Connect
              </button>
              <span
                v-else-if="connectionStatus === 'pending'"
                class="text-sm px-4 py-2 rounded-lg bg-gray-100 text-gray-600"
              >
                Pending Request
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">User Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Name</p>
            <p class="text-gray-800 font-medium">{{ user.name || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Email</p>
            <p class="text-gray-800 font-medium">{{ user.email || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Phone</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.phone || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Current Job</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.current_job || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Company</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.company || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Graduate Year</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.graduate_year || 'Not provided' }}</p>
          </div>
        </div>

        <div class="mt-4 bg-gray-50 rounded-lg p-3">
          <p class="text-gray-500 mb-2">Skills</p>
          <div v-if="skillsList.length" class="flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="text-xs bg-teal-100 text-teal-800 px-2 py-1 rounded-full"
            >
              {{ skill }}
            </span>
          </div>
          <p v-else class="text-gray-800 font-medium text-sm">Not provided</p>
        </div>
      </div>



      <div v-if="user" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">
          {{ isOwnProfile ? 'My Posts' : `${user.name}'s Posts` }}
        </h2>

        <div v-if="sortedPosts.length" class="space-y-4">
          <article
            v-for="post in sortedPosts"
            :key="post.id"
            class="rounded-lg border border-gray-200 p-4"
          >
            <h3 v-if="editingPostId !== post.id && post.title" class="text-base font-semibold text-gray-800">
              {{ post.title }}
            </h3>
            <div v-if="editingPostId === post.id" class="space-y-2">
              <input
                v-model="editTitle"
                type="text"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                placeholder="Post title"
              />
              <textarea
                v-model="editContent"
                rows="4"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                placeholder="Post content"
              />
              <div class="flex gap-2">
                <button
                  @click="savePostEdit(post.id)"
                  :disabled="postActionLoading"
                  class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700 disabled:opacity-60"
                >
                  Save
                </button>
                <button
                  @click="cancelPostEdit"
                  :disabled="postActionLoading"
                  class="rounded-md bg-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-300 disabled:opacity-60"
                >
                  Cancel
                </button>
              </div>
            </div>


            <p v-else class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ post.content }}</p>
            <div class="mt-2 flex items-center justify-between">
              <p class="text-xs text-gray-500">{{ formatPostDate(post.created_at) }}</p>
              <div v-if="isOwnProfile" class="flex gap-2">
                <button
                  @click="startPostEdit(post)"
                  :disabled="postActionLoading"
                  class="rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 disabled:opacity-60"
                >
                  Edit
                </button>
                <button
                  @click="deletePost(post.id)"
                  :disabled="postActionLoading"
                  class="rounded-md bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-60"
                >
                  Delete
                </button>
              </div>

              
            </div>
          </article>
        </div>
        
        <p v-else class="text-sm text-gray-500">No posts yet.</p>
      </div>

      
      <div v-if="user && isOwnProfile" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>

        <p v-if="passwordError" class="text-sm text-red-500 mb-3">{{ passwordError }}</p>
        <p v-if="passwordMessage" class="text-sm text-green-600 mb-3">{{ passwordMessage }}</p>

        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Old Password</label>
            <input
              v-model="oldPassword"
              type="password"
              required
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">New Password</label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="6"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Confirm New Password</label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              minlength="6"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 rounded-md disabled:opacity-60"
          >
            {{ passwordLoading ? 'Updating...' : 'Change Password' }}
          </button>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import defaultCover from '@/assets/images/3840x2160-white-solid-color-background.jpg'

const route = useRoute()
const user = ref(null)
const errorMessage = ref('')
const loggedInUser = ref(null)
const connectionStatus = ref('none')

const oldPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const passwordLoading = ref(false)
const passwordError = ref('')
const passwordMessage = ref('')
const editingPostId = ref(null)
const editTitle = ref('')
const editContent = ref('')
const postActionLoading = ref(false)

const coverImage = computed(() => user.value?.profile?.cover || defaultCover)

const isOwnProfile = computed(() => loggedInUser.value?.id === user.value?.id)

const skillsList = computed(() => {
  const skills = user.value?.profile?.skills
  if (!skills) return []
  return skills
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
})

const sortedPosts = computed(() => {
  const posts = user.value?.posts || []
  return [...posts].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const formatPostDate = (value) => {
  if (!value) return 'Unknown time'
  return new Date(value).toLocaleString()
}

const startPostEdit = (post) => {
  editingPostId.value = post.id
  editTitle.value = post.title || ''
  editContent.value = post.content || ''
}

const cancelPostEdit = () => {
  editingPostId.value = null
  editTitle.value = ''
  editContent.value = ''
}

const savePostEdit = async (postId) => {
  if (!editContent.value.trim()) {
    errorMessage.value = 'Post content is required.'
    return
  }

  postActionLoading.value = true
  try {
    await api.put(`/posts/${postId}`, {
      title: editTitle.value.trim(),
      content: editContent.value.trim(),
    })

    const post = user.value?.posts?.find((item) => item.id === postId)
    if (post) {
      post.title = editTitle.value.trim()
      post.content = editContent.value.trim()
    }

    cancelPostEdit()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to update post.'
  } finally {
    postActionLoading.value = false
  }
}

const deletePost = async (postId) => {
  if (!confirm('Are you sure you want to delete this post?')) return

  postActionLoading.value = true
  try {
    await api.delete(`/posts/${postId}`)
    if (user.value?.posts) {
      user.value.posts = user.value.posts.filter((item) => item.id !== postId)
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to delete post.'
  } finally {
    postActionLoading.value = false
  }
}

const loadLoggedInUser = async () => {
  try {
    const response = await api.get('/me')
    loggedInUser.value = response.data
  } catch {
    loggedInUser.value = null
  }
}

const loadProfile = async (id) => {
  errorMessage.value = ''

  try {
    const response = await api.get(`/profiles/${id}`)
    user.value = response.data.data
  } catch (error) {
    const own = loggedInUser.value?.id && String(loggedInUser.value.id) === String(id)

    if (own) {
      user.value = loggedInUser.value
      return
    }

    user.value = null
    errorMessage.value = error?.response?.data?.message || 'Profile not found or failed to load.'
  }
}

const loadConnectionStatus = async (id) => {
  if (!loggedInUser.value?.id || String(loggedInUser.value.id) === String(id)) {
    connectionStatus.value = 'self'
    return
  }

  try {
    const response = await api.get(`/connections/status/${id}`)
    connectionStatus.value = response.data?.data?.status || 'none'
  } catch {
    connectionStatus.value = 'none'
  }
}

const sendConnectionRequest = async () => {
  if (!user.value?.id) return

  try {
    await api.post('/connections/request', { user_id: user.value.id })
    connectionStatus.value = 'pending'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send connection request.'
  }
}

const changePassword = async () => {
  passwordError.value = ''
  passwordMessage.value = ''

  if (newPassword.value !== newPasswordConfirmation.value) {
    passwordError.value = 'New password confirmation does not match.'
    return
  }

  passwordLoading.value = true
  try {
    await api.post('/profile/change-password', {
      old_password: oldPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    })

    oldPassword.value = ''
    newPassword.value = ''
    newPasswordConfirmation.value = ''
    passwordMessage.value = 'Password changed successfully.'
  } catch (error) {
    passwordError.value = error.response?.data?.message || 'Failed to change password.'
  } finally {
    passwordLoading.value = false
  }
}

watch(
  () => route.params.id,
  (id) => {
    if (id) {
      loadProfile(id)
      loadConnectionStatus(id)
    }
  }
)

onMounted(async () => {
  await loadLoggedInUser()
  if (route.params.id) {
    await loadProfile(route.params.id)
    await loadConnectionStatus(route.params.id)
  }
})
</script>
