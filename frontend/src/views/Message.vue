<template>
  <Navbar />
  <main class="min-h-screen py-6 md:py-8">
    <div class="mx-auto grid max-w-7xl grid-cols-12 gap-5 px-4 sm:px-5">
      <aside class="col-span-12 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur lg:col-span-3">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-800">Conversations</h2>
          <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ contactsPagination.total }}</span>
        </div>

        <div v-if="loadingContacts" class="space-y-3">
          <div v-for="n in 5" :key="`skeleton-contact-${n}`" class="flex items-center gap-3">
            <div class="h-10 w-10 animate-pulse rounded-full bg-slate-200"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
              <div class="h-2 w-1/2 animate-pulse rounded bg-slate-200"></div>
            </div>
          </div>
        </div>

        <div v-else class="space-y-2">
          <button
            v-for="contact in contacts"
            :key="contact.id"
            @click="selectContact(contact)"
            class="flex w-full items-center justify-between gap-3 rounded-xl p-2 text-left transition hover:bg-slate-100"
            :class="selectedUser?.id === contact.id ? 'bg-slate-100 ring-1 ring-slate-200' : ''"
          >
            <div class="flex min-w-0 items-center gap-3">
              <img :src="contact.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-full object-cover" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ contact.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ contact.profile?.headline || 'Friend' }}</p>
              </div>
            </div>
            <span
              v-if="contact.unread_count > 0"
              class="min-w-[18px] rounded-full bg-red-500 px-1 text-center text-[10px] leading-[18px] text-white"
            >
              {{ contact.unread_count > 99 ? '99+' : contact.unread_count }}
            </span>
          </button>
          <p v-if="!contacts.length" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">No chats yet.</p>
        </div>

        <div v-if="contactsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
          <button
            class="rounded-lg border px-3 py-1 text-xs disabled:opacity-50"
            :disabled="contactsPagination.current_page <= 1 || loadingContacts"
            @click="loadContacts(contactsPagination.current_page - 1)"
          >
            Prev
          </button>
          <p class="text-xs text-slate-500">
            {{ contactsPagination.current_page }} / {{ contactsPagination.last_page }}
          </p>
          <button
            class="rounded-lg border px-3 py-1 text-xs disabled:opacity-50"
            :disabled="contactsPagination.current_page >= contactsPagination.last_page || loadingContacts"
            @click="loadContacts(contactsPagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </aside>

      <section class="col-span-12 flex h-[80vh] flex-col rounded-2xl border border-slate-200 bg-white/95 shadow-sm backdrop-blur lg:col-span-6">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
          <div class="flex items-center gap-3">
            <img v-if="selectedUser" :src="selectedUser.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-full object-cover" />
            <div>
              <h3 class="text-sm font-semibold text-slate-800">
                {{ selectedUser ? selectedUser.name : 'Select a conversation' }}
              </h3>
              <p class="text-xs text-slate-500">
                {{ selectedUser ? selectedUser.profile?.headline || 'Alumni member' : 'Choose a friend from left side' }}
              </p>
            </div>
          </div>
          <div v-if="selectedUser" class="flex items-center gap-2">
            <RouterLink :to="{ name: 'Profile', params: { id: selectedUser.id } }" class="rounded-lg border px-3 py-2 text-xs hover:bg-slate-100">
              Profile
            </RouterLink>
            <button
              v-if="blockedByMe"
              class="rounded-lg border px-3 py-2 text-xs hover:bg-slate-100"
              :disabled="processingUserAction"
              @click="unblockSelectedUser"
            >
              Unblock
            </button>
            <button
              v-else-if="connectionStatus === 'accepted'"
              class="rounded-lg border border-red-200 px-3 py-2 text-xs text-red-600 hover:bg-red-50"
              :disabled="processingUserAction"
              @click="blockSelectedUser"
            >
              Block
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white p-4">
          <div v-if="selectedUser && messagesPagination.current_page < messagesPagination.last_page" class="mb-3 flex justify-center">
            <button
              class="rounded-full border bg-white px-3 py-1 text-xs hover:bg-slate-100 disabled:opacity-50"
              :disabled="loadingOlder || loadingMessages"
              @click="loadOlderMessages"
            >
              {{ loadingOlder ? 'Loading...' : 'Load older messages' }}
            </button>
          </div>

          <div v-if="loadingMessages" class="space-y-3">
            <div v-for="n in 4" :key="`skeleton-message-${n}`" class="flex" :class="n % 2 ? 'justify-end' : 'justify-start'">
              <div class="h-14 w-48 animate-pulse rounded-xl bg-slate-200"></div>
            </div>
          </div>

          <div v-else class="space-y-3">
            <div
              v-for="message in messages"
              :key="message.id"
              class="group flex"
              :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'"
            >
              <div class="max-w-[78%]">
                <div
                  class="rounded-2xl px-3 py-2 shadow-sm"
                  :class="message.sender_id === me?.id ? 'bg-gradient-to-r from-cyan-600 to-blue-700 text-white' : 'bg-white text-slate-800 ring-1 ring-slate-200'"
                >
                  <div v-if="editingMessageId === message.id" class="space-y-2">
                    <textarea
                      v-model="editingContent"
                      rows="2"
                      class="w-full rounded border border-white/40 bg-white/95 px-2 py-1 text-sm text-slate-800 focus:outline-none"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                      <button
                        class="rounded bg-white/20 px-2 py-1 text-xs text-white hover:bg-white/30"
                        @click="cancelEdit"
                      >
                        Cancel
                      </button>
                      <button
                        class="rounded bg-white px-2 py-1 text-xs text-teal-700 hover:bg-slate-100 disabled:opacity-60"
                        :disabled="savingEdit"
                        @click="saveEditMessage(message.id)"
                      >
                        {{ savingEdit ? 'Saving...' : 'Save' }}
                      </button>
                    </div>
                  </div>
                  <p v-else-if="message.content" class="whitespace-pre-wrap text-sm">{{ message.content }}</p>

                  <img v-if="message.media_type === 'image' && message.media_url" :src="message.media_url" class="mt-2 max-h-56 rounded-lg object-cover" />
                  <video
                    v-if="message.media_type === 'video' && message.media_url"
                    :src="message.media_url"
                    class="mt-2 max-h-56 rounded-lg"
                    controls
                  ></video>
                </div>
                <div class="mt-1 flex items-center gap-2" :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'">
                  <span class="text-[11px] text-slate-400">{{ formatTime(message.created_at) }}</span>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded px-1 text-[11px] text-blue-500 hover:bg-blue-50 hover:text-blue-600 group-hover:visible"
                    :disabled="deletingMessageId === message.id || savingEdit"
                    @click="startEdit(message)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded px-1 text-[11px] text-red-500 hover:bg-red-50 hover:text-red-600 group-hover:visible"
                    :disabled="deletingMessageId === message.id"
                    @click="deleteMessage(message.id)"
                  >
                    {{ deletingMessageId === message.id ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
            <p v-if="selectedUser && !messages.length" class="rounded-lg bg-white p-3 text-center text-sm text-slate-500 ring-1 ring-slate-200">
              No messages yet. Start the conversation.
            </p>
          </div>
        </div>

        <form v-if="selectedUser" @submit.prevent="sendMessage" class="space-y-2 border-t border-slate-200 p-4">
          <p v-if="errorMessage" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ errorMessage }}</p>
          <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ successMessage }}</p>

          <textarea
            v-model="form.content"
            rows="2"
            placeholder="Write a message..."
            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            :disabled="sending || blockedMe"
          ></textarea>

          <div class="flex items-center justify-between gap-3">
            <input type="file" accept="image/*,video/*" @change="onFileChange" class="text-sm text-slate-500" :disabled="sending || blockedMe" />
            <button
              type="submit"
              :disabled="sending || blockedMe"
              class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-700 px-4 py-2 text-sm font-semibold text-white transition hover:from-cyan-700 hover:to-blue-800 disabled:opacity-60"
            >
              {{ sending ? 'Sending...' : 'Send Message' }}
            </button>
          </div>
          <p v-if="blockedMe" class="text-xs text-red-500">You are blocked and cannot send messages to this user.</p>
        </form>
      </section>

      <aside class="col-span-12 space-y-4 lg:col-span-3">
        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur">
          <h3 class="mb-3 text-base font-semibold text-slate-800">Friends</h3>
          <div v-if="loadingSidebar" class="space-y-3">
            <div v-for="n in 4" :key="`side-friend-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="max-h-[28vh] space-y-2 overflow-y-auto">
            <RouterLink
              v-for="friend in sideFriends"
              :key="friend.id"
              :to="{ name: 'Profile', params: { id: friend.id } }"
              class="flex items-center gap-3 rounded-lg p-2 hover:bg-slate-100"
            >
              <img :src="friend.profile?.avatar || fallbackAvatar" class="h-9 w-9 rounded-full object-cover" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Friend' }}</p>
              </div>
            </RouterLink>
            <p v-if="!sideFriends.length" class="text-xs text-slate-500">No friends found.</p>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm backdrop-blur">
          <h3 class="mb-3 text-base font-semibold text-slate-800">Blocked Friends</h3>
          <div v-if="loadingSidebar" class="space-y-3">
            <div v-for="n in 3" :key="`side-blocked-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="max-h-[28vh] space-y-2 overflow-y-auto">
            <div v-for="friend in sideBlockedFriends" :key="friend.id" class="flex items-center justify-between gap-2 rounded-lg border p-2">
              <RouterLink :to="{ name: 'Profile', params: { id: friend.id } }" class="flex min-w-0 items-center gap-2">
                <img :src="friend.profile?.avatar || fallbackAvatar" class="h-9 w-9 rounded-full object-cover" />
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
              </RouterLink>
              <button
                class="rounded border px-2 py-1 text-xs hover:bg-slate-100"
                :disabled="processingUserAction"
                @click="unblockUserFromSide(friend.id)"
              >
                Unblock
              </button>
            </div>
            <p v-if="!sideBlockedFriends.length" class="text-xs text-slate-500">No blocked friends.</p>
          </div>
        </div>
      </aside>
    </div>
  </main>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import { createMessageSocket } from '@/services/messageSocket'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const route = useRoute()
