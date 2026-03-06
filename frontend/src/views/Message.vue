<template>
  <Navbar />
  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-12 gap-6">
      <aside class="col-span-4 bg-white rounded-xl shadow p-4">
        <h2 class="text-lg font-semibold mb-4">Friends</h2>

        <div class="space-y-2 max-h-[70vh] overflow-y-auto">
          <button
            v-for="contact in contacts"
            :key="contact.id"
            @click="selectContact(contact)"
            class="w-full text-left flex items-center justify-between gap-3 p-2 rounded-lg hover:bg-gray-100"
            :class="selectedUser?.id === contact.id ? 'bg-gray-100' : ''"
          >
            <div class="flex items-center gap-3 min-w-0">
              <img :src="contact.profile?.avatar || fallbackAvatar" class="w-10 h-10 rounded-full object-cover" />
              <div class="min-w-0">
                <p class="text-sm font-medium truncate">{{ contact.name }}</p>
                <p class="text-xs text-gray-500 truncate">{{ contact.profile?.headline || 'Friend' }}</p>
              </div>
            </div>
            <span
              v-if="contact.unread_count > 0"
              class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] leading-[18px] text-center"
            >
              {{ contact.unread_count > 99 ? '99+' : contact.unread_count }}
            </span>
          </button>

          <p v-if="!contacts.length" class="text-sm text-gray-500">No friends available for messaging.</p>
        </div>

        <div v-if="contactsPagination.last_page > 1" class="mt-3 flex items-center justify-between">
          <button
            class="text-xs px-3 py-1 rounded border disabled:opacity-50"
            :disabled="contactsPagination.current_page <= 1"
            @click="loadContacts(contactsPagination.current_page - 1)"
          >
            Prev
          </button>
          <p class="text-xs text-gray-500">
            Page {{ contactsPagination.current_page }} / {{ contactsPagination.last_page }}
          </p>
          <button
            class="text-xs px-3 py-1 rounded border disabled:opacity-50"
            :disabled="contactsPagination.current_page >= contactsPagination.last_page"
            @click="loadContacts(contactsPagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </aside>

      <section class="col-span-8 bg-white rounded-xl shadow flex flex-col h-[80vh]">
        <div class="border-b p-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="font-semibold">
              {{ selectedUser ? selectedUser.name : 'Select a friend to start chat' }}
            </h3>
            <div v-if="selectedUser" class="flex items-center gap-2">
              <RouterLink
                :to="{ name: 'Profile', params: { id: selectedUser.id } }"
                class="text-xs px-3 py-2 rounded border hover:bg-gray-100"
              >
                View Profile
              </RouterLink>
              <button
                v-if="blockedByMe"
                @click="unblockSelectedUser"
                class="text-xs px-3 py-2 rounded border hover:bg-gray-100"
              >
                Unblock
              </button>
              <button
                v-else-if="connectionStatus === 'accepted'"
                @click="blockSelectedUser"
                class="text-xs px-3 py-2 rounded border border-red-200 text-red-600 hover:bg-red-50"
              >
                Block
              </button>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
          <div v-if="selectedUser && messagesPagination.current_page < messagesPagination.last_page" class="flex justify-center">
            <button
              class="text-xs px-3 py-1 rounded border hover:bg-gray-100 disabled:opacity-50"
              :disabled="loadingOlder"
              @click="loadOlderMessages"
            >
              {{ loadingOlder ? 'Loading...' : 'Load older messages' }}
            </button>
          </div>

          <div
            v-for="message in messages"
            :key="message.id"
            class="flex"
            :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'"
          >
            <div
              class="max-w-[70%] rounded-xl px-3 py-2"
              :class="message.sender_id === me?.id ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-800'"
            >
              <p v-if="message.content" class="text-sm whitespace-pre-wrap">{{ message.content }}</p>

              <img
                v-if="message.media_type === 'image' && message.media_url"
                :src="message.media_url"
                class="mt-2 rounded-lg max-h-56 object-cover"
              />

              <video
                v-if="message.media_type === 'video' && message.media_url"
                :src="message.media_url"
                class="mt-2 rounded-lg max-h-56"
                controls
              ></video>
            </div>
          </div>

          <p v-if="selectedUser && !messages.length" class="text-sm text-gray-500">No messages yet.</p>
        </div>

        <form v-if="selectedUser" @submit.prevent="sendMessage" class="border-t p-4 space-y-2">
          <p v-if="errorMessage" class="text-sm text-red-500">{{ errorMessage }}</p>

          <textarea
            v-model="form.content"
            rows="2"
            placeholder="Type your message..."
            class="w-full border rounded-lg px-3 py-2 focus:outline-none"
          ></textarea>

          <div class="flex items-center justify-between gap-3">
            <input type="file" accept="image/*,video/*" @change="onFileChange" class="text-sm" />
            <button
              type="submit"
              :disabled="sending"
              class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 disabled:opacity-60"
            >
              {{ sending ? 'Sending...' : 'Send' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

const route = useRoute()
const router = useRouter()
const CONTACTS_PER_PAGE = 12
const MESSAGES_PER_PAGE = 20

const me = ref(null)
const contacts = ref([])
const selectedUser = ref(null)
const messages = ref([])
const sending = ref(false)
const loadingOlder = ref(false)
const errorMessage = ref('')
const connectionStatus = ref('none')
const blockedByMe = ref(false)
const blockedMe = ref(false)

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

const form = ref({
  content: '',
  mediaFile: null,
})

const loadMe = async () => {
  const response = await api.get('/me')
  me.value = response.data
}

const loadContacts = async (page = 1) => {
  const response = await api.get('/messages/contacts', {
    params: { page, per_page: CONTACTS_PER_PAGE },
  })
  contacts.value = response.data?.data || []
  contactsPagination.value = normalizePagination(response.data, CONTACTS_PER_PAGE)
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

const loadMessages = async (userId, page = 1, appendOlder = false) => {
  errorMessage.value = ''
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
  errorMessage.value = ''

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
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send message.'
  } finally {
    sending.value = false
  }
}

const blockSelectedUser = async () => {
  if (!selectedUser.value) return

  errorMessage.value = ''
  try {
    await api.post(`/connections/user/${selectedUser.value.id}/block`)
    await loadConnectionStatus(selectedUser.value.id)
    await loadContacts(contactsPagination.value.current_page)
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to block user.'
  }
}

const unblockSelectedUser = async () => {
  if (!selectedUser.value) return

  errorMessage.value = ''
  try {
    await api.post(`/connections/user/${selectedUser.value.id}/unblock`)
    await loadConnectionStatus(selectedUser.value.id)
    await loadContacts(contactsPagination.value.current_page)
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to unblock user.'
  }
}

onMounted(async () => {
  try {
    await loadMe()
    await loadContacts(1)

    const contactId = route.params.userId
    if (contactId) {
      const contact = contacts.value.find((item) => String(item.id) === String(contactId))
      if (contact) {
        selectedUser.value = contact
        await loadConnectionStatus(contact.id)
        await loadMessages(contact.id, 1, false)
      } else {
        const response = await api.get(`/users/${contactId}`)
        selectedUser.value = response.data?.data || null
        if (selectedUser.value?.id) {
          await loadConnectionStatus(selectedUser.value.id)
          await loadMessages(selectedUser.value.id, 1, false)
        }
      }
    }
  } catch {
    errorMessage.value = 'Failed to load messages.'
  }
})
</script>
