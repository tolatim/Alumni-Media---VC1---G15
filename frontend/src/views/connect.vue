<template>
  <div>
    <Navbar />

    <main class="connections-page">
      <div class="page-container">

        <!-- --- Page Header ---------------------------------------- -->
        <div class="page-header">
          <div class="page-header-text">
            <h1 class="page-title">My Network</h1>
            <p class="page-sub">Manage your connections, requests and discover new people</p>
          </div>
        </div>

        <!-- --- Grid ----------------------------------------------- -->
        <div class="connections-grid">

          <!-- LEFT: Manage Network sidebar -->
          <aside class="col-left">
            <div class="side-card">
              <p class="side-label">Manage Network</p>
              <nav class="manage-nav">
                <div class="manage-item manage-item--active">
                  <div class="manage-item-icon">
                    <svg viewBox="0 0 18 18" fill="none" width="15" height="15">
                      <circle cx="6.5" cy="5.5" r="3" stroke="currentColor" stroke-width="1.4"/>
                      <circle cx="13" cy="5.5" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                      <path d="M1 16c0-3.038 2.462-4.5 5.5-4.5S12 12.962 12 16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                      <path d="M13 10c1.657 0 3 1.119 3 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                  </div>
                  <span class="manage-item-label">My Connections</span>
                  <span class="manage-item-count">{{ connectionsCount }}</span>
                </div>
              </nav>
            </div>
          </aside>

          <!-- CENTER: Requests + Suggestions -->
          <section class="col-center">

            <!-- Requests For You -->
            <div class="content-card">
              <div class="card-header">
                <div class="card-title-wrap">
                  <h2 class="card-title">Requests For You</h2>
                  <span class="count-pill">{{ pendingStore.pendingPagination.total }}</span>
                </div>
              </div>

              <TransitionGroup name="slide-out" tag="div" class="items-list">
                <article
                  v-for="request in pendingStore.pendingRequests"
                  :key="request.id"
                  class="person-row"
                >
                  <RouterLink
                    :to="{ name: 'Profile', params: { id: request.requester?.id } }"
                    class="person-link"
                  >
                    <div class="person-avatar-wrap">
                      <img
                        v-if="request.requester?.profile?.avatar"
                        :src="request.requester.profile.avatar"
                        class="person-avatar-img"
                        alt=""
                      />
                      <div v-else class="person-avatar-fallback">
                        {{ (request.requester?.name || '?')[0].toUpperCase() }}
                      </div>
                    </div>
                    <div class="person-info">
                      <p class="person-name">{{ request.requester?.name || 'Unknown user' }}</p>
                      <p class="person-sub">Sent you a connection request</p>
                    </div>
                  </RouterLink>
                  <div class="person-actions">
                    <button class="btn-primary" @click="acceptRequest(request.id)">
                      <svg viewBox="0 0 14 14" fill="none" width="12" height="12">
                        <path d="M2 7l4 4 6-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Accept
                    </button>
                    <button class="btn-ghost-danger" @click="rejectRequest(request.id)">
                      <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
                        <path d="M3 3l8 8M11 3L3 11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                      </svg>
                    </button>
                  </div>
                </article>
              </TransitionGroup>

              <div v-if="!pendingStore.pendingRequests.length" class="empty-row">
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                  <circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
                  <path d="M4 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  <path d="M14 5l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                No pending requests
              </div>

              <div v-if="pendingStore.pendingPagination.last_page > 1" class="pagination">
                <button
                  class="page-btn"
                  :disabled="pendingStore.pendingPagination.current_page <= 1"
                  @click="pendingStore.loadPendingRequests(pendingStore.pendingPagination.current_page - 1)"
                >
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Prev
                </button>
                <span class="page-info">
                  {{ pendingStore.pendingPagination.current_page }} / {{ pendingStore.pendingPagination.last_page }}
                </span>
                <button
                  class="page-btn"
                  :disabled="pendingStore.pendingPagination.current_page >= pendingStore.pendingPagination.last_page"
                  @click="pendingStore.loadPendingRequests(pendingStore.pendingPagination.current_page + 1)"
                >
                  Next
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            </div>

            <!-- Suggested Friends -->
            <div class="content-card">
              <div class="card-header">
                <div class="card-title-wrap">
                  <h2 class="card-title">People You May Know</h2>
                </div>
                <div class="friends-search-wrap">
                  <input
                    v-model.trim="suggestionsSearch"
                    type="text"
                    class="friends-search-input"
                    placeholder="Search people..."
                  />
                </div>
              </div>

              <div class="items-list">
                <div
                  v-for="person in filteredSuggestions"
                  :key="person.id"
                  class="person-row"
                >
                  <RouterLink
                    :to="{ name: 'Profile', params: { id: person.id } }"
                    class="person-link"
                  >
                    <div class="person-avatar-wrap">
                      <img
                        v-if="person.profile?.avatar"
                        :src="person.profile.avatar"
                        class="person-avatar-img"
                        alt=""
                      />
                      <div v-else class="person-avatar-fallback person-avatar-fallback--alt">
                        {{ (person.name || '?')[0].toUpperCase() }}
                      </div>
                    </div>
                    <div class="person-info">
                      <p class="person-name">{{ person.name }}</p>
                      <p class="person-sub">{{ person.profile?.headline || 'Alumni member' }}</p>
                    </div>
                  </RouterLink>
                  <button class="btn-connect" @click="sendRequest(person.id)">
                    <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
                      <path d="M7 2v10M2 7h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Connect
                  </button>
                </div>
              </div>

              <div v-if="!filteredSuggestions.length" class="empty-row">
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                  <circle cx="7" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
                  <circle cx="14" cy="7" r="2.5" stroke="currentColor" stroke-width="1.4"/>
                  <path d="M1 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  <path d="M14 12c1.657 0 3 1.119 3 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                {{ suggestionsSearch ? 'No matching people found' : 'No suggestions right now' }}
              </div>

              <div v-if="suggestionsStore.suggestionsPagination.last_page > 1" class="pagination">
                <button
                  class="page-btn"
                  :disabled="suggestionsStore.suggestionsPagination.current_page <= 1"
                  @click="loadSuggestions(suggestionsStore.suggestionsPagination.current_page - 1)"
                >
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Prev
                </button>
                <span class="page-info">
                  {{ suggestionsStore.suggestionsPagination.current_page }} / {{ suggestionsStore.suggestionsPagination.last_page }}
                </span>
                <button
                  class="page-btn"
                  :disabled="suggestionsStore.suggestionsPagination.current_page >= suggestionsStore.suggestionsPagination.last_page"
                  @click="loadSuggestions(suggestionsStore.suggestionsPagination.current_page + 1)"
                >
                  Next
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            </div>

          </section>

          <!-- RIGHT: My Friends -->
          <aside class="col-right">
            <div class="content-card">
              <div class="card-header">
                <div class="card-title-wrap">
                  <h2 class="card-title">My Friends</h2>
                  <span class="count-pill">{{ connectionRowsStore.friendsPagination.total }}</span>
                </div>
                <div class="friends-search-wrap">
                  <input
                    v-model.trim="friendsSearch"
                    type="text"
                    class="friends-search-input"
                    placeholder="Search connections..."
                  />
                </div>
              </div>

              <div class="items-list">
                <article
                  v-for="friend in filteredFriends"
                  :key="friend.id"
                  class="person-row person-row--friend"
                >
                  <RouterLink
                    :to="{ name: 'Profile', params: { id: friend.id } }"
                    class="person-link"
                  >
                    <div class="person-avatar-wrap">
                      <img
                        v-if="friend.profile?.avatar"
                        :src="friend.profile.avatar"
                        class="person-avatar-img"
                        alt=""
                      />
                      <div v-else class="person-avatar-fallback person-avatar-fallback--friend">
                        {{ (friend.name || '?')[0].toUpperCase() }}
                      </div>
                    </div>
                    <div class="person-info">
                      <p class="person-name">{{ friend.name }}</p>
                      <p class="person-sub">{{ friend.profile?.headline || 'Connected' }}</p>
                    </div>
                  </RouterLink>

                  <!-- Context menu trigger -->
                  <div class="friend-menu-wrap">
                    <button
                      class="btn-more"
                      :class="{ 'btn-more--open': openFriendMenuId === friend.id }"
                      @click="toggleFriendMenu(friend.id)"
                    >
                      <svg viewBox="0 0 16 16" fill="currentColor" width="14" height="14">
                        <circle cx="8" cy="3" r="1.3"/><circle cx="8" cy="8" r="1.3"/><circle cx="8" cy="13" r="1.3"/>
                      </svg>
                    </button>

                    <Transition name="menu-pop">
                      <div
                         v-if="openFriendMenuId === friend.id" 
                        class="friend-menu"
                        v-click-outside="closeFriendMenu"
                      >
                        <button class="menu-item" @click="onClickUnfriend(friend.id)">
                          <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
                            <circle cx="6.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M1 14c0-2.761 2.462-4 5.5-4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M11 11l4 4M15 11l-4 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                          </svg>
                          Unfriend
                        </button>
                        <button class="menu-item menu-item--danger" @click="onClickBlock(friend.id)">
                          <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
                            <circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.3"/>
                            <path d="M3.8 12.2l8.4-8.4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>
                          </svg>
                          Block
                        </button>
                      </div>
                    </Transition>
                  </div>
                </article>
              </div>

              <div v-if="!filteredFriends.length" class="empty-row">
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                  <circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
                  <path d="M4 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                {{ friendsSearch ? 'No matching connections found' : 'No connections yet' }}
              </div>

              <div v-if="connectionRowsStore.friendsPagination.last_page > 1" class="pagination">
                <button
                  class="page-btn"
                  :disabled="connectionRowsStore.friendsPagination.current_page <= 1"
                  @click="connectionRowsStore.loadMyConnections(connectionRowsStore.friendsPagination.current_page - 1)"
                >
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  Prev
                </button>
                <span class="page-info">
                  {{ connectionRowsStore.friendsPagination.current_page }} / {{ connectionRowsStore.friendsPagination.last_page }}
                </span>
                <button
                  class="page-btn"
                  :disabled="connectionRowsStore.friendsPagination.current_page >= connectionRowsStore.friendsPagination.last_page"
                  @click="connectionRowsStore.loadMyConnections(connectionRowsStore.friendsPagination.current_page + 1)"
                >
                  Next
                  <svg viewBox="0 0 14 14" fill="none" width="12" height="12"><path d="M5 3l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
              </div>
            </div>
          </aside>

        </div>

        <!-- Error Toast -->
        <Transition name="toast">
          <div v-if="errorMessage" class="error-toast" @click="errorMessage = ''">
            <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
              <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/>
              <path d="M8 5v4M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            {{ errorMessage }}
          </div>
        </Transition>

      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import { usePendingRequestsStore } from '@/stores/pendingRequests'