const router = useRouter()
const CONTACTS_PER_PAGE = 12
const MESSAGES_PER_PAGE = 20

const me = ref(null)
const contacts = ref([])
const selectedUser = ref(null)
const messages = ref([])
const sideFriends = ref([])
const sideBlockedFriends = ref([])

const loadingContacts = ref(false)
const loadingMessages = ref(false)
const loadingSidebar = ref(false)
const loadingOlder = ref(false)
const sending = ref(false)
const processingUserAction = ref(false)
const deletingMessageId = ref(null)
const editingMessageId = ref(null)
const editingContent = ref('')
const savingEdit = ref(false)

const errorMessage = ref('')
const successMessage = ref('')
const connectionStatus = ref('none')
const blockedByMe = ref(false)
const blockedMe = ref(false)

const socketStatus = ref('idle')
let messageSocket = null

const form = ref({
  content: '',
  mediaFile: null,
})

const defaultPagination = (perPage) => ({
  current_page: 1,
  last_page: 1,
  per_page: perPage,
  total: 0,
})

const contactsPagination = ref(defaultPagination(CONTACTS_PER_PAGE))
const messagesPagination = ref(defaultPagination(MESSAGES_PER_PAGE))

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

const clearFeedback = () => {
  errorMessage.value = ''
  successMessage.value = ''
}

