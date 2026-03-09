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



      <div v-if="user" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-900">
          {{ isOwnProfile ? 'My Posts' : `${user.name}'s Posts` }}
        </h2>

        <div v-if="sortedPosts.length" class="space-y-4">
          <article
            v-for="post in sortedPosts"
            :key="post.id"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
          >
            <div class="flex items-start justify-between px-4 pt-4">
              <p class="text-xs text-slate-500">{{ formatPostDate(post.created_at) }}</p>
              <div v-if="isOwnProfile" class="relative">
                <button
                  type="button"
                  @click.stop="togglePostActionsMenu(post.id)"
                  :disabled="postActionLoading"
                  class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300 focus-visible:ring-offset-1 disabled:opacity-60"
                  aria-label="Post actions"
                  title="Post actions"
                >
                  <svg
                    viewBox="0 0 24 24"
                    class="h-4 w-4"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <circle cx="12" cy="5" r="2" />
                    <circle cx="12" cy="12" r="2" />
                    <circle cx="12" cy="19" r="2" />
                  </svg>
                </button>

                <div
                  v-if="openPostActionsMenuId === post.id"
                  class="absolute right-0 z-20 mt-2 w-36 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                >
                  <button
                    type="button"
                    @click="handleStartEdit(post)"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                  >
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M12 20h9" />
                      <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                    </svg>
                    <span>Edit</span>
                  </button>
                  <button
                    type="button"
                    @click="handleDeletePost(post.id)"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                  >
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M3 6h18" />
                      <path d="M8 6V4h8v2" />
                      <path d="M19 6l-1 14H6L5 6" />
                      <path d="M10 11v6" />
                      <path d="M14 11v6" />
                    </svg>
                    <span>Delete</span>
                  </button>
                </div>
              </div>
            </div>
            <h3 v-if="editingPostId !== post.id && post.title" class="px-4 pt-2 text-base font-semibold text-slate-900">
              {{ post.title }}
            </h3>
            <div v-if="editingPostId === post.id" class="space-y-3 p-4">
              <input
                v-model="editTitle"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                placeholder="Post title"
              />
              <textarea
                v-model="editContent"
                rows="4"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
                placeholder="Post content"
              />
              <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Replace media (optional)
                </label>
                <input
                  type="file"
                  accept="image/*,video/*"
                  multiple
                  @change="onEditMediaChange"
                  class="block w-full text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-slate-700"
                />
                <p class="mt-1 text-[11px] text-slate-500">
                  First selected file replaces first old media. More selected files are added.
                </p>
                <button
                  v-if="editMediaFiles.length"
                  type="button"
                  @click="clearEditMediaSelection"
                  class="mt-2 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-100"
                >
                  Clear selected files
                </button>
              </div>
              <div v-if="editMediaPreviews.length" class="grid grid-cols-2 gap-2">
                <template v-for="preview in editMediaPreviews" :key="preview.url">
                  <img
                    v-if="preview.type.startsWith('image/')"
                    :src="preview.url"
                    alt="Selected image"
                    class="w-full max-h-40 rounded-lg border border-slate-200 object-cover"
                  >
                  <video
                    v-else
                    :src="preview.url"
                    class="w-full max-h-40 rounded-lg border border-slate-200 bg-black object-cover"
                    controls
                    preload="metadata"
                  ></video>
                </template>
              </div>
              <div class="flex gap-2">
                <button
                  @click="savePostEdit(post.id)"
                  :disabled="postActionLoading"
                  class="rounded-lg bg-gradient-to-r from-cyan-600 to-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:from-cyan-700 hover:to-blue-700 disabled:opacity-60"
                >
                  Save
                </button>
                <button
                  @click="cancelPostEdit"
                  :disabled="postActionLoading"
                  class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-60"
                >
                  Cancel
                </button>
              </div>
            </div>


            <p v-else class="mt-2 whitespace-pre-line px-4 text-sm text-slate-700">{{ post.content }}</p>
            <div v-if="post.media?.length" class="mt-3 space-y-2 px-4">
              <template v-if="shouldCondenseMedia(post) && !isMediaExpanded(post.id)">
                <div
                  v-if="getCollapsedMainMedia(post)"
                  class="overflow-hidden rounded-xl border border-slate-200"
                >
                  <img
                    v-if="isImageMedia(getCollapsedMainMedia(post))"
                    :src="getMediaSrc(getCollapsedMainMedia(post))"
                    alt="Post image"
                    class="w-full max-h-72 object-cover"
                  >
                  <video
                    v-else-if="isVideoMedia(getCollapsedMainMedia(post))"
                    :src="getMediaSrc(getCollapsedMainMedia(post))"
                    class="w-full max-h-72 bg-black object-cover"
                    controls
                    preload="metadata"
                  ></video>
                </div>

                <div class="grid grid-cols-3 gap-2">
                  <button
                    v-for="(media, index) in getCollapsedRowMedia(post)"
                    :key="media.id ?? media.file_path ?? media.media_url"
                    type="button"
                    @click="expandPostMedia(post.id)"
                    class="relative overflow-hidden rounded-xl border border-slate-200 text-left"
                  >
                    <img
                      v-if="isImageMedia(media)"
                      :src="getMediaSrc(media)"
                      alt="Post image"
                      class="h-28 w-full object-cover"
                    >
                    <video
                      v-else-if="isVideoMedia(media)"
                      :src="getMediaSrc(media)"
                      class="h-28 w-full bg-black object-cover"
                      preload="metadata"
                    ></video>
                    <div
                      v-if="index === 2 && getHiddenMediaCount(post) > 0"
                      class="absolute inset-0 flex items-center justify-center bg-black/55 text-2xl font-semibold text-white"
                    >
                      +{{ getHiddenMediaCount(post) }}
                    </div>
                  </button>
                </div>
              </template>

              <template v-else>
                <div class="grid grid-cols-2 gap-2">
                  <template
                    v-for="media in post.media"
                    :key="media.id ?? media.file_path ?? media.media_url"
                  >
                    <img
                      v-if="isImageMedia(media)"
                      :src="getMediaSrc(media)"
                      alt="Post image"
                      class="w-full max-h-72 rounded-lg border border-slate-200 object-cover"
                    >
                    <video
                      v-else-if="isVideoMedia(media)"
                      :src="getMediaSrc(media)"
                      class="w-full max-h-72 rounded-lg border border-slate-200 bg-black object-cover"
                      controls
                      preload="metadata"
                    ></video>
                  </template>
                </div>
                <div v-if="shouldCondenseMedia(post)" class="flex justify-end">
                  <button
                    type="button"
                    @click="collapsePostMedia(post.id)"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    Show less media
                  </button>
                </div>
              </template>
            </div>
          </article>
        </div>
        
        <p v-else class="text-sm text-slate-500">No posts yet.</p>
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
    </div>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