import { useSuggestionsStore } from '@/stores/suggestions'
import { useConnectionsStore } from '@/stores/connectionRows'

const pendingStore = usePendingRequestsStore()
const suggestionsStore = useSuggestionsStore()
const connectionRowsStore = useConnectionsStore()

// -- WebSocket -----------------------------------------------------
const current_user = JSON.parse(localStorage.getItem('user') || 'null')
const ws = new WebSocket(import.meta.env.VITE_WS_URL || 'ws://localhost:8081')

ws.onopen = () => {
  if (!current_user?.id) return
  ws.send(JSON.stringify({ type: 'auth', user_id: current_user.id }))
}
ws.onmessage = (event) => {
  const message = JSON.parse(event.data)
  const cp = (store) => store.current_page ?? 1
  if (message.type === 'connection_request') {
    pendingStore.loadPendingRequests(cp(pendingStore.pendingPagination))
    suggestionsStore.loadSuggestions(cp(suggestionsStore.suggestionsPagination))
  } else if (message.type === 'accept_request') {
    connectionRowsStore.loadMyConnections(cp(connectionRowsStore.friendsPagination))
  } else if (['unfriend', 'reject', 'block'].includes(message.type)) {
    suggestionsStore.loadSuggestions(cp(suggestionsStore.suggestionsPagination))
    connectionRowsStore.loadMyConnections(cp(connectionRowsStore.friendsPagination))
    if (message.type === 'reject') {
      suggestionsStore.loadSuggestions(cp(suggestionsStore.suggestionsPagination))
    }
  }
}