const formatTime = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
}

const toMessageId = (value) => {
  if (value === null || value === undefined) return null
  return String(value)
}

const extractMessageFromPayload = (payload) => {
  const data = payload?.data ?? payload?.message ?? payload?.payload ?? payload
  if (!data) return null

  const message = data?.message ?? data
  if (!message?.id) return null

  // Normalize sender/receiver fields (backend uses snake_case).
  const senderId = message.sender_id ?? message.senderId
  const receiverId = message.receiver_id ?? message.receiverId
  if (!senderId || !receiverId) return null

  return {
    ...message,
    sender_id: senderId,
    receiver_id: receiverId,
  }
}

const touchContact = (contactId) => {
  if (!contactId) return
  const index = contacts.value.findIndex((contact) => String(contact.id) === String(contactId))
  if (index <= 0) return

  const nextContacts = contacts.value.slice()
  const [contact] = nextContacts.splice(index, 1)
  nextContacts.unshift(contact)
  contacts.value = nextContacts
}

const bumpUnreadForContact = (contactId) => {
  const entry = contacts.value.find((contact) => String(contact.id) === String(contactId))
  if (!entry) return
  entry.unread_count = Number(entry.unread_count || 0) + 1
}

const clearUnreadForContact = (contactId) => {
  const entry = contacts.value.find((contact) => String(contact.id) === String(contactId))
  if (!entry) return
  entry.unread_count = 0
}

