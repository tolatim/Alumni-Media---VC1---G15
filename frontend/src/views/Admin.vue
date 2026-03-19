<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Sidebar from '@/components/admin/Sidebar.vue'
import Topbar from '@/components/admin/Topbar.vue'
import api from '@/services/api'
import { applyThemeMode } from '@/services/appearanceService'

type ReportedPost = {
  post_id: number
  report_count: number
  latest_report_at: string | null
  latest_reason: string | null
  post: {
    id: number
    title: string | null
    content: string | null
    created_at: string | null
    media?: Array<{
      id?: number
      type?: string | null
      file_path?: string | null
      media_url?: string | null
    }>
    user?: {
      id: number
      name?: string
      first_name?: string
      last_name?: string
      email?: string
    }
  } | null
}

type SuspensionDuration = '7_days' | 'permanent'

type AdminUser = {
  id: number
  first_name?: string
  last_name?: string
  name?: string
  email?: string
  role?: string
  suspended_until?: string | null
  suspended_permanently?: boolean
  is_suspended?: boolean
}

type ReviewReport = {
  id: number
  reason?: string | null
  status?: string
  created_at?: string | null
  reporter?: {
    id: number
    name?: string
    email?: string
  } | null
  target?: {
    type?: string
    id?: number
    summary?: string | null
    user_id?: number | null
    exists?: boolean
  } | null
}

type ThemeMode = 'light' | 'dark'
type AdminSection = 'dashboard' | 'settings' | 'reports' | 'posts' | 'users'

const reportedPosts = ref<ReportedPost[]>([])
const selectedReportedPost = ref<ReportedPost | null>(null)
const users = ref<AdminUser[]>([])
const reviewReports = ref<ReviewReport[]>([])

const loadingReportedPosts = ref(false)
const loadingUsers = ref(false)
const loadingReports = ref(false)
const deletingPostId = ref<number | null>(null)
const suspendingUserId = ref<number | null>(null)
const actingReportId = ref<number | null>(null)

const reportMessage = ref('')
const reportError = ref('')
const userMessage = ref('')
const userError = ref('')
const reviewMessage = ref('')
const reviewError = ref('')
const userSearchQuery = ref('')
const appearanceMode = ref<ThemeMode>('light')
const appearanceLogoUrl = ref<string | null>(null)
const appearanceLogoFile = ref<File | null>(null)
const registrationKey = ref('')
const savingAppearance = ref(false)
const appearanceMessage = ref('')
const appearanceError = ref('')
const loadingMoreUsers = ref(false)
const usersPerPage = 10
const adminRealtimeSocket = ref<WebSocket | null>(null)
let adminRefreshTimer: ReturnType<typeof setTimeout> | null = null

const userPagination = ref({
  current_page: 0,
  last_page: 1,
  per_page: usersPerPage,
  total: 0,
})

const suspensionDurationByUserId = ref<Record<number, SuspensionDuration>>({})
const suspensionDurationByReportId = ref<Record<number, SuspensionDuration>>({})
const route = useRoute()

const activeReportsCount = computed(() => reportedPosts.value.length)
const suspendedUsersCount = computed(() => users.value.filter((user) => user.is_suspended).length)
const pendingReviewCount = computed(() => reviewReports.value.length)
const hasMoreUsers = computed(() => userPagination.value.current_page < userPagination.value.last_page)
const activeSection = computed<AdminSection>(() => {
  if (route.path === '/users') return 'users'
  if (route.path === '/posts') return 'posts'
  if (route.path === '/reports') return 'reports'
  if (route.path === '/settings' || route.path === '/admin/settings') return 'settings'
  return 'dashboard'
})

const getPosterName = (item: ReportedPost) => {
  const postUser = item.post?.user
  if (!postUser) return 'Unknown user'
  const nameFromFields = `${postUser.first_name || ''} ${postUser.last_name || ''}`.trim()
  return postUser.name || nameFromFields || postUser.email || 'Unknown user'
}

const openReportedPostViewer = (item: ReportedPost) => {
  selectedReportedPost.value = item
}

const closeReportedPostViewer = () => {
  selectedReportedPost.value = null
}

const getReportedPostMediaSrc = (media: { media_url?: string | null; file_path?: string | null }) => {
  return media?.media_url || media?.file_path || ''
}

