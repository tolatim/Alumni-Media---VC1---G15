<template>
  <Navbar />
  <PageLoading />
  <main class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(8,145,178,0.08),_transparent_35%),linear-gradient(180deg,_#f8fafc_0%,_#ffffff_45%,_#f8fafc_100%)] py-6 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-5">
      <section class="rounded-[30px] border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-600">Build Your Circle</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900 sm:text-3xl">Connections that feel easy to manage</h1>
            <p class="mt-2 text-sm leading-6 text-slate-500">
              Keep up with friend requests, discover alumni you may know, and reach the people you already trust without extra clicks.
            </p>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[420px]">
            <div class="rounded-2xl border border-cyan-100 bg-cyan-50 px-4 py-3">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-700">Friends</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ connectionsCount }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-700">Requests</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ pendingPagination.total }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Suggestions</p>
              <p class="mt-2 text-2xl font-semibold text-slate-900">{{ suggestionsPagination.total }}</p>
            </div>
          </div>
        </div>

        <div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="section in sections"
              :key="section.id"
              type="button"
              class="rounded-full border px-4 py-2 text-xs font-semibold transition"
              :class="activeSection === section.id
                ? 'border-slate-900 bg-slate-900 text-white'
                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
              @click="activeSection = section.id"
            >
              {{ section.label }}
            </button>
          </div>

          <div class="flex w-full items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm lg:max-w-sm">
            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search people by name or headline..."
              class="w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
            >
          </div>
        </div>
      </section>

      <p v-if="errorMessage" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
        {{ errorMessage }}
      </p>

      <div class="mt-5 grid gap-5 xl:grid-cols-[1.15fr_1fr]">
        <section class="space-y-5">
          <section
            v-if="showRequestsSection"
            class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-amber-600">Requests</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">People waiting on you</h2>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ pendingPagination.total }}</span>
            </div>

            <div v-if="!filteredPendingRequests.length" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
              <p class="text-sm font-semibold text-slate-700">No pending requests</p>
              <p class="mt-1 text-xs text-slate-500">When someone sends you a request, it will appear here.</p>
            </div>

            <div v-else class="mt-4 space-y-3">
              <article
                v-for="request in filteredPendingRequests"
                :key="request.id"
                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4"
              >
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                  <RouterLink :to="{ name: 'Profile', params: { id: request.requester?.id } }" class="flex min-w-0 items-center gap-3">
                    <img :src="request.requester?.profile?.avatar || fallbackAvatar" class="h-12 w-12 rounded-2xl object-cover">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold text-slate-900">{{ request.requester?.name || 'Unknown user' }}</p>
                      <p class="truncate text-xs text-slate-500">{{ request.requester?.profile?.headline || 'Wants to connect with you' }}</p>
                    </div>
                  </RouterLink>

                  <div class="flex flex-wrap gap-2 sm:justify-end">
                    <button
                      type="button"
                      class="rounded-xl bg-cyan-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
                      :disabled="isBusy('accept', request.id)"
                      @click="acceptRequest(request.id)"
                    >
                      {{ isBusy('accept', request.id) ? 'Accepting...' : 'Accept' }}
                    </button>
                    <button
                      type="button"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                      :disabled="isBusy('reject', request.id)"
                      @click="rejectRequest(request.id)"
                    >
                      {{ isBusy('reject', request.id) ? 'Rejecting...' : 'Reject' }}
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <div v-if="pendingPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="pendingPagination.current_page <= 1"
                @click="loadPendingRequests(pendingPagination.current_page - 1)"
              >
                Previous
              </button>
              <p class="text-xs text-slate-500">Page {{ pendingPagination.current_page }} of {{ pendingPagination.last_page }}</p>
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="pendingPagination.current_page >= pendingPagination.last_page"
                @click="loadPendingRequests(pendingPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>

          <section
            v-if="showSuggestionsSection"
            class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-600">Suggestions</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">People you may want to know</h2>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ suggestionsPagination.total }}</span>
            </div>

            <div v-if="!filteredSuggestions.length" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
              <p class="text-sm font-semibold text-slate-700">No suggestions right now</p>
              <p class="mt-1 text-xs text-slate-500">Try again later. New alumni suggestions will show up here.</p>
            </div>

            <div v-else class="mt-4 grid gap-3 md:grid-cols-2">
              <article
                v-for="person in filteredSuggestions"
                :key="person.id"
                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4"
              >
                <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex items-center gap-3">
                  <img :src="person.profile?.avatar || fallbackAvatar" class="h-12 w-12 rounded-2xl object-cover">
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ person.name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ person.profile?.headline || 'Alumni member' }}</p>
                  </div>
                </RouterLink>
                <div class="mt-4 flex items-center justify-between gap-2">
                  <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="text-xs font-semibold text-slate-500 hover:text-slate-700">
                    View profile
                  </RouterLink>
                  <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                    :disabled="isBusy('send', person.id)"
                    @click="sendRequest(person.id)"
                  >
                    {{ isBusy('send', person.id) ? 'Sending...' : 'Connect' }}
                  </button>
                </div>
              </article>
            </div>

            <div v-if="suggestionsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="suggestionsPagination.current_page <= 1"
                @click="loadSuggestions(suggestionsPagination.current_page - 1)"
              >
                Previous
              </button>
              <p class="text-xs text-slate-500">Page {{ suggestionsPagination.current_page }} of {{ suggestionsPagination.last_page }}</p>
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="suggestionsPagination.current_page >= suggestionsPagination.last_page"
                @click="loadSuggestions(suggestionsPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>
        </section>

        <aside class="space-y-5">
          <section
            v-if="showFriendsSection"
            class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-600">Friends</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">Your trusted people</h2>
              </div>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ friendsPagination.total }}</span>
            </div>

            <div v-if="!filteredFriends.length" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
              <p class="text-sm font-semibold text-slate-700">No friends match this search</p>
              <p class="mt-1 text-xs text-slate-500">Try another keyword or explore new suggestions.</p>
            </div>

            <div v-else class="mt-4 space-y-3">
              <article
                v-for="friend in filteredFriends"
                :key="friend.id"
                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4"
              >
                <div class="flex flex-col gap-4">
                  <RouterLink :to="{ name: 'Profile', params: { id: friend.id } }" class="flex items-center gap-3">
                    <img :src="friend.profile?.avatar || fallbackAvatar" class="h-12 w-12 rounded-2xl object-cover">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold text-slate-900">{{ friend.name }}</p>
                      <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Connected alumni' }}</p>
                    </div>
                  </RouterLink>

                  <div class="flex flex-wrap gap-2">
                    <RouterLink
                      :to="{ name: 'MessageWithUser', params: { userId: friend.id } }"
                      class="rounded-xl bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                    >
                      Message
                    </RouterLink>
                    <button
                      type="button"
                      class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                      :disabled="isBusy('unfriend', friend.id)"
                      @click="unfriend(friend.id)"
                    >
                      {{ isBusy('unfriend', friend.id) ? 'Removing...' : 'Unfriend' }}
                    </button>
                    <button
                      type="button"
                      class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:opacity-60"
                      :disabled="isBusy('block', friend.id)"
                      @click="blockUser(friend.id)"
                    >
                      {{ isBusy('block', friend.id) ? 'Blocking...' : 'Block' }}
                    </button>
                  </div>
                </div>
              </article>
            </div>

            <div v-if="friendsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="friendsPagination.current_page <= 1"
                @click="loadMyConnections(friendsPagination.current_page - 1)"
              >
                Previous
              </button>
              <p class="text-xs text-slate-500">Page {{ friendsPagination.current_page }} of {{ friendsPagination.last_page }}</p>
              <button
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
                :disabled="friendsPagination.current_page >= friendsPagination.last_page"
                @click="loadMyConnections(friendsPagination.current_page + 1)"
              >
                Next
              </button>
            </div>
          </section>

          <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Friendly Tip</p>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Good networking feels personal</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">
              Send connection requests to people you recognize, and follow up with a short message after they accept. A simple hello often works best.
            </p>
          </section>
        </aside>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Navbar from '@/components/ui/nav.vue'
