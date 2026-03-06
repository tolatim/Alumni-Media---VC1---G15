<template>
  <Navbar />
  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-12 gap-6">
      <aside class="col-span-3 space-y-6">
        <div class="bg-white rounded-xl shadow p-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Manage My Network</h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between p-3 rounded-lg bg-teal-50 text-teal-800">
              <span>My Connections</span>
              <span class="font-semibold">{{ connectionsCount }}</span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg border">
              <span>Events</span>
              <span class="text-gray-500">0</span>
            </div>
            <div class="flex items-center justify-between p-3 rounded-lg border">
              <span>Group</span>
              <span class="text-gray-500">0</span>
            </div>
          </div>
        </div>
      </aside>

      <section class="col-span-6 space-y-6">
        <div class="bg-white rounded-xl shadow p-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Requests For You</h3>
          <div class="space-y-4">
            <div
              v-for="request in pendingRequests"
              :key="request.id"
              class="border rounded-lg p-4"
            >
              <div class="flex items-center justify-between gap-4">
                <RouterLink :to="{ name: 'Profile', params: { id: request.requester?.id } }" class="flex items-center gap-3 min-w-0">
                  <img :src="request.requester?.profile?.avatar || fallbackAvatar" class="w-11 h-11 rounded-full object-cover">
                  <div class="min-w-0">
                    <p class="font-medium text-gray-800 truncate">{{ request.requester?.name || 'Unknown user' }}</p>
                    <p class="text-xs text-gray-500 truncate">sent you a friend request</p>
                  </div>
                </RouterLink>
                <div class="flex gap-2">
                  <button
                    @click="acceptRequest(request.id)"
                    class="text-xs px-3 py-2 rounded-full bg-teal-600 text-white hover:bg-teal-700"
                  >
                    Accept
                  </button>
                  <button
                    @click="rejectRequest(request.id)"
                    class="text-xs px-3 py-2 rounded-full border hover:bg-gray-100"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </div>
            <p v-if="!pendingRequests.length" class="text-sm text-gray-500">No request yet.</p>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow p-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Suggested Friends</h3>
          <div class="space-y-4">
            <div
              v-for="person in suggestions"
              :key="person.id"
              class="flex items-center justify-between gap-4 border rounded-lg p-3"
            >
              <RouterLink :to="{ name: 'Profile', params: { id: person.id } }" class="flex items-center gap-3 min-w-0">
                <img :src="person.profile?.avatar || fallbackAvatar" class="w-10 h-10 rounded-full object-cover">
                <div class="min-w-0">
                  <p class="font-medium text-gray-800 truncate">{{ person.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ person.profile?.headline || 'Alumni member' }}</p>
                </div>
              </RouterLink>
              <button
                @click="sendRequest(person.id)"
                class="text-xs px-3 py-2 rounded-full border hover:bg-gray-100"
              >
                Connect
              </button>
            </div>
            <p v-if="!suggestions.length" class="text-sm text-gray-500">No suggestion right now.</p>
          </div>
        </div>
      </section>

      <aside class="col-span-3">
        <div class="bg-white rounded-xl shadow p-5">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">My Friends</h3>
          <div class="space-y-3">
            <div
              v-for="friend in friends"
              :key="friend.id"
              class="border rounded-lg p-3 flex justify-between relative"
            >
              <RouterLink
                :to="{ name: 'Profile', params: { id: friend.id } }"
                class="flex items-center gap-3 "
              >
                <img :src="friend.profile?.avatar || fallbackAvatar" class="w-9 h-9 rounded-full object-cover">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-800 truncate">{{ friend.name }}</p>
                  <p class="text-xs text-gray-500 truncate">{{ friend.profile?.headline || 'Connected' }}</p>
                </div>
              </RouterLink>
              <div class="px-2 py-1 cursor-pointer" @click="toggleFriendMenu(friend.id)">
                <i class="fa-solid fa-ellipsis-vertical"></i>
              </div>
              <div
                v-if="openFriendMenuId === friend.id"
                class="flex gap-2 mt-3 flex-col absolute -right-20 top-0 bg-white rounded-md border p-2 z-10"
              >
                <button
                  @click="onClickUnfriend(friend.id)"
                  class="text-xs px-3 py-1 rounded-full border hover:bg-gray-100"
                >
                  Unfriend
                </button>
                <button
                  @click="onClickBlock(friend.id)"
                  class="text-xs px-3 py-1 rounded-full border text-red-600 border-red-200 hover:bg-red-50"
                >
                  Block
                </button>
              </div>
            </div>
            <p v-if="!friends.length" class="text-sm text-gray-500">No friends yet.</p>
          </div>
        </div>
      </aside>
    </div>

    <p v-if="errorMessage" class="text-center text-red-500 mt-4">{{ errorMessage }}</p>
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

