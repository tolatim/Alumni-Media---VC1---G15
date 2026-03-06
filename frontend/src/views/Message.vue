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
            class="w-full text-left flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100"
            :class="selectedUser?.id === contact.id ? 'bg-gray-100' : ''"
          >
            <img :src="contact.profile?.avatar || fallbackAvatar" class="w-10 h-10 rounded-full object-cover" />
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ contact.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ contact.profile?.headline || 'Friend' }}</p>
            </div>
          </button>

          <p v-if="!contacts.length" class="text-sm text-gray-500">No friends available for messaging.</p>
        </div>
      </aside>

      <section class="col-span-8 bg-white rounded-xl shadow flex flex-col h-[80vh]">
        <div class="border-b p-4">
          <h3 class="font-semibold">
            {{ selectedUser ? selectedUser.name : 'Select a friend to start chat' }}
          </h3>
        </div>

        <div class="flex-1 overflow-y-auto p-4 space-y-3">
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

const me = ref(null)
const contacts = ref([])
const selectedUser = ref(null)
const messages = ref([])
const sending = ref(false)
const errorMessage = ref('')

const form = ref({
  content: '',
  mediaFile: null,
})

const loadMe = async () => {
  const response = await api.get('/me')
  me.value = response.data
}

const loadContacts = async () => {
  const response = await api.get('/messages/contacts')
  contacts.value = response.data?.data || []
}

const loadMessages = async (userId) => {
  errorMessage.value = ''
  try {
    const response = await api.get(`/messages/${userId}`)
    messages.value = response.data?.data || []
  } catch (error) {
    messages.value = []
    errorMessage.value = error.response?.data?.message || 'Failed to load messages.'
  }
}

const selectContact = async (contact) => {
  selectedUser.value = contact
  await router.replace({ path: `/message/${contact.id}` })
  await loadMessages(contact.id)
}

const onFileChange = (event) => {
  form.value.mediaFile = event.target.files?.[0] || null
}

const sendMessage = async () => {
  if (!selectedUser.value) return
  errorMessage.value = ''

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
    messages.value = [...messages.value, response.data.data]

    form.value.content = ''
    form.value.mediaFile = null
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send message.'
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  try {
    await loadMe()
    await loadContacts()

    const contactId = route.params.userId
    if (contactId) {
      const contact = contacts.value.find((item) => String(item.id) === String(contactId))
      if (contact) {
        selectedUser.value = contact
        await loadMessages(contact.id)
      }
    }
  } catch {
    errorMessage.value = 'Failed to load messages.'
  }
})
</script>