// -- State ---------------------------------------------------------
const errorMessage = ref('')
const openFriendMenuId = ref(null)
const friendsSearch = ref('')
const suggestionsSearch = ref('')

// -- Computed ------------------------------------------------------
const friends = computed(() => {
  let me = null
  try { me = JSON.parse(localStorage.getItem('user')) } catch { /* noop */ }
  if (!me?.id) return []
  return connectionRowsStore.connectionRows
    .map((row) => (row.requester_id === me.id ? row.addressee : row.requester))
    .filter(Boolean)
})

const connectionsCount = computed(
  () => connectionRowsStore.friendsPagination.total || friends.value.length
)

const filteredFriends = computed(() => {
  const keyword = friendsSearch.value.toLowerCase()
  if (!keyword) return friends.value

  return friends.value.filter((friend) => {
    const name = String(friend?.name || '').toLowerCase()
    const headline = String(friend?.profile?.headline || '').toLowerCase()
    return name.includes(keyword) || headline.includes(keyword)
  })
})

const filteredSuggestions = computed(() => {
  const keyword = suggestionsSearch.value.toLowerCase()
  if (!keyword) return suggestionsStore.suggestions

  return suggestionsStore.suggestions.filter((person) => {
    const name = String(person?.name || '').toLowerCase()
    const headline = String(person?.profile?.headline || '').toLowerCase()
    return name.includes(keyword) || headline.includes(keyword)
  })
})

