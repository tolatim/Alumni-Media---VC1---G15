<template>
  <Navbar />
  <main class="min-h-screen py-6 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-5">
      <div class="grid grid-cols-12 gap-5">
        <aside class="col-span-12 space-y-5 lg:col-span-3">
          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-[0.1em] text-slate-500">Manage Network</h3>
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between rounded-xl bg-cyan-50 px-3 py-2 text-cyan-800 ring-1 ring-cyan-100">
                <span class="font-semibold">My Connections</span>
                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold">{{ connectionsCount }}</span>
              </div>
              <div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-slate-600">
                <span class="font-medium">Events</span>
                <span class="text-xs">0</span>
              </div>
              <div class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2 text-slate-600">
                <span class="font-medium">Groups</span>
                <span class="text-xs">0</span>
              </div>
            </div>
          </section>
        </aside>

        <section class="col-span-12 space-y-5 lg:col-span-6">
          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-bold text-slate-900">Requests For You</h3>
              <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ pendingPagination.total }}</span>
            </div>
            <div class="space-y-3">
              <article
                v-for="request in pendingRequests"
                :key="request.id"
                class="rounded-xl border border-slate-200 bg-slate-50 p-3"
              >
                <div class="flex items-center justify-between gap-4">
                  <RouterLink :to="{ name: 'Profile', params: { id: request.requester?.id } }" class="flex min-w-0 items-center gap-3">
                    <img :src="request.requester?.profile?.avatar || fallbackAvatar" class="h-11 w-11 rounded-xl object-cover">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold text-slate-800">{{ request.requester?.name || 'Unknown user' }}</p>
                      <p class="truncate text-xs text-slate-500">Sent you a friend request</p>
                    </div>
                  </RouterLink>
                  <div class="flex gap-2">
                    <button
                      @click="acceptRequest(request.id)"
                      class="rounded-lg bg-cyan-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-cyan-700"
                    >
                      Accept
                    </button>
                    <button
                      @click="rejectRequest(request.id)"
                      class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                      Reject
                    </button>
                  </div>
                </div>
              </article>
              <p v-if="!pendingRequests.length" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">No request yet.</p>
            </div>
            <div v-if="pendingPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="pendingPagination.current_page <= 1"
                @click="loadPendingRequests(pendingPagination.current_page - 1)"
              >
                Prev
              </button>
              <p class="text-xs text-slate-500">
                Page {{ pendingPagination.current_page }} / {{ pendingPagination.last_page }}
              </p>
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="pendingPagination.current_page >= pendingPagination.last_page"
                @click="loadPendingRequests(pendingPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-bold text-slate-900">Suggested Friends</h3>
              <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ suggestionsPagination.total }}</span>
            </div>
            <div class="space-y-3">
              <div
                v-for="person in suggestions"
                :key="person.id"
                class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-3"
              >
                <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex min-w-0 items-center gap-3">
                  <img :src="person.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-xl object-cover">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">{{ person.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ person.profile?.headline || 'Alumni member' }}</p>
                  </div>
                </RouterLink>
                <button
                  @click="sendRequest(person.id)"
                  class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                  Connect
                </button>
              </div>
              <p v-if="!suggestions.length" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">No suggestion right now.</p>
            </div>
            <div v-if="suggestionsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="suggestionsPagination.current_page <= 1"
                @click="loadSuggestions(suggestionsPagination.current_page - 1)"
              >
                Prev
              </button>
              <p class="text-xs text-slate-500">
                Page {{ suggestionsPagination.current_page }} / {{ suggestionsPagination.last_page }}
              </p>
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="suggestionsPagination.current_page >= suggestionsPagination.last_page"
                @click="loadSuggestions(suggestionsPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>
        </section>

        <aside class="col-span-12 lg:col-span-3">
          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-bold text-slate-900">My Friends</h3>
              <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ friendsPagination.total }}</span>
            </div>
            <div class="space-y-3">
              <article
                v-for="friend in friends"
                :key="friend.id"
                class="relative rounded-xl border border-slate-200 bg-slate-50 p-3"
              >
                <div class="flex items-center justify-between gap-2">
                  <RouterLink
                    :to="{ name: 'Profile', params: { id: friend.id } }"
                    class="flex min-w-0 items-center gap-3"
                  >
                    <img :src="friend.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-xl object-cover">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold text-slate-800">{{ friend.name }}</p>
                      <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Connected' }}</p>
                    </div>
                  </RouterLink>

                  <button
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100"
                    @click="toggleFriendMenu(friend.id)"
                  >
                    <i class="fa-solid fa-ellipsis"></i>
                  </button>
                </div>

                <div
                  v-if="openFriendMenuId === friend.id"
                  class="absolute right-3 top-12 z-10 w-32 rounded-xl border border-slate-200 bg-white p-2 shadow-lg"
                >
                  <button
                    @click="onClickUnfriend(friend.id)"
                    class="mb-1 w-full rounded-md px-2 py-1.5 text-left text-xs font-semibold text-slate-700 transition hover:bg-slate-100"
                  >
                    Unfriend
                  </button>
                  <button
                    @click="onClickBlock(friend.id)"
                    class="w-full rounded-md px-2 py-1.5 text-left text-xs font-semibold text-rose-700 transition hover:bg-rose-50"
                  >
                    Block
                  </button>
                </div>
              </article>
              <p v-if="!friends.length" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">No friends yet.</p>
            </div>
            <div v-if="friendsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="friendsPagination.current_page <= 1"
                @click="loadMyConnections(friendsPagination.current_page - 1)"
              >
                Prev
              </button>
              <p class="text-xs text-slate-500">
                Page {{ friendsPagination.current_page }} / {{ friendsPagination.last_page }}
              </p>
              <button
                class="rounded-lg border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="friendsPagination.current_page >= friendsPagination.last_page"
                @click="loadMyConnections(friendsPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>
        </aside>
      </div>

      <p v-if="errorMessage" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center text-sm font-medium text-rose-700">{{ errorMessage }}</p>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
