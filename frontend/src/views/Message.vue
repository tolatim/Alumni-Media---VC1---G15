<template>
  <Navbar />
  <main class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 py-6 md:py-10">
    <div class="mx-auto grid max-w-7xl gap-6 px-4 lg:grid-cols-[320px_1fr_280px] xl:grid-cols-[340px_1fr_300px]">
      <aside
        class="rounded-3xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur"
        :class="selectedUser ? 'hidden lg:block' : ''"
      >
        <div class="mb-4 flex items-center justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Inbox</p>
            <h2 class="text-lg font-semibold text-slate-900">Conversations</h2>
          </div>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
            {{ contactsPagination.total }}
          </span>
        </div>

        <div class="mb-4">
          <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
            <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
            <input
              v-model="contactSearch"
              type="text"
              placeholder="Search people, headlines..."
              class="w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
            >
          </div>
        </div>

        <div v-if="loadingContacts" class="space-y-3">
          <div v-for="n in 5" :key="`skeleton-contact-${n}`" class="flex items-center gap-3">
            <div class="h-11 w-11 animate-pulse rounded-full bg-slate-200"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
              <div class="h-2 w-1/2 animate-pulse rounded bg-slate-200"></div>
            </div>
          </div>
        </div>

        <div v-else class="space-y-2">
          <button
            v-for="contact in filteredContacts"
            :key="contact.id"
            @click="selectContact(contact)"
            class="group flex w-full items-center justify-between gap-3 rounded-2xl border border-transparent p-2 text-left transition hover:border-slate-200 hover:bg-slate-50"
            :class="selectedUser?.id === contact.id ? 'border-slate-200 bg-slate-100' : ''"
          >
            <div class="flex min-w-0 items-center gap-3">
              <img
                :src="contact.profile?.avatar || fallbackAvatar"
                class="h-11 w-11 rounded-2xl object-cover"
                loading="lazy"
              >
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-800">{{ contact.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ contact.profile?.headline || 'Friend' }}</p>
              </div>
            </div>
            <span
              v-if="contact.unread_count > 0"
              class="min-w-[20px] rounded-full bg-rose-500 px-1.5 text-center text-[10px] font-semibold leading-[20px] text-white"
            >
              {{ contact.unread_count > 99 ? '99+' : contact.unread_count }}
            </span>
          </button>
          <p v-if="!filteredContacts.length" class="rounded-2xl bg-slate-50 p-3 text-sm text-slate-500">
            No chats yet. Start a conversation.
          </p>
        </div>

        <div v-if="contactsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
          <button
            class="rounded-lg border px-3 py-1 text-xs disabled:opacity-50"
            :disabled="contactsPagination.current_page <= 1 || loadingContacts"
            @click="messageStore.loadContacts(contactsPagination.current_page - 1)"
          >
            Prev
          </button>
          <p class="text-xs text-slate-500">
            {{ contactsPagination.current_page }} / {{ contactsPagination.last_page }}
          </p>
          <button
            class="rounded-lg border px-3 py-1 text-xs disabled:opacity-50"
            :disabled="contactsPagination.current_page >= contactsPagination.last_page || loadingContacts"
            @click="messageStore.loadContacts(contactsPagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </aside>

      <section
        class="flex h-[72vh] flex-col rounded-3xl border border-slate-200 bg-white/95 shadow-sm backdrop-blur sm:h-[78vh] lg:h-[82vh]"
        :class="selectedUser ? 'flex' : 'hidden lg:flex'"
      >
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
          <div class="flex items-center gap-3">
            <button
              v-if="selectedUser"
              type="button"
              class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 lg:hidden"
              @click="clearSelectedUser"
              aria-label="Back"
            >
              <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="relative">
              <img
                v-if="selectedUser"
                :src="selectedUser.profile?.avatar || fallbackAvatar"
                class="h-12 w-12 rounded-2xl object-cover"
                loading="lazy"
              >
              <span
                v-if="selectedUser"
                class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400"
              ></span>
            </div>
            <div>
              <h3 class="text-base font-semibold text-slate-900">
                {{ selectedUser ? selectedUser.name : 'Select a conversation' }}
              </h3>
              <p class="text-xs text-slate-500">
                {{ selectedUser ? selectedUser.profile?.headline || 'Alumni member' : 'Choose someone to start chatting' }}
              </p>
            </div>
          </div>
          <div v-if="selectedUser" class="flex items-center gap-2">
            <RouterLink :to="{ name: 'Profile', params: { id: selectedUser.id } }" class="rounded-xl border px-3 py-2 text-xs font-semibold hover:bg-slate-100">
              View Profile
            </RouterLink>
            <button
              v-if="blockedByMe"
              class="rounded-xl border px-3 py-2 text-xs font-semibold hover:bg-slate-100"
              :disabled="processingUserAction"
              @click="messageStore.unblockSelectedUser()"
            >
              Unblock
            </button>
            <button
              v-else-if="connectionStatus === 'accepted'"
              class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50"
              :disabled="processingUserAction"
              @click="messageStore.blockSelectedUser()"
            >
              Block
            </button>
          </div>
        </div>

        <div ref="messageFeedRef" class="flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 via-white to-slate-100/60 p-4">
          <div v-if="selectedUser && messagesPagination.current_page < messagesPagination.last_page" class="mb-3 flex justify-center">
            <button
              class="rounded-full border bg-white px-3 py-1 text-xs hover:bg-slate-100 disabled:opacity-50"
              :disabled="loadingOlder || loadingMessages"
              @click="messageStore.loadOlderMessages()"
            >
              {{ loadingOlder ? 'Loading...' : 'Load older messages' }}
            </button>
          </div>

          <div v-if="loadingMessages" class="space-y-3">
            <div v-for="n in 4" :key="`skeleton-message-${n}`" class="flex" :class="n % 2 ? 'justify-end' : 'justify-start'">
              <div class="h-16 w-52 animate-pulse rounded-2xl bg-slate-200"></div>
            </div>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="message in messages"
              :key="message.id"
              v-memo="[message.id, editingMessageId === message.id]"
              class="group flex"
              :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'"
            >
              <div class="max-w-[78%]">
                <div
                  class="rounded-2xl px-4 py-2.5 text-sm shadow-sm"
                  :class="message.sender_id === me?.id ? 'bg-slate-900 text-white' : 'bg-white text-slate-800 ring-1 ring-slate-200'"
                >
                  <div v-if="editingMessageId === message.id" class="space-y-2">
                    <textarea
                      v-model="editingContent"
                      rows="2"
                      class="w-full rounded-xl border border-white/40 bg-white/95 px-3 py-2 text-sm text-slate-800 focus:outline-none"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                      <button class="rounded bg-white/20 px-2 py-1 text-xs text-white hover:bg-white/30" @click="messageStore.cancelEdit()">
                        Cancel
                      </button>
                      <button
                        class="rounded bg-white px-2 py-1 text-xs text-slate-900 hover:bg-slate-100 disabled:opacity-60"
                        :disabled="savingEdit"
                        @click="messageStore.saveEditMessage(message.id)"
                      >
                        {{ savingEdit ? 'Saving...' : 'Save' }}
                      </button>
                    </div>
                  </div>
                  <p v-else-if="message.content" class="whitespace-pre-wrap">{{ message.content }}</p>

                  <img
                    v-if="message.media_type === 'image' && message.media_url"
                    :src="message.media_url"
                    class="mt-3 max-h-64 rounded-xl object-cover"
                    loading="lazy"
                  >
                  <video
                    v-if="message.media_type === 'video' && message.media_url"
                    :src="message.media_url"
                    class="mt-3 max-h-64 rounded-xl"
                    controls
                  ></video>
                </div>
                <div class="mt-1 flex items-center gap-2" :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'">
                  <span class="text-[11px] text-slate-400">{{ formatTime(message.created_at) }}</span>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded px-1 text-[11px] text-slate-500 hover:bg-slate-100 group-hover:visible"
                    :disabled="deletingMessageId === message.id || savingEdit"
                    @click="messageStore.startEdit(message)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded px-1 text-[11px] text-rose-500 hover:bg-rose-50 group-hover:visible"
                    :disabled="deletingMessageId === message.id"
                    @click="openDeleteConfirm(message)"
                  >
                    {{ deletingMessageId === message.id ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
            <p v-if="selectedUser && !messages.length" class="rounded-2xl bg-white/80 p-4 text-center text-sm text-slate-500 ring-1 ring-slate-200">
              No messages yet. Start the conversation.
            </p>
          </div>
        </div>

        <form v-if="selectedUser" @submit.prevent="sendMessage" class="border-t border-slate-200 p-4">
          <div v-if="errorMessage" class="mb-2 rounded-2xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
            {{ errorMessage }}
          </div>
          <div v-if="successMessage" class="mb-2 rounded-2xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ successMessage }}
          </div>

          <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-3">
            <textarea
              v-model="form.content"
              rows="2"
              placeholder="Write a message..."
              class="w-full resize-none bg-transparent text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none"
              :disabled="sending || blockedMe"
            ></textarea>
            <div class="flex flex-wrap items-center justify-between gap-3">
              <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-500">
                <input type="file" accept="image/*,video/*" @change="onFileChange" class="hidden" :disabled="sending || blockedMe">
                <span class="rounded-xl border px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Attach</span>
                <span v-if="form.mediaFile" class="truncate text-[11px] text-slate-400">{{ form.mediaFile.name }}</span>
              </label>
              <button
                type="submit"
                :disabled="sending || blockedMe"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-60"
              >
                {{ sending ? 'Sending...' : 'Send Message' }}
              </button>
            </div>
            <p v-if="blockedMe" class="text-xs text-rose-500">You are blocked and cannot send messages to this user.</p>
          </div>
        </form>
      </section>

      <aside class="space-y-4" :class="selectedUser ? 'hidden lg:block' : ''">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur">
          <h3 class="text-sm font-semibold text-slate-800">Quick Actions</h3>
          <div class="mt-3 grid gap-2">
            <RouterLink to="/connection" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
              Find new connections
            </RouterLink>
            <RouterLink to="/post" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
              Share an update
            </RouterLink>
            <RouterLink to="/profile/edit" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
              Edit profile
            </RouterLink>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur">
          <h3 class="text-sm font-semibold text-slate-800">Friends</h3>
          <div v-if="loadingSidebar" class="mt-3 space-y-3">
            <div v-for="n in 4" :key="`side-friend-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="mt-3 max-h-[24vh] space-y-2 overflow-y-auto">
            <RouterLink
              v-for="friend in sideFriends"
              :key="friend.id"
              :to="{ name: 'Profile', params: { id: friend.id } }"
              class="flex items-center gap-3 rounded-2xl p-2 hover:bg-slate-50"
            >
              <img :src="friend.profile?.avatar || fallbackAvatar" class="h-9 w-9 rounded-2xl object-cover" loading="lazy">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Friend' }}</p>
              </div>
            </RouterLink>
            <p v-if="!sideFriends.length" class="text-xs text-slate-500">No friends found.</p>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur">
          <h3 class="text-sm font-semibold text-slate-800">Blocked</h3>
          <div v-if="loadingSidebar" class="mt-3 space-y-3">
            <div v-for="n in 3" :key="`side-blocked-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="mt-3 max-h-[20vh] space-y-2 overflow-y-auto">
            <div v-for="friend in sideBlockedFriends" :key="friend.id" class="flex items-center justify-between gap-2 rounded-2xl border p-2">
              <RouterLink :to="{ name: 'Profile', params: { id: friend.id } }" class="flex min-w-0 items-center gap-2">
                <img :src="friend.profile?.avatar || fallbackAvatar" class="h-9 w-9 rounded-2xl object-cover" loading="lazy">
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
              </RouterLink>
              <button
                class="rounded-xl border px-2 py-1 text-[11px] font-semibold hover:bg-slate-100"
                :disabled="processingUserAction"
                @click="messageStore.unblockUserFromSide(friend.id)"
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

  <div v-if="confirmDeleteMessageId" class="fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDeleteConfirm"></div>
    <div class="relative w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
      <h3 class="text-base font-semibold text-slate-900">Delete message?</h3>
      <p class="mt-1 text-sm text-slate-600">Are you sure you want to delete this message? This action cannot be undone.</p>
      <div class="mt-4 flex justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
          @click="closeDeleteConfirm"
        >
          Cancel
        </button>
        <button
          type="button"
          class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100"
          :disabled="deletingMessageId === confirmDeleteMessageId"
          @click="confirmDeleteMessage"
        >
          {{ deletingMessageId === confirmDeleteMessageId ? 'Deleting...' : 'Delete' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import { useMessageStore } from '@/stores/messageStore'

const route = useRoute()
const router = useRouter()
const messageStore = useMessageStore()

const {
  me,
  contacts,
  selectedUser,
  messages,
  sideFriends,
  sideBlockedFriends,
  loadingContacts,
  loadingMessages,
  loadingSidebar,
  loadingOlder,
  sending,
  processingUserAction,
  deletingMessageId,
  editingMessageId,
  editingContent,
  savingEdit,
  errorMessage,
  successMessage,
  connectionStatus,
  blockedByMe,
  blockedMe,
  contactsPagination,
  messagesPagination,
} = storeToRefs(messageStore)

const form = ref({
  content: '',
  mediaFile: null,
})

const confirmDeleteMessageId = ref(null)
const contactSearch = ref('')
const messageFeedRef = ref(null)

const filteredContacts = computed(() => {
  const query = contactSearch.value.trim().toLowerCase()
  if (!query) return contacts.value
  return contacts.value.filter((contact) => {
    const name = String(contact?.name || '').toLowerCase()
    const headline = String(contact?.profile?.headline || '').toLowerCase()
    return name.includes(query) || headline.includes(query)
  })
})

const formatTime = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
}

const scrollToBottom = async () => {
  await nextTick()
  if (!messageFeedRef.value) return
  messageFeedRef.value.scrollTop = messageFeedRef.value.scrollHeight
}

const selectContact = async (contact) => {
  await messageStore.selectContact(contact)
  window.history.replaceState(window.history.state, '', `/message/${contact.id}`)
  await scrollToBottom()
}

const clearSelectedUser = () => {
  selectedUser.value = null
  messages.value = []
  messagesPagination.value = { ...messagesPagination.value, current_page: 1, last_page: 1, total: 0 }
  window.history.replaceState(window.history.state, '', '/message')
}

const onFileChange = (event) => {
  form.value.mediaFile = event.target.files?.[0] || null
}

const sendMessage = async () => {
  const didSend = await messageStore.sendMessage({
    content: form.value.content,
    mediaFile: form.value.mediaFile,
  })
  if (didSend) {
    form.value.content = ''
    form.value.mediaFile = null
    await scrollToBottom()
  }
}

const openDeleteConfirm = (message) => {
  confirmDeleteMessageId.value = message?.id || null
}

const closeDeleteConfirm = () => {
  confirmDeleteMessageId.value = null
}

const confirmDeleteMessage = async () => {
  if (!confirmDeleteMessageId.value) return
  await messageStore.deleteMessage(confirmDeleteMessageId.value)
  confirmDeleteMessageId.value = null
}

watch(
  () => route.params.userId,
  async (userId) => {
    if (!userId) return
    if (selectedUser.value?.id && String(selectedUser.value.id) === String(userId)) return
    await messageStore.initMessaging(userId)
    await scrollToBottom()
  }
)

watch(
  () => messages.value.length,
  async () => {
    if (messagesPagination.value.current_page !== 1) return
    await scrollToBottom()
  }
)

onMounted(async () => {
  await messageStore.initMessaging(route.params.userId)
  messageStore.connectSocket()
  await scrollToBottom()
})

onUnmounted(() => {
  messageStore.disconnectSocket()
})
</script>
