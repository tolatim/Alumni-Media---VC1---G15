<template>
  <Navbar />
  <main class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.06),_transparent_30%),linear-gradient(180deg,_#f8fafc_0%,_#ffffff_45%,_#f8fafc_100%)] py-6 md:py-8">
    <div class="mx-auto grid max-w-7xl gap-5 px-4 lg:grid-cols-[330px_1fr_300px] xl:grid-cols-[350px_1fr_320px]">
      <aside
        class="rounded-[30px] border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur"
        :class="selectedUser ? 'hidden lg:block' : ''"
      >
        <div class="rounded-3xl bg-slate-900 px-4 py-4 text-white">
          <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-300">Messages</p>
          <h1 class="mt-2 text-xl font-semibold">Stay close to your network</h1>
          <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-2xl bg-white/10 px-3 py-3">
              <p class="text-xs text-slate-300">Chats</p>
              <p class="mt-1 text-xl font-semibold">{{ contactsPagination.total }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 px-3 py-3">
              <p class="text-xs text-slate-300">Unread</p>
              <p class="mt-1 text-xl font-semibold">{{ unreadContactsCount }}</p>
            </div>
          </div>
        </div>

        <div class="mt-4 flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
          <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
          <input
            v-model="contactSearch"
            type="text"
            placeholder="Search conversations..."
            class="w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
          >
        </div>

        <div class="mt-3 flex gap-2">
          <button
            type="button"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
            :class="contactFilter === 'all'
              ? 'border-slate-900 bg-slate-900 text-white'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="contactFilter = 'all'"
          >
            All
          </button>
          <button
            type="button"
            class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
            :class="contactFilter === 'unread'
              ? 'border-slate-900 bg-slate-900 text-white'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            @click="contactFilter = 'unread'"
          >
            Unread
          </button>
        </div>

        <div v-if="loadingContacts" class="mt-4 space-y-3">
          <div v-for="n in 5" :key="`skeleton-contact-${n}`" class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3">
            <div class="h-11 w-11 animate-pulse rounded-2xl bg-slate-200"></div>
            <div class="flex-1 space-y-2">
              <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
              <div class="h-2 w-1/2 animate-pulse rounded bg-slate-200"></div>
            </div>
          </div>
        </div>

        <div v-else class="mt-4 space-y-2">
          <button
            v-for="contact in filteredContacts"
            :key="contact.id"
            type="button"
            class="w-full rounded-2xl border p-3 text-left transition"
            :class="selectedUser?.id === contact.id
              ? 'border-slate-900 bg-slate-900 text-white'
              : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
            @click="selectContact(contact)"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="flex min-w-0 items-center gap-3">
                <img
                  :src="contact.profile?.avatar || fallbackAvatar"
                  class="h-11 w-11 rounded-2xl object-cover"
                  loading="lazy"
                >
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold">{{ contact.name }}</p>
                  <p
                    class="truncate text-xs"
                    :class="selectedUser?.id === contact.id ? 'text-slate-300' : 'text-slate-500'"
                  >
                    {{ contact.profile?.headline || 'Friend' }}
                  </p>
                </div>
              </div>

              <span
                v-if="contact.unread_count > 0"
                class="min-w-[22px] rounded-full px-1.5 text-center text-[10px] font-semibold leading-[22px]"
                :class="selectedUser?.id === contact.id ? 'bg-white text-slate-900' : 'bg-rose-500 text-white'"
              >
                {{ contact.unread_count > 99 ? '99+' : contact.unread_count }}
              </span>
            </div>
          </button>

          <div v-if="!filteredContacts.length" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center">
            <p class="text-sm font-semibold text-slate-700">No conversations found</p>
            <p class="mt-1 text-xs text-slate-500">Try another search, or make a new connection first.</p>
          </div>
        </div>

        <div v-if="contactsPagination.last_page > 1" class="mt-4 flex items-center justify-between">
          <button
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
            :disabled="contactsPagination.current_page <= 1 || loadingContacts"
            @click="messageStore.loadContacts(contactsPagination.current_page - 1)"
          >
            Previous
          </button>
          <p class="text-xs text-slate-500">{{ contactsPagination.current_page }} / {{ contactsPagination.last_page }}</p>
          <button
            class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 disabled:opacity-50"
            :disabled="contactsPagination.current_page >= contactsPagination.last_page || loadingContacts"
            @click="messageStore.loadContacts(contactsPagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </aside>

      <section
        class="flex h-[75vh] flex-col rounded-[30px] border border-slate-200 bg-white/95 shadow-sm backdrop-blur sm:h-[80vh]"
        :class="selectedUser ? 'flex' : 'hidden lg:flex'"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5">
          <div class="flex min-w-0 items-center gap-3">
            <button
              v-if="selectedUser"
              type="button"
              class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 lg:hidden"
              aria-label="Back to conversations"
              @click="clearSelectedUser"
            >
              <i class="fa-solid fa-arrow-left"></i>
            </button>

            <div v-if="selectedUser" class="relative">
              <img
                :src="selectedUser.profile?.avatar || fallbackAvatar"
                class="h-12 w-12 rounded-2xl object-cover"
                loading="lazy"
              >
              <span class="absolute -bottom-1 -right-1 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400"></span>
            </div>

            <div class="min-w-0">
              <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                {{ selectedUser ? 'Active chat' : 'Inbox' }}
              </p>
              <h2 class="truncate text-lg font-semibold text-slate-900">
                {{ selectedUser ? selectedUser.name : 'Choose a conversation' }}
              </h2>
              <p class="truncate text-xs text-slate-500">
                {{ selectedUser ? selectedUser.profile?.headline || 'Alumni member' : 'Pick someone from the left to start talking.' }}
              </p>
            </div>
          </div>

          <div v-if="selectedUser" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
              <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
              <input
                v-model="messageSearch"
                type="text"
                placeholder="Search in this chat..."
                class="w-40 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none"
              >
            </div>
            <RouterLink :to="{ name: 'Profile', params: { id: selectedUser.id } }" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
              Profile
            </RouterLink>
            <button
              v-if="blockedByMe"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-60"
              :disabled="processingUserAction"
              @click="messageStore.unblockSelectedUser()"
            >
              {{ processingUserAction ? 'Please wait...' : 'Unblock' }}
            </button>
            <button
              v-else-if="connectionStatus === 'accepted'"
              class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-60"
              :disabled="processingUserAction"
              @click="messageStore.blockSelectedUser()"
            >
              {{ processingUserAction ? 'Please wait...' : 'Block' }}
            </button>
          </div>
        </div>

        <div ref="messageFeedRef" class="flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 via-white to-slate-100/70 px-4 py-4 sm:px-5">
          <div v-if="selectedUser && messagesPagination.current_page < messagesPagination.last_page" class="mb-4 flex justify-center">
            <button
              type="button"
              class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50"
              :disabled="loadingOlder || loadingMessages"
              @click="messageStore.loadOlderMessages()"
            >
              {{ loadingOlder ? 'Loading older messages...' : 'Load older messages' }}
            </button>
          </div>

          <div v-if="!selectedUser" class="flex h-full items-center justify-center">
            <div class="max-w-md text-center">
              <div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-slate-900 text-white">
                <i class="fa-solid fa-comments text-xl"></i>
              </div>
              <h3 class="mt-5 text-xl font-semibold text-slate-900">Your inbox should feel simple</h3>
              <p class="mt-2 text-sm leading-6 text-slate-500">
                Pick a conversation to continue where you left off, or head to connections to meet someone new.
              </p>
              <div class="mt-5 flex flex-wrap justify-center gap-2">
                <RouterLink to="/connection" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                  Find connections
                </RouterLink>
                <button
                  v-if="filteredContacts.length"
                  type="button"
                  class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                  @click="selectContact(filteredContacts[0])"
                >
                  Open first chat
                </button>
              </div>
            </div>
          </div>

          <div v-else-if="loadingMessages" class="space-y-4">
            <div v-for="n in 5" :key="`skeleton-message-${n}`" class="flex" :class="n % 2 ? 'justify-end' : 'justify-start'">
              <div class="h-16 w-56 animate-pulse rounded-3xl bg-slate-200"></div>
            </div>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="message in visibleMessages"
              :key="message.id"
              v-memo="[message.id, editingMessageId === message.id]"
              class="group flex"
              :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'"
            >
              <div class="max-w-[82%]">
                <div
                  class="rounded-[24px] px-4 py-3 shadow-sm"
                  :class="message.sender_id === me?.id
                    ? 'bg-slate-900 text-white'
                    : 'bg-white text-slate-800 ring-1 ring-slate-200'"
                >
                  <div v-if="editingMessageId === message.id" class="space-y-3">
                    <textarea
                      v-model="editingContent"
                      rows="3"
                      class="w-full rounded-2xl border border-white/20 bg-white/95 px-3 py-2 text-sm text-slate-800 focus:outline-none"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                      <button
                        type="button"
                        class="rounded-xl border border-white/20 px-3 py-2 text-xs font-semibold text-white hover:bg-white/10"
                        @click="messageStore.cancelEdit()"
                      >
                        Cancel
                      </button>
                      <button
                        type="button"
                        class="rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-100 disabled:opacity-60"
                        :disabled="savingEdit"
                        @click="messageStore.saveEditMessage(message.id)"
                      >
                        {{ savingEdit ? 'Saving...' : 'Save changes' }}
                      </button>
                    </div>
                  </div>

                  <template v-else>
                    <p v-if="message.content" class="whitespace-pre-wrap text-sm leading-6">{{ message.content }}</p>
                    <img
                      v-if="message.media_type === 'image' && message.media_url"
                      :src="message.media_url"
                      class="mt-3 max-h-72 rounded-2xl object-cover"
                      loading="lazy"
                    >
                    <video
                      v-if="message.media_type === 'video' && message.media_url"
                      :src="message.media_url"
                      class="mt-3 max-h-72 rounded-2xl"
                      controls
                    ></video>
                  </template>
                </div>

                <div class="mt-1 flex items-center gap-2 px-1" :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'">
                  <span class="text-[11px] text-slate-400">{{ formatTime(message.created_at) }}</span>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    type="button"
                    class="invisible rounded px-2 py-1 text-[11px] font-semibold text-slate-500 hover:bg-slate-100 group-hover:visible"
                    :disabled="deletingMessageId === message.id || savingEdit"
                    @click="messageStore.startEdit(message)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="message.sender_id === me?.id && editingMessageId !== message.id"
                    type="button"
                    class="invisible rounded px-2 py-1 text-[11px] font-semibold text-rose-600 hover:bg-rose-50 group-hover:visible"
                    :disabled="deletingMessageId === message.id"
                    @click="openDeleteConfirm(message)"
                  >
                    {{ deletingMessageId === message.id ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>

            <div v-if="selectedUser && !visibleMessages.length && messageSearch" class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center">
              <p class="text-sm font-semibold text-slate-700">No messages match your search</p>
              <p class="mt-1 text-xs text-slate-500">Try a different word or clear the search box.</p>
            </div>

            <div v-else-if="selectedUser && !messages.length" class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center">
              <p class="text-sm font-semibold text-slate-700">No messages yet</p>
              <p class="mt-1 text-xs text-slate-500">A simple hello is often the best way to begin.</p>
            </div>
          </div>
        </div>

        <form v-if="selectedUser" class="border-t border-slate-200 px-4 py-4 sm:px-5" @submit.prevent="sendMessage">
          <div v-if="errorMessage" class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            {{ errorMessage }}
          </div>
          <div v-if="successMessage" class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            {{ successMessage }}
          </div>

          <div class="rounded-[26px] border border-slate-200 bg-white p-3 shadow-sm">
            <textarea
              v-model="form.content"
              rows="3"
              placeholder="Write something thoughtful..."
              class="w-full resize-none bg-transparent text-sm leading-6 text-slate-800 placeholder:text-slate-400 focus:outline-none"
              :disabled="sending || blockedMe"
            ></textarea>

            <div class="mt-3 flex flex-col gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <label class="inline-flex cursor-pointer items-center gap-2">
                  <input
                    type="file"
                    accept="image/*,video/*"
                    class="hidden"
                    :disabled="sending || blockedMe"
                    @change="onFileChange"
                  >
                  <span class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 font-semibold text-slate-700 hover:bg-slate-100">Attach photo/video</span>
                </label>
                <span v-if="form.mediaFile" class="truncate text-[11px] text-slate-400">{{ form.mediaFile.name }}</span>
                <span class="text-[11px] text-slate-400">{{ trimmedMessageLength }}/5000</span>
              </div>

              <button
                type="submit"
                :disabled="!canSend || sending || blockedMe"
                class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:opacity-50"
              >
                {{ sending ? 'Sending...' : 'Send message' }}
              </button>
            </div>

            <p v-if="blockedMe" class="mt-3 text-xs font-medium text-rose-600">
              You are blocked and cannot send messages to this user.
            </p>
          </div>
        </form>
      </section>

      <aside class="space-y-5" :class="selectedUser ? 'hidden lg:block' : ''">
        <section class="rounded-[30px] border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Quick Actions</p>
          <div class="mt-4 grid gap-2">
            <RouterLink to="/connection" class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
              Find new connections
            </RouterLink>
            <RouterLink to="/post" class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
              Share an update
            </RouterLink>
            <RouterLink to="/profile/edit" class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
              Edit profile
            </RouterLink>
          </div>
        </section>

        <section class="rounded-[30px] border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
            {{ selectedUser ? 'Conversation Notes' : 'Helpful Reminder' }}
          </p>
          <div v-if="selectedUser" class="mt-3 space-y-3">
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
              <p class="text-xs text-slate-500">Connection status</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ connectionLabel }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
              <p class="text-xs text-slate-500">Visible messages</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ visibleMessages.length }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 px-4 py-3">
              <p class="text-xs text-slate-500">Tone tip</p>
              <p class="mt-1 text-sm leading-6 text-slate-700">Short, warm messages feel easiest to reply to.</p>
            </div>
          </div>
          <div v-else class="mt-3 rounded-2xl bg-slate-50 px-4 py-4">
            <p class="text-sm leading-6 text-slate-600">
              Messaging works best when it feels personal. Start with people you already know, then keep the first message short and kind.
            </p>
          </div>
        </section>

        <section class="rounded-[30px] border border-slate-200 bg-white/90 p-5 shadow-sm backdrop-blur">
          <div class="flex items-center justify-between">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">Blocked</p>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ sideBlockedFriends.length }}</span>
          </div>
          <div v-if="loadingSidebar" class="mt-4 space-y-3">
            <div v-for="n in 3" :key="`blocked-skeleton-${n}`" class="h-12 animate-pulse rounded-2xl bg-slate-200"></div>
          </div>
          <div v-else-if="sideBlockedFriends.length" class="mt-4 space-y-2">
            <div
              v-for="friend in sideBlockedFriends"
              :key="friend.id"
              class="flex items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-3"
            >
              <RouterLink :to="{ name: 'Profile', params: { id: friend.id } }" class="flex min-w-0 items-center gap-3">
                <img :src="friend.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-2xl object-cover" loading="lazy">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-900">{{ friend.name }}</p>
                  <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Blocked user' }}</p>
                </div>
              </RouterLink>
              <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 disabled:opacity-60"
                :disabled="processingUserAction"
                @click="messageStore.unblockUserFromSide(friend.id)"
              >
                Unblock
              </button>
            </div>
          </div>
          <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center">
            <p class="text-sm font-semibold text-slate-700">No blocked users</p>
            <p class="mt-1 text-xs text-slate-500">Everyone is reachable right now.</p>
          </div>
        </section>
      </aside>
    </div>
  </main>

  <div v-if="confirmDeleteMessageId" class="fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDeleteConfirm"></div>
    <div class="relative w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl">
      <h3 class="text-lg font-semibold text-slate-900">Delete this message?</h3>
      <p class="mt-2 text-sm leading-6 text-slate-500">This removes it from the conversation and cannot be undone.</p>
      <div class="mt-5 flex justify-end gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"
          @click="closeDeleteConfirm"
        >
          Cancel
        </button>
        <button
          type="button"
          class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-60"
          :disabled="deletingMessageId === confirmDeleteMessageId"
          @click="confirmDeleteMessage"
        >
          {{ deletingMessageId === confirmDeleteMessageId ? 'Deleting...' : 'Delete message' }}
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
const contactFilter = ref('all')
const messageSearch = ref('')
const messageFeedRef = ref(null)

const unreadContactsCount = computed(() =>
  contacts.value.reduce((sum, contact) => sum + Number(contact?.unread_count || 0), 0)
)

const filteredContacts = computed(() => {
  const query = contactSearch.value.trim().toLowerCase()
  return contacts.value.filter((contact) => {
    const name = String(contact?.name || '').toLowerCase()
    const headline = String(contact?.profile?.headline || '').toLowerCase()
    const matchesQuery = !query || name.includes(query) || headline.includes(query)
    const matchesFilter = contactFilter.value === 'all' || Number(contact?.unread_count || 0) > 0
    return matchesQuery && matchesFilter
  })
})

const visibleMessages = computed(() => {
  const query = messageSearch.value.trim().toLowerCase()
  if (!query) return messages.value
  return messages.value.filter((message) => {
    const content = String(message?.content || '').toLowerCase()
    const mediaType = String(message?.media_type || '').toLowerCase()
    return content.includes(query) || mediaType.includes(query)
  })
})

const trimmedMessageLength = computed(() => form.value.content.trim().length)
const canSend = computed(() => trimmedMessageLength.value > 0 || !!form.value.mediaFile)

const connectionLabel = computed(() => {
  if (blockedByMe.value) return 'Blocked by you'
  if (blockedMe.value) return 'They blocked you'
  if (connectionStatus.value === 'accepted') return 'Connected'
  if (connectionStatus.value === 'blocked') return 'Blocked'
  return 'Unknown'
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
  messageSearch.value = ''
  await messageStore.selectContact(contact)
  window.history.replaceState(window.history.state, '', `/message/${contact.id}`)
  await scrollToBottom()
}

const clearSelectedUser = () => {
  router.replace({ path: '/message' })
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
    if (!userId) {
      messageSearch.value = ''
      await messageStore.initMessaging(null)
      return
    }
    if (selectedUser.value?.id && String(selectedUser.value.id) === String(userId)) return
    messageSearch.value = ''
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