import PageLoading from '@/components/ui/PageLoading.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const errorMessage = ref('')
const me = ref(null)
const pendingRequests = ref([])
const suggestions = ref([])
const connectionRows = ref([])
const searchQuery = ref('')
const activeSection = ref('all')
const busyAction = ref('')

const CONNECTIONS_PER_PAGE = 8
const PENDING_PER_PAGE = 6
const SUGGESTIONS_PER_PAGE = 6

const sections = [
  { id: 'all', label: 'Everything' },
  { id: 'requests', label: 'Requests' },
  { id: 'suggestions', label: 'Suggestions' },
  { id: 'friends', label: 'Friends' },
]

const defaultPagination = (perPage) => ({
  current_page: 1,
  last_page: 1,
  per_page: perPage,
  total: 0,
})

const pendingPagination = ref(defaultPagination(PENDING_PER_PAGE))
const suggestionsPagination = ref(defaultPagination(SUGGESTIONS_PER_PAGE))
const friendsPagination = ref(defaultPagination(CONNECTIONS_PER_PAGE))

const meId = computed(() => Number(me.value?.id || 0))

const toId = (value) => Number(value || 0)

const upsertById = (items, nextItem) => {
  if (!nextItem?.id) return items
  const targetId = toId(nextItem.id)
  return [nextItem, ...items.filter((item) => toId(item.id) !== targetId)]
}

