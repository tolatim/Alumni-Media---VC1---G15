<template>
  <div class="col-span-6 space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur">
      <div class="flex items-center gap-3">
        <img :src="currentUser?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-full border border-slate-200 object-cover shadow-sm">
        <input
          v-model="searchQuery"
          type="text" 
          placeholder="Search posts..."
          class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 transition focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
        >
      </div>

      <div
        v-if="searchQuery.trim().length >= 2"
        class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white"
      >
        <div v-if="userSearchLoading" class="px-4 py-3 text-xs font-semibold text-slate-500">
          Searching users...
        </div>
        <div v-else-if="userSearchError" class="px-4 py-3 text-xs font-semibold text-rose-700">
          {{ userSearchError }}
        </div>
        <div v-else-if="userResults.length">
          <div
            v-for="user in userResults"
            :key="user.id"
            class="flex items-center justify-between gap-3 px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50"
          >
            <RouterLink
              :to="`/profile/${user.id}`"
              class="flex min-w-0 items-center gap-3"
            >
              <img
                :src="user?.profile?.avatar || fallbackAvatar"
                class="h-9 w-9 rounded-full border border-slate-200 object-cover"
                alt="User avatar"
              >
              <div class="min-w-0">
                <div class="truncate font-semibold text-slate-900">{{ user?.name || 'Unknown user' }}</div>
                <div class="truncate text-xs text-slate-500">{{ user?.email }}</div>
              </div>
            </RouterLink>

            <div class="shrink-0">
              <button
                v-if="shouldShowConnectAction(user)"
                type="button"
                @click.prevent.stop="onConnect(user.id)"
                :disabled="isConnectingId === user.id"
                class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {{ isConnectingId === user.id ? 'Sending...' : 'Connect' }}
              </button>

              <span
                v-else-if="getStatus(user.id) === 'pending'"
                class="rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600"
              >
                Pending
              </span>

              <span
                v-else-if="getStatus(user.id) === 'accepted'"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"
              >
                Connected
              </span>

              <span
                v-else-if="getStatus(user.id) === 'blocked'"
                class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700"
              >
                Blocked
              </span>
            </div>
          </div>
        </div>
        <div v-else class="px-4 py-3 text-xs font-semibold text-slate-500">
          No users found.
        </div>
      </div>

      <div class="mt-4 flex items-center justify-end">
        <RouterLink
          to="/post"
          class="inline-flex items-center rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:from-cyan-700 hover:to-blue-700"
        >
          Create Post
        </RouterLink>
      </div>
    </div>

    <PostCard
      v-for="post in filteredPosts"
      :key="post.id"
      :post="post"
      :current-user="currentUser"
      @deleted="handlePostDeleted"
      @refresh-posts="emit('refreshPosts')"
    />

    <div
      v-if="!filteredPosts.length"
      class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"
    >
      <p class="text-sm font-semibold text-slate-700">
        {{ posts.length ? 'No matching posts found.' : 'No posts yet.' }}
      </p>
      <p class="mt-1 text-sm text-slate-500">
        {{ posts.length ? 'Try another keyword in search.' : 'Start the first conversation.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import PostCard from '@/components/ui/PostCard.vue'
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

const emit = defineEmits(['refreshPosts'])

const searchQuery = ref('')
const deletedPostIds = ref([])
const userResults = ref([])
const userSearchLoading = ref(false)
const userSearchError = ref('')
const statusByUserId = ref({})
const isConnectingId = ref(null)
let userSearchTimer = null
let userSearchRequestId = 0
let statusRequestId = 0

const getApiMessage = (error, fallback) => error?.response?.data?.message || fallback

const fetchUsers = async (query) => {
  const requestId = ++userSearchRequestId
  userSearchLoading.value = true
  userSearchError.value = ''

  try {
    const res = await api.get('/users', {
      params: { search: query, per_page: 6 },
      headers: { 'X-Skip-Loading': 'true' },
    })

    if (requestId === userSearchRequestId) {
      userResults.value = res.data?.data || []
      await fetchStatusesForUsers(userResults.value)
    }
  } catch (error) {
    if (requestId === userSearchRequestId) {
      userResults.value = []
      userSearchError.value = getApiMessage(error, 'Failed to search users.')
    }
  } finally {
    if (requestId === userSearchRequestId) {
      userSearchLoading.value = false
    }
  }
}

const getStatus = (userId) => {
  const key = String(userId)
  return statusByUserId.value?.[key]?.status || 'unknown'
}

const shouldShowConnectAction = (user) => {
  const meId = Number(props.currentUser?.id || 0)
  const targetId = Number(user?.id || 0)
  if (!meId || !targetId || meId === targetId) return false

  const status = getStatus(targetId)
  return status === 'none' || status === 'unknown'
}

const fetchStatusesForUsers = async (users) => {
  const requestId = ++statusRequestId
  const list = Array.isArray(users) ? users : []
  const ids = list
    .map((item) => Number(item?.id || 0))
    .filter((id) => Number.isInteger(id) && id > 0)

  if (!ids.length) {
    statusByUserId.value = {}
    return
  }

  try {
    const responses = await Promise.allSettled(
      ids.map((id) =>
        api.get(`/connections/status/${id}`, {
          headers: { 'X-Skip-Loading': 'true' },
        })
      )
    )

    if (requestId !== statusRequestId) return

    const next = {}
    responses.forEach((res, index) => {
      const id = ids[index]
      if (res.status !== 'fulfilled') {
        next[String(id)] = { status: 'unknown' }
        return
      }

      const payload = res.value?.data?.data || {}
      next[String(id)] = {
        status: payload.status || 'none',
        blocked_by_me: Boolean(payload.blocked_by_me),
        blocked_me: Boolean(payload.blocked_me),
      }
    })

    statusByUserId.value = next
  } catch {
    if (requestId !== statusRequestId) return
    statusByUserId.value = {}
  }
}

const onConnect = async (userId) => {
  const targetId = Number(userId || 0)
  if (!targetId) return

  isConnectingId.value = targetId
  try {
    await api.post('/connections/request', { user_id: targetId }, { headers: { 'X-Skip-Loading': 'true' } })
    statusByUserId.value = {
      ...(statusByUserId.value || {}),
      [String(targetId)]: { status: 'pending' },
    }
  } catch (error) {
    userSearchError.value = getApiMessage(error, 'Failed to send connection request.')
  } finally {
    isConnectingId.value = null
  }
}

watch(searchQuery, (value) => {
  const query = value.trim()

  if (userSearchTimer) {
    clearTimeout(userSearchTimer)
    userSearchTimer = null
  }

  if (query.length < 2) {
    userResults.value = []
    userSearchError.value = ''
    userSearchLoading.value = false
    statusByUserId.value = {}
    userSearchRequestId++
    statusRequestId++
    return
  }

  userSearchTimer = setTimeout(() => {
    fetchUsers(query)
  }, 250)
})

onBeforeUnmount(() => {
  if (userSearchTimer) clearTimeout(userSearchTimer)
  userSearchRequestId++
  statusRequestId++
})

const filteredPosts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const visiblePosts = props.posts.filter((post) => !deletedPostIds.value.includes(post.id))
  if (!query) return visiblePosts

  return visiblePosts.filter((post) => {
    const title = (post?.title || '').toLowerCase()
    const content = (post?.content || '').toLowerCase()
    const name =
      (post?.user?.name ||
        `${post?.user?.first_name || ''} ${post?.user?.last_name || ''}`.trim() ||
        '').toLowerCase()
    return title.includes(query) || content.includes(query) || name.includes(query)
  })
})

const handlePostDeleted = (postId) => {
  deletedPostIds.value = [...deletedPostIds.value, postId]
}
</script>