const upsertMessageInView = (message) => {
  const idKey = toMessageId(message?.id)
  if (!idKey) return

  const exists = messages.value.some((m) => toMessageId(m?.id) === idKey)
  if (!exists) {
    messages.value = [...messages.value, message]
    return
  }

  messages.value = messages.value.map((m) => (toMessageId(m?.id) === idKey ? message : m))
}

const removeMessageFromView = (deletedId) => {
  const idKey = toMessageId(deletedId)
  if (!idKey) return

  messages.value = messages.value.filter((m) => toMessageId(m?.id) !== idKey)

  if (toMessageId(editingMessageId.value) === idKey) {
    editingMessageId.value = null
    editingContent.value = ''
    savingEdit.value = false
  }
}

const markReadConversation = async (conversationUserId) => {
  if (!conversationUserId) return
  try {
    await api.post(`/messages/${conversationUserId}/read`)
  } catch {
    // Ignore read failures; the periodic refresh will correct state.
  }
}

const handleIncomingMessage = async (message) => {
  const meId = me.value?.id
  if (!meId) return

  const counterpartId = message.sender_id === meId ? message.receiver_id : message.sender_id
  if (!counterpartId) return

  touchContact(counterpartId)

  const isActiveChat = !!selectedUser.value?.id && String(selectedUser.value.id) === String(counterpartId)
  if (isActiveChat) {
    if (messagesPagination.value.current_page === 1) {
      upsertMessageInView(message)
    } else {
      await loadMessages(counterpartId, 1, false)
    }

    clearUnreadForContact(counterpartId)
    await markReadConversation(counterpartId)
    window.dispatchEvent(new Event('messages:updated'))
    return
  }

  bumpUnreadForContact(counterpartId)
  window.dispatchEvent(new Event('messages:updated'))
}

const handleUpdatedMessage = async (message) => {
  const meId = me.value?.id
  if (!meId) return

  const counterpartId = message.sender_id === meId ? message.receiver_id : message.sender_id
  if (!counterpartId) return

  touchContact(counterpartId)

  const isActiveChat = !!selectedUser.value?.id && String(selectedUser.value.id) === String(counterpartId)
  if (isActiveChat && messagesPagination.value.current_page === 1) {
    upsertMessageInView(message)
  }

  window.dispatchEvent(new Event('messages:updated'))
}

const handleDeletedMessage = (deletedId) => {
  if (!deletedId) return

  // If the message is currently visible in this chat, remove it.
  removeMessageFromView(deletedId)
  window.dispatchEvent(new Event('messages:updated'))
}

const handleSocketPayload = (payload) => {
  if (!payload) return

  const eventName = payload?.event || payload?.type || payload?.name || payload?.action
  const normalizedEvent = typeof eventName === 'string' ? eventName.toLowerCase() : ''

  if (normalizedEvent.includes('deleted') || normalizedEvent.includes('removed')) {
    const deletedId = payload?.message_id || payload?.id || payload?.data?.id
    handleDeletedMessage(deletedId)
    return
  }

  const message = extractMessageFromPayload(payload)
  if (!message) return

  if (normalizedEvent.includes('updated') || normalizedEvent.includes('edited')) {
    void handleUpdatedMessage(message)
    return
  }

  void handleIncomingMessage(message)
}

const connectRealtimeSocket = () => {
  if (messageSocket) return
  const token = localStorage.getItem('token')
  if (!token || !me.value?.id) return

  socketStatus.value = 'connecting'
  messageSocket = createMessageSocket({
    getAuthToken: () => localStorage.getItem('token'),
    getUserId: () => me.value?.id,
    onOpen: () => {
      socketStatus.value = 'connected'
    },
    onClose: () => {
      socketStatus.value = 'disconnected'
    },
    onError: () => {
      socketStatus.value = 'error'
    },
    onMessage: handleSocketPayload,
  })

  socketStatus.value = 'connecting'
  messageSocket.connect()
}