const matchesSearch = (person) => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return true
  const name = String(person?.name || '').toLowerCase()
  const headline = String(person?.profile?.headline || '').toLowerCase()
  return name.includes(query) || headline.includes(query)
}

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
  const currentUserId = meId.value
  if (!currentUserId) return []

  return connectionRows.value
    .map((row) => (toId(row.requester_id) === currentUserId ? row.addressee : row.requester))
    .filter(Boolean)
})

const filteredPendingRequests = computed(() =>
  pendingRequests.value.filter((row) => matchesSearch(row.requester))
)

const filteredSuggestions = computed(() =>
  suggestions.value.filter((person) => matchesSearch(person))
)

const filteredFriends = computed(() =>
  friends.value.filter((friend) => matchesSearch(friend))
)

const showRequestsSection = computed(() => activeSection.value === 'all' || activeSection.value === 'requests')
const showSuggestionsSection = computed(() => activeSection.value === 'all' || activeSection.value === 'suggestions')
const showFriendsSection = computed(() => activeSection.value === 'all' || activeSection.value === 'friends')
const connectionsCount = computed(() => friendsPagination.value.total || friends.value.length)

const setBusy = (type, id) => {
  busyAction.value = `${type}:${id}`
}

const clearBusy = () => {
  busyAction.value = ''
}

const isBusy = (type, id) => busyAction.value === `${type}:${id}`

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
    api.get('/users', { params: { page: 1, per_page: 50 } }),
    api.get('/connections/my', { params: { page: 1, per_page: 200 } }),
    api.get('/connections/pending', { params: { page: 1, per_page: 200 } }),
    api.get('/connections/blocked', { params: { page: 1, per_page: 200 } }),
  ])

  const users = usersRes.status === 'fulfilled' ? (usersRes.value.data?.data || []) : []
  const myRows = myRes.status === 'fulfilled' ? (myRes.value.data?.data || []) : []
  const pendingRows = pendingRes.status === 'fulfilled' ? (pendingRes.value.data?.data || []) : []
  const blockedRows = blockedRes.status === 'fulfilled' ? (blockedRes.value.data?.data || []) : []

  const existingIds = new Set()
  ;[...myRows, ...pendingRows, ...blockedRows].forEach((row) => {
    const requesterId = toId(row.requester_id)
    const addresseeId = toId(row.addressee_id)
    if (requesterId === meId.value && addresseeId) existingIds.add(addresseeId)
    if (addresseeId === meId.value && requesterId) existingIds.add(requesterId)
  })

  suggestions.value = users
    .filter((row) => toId(row.id) !== meId.value && !existingIds.has(toId(row.id)))
    .slice(0, SUGGESTIONS_PER_PAGE)
  suggestionsPagination.value = defaultPagination(SUGGESTIONS_PER_PAGE)
}