const getReportedPostMediaType = (media: { type?: string | null; media_url?: string | null; file_path?: string | null }) => {
  const explicitType = String(media?.type || '').toLowerCase()
  if (explicitType === 'image' || explicitType === 'video') return explicitType

  const src = getReportedPostMediaSrc(media).toLowerCase()
  if (/\.(mp4|mov|avi|webm|mkv)(\?|#|$)/.test(src)) return 'video'
  if (/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?|#|$)/.test(src)) return 'image'
  return ''
}

const isReportedPostImage = (media: { type?: string | null; media_url?: string | null; file_path?: string | null }) =>
  getReportedPostMediaType(media) === 'image'

const isReportedPostVideo = (media: { type?: string | null; media_url?: string | null; file_path?: string | null }) =>
  getReportedPostMediaType(media) === 'video'

const getUserName = (user: AdminUser) => {
  const nameFromFields = `${user.first_name || ''} ${user.last_name || ''}`.trim()
  return user.name || nameFromFields || user.email || `User #${user.id}`
}

const getReporterName = (report: ReviewReport) => {
  return report.reporter?.name || report.reporter?.email || 'Unknown reporter'
}

const getTargetLabel = (report: ReviewReport) => {
  const type = report.target?.type || 'unknown'
  const id = report.target?.id
  const summary = report.target?.summary
  return `${type}${id ? ` #${id}` : ''}${summary ? ` - ${summary}` : ''}`
}

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString()
}

const getSuspensionLabel = (user: AdminUser) => {
  if (!user?.is_suspended) return 'Active'
  if (user?.suspended_permanently) return 'Suspended (Permanent)'
  return `Suspended until ${formatDateTime(user?.suspended_until)}`
}

const removeReviewReport = (reportId: number) => {
  reviewReports.value = reviewReports.value.filter((item) => item.id !== reportId)
}

const loadReportedPosts = async () => {
  loadingReportedPosts.value = true
  reportError.value = ''

  try {
    const response = await api.get('/admin/reported-posts')
    reportedPosts.value = response.data?.data || []
  } catch (error: any) {
    reportError.value = error?.response?.data?.message || 'Failed to load reported posts.'
  } finally {
    loadingReportedPosts.value = false
  }
}

const loadUsers = async (reset = true) => {
  if (reset) {
    loadingUsers.value = true
  } else {
    if (loadingMoreUsers.value || loadingUsers.value || !hasMoreUsers.value) return
    loadingMoreUsers.value = true
  }

  userError.value = ''

  try {
    const nextPage = reset ? 1 : userPagination.value.current_page + 1
    const response = await api.get('/admin/users', {
      params: {
        q: userSearchQuery.value.trim() || undefined,
        page: nextPage,
        per_page: usersPerPage,
      },
    })

    const incomingUsers = response.data?.data || []
    if (reset) {
      users.value = incomingUsers
    } else {
      users.value = [...users.value, ...incomingUsers]
    }

    const pagination = response.data?.pagination || {}
    userPagination.value = {
      current_page: Number(pagination.current_page) || nextPage,
      last_page: Number(pagination.last_page) || nextPage,
      per_page: Number(pagination.per_page) || usersPerPage,
      total: Number(pagination.total) || users.value.length,
    }

    const nextMap: Record<number, SuspensionDuration> = {}
    users.value.forEach((user) => {
      nextMap[user.id] = suspensionDurationByUserId.value[user.id] || '7_days'
    })
    suspensionDurationByUserId.value = nextMap
  } catch (error: any) {
    userError.value = error?.response?.data?.message || 'Failed to load users.'
  } finally {
    if (reset) {
      loadingUsers.value = false
    } else {
      loadingMoreUsers.value = false
    }
  }
}

const clearUserSearch = async () => {
  userSearchQuery.value = ''
  await loadUsers(true)
}

const loadAdminAppearance = async () => {
  appearanceError.value = ''

  try {
    const response = await api.get('/admin/settings/appearance')
    const data = response?.data?.data || {}
    const mode = data?.theme_mode === 'dark' ? 'dark' : 'light'

    appearanceMode.value = mode
    appearanceLogoUrl.value = data?.logo_url || null
    registrationKey.value = data?.registration_key || ''
    applyThemeMode(mode)
  } catch (error: any) {
    appearanceError.value = error?.response?.data?.message || 'Failed to load appearance settings.'
  }
}

const onAppearanceLogoSelected = (event: Event) => {
  const target = event.target as HTMLInputElement | null
  const file = target?.files?.[0]
  if (!file) return

  appearanceLogoFile.value = file
}

const saveAppearance = async () => {
  savingAppearance.value = true
  appearanceError.value = ''
  appearanceMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('theme_mode', appearanceMode.value)
    formData.append('registration_key', registrationKey.value || '')
    if (appearanceLogoFile.value) {
      formData.append('logo', appearanceLogoFile.value)
    }

    const response = await api.post('/admin/settings/appearance', formData)
    const data = response?.data?.data || {}
    appearanceMode.value = data?.theme_mode === 'dark' ? 'dark' : 'light'
    appearanceLogoUrl.value = data?.logo_url || null
    registrationKey.value = data?.registration_key || ''
    appearanceLogoFile.value = null
    applyThemeMode(appearanceMode.value)
    appearanceMessage.value = 'Settings saved.'
  } catch (error: any) {
    appearanceError.value = error?.response?.data?.message || 'Failed to save settings.'
  } finally {
    savingAppearance.value = false
  }
}

const onUsersScroll = async (event: Event) => {
  const target = event.target as HTMLElement | null
  if (!target) return

  const thresholdPx = 80
  const reachedBottom = target.scrollTop + target.clientHeight >= target.scrollHeight - thresholdPx
  if (!reachedBottom) return

  await loadUsers(false)
}

const loadReportsForReview = async () => {
  loadingReports.value = true
  reviewError.value = ''

  try {
    const response = await api.get('/admin/reports')
    reviewReports.value = response.data?.data || []

    const nextMap: Record<number, SuspensionDuration> = {}
    reviewReports.value.forEach((report) => {
      nextMap[report.id] = suspensionDurationByReportId.value[report.id] || '7_days'
    })
    suspensionDurationByReportId.value = nextMap
  } catch (error: any) {
    reviewError.value = error?.response?.data?.message || 'Failed to load reports.'
  } finally {
    loadingReports.value = false
  }
}

const deleteReportedPost = async (item: ReportedPost) => {
  if (!item?.post_id) return
  const shouldDelete = window.confirm('Delete this reported post? This action cannot be undone.')
  if (!shouldDelete) return

  deletingPostId.value = item.post_id
  reportMessage.value = ''
  reportError.value = ''

  try {
    await api.delete(`/admin/reported-posts/${item.post_id}`)
    reportedPosts.value = reportedPosts.value.filter((post) => post.post_id !== item.post_id)
    if (selectedReportedPost.value?.post_id === item.post_id) {
      closeReportedPostViewer()
    }
    reportMessage.value = `Post #${item.post_id} was deleted.`
  } catch (error: any) {
    reportError.value = error?.response?.data?.message || 'Failed to delete post.'
  } finally {
    deletingPostId.value = null
  }
}

const suspendUser = async (user: AdminUser) => {
  const selectedDuration = suspensionDurationByUserId.value[user.id] || '7_days'
  const readableDuration = selectedDuration === 'permanent' ? 'permanent suspension' : '7-day suspension'

  const shouldSuspend = window.confirm(`Suspend ${getUserName(user)} with ${readableDuration}?`)
  if (!shouldSuspend) return

  suspendingUserId.value = user.id
  userMessage.value = ''
  userError.value = ''

  try {
    await api.post(`/admin/users/${user.id}/suspend`, {
      duration: selectedDuration,
    })

    const nowIso = new Date().toISOString()
    users.value = users.value.map((item) => {
      if (item.id !== user.id) return item

      if (selectedDuration === 'permanent') {
        return {
          ...item,
          is_suspended: true,
          suspended_permanently: true,
          suspended_until: null,
        }
      }

      const sevenDaysLater = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString()
      return {
        ...item,
        is_suspended: true,
        suspended_permanently: false,
        suspended_until: sevenDaysLater || nowIso,
      }
    })

    userMessage.value = `${getUserName(user)} has been suspended.`
  } catch (error: any) {
    userError.value = error?.response?.data?.message || 'Failed to suspend user.'
  } finally {
    suspendingUserId.value = null
  }
}

const ignoreReport = async (report: ReviewReport) => {
  actingReportId.value = report.id
  reviewMessage.value = ''
  reviewError.value = ''

  try {
    await api.post(`/admin/reports/${report.id}/ignore`)
    removeReviewReport(report.id)
    reviewMessage.value = `Report #${report.id} ignored.`
  } catch (error: any) {
    reviewError.value = error?.response?.data?.message || 'Failed to ignore report.'
  } finally {
    actingReportId.value = null
  }
}

const deletePostFromReport = async (report: ReviewReport) => {
  const isPostTarget = report.target?.type === 'post'
  if (!isPostTarget) return

  const shouldDelete = window.confirm(`Delete the post for report #${report.id}?`)
  if (!shouldDelete) return

  actingReportId.value = report.id
  reviewMessage.value = ''
  reviewError.value = ''

  try {
    await api.post(`/admin/reports/${report.id}/delete-post`)
    removeReviewReport(report.id)
    reviewMessage.value = `Post deleted from report #${report.id}.`
    await loadReportedPosts()
  } catch (error: any) {
    reviewError.value = error?.response?.data?.message || 'Failed to delete post from report.'
  } finally {
    actingReportId.value = null
  }
}

const suspendUserFromReport = async (report: ReviewReport) => {
  const selectedDuration = suspensionDurationByReportId.value[report.id] || '7_days'
  const readableDuration = selectedDuration === 'permanent' ? 'permanent suspension' : '7-day suspension'

  const shouldSuspend = window.confirm(`Suspend user from report #${report.id} with ${readableDuration}?`)
  if (!shouldSuspend) return

  actingReportId.value = report.id
  reviewMessage.value = ''
  reviewError.value = ''

  try {
    await api.post(`/admin/reports/${report.id}/suspend-user`, {
      duration: selectedDuration,
    })

    removeReviewReport(report.id)
    reviewMessage.value = `User suspended from report #${report.id}.`
    await loadUsers()
  } catch (error: any) {
    reviewError.value = error?.response?.data?.message || 'Failed to suspend user from report.'
  } finally {
    actingReportId.value = null
  }
}

const refreshAdminDataSoon = () => {
  if (adminRefreshTimer) {
    clearTimeout(adminRefreshTimer)
  }

  adminRefreshTimer = setTimeout(() => {
    loadReportedPosts()
    loadUsers(true)
    loadReportsForReview()
    adminRefreshTimer = null
  }, 350)
}

const connectAdminRealtime = () => {
  const rawUser = localStorage.getItem('user')
  if (!rawUser) return

  let currentUser: any = null
  try {
    currentUser = JSON.parse(rawUser)
  } catch {
    return
  }

  const roleName = typeof currentUser?.role === 'string' ? currentUser.role : currentUser?.role?.name
  if (roleName !== 'admin' || !currentUser?.id) return

  const socket = new WebSocket('ws://localhost:8081')
  adminRealtimeSocket.value = socket

  socket.onopen = () => {
    socket.send(JSON.stringify({
      type: 'auth',
      user_id: currentUser.id,
      role: 'admin',
      channel: 'admin',
    }))
  }

  socket.onmessage = (event) => {
    try {
      const payload = JSON.parse(event.data)
      if (payload?.audience === 'admins' || payload?.type === 'admin_activity') {
        refreshAdminDataSoon()
      }
    } catch (error) {
      console.error('Invalid admin websocket payload', error)
    }
  }

  socket.onerror = () => {
    // Keep silent in UI; admin can still refresh manually.
  }

  socket.onclose = () => {
    if (adminRealtimeSocket.value === socket) {
      adminRealtimeSocket.value = null
    }
  }
}

onMounted(() => {
  loadAdminAppearance()
  loadReportedPosts()
  loadUsers(true)
  loadReportsForReview()
  connectAdminRealtime()
})

onUnmounted(() => {
  if (adminRefreshTimer) {
    clearTimeout(adminRefreshTimer)
    adminRefreshTimer = null
  }

  if (adminRealtimeSocket.value) {
    adminRealtimeSocket.value.close()
    adminRealtimeSocket.value = null
  }
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-gray-50">
    <Sidebar :logo-url="appearanceLogoUrl" />

    <div class="flex-1 flex flex-col overflow-hidden">
      <Topbar />

      <main class="h-full overflow-y-auto p-6 space-y-8">
        <div v-if="activeSection === 'dashboard'" class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div class="rounded-xl border border-red-100 bg-red-50 p-4">
            <h1 class="text-xl font-semibold text-gray-900">Reported Posts Moderation</h1>
            <p class="mt-1 text-sm text-gray-700">
              Active reported posts: <span class="font-semibold">{{ activeReportsCount }}</span>
            </p>
          </div>
          <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
            <h2 class="text-xl font-semibold text-gray-900">User Suspension</h2>
            <p class="mt-1 text-sm text-gray-700">
              Suspended users: <span class="font-semibold">{{ suspendedUsersCount }}</span>
            </p>
          </div>
          <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <h2 class="text-xl font-semibold text-gray-900">Reports Review</h2>
            <p class="mt-1 text-sm text-gray-700">
              Pending reports: <span class="font-semibold">{{ pendingReviewCount }}</span>
            </p>
          </div>
        </div>

        <section v-if="activeSection === 'settings'" class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Admin Settings</h2>
          </div>

          <div class="space-y-4 px-5 py-4">
            <p v-if="appearanceMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
              {{ appearanceMessage }}
            </p>
            <p v-if="appearanceError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
              {{ appearanceError }}
            </p>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Theme Mode</p>
                <div class="flex items-center gap-4">
                  <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="appearanceMode" type="radio" value="light">
                    <span>Light mode</span>
                  </label>
                  <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="appearanceMode" type="radio" value="dark">
                    <span>Dark mode</span>
                  </label>
                </div>
              </div>

              <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">App Logo</p>
                <input
                  type="file"
                  accept="image/*"
                  class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-xs text-gray-700"
                  @change="onAppearanceLogoSelected"
                >
                <img
                  v-if="appearanceLogoUrl"
                  :src="appearanceLogoUrl"
                  alt="Current app logo"
                  class="h-12 w-12 rounded-md border border-gray-200 object-cover"
                >
              </div>
            </div>

            <div class="space-y-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registration Key</p>
              <input
                v-model="registrationKey"
                type="text"
                placeholder="Set registration key for new users"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
              <p class="text-xs text-gray-500">
                New users must enter this key during registration.
              </p>
            </div>

            <div class="flex justify-end">
              <button
                type="button"
                class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                :disabled="savingAppearance"
                @click="saveAppearance"
              >
                {{ savingAppearance ? 'Saving...' : 'Save Settings' }}
              </button>
            </div>
          </div>
        </section>

        <section v-if="activeSection === 'reports'" class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Reports Review</h2>
            <button
              type="button"
              class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
              @click="loadReportsForReview"
              :disabled="loadingReports"
            >
              {{ loadingReports ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>

          <p v-if="reviewMessage" class="mx-5 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ reviewMessage }}
          </p>

          <p v-if="reviewError" class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
            {{ reviewError }}
          </p>

          <div v-if="loadingReports" class="px-5 py-6 text-sm text-gray-500">
            Loading reports...
          </div>

          <div v-else-if="!reviewReports.length" class="px-5 py-6 text-sm text-gray-500">
            No pending reports.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                  <th class="px-4 py-3 font-semibold">Reporter</th>
                  <th class="px-4 py-3 font-semibold">Target</th>
                  <th class="px-4 py-3 font-semibold">Reason</th>
                  <th class="px-4 py-3 font-semibold">Reported At</th>
                  <th class="px-4 py-3 font-semibold">Suspend Duration</th>
                  <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="report in reviewReports" :key="report.id" class="border-b border-gray-100 align-top">
                  <td class="px-4 py-3">
                    <p class="font-semibold text-gray-900">{{ getReporterName(report) }}</p>
                    <p class="mt-1 text-xs text-gray-600">ID: {{ report.reporter?.id || 'N/A' }}</p>
                  </td>
                  <td class="px-4 py-3 text-gray-700">{{ getTargetLabel(report) }}</td>
                  <td class="px-4 py-3 text-gray-700">{{ report.reason || 'No reason provided.' }}</td>
                  <td class="px-4 py-3 text-gray-600">{{ formatDateTime(report.created_at) }}</td>
                  <td class="px-4 py-3">
                    <select
                      v-model="suspensionDurationByReportId[report.id]"
                      class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs text-gray-700"
                    >
                      <option value="7_days">7 days</option>
                      <option value="permanent">Permanent</option>
                    </select>
                  </td>
                  <td class="px-4 py-3">
                    <div class="flex gap-2">
                      <button
                        type="button"
                        class="rounded-md bg-gray-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-gray-700 disabled:opacity-60"
                        :disabled="actingReportId === report.id"
                        @click="ignoreReport(report)"
                      >
                        Ignore
                      </button>
                      <button
                        type="button"
                        class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                        :disabled="actingReportId === report.id || report.target?.type !== 'post'"
                        @click="deletePostFromReport(report)"
                      >
                        Delete post
                      </button>
                      <button
                        type="button"
                        class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 disabled:opacity-60"
                        :disabled="actingReportId === report.id"
                        @click="suspendUserFromReport(report)"
                      >
                        Suspend user
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="activeSection === 'posts'" class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">Reported Posts</h2>
            <button
              type="button"
              class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
              @click="loadReportedPosts"
              :disabled="loadingReportedPosts"
            >
              {{ loadingReportedPosts ? 'Refreshing...' : 'Refresh' }}
            </button>
          </div>

          <p v-if="reportMessage" class="mx-5 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ reportMessage }}
          </p>

          <p v-if="reportError" class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
            {{ reportError }}
          </p>

          <div v-if="loadingReportedPosts" class="px-5 py-6 text-sm text-gray-500">
            Loading reported posts...
          </div>

          <div v-else-if="!reportedPosts.length" class="px-5 py-6 text-sm text-gray-500">
            No reported posts right now.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                  <th class="px-4 py-3 font-semibold">Post</th>
                  <th class="px-4 py-3 font-semibold">Author</th>
                  <th class="px-4 py-3 font-semibold">Reports</th>
                  <th class="px-4 py-3 font-semibold">Latest Reason</th>
                  <th class="px-4 py-3 font-semibold">Reported At</th>
                  <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in reportedPosts" :key="item.post_id" class="border-b border-gray-100 align-top">
                  <td class="px-4 py-3">
                    <p class="font-semibold text-gray-900">
                      {{ item.post?.title || `Post #${item.post_id}` }}
                    </p>
                    <p class="mt-1 line-clamp-2 max-w-md text-xs text-gray-600">
                      {{ item.post?.content || 'No content.' }}
                    </p>
                  </td>
                  <td class="px-4 py-3 text-gray-700">{{ getPosterName(item) }}</td>
                  <td class="px-4 py-3">
                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                      {{ item.report_count }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-700">{{ item.latest_reason || 'No reason provided.' }}</td>
                  <td class="px-4 py-3 text-gray-600">{{ formatDateTime(item.latest_report_at) }}</td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <button
                        type="button"
                        class="rounded-md border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                        @click="openReportedPostViewer(item)"
                      >
                        View
                      </button>
                      <button
                        type="button"
                        class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="deleteReportedPost(item)"
                        :disabled="deletingPostId === item.post_id"
                      >
                        {{ deletingPostId === item.post_id ? 'Deleting...' : 'Delete' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <div
          v-if="selectedReportedPost"
          class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
          @click.self="closeReportedPostViewer"
        >
          <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl">
            <div class="sticky top-0 flex items-center justify-between border-b border-gray-200 bg-white px-5 py-4">
              <div>
                <h3 class="text-base font-semibold text-gray-900">
                  Reported Post #{{ selectedReportedPost.post_id }}
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                  Author: {{ getPosterName(selectedReportedPost) }}
                </p>
              </div>
              <button
                type="button"
                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                @click="closeReportedPostViewer"
              >
                Close
              </button>
            </div>

            <div class="space-y-4 px-5 py-4">
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600">
                <p><span class="font-semibold text-gray-800">Reports:</span> {{ selectedReportedPost.report_count }}</p>
                <p class="mt-1"><span class="font-semibold text-gray-800">Latest Reason:</span> {{ selectedReportedPost.latest_reason || 'No reason provided.' }}</p>
                <p class="mt-1"><span class="font-semibold text-gray-800">Reported At:</span> {{ formatDateTime(selectedReportedPost.latest_report_at) }}</p>
              </div>

              <div>
                <p class="text-sm font-semibold text-gray-900">
                  {{ selectedReportedPost.post?.title || `Post #${selectedReportedPost.post_id}` }}
                </p>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                  {{ selectedReportedPost.post?.content || 'No content.' }}
                </p>
              </div>

              <div
                v-if="selectedReportedPost.post?.media?.length"
                class="grid grid-cols-1 gap-3 sm:grid-cols-2"
              >
                <div
                  v-for="media in selectedReportedPost.post.media"
                  :key="media.id || media.file_path || media.media_url"
                  class="overflow-hidden rounded-lg border border-gray-200"
                >
                  <img
                    v-if="isReportedPostImage(media)"
                    :src="getReportedPostMediaSrc(media)"
                    alt="Reported post media"
                    class="h-56 w-full object-cover"
                  >
                  <video
                    v-else-if="isReportedPostVideo(media)"
                    :src="getReportedPostMediaSrc(media)"
                    class="h-56 w-full bg-black object-cover"
                    controls
                    preload="metadata"
                  ></video>
                </div>
              </div>
            </div>
          </div>
        </div>

        <section v-if="activeSection === 'users'" class="rounded-xl border border-gray-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">User Profiles</h2>
            <div class="flex items-center gap-2">
              <input
                v-model="userSearchQuery"
                type="text"
                placeholder="Search alumni by name or email"
                class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                @keyup.enter="loadUsers"
              >
              <button
                type="button"
                class="rounded-lg border border-blue-300 bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                :disabled="loadingUsers"
                @click="loadUsers"
              >
                Search
              </button>
              <button
                type="button"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                :disabled="loadingUsers"
                @click="clearUserSearch"
              >
                Clear
              </button>
            </div>
          </div>

          <p v-if="userMessage" class="mx-5 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700">
            {{ userMessage }}
          </p>

          <p v-if="userError" class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
            {{ userError }}
          </p>

          <div v-if="loadingUsers" class="px-5 py-6 text-sm text-gray-500">
            Loading users...
          </div>

          <div v-else-if="!users.length" class="px-5 py-6 text-sm text-gray-500">
            No users found.
          </div>

          <div v-else class="max-h-[520px] overflow-auto" @scroll.passive="onUsersScroll">
            <table class="w-full min-w-[920px] text-sm">
              <thead>
                <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                  <th class="px-4 py-3 font-semibold">User Profile</th>
                  <th class="px-4 py-3 font-semibold">Role</th>
                  <th class="px-4 py-3 font-semibold">Status</th>
                  <th class="px-4 py-3 font-semibold">Duration</th>
                  <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id" class="border-b border-gray-100 align-top">
                  <td class="px-4 py-3">
                    <p class="font-semibold text-gray-900">{{ getUserName(user) }}</p>
                    <p class="mt-1 text-xs text-gray-600">{{ user.email || 'No email' }}</p>
                  </td>
                  <td class="px-4 py-3 text-gray-700">{{ user.role || 'user' }}</td>
                  <td class="px-4 py-3">
                    <span
                      :class="[
                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                        user.is_suspended ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700',
                      ]"
                    >
                      {{ getSuspensionLabel(user) }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <select
                      v-model="suspensionDurationByUserId[user.id]"
                      class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs text-gray-700"
                    >
                      <option value="7_days">7 days</option>
                      <option value="permanent">Permanent</option>
                    </select>
                  </td>
                  <td class="px-4 py-3">
                    <button
                      type="button"
                      class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                      :disabled="suspendingUserId === user.id || user.role === 'admin'"
                      @click="suspendUser(user)"
                    >
                      {{ suspendingUserId === user.id ? 'Suspending...' : 'Suspend' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="loadingMoreUsers" class="px-4 py-3 text-xs text-gray-500">
              Loading more alumni...
            </div>

            <div v-else-if="!hasMoreUsers && users.length" class="px-4 py-3 text-xs text-gray-400">
              End of alumni list
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>
</template>
