<template>
  <Navbar />

  <main class="min-h-screen bg-[#f0f4f8] py-8">
    <div class="mx-auto max-w-5xl px-4 space-y-5">

      <!-- Error -->
      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ errorMessage }}
      </div>

      <!-- Hero Card -->
      <div v-if="user" class="overflow-hidden rounded-3xl bg-white shadow-lg border border-slate-100">
        <!-- Cover -->
        <div class="h-52 w-full relative">
          <img :src="coverImage" class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />
        </div>

        <!-- Avatar + Info -->
        <div class="relative px-8 pb-7">
          <div class="absolute -top-14 left-8 ring-4 ring-white rounded-full shadow-xl">
            <img
              :src="user.profile?.avatar || fallbackAvatar"
              class="w-28 h-28 rounded-full object-cover"
            />
          </div>

          <div class="pt-16 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ user.name }}</h1>
              <p class="mt-0.5 text-slate-500 font-medium">
                {{ user.profile?.headline || user.profile?.current_job || 'No headline added' }}
              </p>
              <div class="mt-2 flex items-center gap-1.5 text-sm text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                {{ user.profile?.location || 'No location added' }}
              </div>
            </div>

            <!-- Actions -->
            <div v-if="isOwnProfile">
              <RouterLink
                to="/profile/edit"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-cyan-600 hover:to-blue-700 transition-all"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z"/>
                </svg>
                Edit Profile
              </RouterLink>
            </div>
            <div v-else class="flex gap-2">
              <RouterLink
                v-if="connectionStatus === 'accepted'"
                :to="`/message/${user.id}`"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-blue-700 hover:to-indigo-700 transition-all"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
                </svg>
                Message
              </RouterLink>
              <button
                v-else-if="connectionStatus === 'none'"
                @click="sendConnectionRequest"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md hover:from-cyan-600 hover:to-blue-700 transition-all"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z"/>
                </svg>
                Connect
              </button>
              <span
                v-else-if="connectionStatus === 'pending'"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-500"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                Pending
              </span>
            </div>
          </div>

          <!-- Skills pills inline under name -->
          <div v-if="skillsList.length" class="mt-4 flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="rounded-full bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 px-3 py-1 text-xs font-semibold text-cyan-700"
            >
              {{ skill }}
            </span>
          </div>
        </div>
      </div>

      <!-- Info Grid -->
      <div v-if="user" class="rounded-3xl bg-white border border-slate-100 shadow-lg p-7">
        <div class="flex items-center gap-2 mb-5">
          <div class="w-1 h-5 rounded-full bg-gradient-to-b from-cyan-500 to-blue-600"></div>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">About</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="item in infoItems" :key="item.label" class="flex items-start gap-3 rounded-2xl bg-slate-50 border border-slate-100 p-4 hover:bg-blue-50/40 hover:border-blue-100 transition-colors">
            <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-xl bg-gradient-to-br from-cyan-100 to-blue-100 flex items-center justify-center">
              <component :is="item.icon" class="w-4 h-4 text-blue-600" />
            </div>
            <div class="min-w-0">
              <p class="text-xs text-slate-400 font-medium uppercase tracking-wide">{{ item.label }}</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-800 truncate">{{ item.value || 'Not provided' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Change Password (own profile only) -->
      <div v-if="user && isOwnProfile" class="rounded-3xl bg-white border border-slate-100 shadow-lg p-7">
        <div class="flex items-center gap-2 mb-5">
          <div class="w-1 h-5 rounded-full bg-gradient-to-b from-cyan-500 to-blue-600"></div>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">Change Password</h2>
        </div>

        <p v-if="passwordError" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-700">{{ passwordError }}</p>
        <p v-if="passwordMessage" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">{{ passwordMessage }}</p>

        <form @submit.prevent="changePassword" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Old Password</label>
            <input v-model="oldPassword" type="password" required
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">New Password</label>
            <input v-model="newPassword" type="password" required minlength="6"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Confirm Password</label>
            <input v-model="newPasswordConfirmation" type="password" required minlength="6"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div class="sm:col-span-3">
            <button type="submit" :disabled="passwordLoading"
              class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:from-cyan-600 hover:to-blue-700 disabled:opacity-60 transition-all">
              <svg v-if="passwordLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              {{ passwordLoading ? 'Updating...' : 'Update Password' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Posts -->
      <div v-if="user" class="rounded-3xl bg-white border border-slate-100 shadow-lg p-7">
        <div class="flex items-center gap-2 mb-5">
          <div class="w-1 h-5 rounded-full bg-gradient-to-b from-cyan-500 to-blue-600"></div>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">
            {{ isOwnProfile ? 'My Posts' : `${user.name}'s Posts` }}
          </h2>
          <span v-if="sortedPosts.length" class="ml-auto text-xs font-semibold text-slate-400 bg-slate-100 rounded-full px-2.5 py-0.5">
            {{ sortedPosts.length }}
          </span>
        </div>

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
        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
          <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
            <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
            </svg>
          </div>
          <p class="text-sm font-medium text-slate-400">No posts yet</p>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { computed, h, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
  return skills.split(',').map((s) => s.trim()).filter(Boolean)
})