// -- Actions -------------------------------------------------------
const showError = (err, fallback) => {
  errorMessage.value = err.response?.data?.message || fallback
  setTimeout(() => { errorMessage.value = '' }, 4000)
}

const sendRequest = async (userId) => {
  try {
    await api.post('/connections/request', { user_id: userId })
    await suggestionsStore.loadSuggestions(suggestionsStore.suggestionsPagination.current_page)
  } catch (e) { showError(e, 'Failed to send request.') }
}

const acceptRequest = async (connectionId) => {
  try {
    const res = await api.post(`/connections/${connectionId}/accept`)
    if (res.data?.data) await connectionRowsStore.loadMyConnections(connectionRowsStore.friendsPagination.current_page)
    await pendingStore.loadPendingRequests(pendingStore.pendingPagination.current_page)
    await suggestionsStore.loadSuggestions(suggestionsStore.suggestionsPagination.current_page)
  } catch (e) { showError(e, 'Failed to accept request.') }
}

const rejectRequest = async (connectionId) => {
  try {
    await api.post(`/connections/${connectionId}/reject`)
    await pendingStore.loadPendingRequests(pendingStore.pendingPagination.current_page)
    await suggestionsStore.loadSuggestions(suggestionsStore.suggestionsPagination.current_page)
  } catch (e) { showError(e, 'Failed to reject request.') }
}

const toggleFriendMenu = (friendId) => {
  openFriendMenuId.value = openFriendMenuId.value === friendId ? null : friendId
}

const closeFriendMenu = () => {
  openFriendMenuId.value = null
}

const unfriend = async (userId) => {
  try {
    await api.post(`/connections/user/${userId}/unfriend`)
    await connectionRowsStore.loadMyConnections(connectionRowsStore.friendsPagination.current_page)
    await suggestionsStore.loadSuggestions(suggestionsStore.suggestionsPagination.current_page)
  } catch (e) { showError(e, 'Failed to unfriend user.') }
}

