<template>
  <Navbar />

  <main class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-50 to-white py-8">
    <div class="mx-auto max-w-6xl px-4">
      <div v-if="errorMessage" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ errorMessage }}
      </div>

      <div v-if="user" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_14px_40px_rgba(15,23,42,0.08)]">
        <div class="h-60 w-full relative">
          <img :src="coverImage" class="w-full h-full object-cover" />
        </div>

        <div class="relative px-6 pb-6">
          <div class="absolute -top-16 left-6">
            <img
              :src="user.profile?.avatar || fallbackAvatar"
              class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md"
            />
          </div>

          <div class="pt-20">
            <h1 class="text-2xl font-bold text-slate-900">{{ user.name }}</h1>

            <p class="mt-1 text-slate-600">
              {{ user.profile?.headline || user.profile?.current_job || 'Add your headline' }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
              {{ user.profile?.location || 'No location added' }}
            </p>

            <div class="mt-4 flex gap-3" v-if="isOwnProfile">
              <RouterLink
                to="/profile/edit"
                class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-cyan-700 hover:to-blue-700"
              >
                Edit Profile
              </RouterLink>
            </div>
            <div class="mt-4 flex gap-3" v-else>
              <RouterLink
                v-if="connectionStatus === 'accepted'"
                :to="`/message/${user.id}`"
                class="rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-blue-700 hover:to-indigo-700"
              >
                Message
              </RouterLink>
              <button
                v-else-if="connectionStatus === 'none'"
                @click="sendConnectionRequest"
                class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:from-cyan-700 hover:to-blue-700"
              >
                Connect
              </button>
              <span
                v-else-if="connectionStatus === 'pending'"
                class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600"
              >
                Pending Request
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="user" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">User Information</h2>

        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Name</p>
            <p class="font-medium text-slate-800">{{ user.name || 'Not provided' }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Email</p>
            <p class="font-medium text-slate-800">{{ user.email || 'Not provided' }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Phone</p>
            <p class="font-medium text-slate-800">{{ user.profile?.phone || 'Not provided' }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Current Job</p>
            <p class="font-medium text-slate-800">{{ user.profile?.current_job || 'Not provided' }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Company</p>
            <p class="font-medium text-slate-800">{{ user.profile?.company || 'Not provided' }}</p>
          </div>

          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
            <p class="text-slate-500">Graduate Year</p>
            <p class="font-medium text-slate-800">{{ user.profile?.graduate_year || 'Not provided' }}</p>
          </div>
        </div>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="mb-2 text-slate-500">Skills</p>
          <div v-if="skillsList.length" class="flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="rounded-full bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-800"
            >
              {{ skill }}
            </span>
          </div>
          <p v-else class="text-sm font-medium text-slate-800">Not provided</p>
        </div>
      </div>



      

      
      <div v-if="user && isOwnProfile" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">Change Password</h2>

        <p v-if="passwordError" class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ passwordError }}</p>
        <p v-if="passwordMessage" class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ passwordMessage }}</p>

        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">Old Password</label>
            <input
              v-model="oldPassword"
              type="password"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">New Password</label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="6"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-600">Confirm New Password</label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              minlength="6"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:from-cyan-700 hover:to-blue-700 disabled:opacity-60"
          >
            {{ passwordLoading ? 'Updating...' : 'Change Password' }}
          </button>
        </form>
      </div>
      <div v-if="user" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">
          {{ isOwnProfile ? 'My Posts' : `${user.name}'s Posts` }}
        </h2>

        <div v-if="sortedPosts.length" class="space-y-5">
          <PostCard
            v-for="post in postsWithAuthor"
            :key="post.id"
            :post="post"
            :current-user="loggedInUser"
            :comments-refresh-key="commentsRefreshKey"
            auto-open-comments
            @deleted="refreshProfilePosts"
            @refresh-posts="refreshProfilePosts"
          />
        </div>
        <p v-else class="text-sm text-slate-500">No posts yet.</p>
      </div>
      
    </div>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import PostCard from '@/components/ui/PostCard.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import defaultCover from '@/assets/images/3840x2160-white-solid-color-background.jpg'
import { setPostHubUserId, subscribeToPostEvents } from '@/utils/postHub'

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

const postsWithAuthor = computed(() =>
  sortedPosts.value.map((post) => ({
    ...post,
    user: post.user || {
      id: user.value?.id,
      name: user.value?.name,
      profile: user.value?.profile,
    },
    user_id: post.user_id || user.value?.id,
  }))
)

const loadLoggedInUser = async () => {
  try {
    const response = await api.get('/me')
    loggedInUser.value = response.data
    setPostHubUserId(loggedInUser.value?.id ?? null)
  } catch {
    loggedInUser.value = null
    setPostHubUserId(null)
  }
}

const loadProfile = async (id) => {
  errorMessage.value = ''

  if (!id) {
    user.value = null
    return
  }

  try {
    const response = await api.get(`/profiles/${id}`)
    user.value = response.data.data
    commentsRefreshKey.value += 1
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

const commentsRefreshKey = ref(0)
let unsubscribePostHub = null

const refreshProfilePosts = async () => {
  if (!route.params.id) return
  await loadProfile(route.params.id)
}

const updateProfilePostLike = (postId, payload = {}) => {
  const normalizedPostId = Number(postId)
  if (!Number.isFinite(normalizedPostId) || !Array.isArray(user.value?.posts)) return

  const normalizedLikesCount = Number(payload.likes_count ?? payload.likesCount)
  const actorUserId = Number(payload.actor_user_id ?? payload.actorUserId)
  const actorLiked = payload.liked

  const applyLikeUpdate = (post) => {
    if (!post) return post

    const nextPost = { ...post }

    if (Number.isFinite(normalizedLikesCount)) {
      nextPost.likes_count = normalizedLikesCount
    }

    if (Number.isFinite(actorUserId) && Number(loggedInUser.value?.id) === actorUserId && typeof actorLiked === 'boolean') {
      nextPost.liked_by_me = actorLiked
    }

    return nextPost
  }

  user.value = {
    ...user.value,
    posts: user.value.posts.map((post) => {
      if (Number(post.id) === normalizedPostId) {
        return applyLikeUpdate(post)
      }

      if (Number(post.shared_post?.id) === normalizedPostId) {
        return {
          ...post,
          shared_post: applyLikeUpdate(post.shared_post),
        }
      }

      return post
    }),
  }
}

const updateProfilePostComments = (postId, payload = {}) => {
  const normalizedPostId = Number(postId)
  if (!Number.isFinite(normalizedPostId) || !Array.isArray(user.value?.posts)) return

  const normalizedCommentsCount = Number(payload.comments_count ?? payload.commentsCount)

  const applyCommentUpdate = (post) => {
    if (!post) return post

    const nextPost = { ...post }

    if (Number.isFinite(normalizedCommentsCount)) {
      nextPost.comments_count = normalizedCommentsCount
    }

    return nextPost
  }

  user.value = {
    ...user.value,
    posts: user.value.posts.map((post) => {
      if (Number(post.id) === normalizedPostId) {
        return applyCommentUpdate(post)
      }

      if (Number(post.shared_post?.id) === normalizedPostId) {
        return {
          ...post,
          shared_post: applyCommentUpdate(post.shared_post),
        }
      }

      return post
    }),
  }
}

const handlePostEvent = (payload) => {
  if (!payload) return

  if (payload.type === 'post_like_updated') {
    const postId = payload.data?.post_id || payload.data?.postId
    if (postId) {
      updateProfilePostLike(postId, payload.data)
    }
    return
  }

  if (payload.type === 'post_comment_updated') {
    const postId = payload.data?.post_id || payload.data?.postId
    if (postId) {
      updateProfilePostComments(postId, payload.data)
    }
    return
  }

  if (payload.type === 'post_created' || payload.type === 'post_updated' || payload.type === 'post_deleted') {
    refreshProfilePosts()
  }
}

const refreshIntervalMs = 15000
let profileRefreshTimer = null

const stopProfileRefreshLoop = () => {
  if (profileRefreshTimer) {
    clearInterval(profileRefreshTimer)
    profileRefreshTimer = null
  }
}

const startProfileRefreshLoop = () => {
  stopProfileRefreshLoop()
  profileRefreshTimer = setInterval(async () => {
    if (route.params.id) {
      await loadProfile(route.params.id)
    }
  }, refreshIntervalMs)
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
    stopProfileRefreshLoop()
    if (id) {
      loadProfile(id)
      loadConnectionStatus(id)
      startProfileRefreshLoop()
    }
  }
)

onMounted(async () => {
  await loadLoggedInUser()
  unsubscribePostHub = subscribeToPostEvents(handlePostEvent)
  if (route.params.id) {
    await loadProfile(route.params.id)
    await loadConnectionStatus(route.params.id)
    startProfileRefreshLoop()
  }
})

onBeforeUnmount(() => {
  stopProfileRefreshLoop()
  if (unsubscribePostHub) {
    unsubscribePostHub()
    unsubscribePostHub = null
  }
})
</script>