const friends = computed(() => {
  const meRaw = localStorage.getItem('user')
  const me = meRaw ? JSON.parse(meRaw) : null
  if (!me?.id) return []

  return connectionRows.value
    .map((row) => {
      if (row.requester_id === me.id) return row.addressee
      return row.requester
    })
    .filter(Boolean)
})

const connectionsCount = computed(() => friends.value.length)

const loadData = async () => {
  errorMessage.value = ''

  const [meRes, myRes, pendingRes, suggestionRes] = await Promise.allSettled([
    api.get('/me'),
    api.get('/connections/my'),
    api.get('/connections/pending'),
    api.get('/users/suggestions'),
  ])

  if (meRes.status === 'fulfilled') {
    localStorage.setItem('user', JSON.stringify(meRes.value.data))
  } else {
    errorMessage.value = meRes.reason?.response?.data?.message || 'Failed to load user.'
    return
  }

  connectionRows.value = myRes.status === 'fulfilled' ? (myRes.value.data?.data || []) : []
  pendingRequests.value = pendingRes.status === 'fulfilled' ? (pendingRes.value.data?.data || []) : []
  suggestions.value = suggestionRes.status === 'fulfilled' ? (suggestionRes.value.data?.data || []) : []

  if (myRes.status === 'rejected' || pendingRes.status === 'rejected' || suggestionRes.status === 'rejected') {
    errorMessage.value = 'Some connection sections failed to load.'
  }
}

const sendRequest = async (userId) => {
  try {
    await api.post('/connections/request', { user_id: userId })
    suggestions.value = suggestions.value.filter((person) => person.id !== userId)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send request.'
  }
}

const acceptRequest = async (connectionId) => {
  try {
    const response = await api.post(`/connections/${connectionId}/accept`)
    const row = response.data?.data
    if (row) {
      connectionRows.value = [row, ...connectionRows.value]
    }
    pendingRequests.value = pendingRequests.value.filter((item) => item.id !== connectionId)
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to accept request.'
  }
}

const rejectRequest = async (connectionId) => {
  try {
    await api.post(`/connections/${connectionId}/reject`)
    pendingRequests.value = pendingRequests.value.filter((item) => item.id !== connectionId)
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to reject request.'
  }
}

const refreshSuggestions = async () => {
  try {
    const response = await api.get('/users/suggestions')
    suggestions.value = response.data?.data || []
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
    connectionRows.value = connectionRows.value.filter((row) => {
      return !(
        (row.requester_id === userId || row.addressee_id === userId) &&
        row.status === 'accepted'
      )
    })
    await refreshSuggestions()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unfriend user.'
  }
}

const blockUser = async (userId) => {
  try {
    await api.post(`/connections/user/${userId}/block`)
    connectionRows.value = connectionRows.value.filter((row) => {
      return !(row.requester_id === userId || row.addressee_id === userId)
    })
    pendingRequests.value = pendingRequests.value.filter((row) => row.requester_id !== userId)
    suggestions.value = suggestions.value.filter((person) => person.id !== userId)
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