const expandedMediaPostIds = ref([])
const editingPostId = ref(null)
const editTitle = ref('')
const editContent = ref('')
const editMediaFiles = ref([])
const editMediaPreviews = ref([])
const postActionLoading = ref(false)
const openPostActionsMenuId = ref(null)

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

const getMediaSrc = (media) => media?.media_url || media?.file_path || ''

const getMediaType = (media) => {
  const explicitType = String(media?.type || '').toLowerCase()
  if (explicitType === 'image' || explicitType === 'video') return explicitType

  const src = getMediaSrc(media).toLowerCase()
  if (/\.(mp4|mov|avi|webm|mkv)(\?|#|$)/.test(src)) return 'video'
  if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|#|$)/.test(src)) return 'image'
  return ''
}

const isImageMedia = (media) => getMediaType(media) === 'image'
const isVideoMedia = (media) => getMediaType(media) === 'video'
const shouldCondenseMedia = (post) => Array.isArray(post?.media) && post.media.length > 4
const isMediaExpanded = (postId) => expandedMediaPostIds.value.includes(postId)
const expandPostMedia = (postId) => {
  if (isMediaExpanded(postId)) return
  expandedMediaPostIds.value = [...expandedMediaPostIds.value, postId]
}
const collapsePostMedia = (postId) => {
  expandedMediaPostIds.value = expandedMediaPostIds.value.filter((id) => id !== postId)
}
const getCollapsedMainMedia = (post) => post?.media?.[0] || null
const getCollapsedRowMedia = (post) => (post?.media || []).slice(1, 4)
const getHiddenMediaCount = (post) => Math.max((post?.media?.length || 0) - 4, 0)

const startPostEdit = (post) => {
  editingPostId.value = post.id
  editTitle.value = post.title || ''
  editContent.value = post.content || ''
  clearEditMediaSelection()
}

const togglePostActionsMenu = (postId) => {
  openPostActionsMenuId.value = openPostActionsMenuId.value === postId ? null : postId
}

const handleStartEdit = (post) => {
  openPostActionsMenuId.value = null
  startPostEdit(post)
}

const handleDeletePost = (postId) => {
  openPostActionsMenuId.value = null
  deletePost(postId)
}

const closePostActionsMenu = () => {
  openPostActionsMenuId.value = null
}

const cancelPostEdit = () => {
  editingPostId.value = null
  editTitle.value = ''
  editContent.value = ''
  clearEditMediaSelection()
}

const clearEditMediaSelection = () => {
  editMediaPreviews.value.forEach((preview) => {
    if (preview?.url) {
      URL.revokeObjectURL(preview.url)
    }
  })
  editMediaFiles.value = []
  editMediaPreviews.value = []
}

const onEditMediaChange = (event) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return

  files.forEach((file) => {
    editMediaFiles.value.push(file)
    editMediaPreviews.value.push({
      url: URL.createObjectURL(file),
      type: file.type || '',
    })
  })

  event.target.value = ''
}

const savePostEdit = async (postId) => {
  const hasTitle = !!editTitle.value.trim()
  const hasContent = !!editContent.value.trim()
  const hasNewMedia = editMediaFiles.value.length > 0
  const currentPost = user.value?.posts?.find((item) => item.id === postId)
  const hasExistingMedia = (currentPost?.media?.length || 0) > 0

  if (!hasTitle && !hasContent && !hasNewMedia && !hasExistingMedia) {
    errorMessage.value = 'Please add title, content, or at least one image/video.'
    return
  }

  postActionLoading.value = true
  try {
    if (editMediaFiles.value.length) {
      const formData = new FormData()
      formData.append('title', editTitle.value.trim())
      formData.append('content', editContent.value.trim())

      editMediaFiles.value.forEach((file) => {
        if (file.type.startsWith('image/')) {
          formData.append('images[]', file)
        } else if (file.type.startsWith('video/')) {
          formData.append('videos[]', file)
        }
      })

      await api.post(`/posts/${postId}`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    } else {
      await api.put(`/posts/${postId}`, {
        title: editTitle.value.trim(),
        content: editContent.value.trim(),
      })
    }

    await loadProfile(route.params.id)
    const post = user.value?.posts?.find((item) => item.id === postId)
    if (!post) {
      cancelPostEdit()
      return
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

onMounted(() => {
  window.addEventListener('click', closePostActionsMenu)
})

onBeforeUnmount(() => {
  window.removeEventListener('click', closePostActionsMenu)
})
</script>