// Inline SVG icon components for info grid
const IconUser = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z' })]) }
const IconMail = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75' })]) }
const IconPhone = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z' })]) }
const IconBriefcase = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0' })]) }
const IconBuilding = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21' })]) }
const IconAcademic = { render: () => h('svg', { fill: 'none', stroke: 'currentColor', 'stroke-width': '2', viewBox: '0 0 24 24' }, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5' })]) }

const infoItems = computed(() => [
  { label: 'Name',          value: user.value?.name,                      icon: IconUser },
  { label: 'Email',         value: user.value?.email,                     icon: IconMail },
  { label: 'Phone',         value: user.value?.profile?.phone,            icon: IconPhone },
  { label: 'Current Job',   value: user.value?.profile?.current_job,      icon: IconBriefcase },
  { label: 'Company',       value: user.value?.profile?.company,          icon: IconBuilding },
  { label: 'Graduate Year', value: user.value?.profile?.graduate_year,    icon: IconAcademic },
])

const sortedPosts = computed(() => {
  const posts = user.value?.posts || []
  return [...posts].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const postsWithAuthor = computed(() =>
  sortedPosts.value.map((post) => ({
    ...post,
    user: post.user || { id: user.value?.id, name: user.value?.name, profile: user.value?.profile },
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
  if (!id) { user.value = null; return }
  try {
    const response = await api.get(`/profiles/${id}`)
    user.value = response.data.data
    commentsRefreshKey.value += 1
  } catch (error) {
    const own = loggedInUser.value?.id && String(loggedInUser.value.id) === String(id)
    if (own) { user.value = loggedInUser.value; return }
    user.value = null
    errorMessage.value = error?.response?.data?.message || 'Profile not found or failed to load.'
  }
}

const loadConnectionStatus = async (id) => {
  if (!loggedInUser.value?.id || String(loggedInUser.value.id) === String(id)) {
    connectionStatus.value = 'self'; return
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
  if (profileRefreshTimer) { clearInterval(profileRefreshTimer); profileRefreshTimer = null }
}

const startProfileRefreshLoop = () => {
  stopProfileRefreshLoop()
  profileRefreshTimer = setInterval(async () => {
    if (route.params.id) await loadProfile(route.params.id)
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
    passwordError.value = 'New password confirmation does not match.'; return
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

watch(() => route.params.id, (id) => {
  stopProfileRefreshLoop()
  if (id) { loadProfile(id); loadConnectionStatus(id); startProfileRefreshLoop() }
})

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
onBeforeUnmount(() => stopProfileRefreshLoop())
</script>