const errorMessage = ref('')
const pendingRequests = ref([])
const suggestions = ref([])
const connectionRows = ref([])
const openFriendMenuId = ref(null)
const CONNECTIONS_PER_PAGE = 8
const PENDING_PER_PAGE = 6
const SUGGESTIONS_PER_PAGE = 6

const defaultPagination = (perPage) => ({
  current_page: 1,
  last_page: 1,
  per_page: perPage,
  total: 0,
})

const pendingPagination = ref(defaultPagination(PENDING_PER_PAGE))
const suggestionsPagination = ref(defaultPagination(SUGGESTIONS_PER_PAGE))
const friendsPagination = ref(defaultPagination(CONNECTIONS_PER_PAGE))

const normalizePagination = (payload, fallbackPerPage) => {
  const pagination = payload?.pagination
  if (pagination) return pagination

  const list = Array.isArray(payload?.data) ? payload.data : []
  return {
    current_page: 1,
    last_page: 1,
    per_page: fallbackPerPage,
    total: list.length,
  }
}

const friends = computed(() => {
  let me = null
  try {
    const meRaw = localStorage.getItem('user')
    me = meRaw ? JSON.parse(meRaw) : null
  } catch {
    me = null
  }

  if (!me?.id) return []

  return connectionRows.value
    .map((row) => {
      if (row.requester_id === me.id) return row.addressee
      return row.requester
    })
    .filter(Boolean)
})

const connectionsCount = computed(() => friendsPagination.value.total || friends.value.length)

const loadMyConnections = async (page = 1) => {
  const response = await api.get('/connections/my', {
    params: { page, per_page: CONNECTIONS_PER_PAGE },
  })
  connectionRows.value = response.data?.data || []
  friendsPagination.value = normalizePagination(response.data, CONNECTIONS_PER_PAGE)
}

const loadPendingRequests = async (page = 1) => {
  const response = await api.get('/connections/pending', {
    params: { page, per_page: PENDING_PER_PAGE },
  })
  pendingRequests.value = response.data?.data || []
  pendingPagination.value = normalizePagination(response.data, PENDING_PER_PAGE)
}

const loadSuggestions = async (page = 1) => {
  const response = await api.get('/users/suggestions', {
    params: { page, per_page: SUGGESTIONS_PER_PAGE },
  })
  suggestions.value = response.data?.data || []
  suggestionsPagination.value = normalizePagination(response.data, SUGGESTIONS_PER_PAGE)
}