const disconnectRealtimeSocket = () => {
  if (!messageSocket) return
  messageSocket.disconnect()
  messageSocket = null
  socketStatus.value = 'idle'
}

const loadMe = async () => {
  const response = await api.get('/me')
  me.value = response.data
}

const loadContacts = async (page = 1) => {
  loadingContacts.value = true
  try {
    const response = await api.get('/messages/contacts', {
      params: { page, per_page: CONTACTS_PER_PAGE },
    })
    contacts.value = response.data?.data || []
    contactsPagination.value = normalizePagination(response.data, CONTACTS_PER_PAGE)
  } finally {
    loadingContacts.value = false
  }
}

const loadConnectionStatus = async (userId) => {
  try {
    const response = await api.get(`/connections/status/${userId}`)
    connectionStatus.value = response.data?.data?.status || 'none'
    blockedByMe.value = !!response.data?.data?.blocked_by_me
    blockedMe.value = !!response.data?.data?.blocked_me
  } catch {
    connectionStatus.value = 'none'
    blockedByMe.value = false
    blockedMe.value = false
  }
}

const loadSidebarConnections = async () => {
  const meId = me.value?.id
  if (!meId) return

  loadingSidebar.value = true
  try {
    const [friendsRes, blockedRes] = await Promise.all([
      api.get('/connections/my', { params: { page: 1, per_page: 50 } }),
      api.get('/connections/blocked', { params: { page: 1, per_page: 50 } }),
    ])

    const friendRows = friendsRes.data?.data || []
    sideFriends.value = friendRows
      .map((row) => (row.requester_id === meId ? row.addressee : row.requester))
      .filter(Boolean)

    const blockedRows = blockedRes.data?.data || []
    sideBlockedFriends.value = blockedRows
      .map((row) => row.addressee)
      .filter(Boolean)
  } catch {
    sideFriends.value = []
    sideBlockedFriends.value = []
  } finally {
    loadingSidebar.value = false
  }
}

const loadMessages = async (userId, page = 1, appendOlder = false) => {
  loadingMessages.value = !appendOlder
  clearFeedback()
  try {
    const response = await api.get(`/messages/${userId}`, {
      params: { page, per_page: MESSAGES_PER_PAGE },
    })
    const chunk = response.data?.data || []
    messages.value = appendOlder ? [...chunk, ...messages.value] : chunk
    messagesPagination.value = normalizePagination(response.data, MESSAGES_PER_PAGE)

    if (!appendOlder || page === 1) {
      await api.post(`/messages/${userId}/read`)
    }

    await loadContacts(contactsPagination.value.current_page)
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    if (!appendOlder) {
      messages.value = []
      messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
    }
    errorMessage.value = error.response?.data?.message || 'Failed to load messages.'
  } finally {
    loadingMessages.value = false
  }
}

const selectContact = async (contact) => {
  selectedUser.value = contact
  await loadConnectionStatus(contact.id)
  await router.replace({ path: `/message/${contact.id}` })
  messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
  await loadMessages(contact.id, 1, false)
}

const loadOlderMessages = async () => {
  if (!selectedUser.value) return
  if (messagesPagination.value.current_page >= messagesPagination.value.last_page) return

  loadingOlder.value = true
  try {
    const nextPage = messagesPagination.value.current_page + 1
    await loadMessages(selectedUser.value.id, nextPage, true)
  } finally {
    loadingOlder.value = false
  }
}

const onFileChange = (event) => {
  form.value.mediaFile = event.target.files?.[0] || null
}