const blockUser = async (userId) => {
  try {
    await api.post(`/connections/user/${userId}/block`)
    await connectionRowsStore.loadMyConnections(connectionRowsStore.friendsPagination.current_page)
    await pendingStore.loadPendingRequests(pendingStore.pendingPagination.current_page)
    await suggestionsStore.loadSuggestions(suggestionsStore.suggestionsPagination.current_page)
  } catch (e) { showError(e, 'Failed to block user.') }
}

const onClickUnfriend = async (userId) => {
  openFriendMenuId.value = null
  await unfriend(userId)
}
const onClickBlock = async (userId) => {
  openFriendMenuId.value = null
  await blockUser(userId)
}

const loadSuggestions = async (page) => {
  await suggestionsStore.loadSuggestions(page)
}

// -- Click-outside directive ---------------------------------------
const vClickOutside = {
  mounted(el, binding) {
    el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(e) }
    document.addEventListener('click', el._clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('click', el._clickOutside)
  },
}

onMounted(async () => {
  await pendingStore.loadPendingRequests()
  await suggestionsStore.loadSuggestions()
  await connectionRowsStore.loadMyConnections()
})

onBeforeUnmount(() => {
  try {
    ws.close()
  } catch {
    // ignore close errors
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Lora:wght@600&display=swap');

/* -- Page shell ------------------------------------------------ */
.connections-page {
  min-height: 100vh;
  padding: 32px 0 64px;
  font-family: 'DM Sans', sans-serif;
}
.page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Page header */
.page-header {
  margin-bottom: 24px;
  display: flex;
  align-items: flex-end;
  gap: 16px;
}
.page-title {
  font-family: 'Lora', serif;
  font-size: 26px;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.1;
  margin: 0 0 4px;
}
.page-sub {
  font-size: 13.5px;
  color: #94a3b8;
  margin: 0;
}

/* -- Grid ------------------------------------------------------ */
.connections-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}
@media (min-width: 1024px) {
  .connections-grid {
    grid-template-columns: 220px 1fr 280px;
    align-items: start;
  }
}
.col-left, .col-right { display: flex; flex-direction: column; gap: 16px; }
.col-center { display: flex; flex-direction: column; gap: 16px; }

/* -- Shared card ----------------------------------------------- */
.side-card,
.content-card {
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  padding: 18px;
}

/* -- Left sidebar: Manage Network ------------------------------ */
.side-label {
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #94a3b8;
  margin: 0 0 12px 2px;
}
.manage-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.manage-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.15s;
  font-size: 13.5px;
  font-weight: 500;
  color: #475569;
}
.manage-item:hover { background: #f8fafc; }
.manage-item--active {
  background: #eff6ff;
  color: #1d6fbd;
}
.manage-item--active .manage-item-icon { background: #dbeafe; }
.manage-item--active .manage-item-icon svg { stroke: #1d6fbd; }
.manage-item-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s;
}
.manage-item-icon svg { stroke: #64748b; }
.manage-item-label { flex: 1; }
.manage-item-count {
  font-size: 11px;
  font-weight: 600;
  background: #fff;
  border: 1px solid #bfdbfe;
  color: #1d6fbd;
  border-radius: 20px;
  padding: 1px 8px;
  min-width: 24px;
  text-align: center;
}
.manage-item-count--muted {
  background: #f1f5f9;
  border-color: #e2e8f0;
  color: #94a3b8;
}

/* -- Card header ----------------------------------------------- */
.card-header { margin-bottom: 16px; }
.card-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}
.card-title {
  font-family: 'Lora', serif;
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
}
.count-pill {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 1px 9px;
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
}

.friends-search-wrap {
  margin-top: 10px;
}

.friends-search-input {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #0f172a;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  padding: 8px 10px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.friends-search-input::placeholder {
  color: #94a3b8;
}

.friends-search-input:focus {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

/* -- Person rows ----------------------------------------------- */
.items-list { display: flex; flex-direction: column; gap: 6px; }

.person-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  background: #fafafa;
  transition: border-color 0.15s, background 0.15s;
}
.person-row:hover { border-color: #e2e8f0; background: #fff; }
.person-row--friend { background: #fff; border-color: #f1f5f9; }

.person-link {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex: 1;
  min-width: 0;
}

.person-avatar-wrap { flex-shrink: 0; }
.person-avatar-img,
.person-avatar-fallback {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: block;
}
.person-avatar-img { object-fit: cover; }
.person-avatar-fallback {
  background: linear-gradient(135deg, #1565a8, #0b8faa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Lora', serif;
  font-size: 15px;
  color: #fff;
  font-weight: 600;
}
.person-avatar-fallback--alt {
  background: linear-gradient(135deg, #7c3aed, #0b8faa);
}
.person-avatar-fallback--friend {
  background: linear-gradient(135deg, #16a34a, #1565a8);
}

.person-info { min-width: 0; }
.person-name {
  font-size: 13.5px;
  font-weight: 500;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.person-sub {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* -- Buttons --------------------------------------------------- */
.person-actions { display: flex; gap: 6px; align-items: center; flex-shrink: 0; }

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #0c3d60, #1565a8 60%, #0b8faa);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s, transform 0.12s;
}
.btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }

.btn-ghost-danger {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
  cursor: pointer;
  flex-shrink: 0;
  padding: 0;
  transition: background 0.15s;
}
.btn-ghost-danger:hover { background: #ffe4e6; }

.btn-connect {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d6fbd;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  transition: background 0.15s, border-color 0.15s;
}
.btn-connect:hover { background: #dbeafe; border-color: #93c5fd; }

/* -- Friend context menu --------------------------------------- */
.friend-menu-wrap { position: relative; flex-shrink: 0; }

.btn-more {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  padding: 0;
  transition: background 0.15s, color 0.15s;
}
.btn-more:hover, .btn-more--open {
  background: #f1f5f9;
  color: #334155;
}

.friend-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 6px);
  z-index: 20;
  width: 140px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  box-shadow: 0 8px 24px rgba(0,0,0,0.10);
  padding: 6px;
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: none;
  background: transparent;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 500;
  color: #334155;
  cursor: pointer;
  text-align: left;
  transition: background 0.12s;
}
.menu-item:hover { background: #f1f5f9; }
.menu-item--danger { color: #be123c; }
.menu-item--danger:hover { background: #fff1f2; }

/* -- Pagination ------------------------------------------------ */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #f1f5f9;
}
.page-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: background 0.15s;
}
.page-btn:hover:not(:disabled) { background: #f1f5f9; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 12px; color: #94a3b8; }

/* -- Empty row ------------------------------------------------- */
.empty-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 14px;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 13px;
  color: #94a3b8;
}
.empty-row svg { flex-shrink: 0; stroke: #94a3b8; }

/* -- Error toast ----------------------------------------------- */
.error-toast {
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 12px;
  background: #fff1f2;
  border: 1px solid #fecdd3;
  color: #be123c;
  font-size: 13px;
  font-weight: 500;
  box-shadow: 0 4px 20px rgba(190,18,60,0.12);
  cursor: pointer;
  z-index: 100;
}
.error-toast svg { flex-shrink: 0; stroke: #be123c; }

/* -- Transitions ----------------------------------------------- */
.slide-out-enter-active { transition: opacity 0.2s, transform 0.2s; }
.slide-out-leave-active { transition: opacity 0.18s, transform 0.18s; }
.slide-out-enter-from { opacity: 0; transform: translateY(6px); }
.slide-out-leave-to { opacity: 0; transform: translateY(-4px); }
.slide-out-move { transition: transform 0.25s ease; }

.menu-pop-enter-active { transition: opacity 0.15s, transform 0.15s; }
.menu-pop-leave-active { transition: opacity 0.1s, transform 0.1s; }
.menu-pop-enter-from { opacity: 0; transform: scale(0.95) translateY(-4px); }
.menu-pop-leave-to { opacity: 0; transform: scale(0.95) translateY(-4px); }

.toast-enter-active { transition: opacity 0.2s, transform 0.2s; }
.toast-leave-active { transition: opacity 0.15s, transform 0.15s; }
.toast-enter-from { opacity: 0; transform: translateX(-50%) translateY(12px); }
.toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(12px); }
</style>