const loadSuggestionsFallback = async () => {
  const [usersRes, myRes, pendingRes, blockedRes] = await Promise.allSettled([
    api.get('/users'),
    api.get('/connections/my', { params: { page: 1, per_page: 200 } }),
    api.get('/connections/pending', { params: { page: 1, per_page: 200 } }),
    api.get('/connections/blocked', { params: { page: 1, per_page: 200 } }),
  ])

  const users = usersRes.status === 'fulfilled' ? (usersRes.value.data?.data || []) : []
  const myRows = myRes.status === 'fulfilled' ? (myRes.value.data?.data || []) : []
  const pendingRows = pendingRes.status === 'fulfilled' ? (pendingRes.value.data?.data || []) : []
  const blockedRows = blockedRes.status === 'fulfilled' ? (blockedRes.value.data?.data || []) : []

  let me = null
  try {
    const meRaw = localStorage.getItem('user')
    me = meRaw ? JSON.parse(meRaw) : null
  } catch {
    me = null
  }

  const meId = Number(me?.id || 0)
  const existingIds = new Set()
  ;[...myRows, ...pendingRows, ...blockedRows].forEach((row) => {
    const requesterId = Number(row.requester_id || 0)
    const addresseeId = Number(row.addressee_id || 0)
    if (requesterId === meId && addresseeId) existingIds.add(addresseeId)
    if (addresseeId === meId && requesterId) existingIds.add(requesterId)
  })

  suggestions.value = users
    .filter((row) => Number(row.id) !== meId && !existingIds.has(Number(row.id)))
    .slice(0, SUGGESTIONS_PER_PAGE)
  suggestionsPagination.value = defaultPagination(SUGGESTIONS_PER_PAGE)
}

const loadData = async () => {
  errorMessage.value = ''

  const [meRes, myRes, pendingRes, suggestionRes] = await Promise.allSettled([
    api.get('/me'),
    loadMyConnections(1),
    loadPendingRequests(1),
    loadSuggestions(1),
  ])

  if (meRes.status === 'fulfilled') {
    localStorage.setItem('user', JSON.stringify(meRes.value.data))
  } else {
    errorMessage.value = meRes.reason?.response?.data?.message || 'Failed to load user.'
    return
  }

  const failedSections = []

  if (myRes.status === 'rejected') failedSections.push('My friends')
  if (pendingRes.status === 'rejected') failedSections.push('Pending requests')

  if (suggestionRes.status === 'rejected') {
    try {
      await loadSuggestionsFallback()
    } catch {
      suggestions.value = []
      failedSections.push('Suggestions')
    }
  }

  if (failedSections.length) {
    errorMessage.value = `${failedSections.join(', ')} failed to load.`
  }
}

const sendRequest = async (userId) => {
  try {
    await api.post('/connections/request', { user_id: userId })
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send request.'
  }
}

const acceptRequest = async (connectionId) => {
  try {
    const response = await api.post(`/connections/${connectionId}/accept`)
    const row = response.data?.data
    if (row) {
      await loadMyConnections(friendsPagination.value.current_page)
    }
    await loadPendingRequests(pendingPagination.value.current_page)
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to accept request.'
  }
}

const rejectRequest = async (connectionId) => {
  try {
    await api.post(`/connections/${connectionId}/reject`)
    await loadPendingRequests(pendingPagination.value.current_page)
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to reject request.'
  }
}

const refreshSuggestions = async () => {
  try {
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch {
    suggestions.value = []
  }
}

const toggleFriendMenu = (friendId) => {
  openFriendMenuId.value = openFriendMenuId.value === friendId ? null : friendId
}

const unfriend = async (userId) => {
  try {
    await api.post(`/connections/user/${userId}/unfriend`)
    await loadMyConnections(friendsPagination.value.current_page)
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unfriend user.'
  }
}

const blockUser = async (userId) => {
  try {
    await api.post(`/connections/user/${userId}/block`)
    await loadMyConnections(friendsPagination.value.current_page)
    await loadPendingRequests(pendingPagination.value.current_page)
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to block user.'
  }
}

const onClickUnfriend = async (userId) => {
  await unfriend(userId)
  openFriendMenuId.value = null
}

const onClickBlock = async (userId) => {
  await blockUser(userId)
  openFriendMenuId.value = null
}

onMounted(loadData)
</script>