const sendMessage = async () => {
  if (!selectedUser.value) return
  clearFeedback()

  if (connectionStatus.value !== 'accepted' && connectionStatus.value !== 'blocked') {
    errorMessage.value = 'You can only send messages to friends.'
    return
  }

  if (blockedMe.value) {
    errorMessage.value = 'You are blocked and cannot message this user.'
    return
  }

  const hasContent = !!form.value.content?.trim()
  const hasFile = !!form.value.mediaFile

  if (!hasContent && !hasFile) {
    errorMessage.value = 'Type a message or choose image/video.'
    return
  }

  sending.value = true
  try {
    const formData = new FormData()
    if (hasContent) formData.append('content', form.value.content.trim())
    if (hasFile) formData.append('media_file', form.value.mediaFile)

    const response = await api.post(`/messages/${selectedUser.value.id}`, formData)

    if (messagesPagination.value.current_page === 1) {
      messages.value = [...messages.value, response.data.data]
    } else {
      messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
      await loadMessages(selectedUser.value.id, 1, false)
    }

    await loadContacts(contactsPagination.value.current_page)
    form.value.content = ''
    form.value.mediaFile = null
    successMessage.value = 'Message sent.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send message.'
  } finally {
    sending.value = false
  }
}

const deleteMessage = async (messageId) => {
  clearFeedback()
  deletingMessageId.value = messageId
  try {
    await api.delete(`/messages/item/${messageId}`)
    messages.value = messages.value.filter((item) => item.id !== messageId)
    successMessage.value = 'Message deleted.'
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to delete message.'
  } finally {
    deletingMessageId.value = null
  }
}

const startEdit = (message) => {
  editingMessageId.value = message.id
  editingContent.value = message.content || ''
  clearFeedback()
}

const cancelEdit = () => {
  editingMessageId.value = null
  editingContent.value = ''
}

const saveEditMessage = async (messageId) => {
  const content = editingContent.value.trim()
  if (!content) {
    errorMessage.value = 'Message content is required.'
    return
  }

  savingEdit.value = true
  clearFeedback()
  try {
    const response = await api.put(`/messages/item/${messageId}`, { content })
    const updated = response.data?.data
    messages.value = messages.value.map((item) => (item.id === messageId ? updated : item))
    successMessage.value = 'Message updated.'
    cancelEdit()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to edit message.'
  } finally {
    savingEdit.value = false
  }
}

const blockSelectedUser = async () => {
  if (!selectedUser.value) return

  clearFeedback()
  processingUserAction.value = true
  try {
    await api.post(`/connections/user/${selectedUser.value.id}/block`)
    await loadConnectionStatus(selectedUser.value.id)
    await loadContacts(contactsPagination.value.current_page)
    await loadSidebarConnections()
    successMessage.value = 'User blocked.'
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to block user.'
  } finally {
    processingUserAction.value = false
  }
}

const unblockSelectedUser = async () => {
  if (!selectedUser.value) return

  clearFeedback()
  processingUserAction.value = true
  try {
    await api.post(`/connections/user/${selectedUser.value.id}/unblock`)
    await loadConnectionStatus(selectedUser.value.id)
    await loadContacts(contactsPagination.value.current_page)
    await loadSidebarConnections()
    successMessage.value = 'User unblocked.'
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unblock user.'
  } finally {
    processingUserAction.value = false
  }
}

const unblockUserFromSide = async (userId) => {
  clearFeedback()
  processingUserAction.value = true
  try {
    await api.post(`/connections/user/${userId}/unblock`)
    await loadSidebarConnections()
    await loadContacts(contactsPagination.value.current_page)
    if (selectedUser.value?.id === userId) {
      await loadConnectionStatus(userId)
    }
    successMessage.value = 'User unblocked.'
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unblock user.'
  } finally {
    processingUserAction.value = false
  }
}

onMounted(async () => {
  try {
    await loadMe()
    connectRealtimeSocket()
    await Promise.all([loadContacts(1), loadSidebarConnections()])

    const contactId = route.params.userId
    if (!contactId) return

    const contact = contacts.value.find((item) => String(item.id) === String(contactId))
    if (contact) {
      selectedUser.value = contact
      await loadConnectionStatus(contact.id)
      await loadMessages(contact.id, 1, false)
      return
    }

    const response = await api.get(`/users/${contactId}`)
    selectedUser.value = response.data?.data || null
    if (selectedUser.value?.id) {
      await loadConnectionStatus(selectedUser.value.id)
      await loadMessages(selectedUser.value.id, 1, false)
    }
  } catch {
    errorMessage.value = 'Failed to load messages.'
  }
})

onUnmounted(() => {
  disconnectRealtimeSocket()
})
</script>