const removeSuggestion = (userId) => {
  const targetId = toId(userId)
  suggestions.value = suggestions.value.filter((person) => toId(person.id) !== targetId)
  suggestionsPagination.value = {
    ...suggestionsPagination.value,
    total: Math.max(0, Number(suggestionsPagination.value.total || 0) - 1),
  }
}

const removePendingRequest = (connectionId) => {
  const targetId = toId(connectionId)
  pendingRequests.value = pendingRequests.value.filter((row) => toId(row.id) !== targetId)
  pendingPagination.value = {
    ...pendingPagination.value,
    total: Math.max(0, Number(pendingPagination.value.total || 0) - 1),
  }
}

const addConnectionRow = (row) => {
  if (!row?.id) return
  connectionRows.value = upsertById(connectionRows.value, row)
  friendsPagination.value = {
    ...friendsPagination.value,
    total: Number(friendsPagination.value.total || 0) + 1,
  }
}

const removeConnectionByUser = (userId) => {
  const targetId = toId(userId)
  const before = connectionRows.value.length
  connectionRows.value = connectionRows.value.filter((row) => {
    const requesterId = toId(row.requester_id)
    const addresseeId = toId(row.addressee_id)
    return requesterId !== targetId && addresseeId !== targetId
  })

  if (connectionRows.value.length !== before) {
    friendsPagination.value = {
      ...friendsPagination.value,
      total: Math.max(0, Number(friendsPagination.value.total || 0) - 1),
    }
  }
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
    me.value = meRes.value.data
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
    setBusy('send', userId)
    await api.post('/connections/request', { user_id: userId })
    removeSuggestion(userId)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send request.'
  } finally {
    clearBusy()
  }
}

const acceptRequest = async (connectionId) => {
  try {
    setBusy('accept', connectionId)
    const response = await api.post(`/connections/${connectionId}/accept`)
    const row = response.data?.data
    if (row) {
      addConnectionRow(row)
      const requesterId = toId(row.requester_id)
      if (requesterId) removeSuggestion(requesterId)
    }
    removePendingRequest(connectionId)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to accept request.'
  } finally {
    clearBusy()
  }
}

const rejectRequest = async (connectionId) => {
  try {
    setBusy('reject', connectionId)
    const targetId = toId(connectionId)
    const row = pendingRequests.value.find((item) => toId(item.id) === targetId)
    await api.post(`/connections/${connectionId}/reject`)
    removePendingRequest(connectionId)
    if (row?.requester) {
      suggestions.value = upsertById(suggestions.value, row.requester)
      suggestionsPagination.value = {
        ...suggestionsPagination.value,
        total: Number(suggestionsPagination.value.total || 0) + 1,
      }
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to reject request.'
  } finally {
    clearBusy()
  }
}

const refreshSuggestions = async () => {
  try {
    await loadSuggestions(suggestionsPagination.value.current_page)
  } catch {
    suggestions.value = []
  }
}

const unfriend = async (userId) => {
  try {
    setBusy('unfriend', userId)
    await api.post(`/connections/user/${userId}/unfriend`)
    removeConnectionByUser(userId)
    void refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unfriend user.'
  } finally {
    clearBusy()
  }
}

const blockUser = async (userId) => {
  try {
    setBusy('block', userId)
    await api.post(`/connections/user/${userId}/block`)
    removeConnectionByUser(userId)
    const targetId = toId(userId)
    pendingRequests.value = pendingRequests.value.filter((row) => toId(row.requester?.id) !== targetId)
    removeSuggestion(userId)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to block user.'
  } finally {
    clearBusy()
  }
}

onMounted(loadData)
</script>
