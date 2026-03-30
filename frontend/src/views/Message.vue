<template>
  <Navbar />
  <main class="relative min-h-screen overflow-hidden bg-slate-100 py-6 md:py-8">
    <div class="pointer-events-none absolute -left-28 -top-24 h-72 w-72 rounded-full bg-cyan-200/40 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 right-0 h-80 w-80 rounded-full bg-blue-200/35 blur-3xl"></div>
    <div class="mx-auto grid max-w-7xl grid-cols-12 items-start gap-5 px-4 sm:px-5">
      <aside class="col-span-12 rounded-3xl border border-white/70 bg-white/85 p-4 shadow-[0_18px_45px_-24px_rgba(14,116,144,0.45)] backdrop-blur-xl md:col-span-4 lg:col-span-3">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-base font-semibold text-slate-800">Conversations</h2>
          <div class="flex items-center gap-2">
            <button
              class="rounded-xl border border-cyan-200 bg-gradient-to-r from-cyan-50 to-sky-50 px-2.5 py-1.5 text-xs font-semibold text-cyan-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-cyan-100"
              @click="openCreateGroupModal"
            >
              Create Group
            </button>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ totalConversationCount }}</span>
          </div>
        </div>

        <div class="mb-3">
          <input
            v-model.trim="contactSearch"
            type="text"
            placeholder="Search friends..."
            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
          />
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
            v-for="contact in filteredContacts"
            :key="contact.id"
            @click="selectContact(contact)"
            class="flex w-full items-center justify-between gap-3 rounded-2xl p-2.5 text-left transition hover:bg-slate-100/90 hover:shadow-sm"
            :class="isDirectChat && selectedUser?.id === contact.id ? 'bg-slate-100 ring-1 ring-slate-200 shadow-sm' : ''"
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
          <p v-if="!filteredContacts.length" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">
            {{ contactSearch ? 'No matching friends found.' : 'No direct chats yet.' }}
          </p>
        </div>

        <div class="mt-4">
          <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Groups</h3>
          <div class="mb-2">
            <input
              v-model.trim="groupSearch"
              type="text"
              placeholder="Search groups..."
              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
            />
          </div>
          <div v-if="loadingGroups" class="space-y-2">
            <div v-for="n in 3" :key="`skeleton-group-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="space-y-2">
            <button
              v-for="group in filteredGroups"
              :key="`group-${group.id}`"
              @click="selectGroup(group)"
              class="flex w-full items-center gap-3 rounded-2xl p-2.5 text-left transition hover:bg-slate-100/90 hover:shadow-sm"
              :class="!isDirectChat && selectedGroup?.id === group.id ? 'bg-slate-100 ring-1 ring-slate-200 shadow-sm' : ''"
            >
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-cyan-100 to-blue-100 text-sm font-semibold text-cyan-700 ring-1 ring-cyan-200">
                {{ String(group.name || 'G').charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ group.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ (group.members || []).length }} members</p>
              </div>
            </button>
            <p v-if="!filteredGroups.length" class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">
              {{ groupSearch ? 'No matching groups found.' : 'No groups yet.' }}
            </p>
          </div>
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

      <section class="col-span-12 flex h-[80vh] flex-col rounded-3xl border border-white/70 bg-white/90 shadow-[0_20px_55px_-26px_rgba(15,23,42,0.5)] backdrop-blur-xl md:col-span-8 lg:col-span-6">
        <div class="flex items-center justify-between border-b border-slate-200/80 bg-white/80 px-4 py-3 backdrop-blur">
          <div class="flex items-center gap-3">
            <img v-if="isDirectChat && selectedUser" :src="selectedUser.profile?.avatar || fallbackAvatar" class="h-10 w-10 rounded-full object-cover ring-2 ring-white shadow-sm" />
            <div v-else-if="!isDirectChat && selectedGroup" class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-cyan-100 to-blue-100 text-sm font-semibold text-cyan-700 ring-1 ring-cyan-200">
              {{ String(selectedGroup.name || 'G').charAt(0).toUpperCase() }}
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-800">
                {{ activeTitle }}
              </h3>
              <p class="text-xs text-slate-500">
                {{ activeSubtitle }}
              </p>
            </div>
          </div>
          <div v-if="isDirectChat && selectedUser" class="flex items-center gap-2">
            <RouterLink :to="{ name: 'Profile', params: { id: selectedUser.id } }" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium transition hover:bg-slate-100">
              Profile
            </RouterLink>
            <button
              v-if="blockedByMe"
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium transition hover:bg-slate-100"
              :disabled="processingUserAction"
              @click="unblockSelectedUser"
            >
              Unblock
            </button>
            <button
              v-else-if="connectionStatus === 'accepted'"
              class="rounded-xl border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50"
              :disabled="processingUserAction"
              @click="blockSelectedUser"
            >
              Block
            </button>
          </div>
        </div>

        <div class="chat-scroll flex-1 overflow-y-auto bg-gradient-to-b from-sky-50/70 via-white to-cyan-50/60 p-4">
          <div v-if="activeChatId && messagesPagination.current_page < messagesPagination.last_page" class="mb-3 flex justify-center">
            <button
              class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium shadow-sm transition hover:bg-slate-100 disabled:opacity-50"
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
              <div class="max-w-[78%] bubble-pop">
                <div
                  class="rounded-2xl px-3.5 py-2.5 shadow-sm"
                  :class="message.sender_id === me?.id ? 'bg-gradient-to-r from-sky-600 to-indigo-700 text-white shadow-[0_10px_20px_-12px_rgba(37,99,235,0.8)]' : 'bg-white/95 text-slate-800 ring-1 ring-slate-200'"
                >
                  <div v-if="editingMessageId === message.id" class="space-y-2">
                    <textarea
                      v-model="editingContent"
                      rows="2"
                      class="w-full rounded-xl border border-white/40 bg-white/95 px-2 py-1 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-white/50"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                      <button
                        class="rounded-lg bg-white/20 px-2 py-1 text-xs text-white transition hover:bg-white/30"
                        @click="cancelEdit"
                      >
                        Cancel
                      </button>
                      <button
                        class="rounded-lg bg-white px-2 py-1 text-xs font-medium text-teal-700 transition hover:bg-slate-100 disabled:opacity-60"
                        :disabled="savingEdit"
                        @click="saveEditMessage(message.id)"
                      >
                        {{ savingEdit ? 'Saving...' : 'Save' }}
                      </button>
                    </div>
                  </div>
                  <p v-else-if="message.content" class="whitespace-pre-wrap text-sm leading-relaxed">{{ message.content }}</p>

                  <img v-if="message.media_type === 'image' && message.media_url" :src="message.media_url" class="mt-2 max-h-56 rounded-xl object-cover ring-1 ring-black/5" />
                  <video
                    v-if="message.media_type === 'video' && message.media_url"
                    :src="message.media_url"
                    class="mt-2 max-h-56 rounded-xl ring-1 ring-black/5"
                    controls
                  ></video>
                </div>
                <div class="mt-1 flex items-center gap-2" :class="message.sender_id === me?.id ? 'justify-end' : 'justify-start'">
                  <span class="text-[11px] text-slate-400">{{ formatTime(message.created_at) }}</span>
                  <button
                    v-if="isDirectChat && message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded-md px-1.5 text-[11px] text-blue-500 transition hover:bg-blue-50 hover:text-blue-600 group-hover:visible"
                    :disabled="deletingMessageId === message.id || savingEdit"
                    @click="startEdit(message)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="isDirectChat && message.sender_id === me?.id && editingMessageId !== message.id"
                    class="invisible rounded-md px-1.5 text-[11px] text-red-500 transition hover:bg-red-50 hover:text-red-600 group-hover:visible"
                    :disabled="deletingMessageId === message.id"
                    @click="deleteMessage(message.id)"
                  >
                    {{ deletingMessageId === message.id ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
            <p v-if="activeChatId && !messages.length" class="rounded-lg bg-white p-3 text-center text-sm text-slate-500 ring-1 ring-slate-200">
              No messages yet. Start the conversation.
            </p>
          </div>
        </div>

        <form
          v-if="activeChatId"
          @submit.prevent="sendMessage"
          class="space-y-3 border-t border-slate-200/90 bg-gradient-to-r from-slate-50 via-white to-cyan-50/60 p-4 backdrop-blur"
        >
          <p v-if="errorMessage" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ errorMessage }}</p>
          <p v-if="successMessage" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ successMessage }}</p>

          <textarea
            v-model="form.content"
            rows="2"
            placeholder="Write a message..."
            class="w-full rounded-3xl border border-cyan-300/80 bg-white/90 px-4 py-3 text-sm shadow-[inset_0_2px_10px_rgba(15,23,42,0.05)] outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"
            :disabled="sending || (isDirectChat && blockedMe)"
          ></textarea>

          <div class="flex flex-wrap items-center justify-between gap-3">
            <label
              class="group flex min-w-[220px] cursor-pointer items-center gap-2 rounded-2xl border border-cyan-200 bg-white px-3 py-2 text-sm shadow-sm transition hover:border-cyan-300 hover:shadow"
              :class="sending || (isDirectChat && blockedMe) ? 'pointer-events-none opacity-60' : ''"
            >
              <input
                type="file"
                accept="image/*,video/*"
                @change="onFileChange"
                class="hidden"
                :disabled="sending || (isDirectChat && blockedMe)"
              />
              <span class="rounded-lg bg-cyan-100 px-2 py-1 text-xs font-semibold text-cyan-700">Attach</span>
              <span class="max-w-[210px] truncate text-slate-600">
                {{ form.mediaFile ? form.mediaFile.name : 'Photo or video' }}
              </span>
            </label>
            <button
              type="submit"
              :disabled="sending || (isDirectChat && blockedMe)"
              class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-600 to-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-[0_12px_24px_-14px_rgba(8,145,178,0.95)] transition hover:-translate-y-0.5 hover:from-cyan-700 hover:to-blue-800 disabled:opacity-60"
            >
              <span class="text-xs">➤</span>
              {{ sending ? 'Sending...' : 'Send Message' }}
            </button>
          </div>
          <p v-if="isDirectChat && blockedMe" class="text-xs text-red-500">You are blocked and cannot send messages to this user.</p>
        </form>
      </section>

      <aside class="col-span-12 hidden space-y-4 lg:col-span-3 lg:block">
        <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-[0_14px_40px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
          <h3 class="mb-3 text-base font-semibold text-slate-800">Friends</h3>
          <div v-if="loadingSidebar" class="space-y-3">
            <div v-for="n in 4" :key="`side-friend-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="panel-scroll max-h-[28vh] space-y-2 overflow-y-auto">
            <RouterLink
              v-for="friend in sideFriends"
              :key="friend.id"
              :to="{ name: 'Profile', params: { id: friend.id } }"
              class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-slate-100"
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

        <div class="rounded-3xl border border-white/70 bg-white/85 p-4 shadow-[0_14px_40px_-24px_rgba(15,23,42,0.45)] backdrop-blur-xl">
          <h3 class="mb-3 text-base font-semibold text-slate-800">Blocked Friends</h3>
          <div v-if="loadingSidebar" class="space-y-3">
            <div v-for="n in 3" :key="`side-blocked-skeleton-${n}`" class="h-10 animate-pulse rounded bg-slate-200"></div>
          </div>
          <div v-else class="panel-scroll max-h-[28vh] space-y-2 overflow-y-auto">
            <div v-for="friend in sideBlockedFriends" :key="friend.id" class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white/90 p-2">
              <RouterLink :to="{ name: 'Profile', params: { id: friend.id } }" class="flex min-w-0 items-center gap-2">
                <img :src="friend.profile?.avatar || fallbackAvatar" class="h-9 w-9 rounded-full object-cover" />
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
              </RouterLink>
              <button
                class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-xs font-medium transition hover:bg-slate-100"
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

    <div v-if="showCreateGroupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/45 p-4 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-3xl border border-white/70 bg-white/95 p-4 shadow-[0_26px_65px_-30px_rgba(15,23,42,0.75)]">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-base font-semibold text-slate-800">Create Group</h3>
          <button class="rounded-lg px-2 py-1 text-sm text-slate-500 transition hover:bg-slate-100" @click="closeCreateGroupModal">Close</button>
        </div>

        <div class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Group Name</label>
            <input
              v-model="groupForm.name"
              type="text"
              placeholder="Ex: Senior Project Team"
              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
              :disabled="creatingGroup"
            />
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Search Friends</label>
            <input
              v-model="groupForm.search"
              type="text"
              placeholder="Search by name..."
              class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-100"
              :disabled="creatingGroup"
            />
          </div>

          <div class="panel-scroll max-h-56 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-2">
            <label
              v-for="friend in filteredGroupFriends"
              :key="`group-member-${friend.id}`"
              class="flex cursor-pointer items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50"
            >
              <input
                type="checkbox"
                :checked="groupForm.memberIds.includes(friend.id)"
                @change="toggleGroupMember(friend.id)"
                :disabled="creatingGroup"
              />
              <img :src="friend.profile?.avatar || fallbackAvatar" class="h-8 w-8 rounded-full object-cover" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-slate-800">{{ friend.name }}</p>
                <p class="truncate text-xs text-slate-500">{{ friend.profile?.headline || 'Friend' }}</p>
              </div>
            </label>
            <p v-if="!filteredGroupFriends.length" class="p-2 text-xs text-slate-500">No matching friends found.</p>
          </div>

          <p class="text-xs text-slate-500">Selected: {{ groupForm.memberIds.length }} friend(s)</p>
          <p v-if="createGroupError" class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ createGroupError }}</p>

          <div class="flex justify-end gap-2">
            <button
              class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium transition hover:bg-slate-100"
              :disabled="creatingGroup"
              @click="closeCreateGroupModal"
            >
              Cancel
            </button>
            <button
              class="rounded-xl bg-gradient-to-r from-cyan-600 to-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-[0_10px_20px_-12px_rgba(8,145,178,0.85)] transition hover:-translate-y-0.5 disabled:opacity-60"
              :disabled="creatingGroup"
              @click="createGroup"
            >
              {{ creatingGroup ? 'Creating...' : 'Create Group' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import { useMessageStore } from '@/stores/message'

const route = useRoute()
const router = useRouter()
const MESSAGES_PER_PAGE = 20
let messageSocket = null
let reconnectTimer = null
let shouldReconnectSocket = true
const messageStore = useMessageStore()
const {
  me,
  contacts,
  groups,
  selectedUser,
  selectedGroup,
  activeChatType,
  messages,
  sideFriends,
  sideBlockedFriends,
  loadingContacts,
  loadingGroups,
  loadingMessages,
  loadingSidebar,
  connectionStatus,
  blockedByMe,
  blockedMe,
  contactsPagination,
  messagesPagination,
} = storeToRefs(messageStore)

const loadingOlder = ref(false)
const sending = ref(false)
const showCreateGroupModal = ref(false)
const creatingGroup = ref(false)
const processingUserAction = ref(false)
const deletingMessageId = ref(null)
const editingMessageId = ref(null)
const editingContent = ref('')
const savingEdit = ref(false)

const errorMessage = ref('')
const successMessage = ref('')
const createGroupError = ref('')
const contactSearch = ref('')
const groupSearch = ref('')
const groupForm = ref({
  name: '',
  search: '',
  memberIds: [],
})

const resolveWsUrl = () => {
  const configured = String(import.meta.env.VITE_WS_URL || '').trim()
  if (!configured) {
    const protocol = window.location.protocol === 'https:' ? 'wss' : 'ws'
    return `${protocol}://${window.location.hostname}:3000/ws`
  }

  try {
    const parsed = new URL(configured)
    const pageHost = window.location.hostname
    const isLocalWsHost = parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1'
    const isRemotePage = pageHost !== 'localhost' && pageHost !== '127.0.0.1'

    if (isLocalWsHost && isRemotePage) {
      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
      parsed.hostname = pageHost
      parsed.protocol = protocol
      return parsed.toString()
    }

    return parsed.toString()
  } catch {
    return configured
  }
}

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

const isDirectChat = computed(() => activeChatType.value === 'direct')
const activeChatId = computed(() => (isDirectChat.value ? selectedUser.value?.id : selectedGroup.value?.id))
const totalConversationCount = computed(() => Number(contactsPagination.value.total || 0) + groups.value.length)
const activeTitle = computed(() => {
  if (isDirectChat.value && selectedUser.value) return selectedUser.value.name
  if (!isDirectChat.value && selectedGroup.value) return selectedGroup.value.name
  return 'Select a conversation'
})
const activeSubtitle = computed(() => {
  if (isDirectChat.value && selectedUser.value) {
    return selectedUser.value.profile?.headline || 'Alumni member'
  }
  if (!isDirectChat.value && selectedGroup.value) {
    return `${(selectedGroup.value.members || []).length} members`
  }
  return 'Choose a friend or group from left side'
})

const filteredGroupFriends = computed(() => {
  const keyword = groupForm.value.search.trim().toLowerCase()
  if (!keyword) return sideFriends.value

  return sideFriends.value.filter((friend) => {
    const name = String(friend.name || '').toLowerCase()
    const headline = String(friend.profile?.headline || '').toLowerCase()
    return name.includes(keyword) || headline.includes(keyword)
  })
})

const filteredContacts = computed(() => {
  const keyword = contactSearch.value.toLowerCase()
  if (!keyword) return contacts.value

  return contacts.value.filter((contact) => {
    const name = String(contact?.name || '').toLowerCase()
    const headline = String(contact?.profile?.headline || '').toLowerCase()
    return name.includes(keyword) || headline.includes(keyword)
  })
})

const filteredGroups = computed(() => {
  const keyword = groupSearch.value.toLowerCase()
  if (!keyword) return groups.value

  return groups.value.filter((group) => {
    const name = String(group?.name || '').toLowerCase()
    return name.includes(keyword)
  })
})

const clearFeedback = () => {
  errorMessage.value = ''
  successMessage.value = ''
}

const openCreateGroupModal = () => {
  createGroupError.value = ''
  groupForm.value = {
    name: '',
    search: '',
    memberIds: [],
  }
  showCreateGroupModal.value = true
}

const closeCreateGroupModal = () => {
  showCreateGroupModal.value = false
  createGroupError.value = ''
}

const toggleGroupMember = (friendId) => {
  const targetId = Number(friendId)
  const exists = groupForm.value.memberIds.includes(targetId)
  if (exists) {
    groupForm.value.memberIds = groupForm.value.memberIds.filter((id) => id !== targetId)
    return
  }

  groupForm.value.memberIds = [...groupForm.value.memberIds, targetId]
}

const createGroup = async () => {
  createGroupError.value = ''

  const name = groupForm.value.name.trim()
  if (!name) {
    createGroupError.value = 'Group name is required.'
    return
  }

  if (!groupForm.value.memberIds.length) {
    createGroupError.value = 'Please select at least 1 friend.'
    return
  }

  creatingGroup.value = true
  try {
    const response = await api.post('/groups', {
      name,
      member_ids: groupForm.value.memberIds,
    })
    const createdGroup = response.data?.data || null

    successMessage.value = 'Group created successfully.'
    closeCreateGroupModal()
    await Promise.all([loadSidebarConnections(), loadGroups()])
    if (createdGroup?.id) {
      await selectGroup(createdGroup)
    }
  } catch (error) {
    createGroupError.value = error.response?.data?.message || 'Failed to create group.'
  } finally {
    creatingGroup.value = false
  }
}

const formatTime = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
}

const loadMe = async () => {
  await messageStore.loadMe()
}

const scheduleReconnect = () => {
  if (!shouldReconnectSocket || reconnectTimer) return
  reconnectTimer = setTimeout(() => {
    reconnectTimer = null
    connectMessageSocket()
  }, 2000)
}

const handleSocketPayload = async (payload) => {
  const eventType = String(payload?.type || '')
  const eventData = payload?.data || {}

  if (eventType === 'direct_message') {
    const myId = Number(me.value?.id || 0)
    const senderId = Number(eventData.sender_id ?? eventData.message?.sender_id ?? 0)
    const receiverId = Number(eventData.receiver_id ?? eventData.message?.receiver_id ?? 0)
    const partnerId = senderId === myId ? receiverId : senderId
    const incomingMessage = eventData.message || null

    if (isDirectChat.value && Number(selectedUser.value?.id || 0) === partnerId && incomingMessage?.id) {
      const exists = messages.value.some((item) => Number(item.id) === Number(incomingMessage.id))
      if (!exists) {
        messages.value = [...messages.value, incomingMessage]
      }
      await api.post(`/messages/${partnerId}/read`)
      window.dispatchEvent(new Event('messages:updated'))
      return
    }

    await loadContacts(contactsPagination.value.current_page || 1)
    window.dispatchEvent(new Event('messages:updated'))
    return
  }

  if (eventType === 'group_message') {
    const groupId = Number(eventData.group_id ?? eventData.message?.group_chat_id ?? 0)
    const incomingMessage = eventData.message || null

    if (!isDirectChat.value && Number(selectedGroup.value?.id || 0) === groupId && incomingMessage?.id) {
      const exists = messages.value.some((item) => Number(item.id) === Number(incomingMessage.id))
      if (!exists) {
        messages.value = [...messages.value, incomingMessage]
      }
      window.dispatchEvent(new Event('messages:updated'))
      return
    }

    await loadGroups()
    window.dispatchEvent(new Event('messages:updated'))
  }
}

const connectMessageSocket = () => {
  const wsUrl = resolveWsUrl()
  const userId = Number(me.value?.id || 0)
  if (!userId || !wsUrl) return

  if (messageSocket && (messageSocket.readyState === WebSocket.OPEN || messageSocket.readyState === WebSocket.CONNECTING)) {
    return
  }

  try {
    messageSocket = new WebSocket(wsUrl)
  } catch {
    scheduleReconnect()
    return
  }

  messageSocket.onopen = () => {
    const role = typeof me.value?.role === 'string' ? me.value.role : me.value?.role?.name
    messageSocket?.send(JSON.stringify({
      type: 'auth',
      user_id: userId,
      role: role || '',
    }))
  }

  messageSocket.onmessage = async (event) => {
    try {
      const payload = JSON.parse(event.data)
      await handleSocketPayload(payload)
    } catch {
      // ignore invalid payload
    }
  }

  messageSocket.onerror = () => {
    try {
      messageSocket?.close()
    } catch {
      // ignore close errors
    }
  }

  messageSocket.onclose = () => {
    messageSocket = null
    scheduleReconnect()
  }
}

const loadContacts = async (page = 1) => {
  await messageStore.loadContacts(page)
}

const loadGroups = async () => {
  await messageStore.loadGroups()
}

const loadConnectionStatus = async (userId) => {
  await messageStore.loadConnectionStatus(userId)
}

const loadSidebarConnections = async () => {
  await messageStore.loadSidebarConnections()
}

const loadMessages = async (targetId, page = 1, appendOlder = false, chatType = activeChatType.value) => {
  clearFeedback()
  try {
    await messageStore.loadMessages(targetId, page, appendOlder, chatType)
  } catch (error) {
    if (!appendOlder) {
      messages.value = []
      messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
    }
    errorMessage.value = error.response?.data?.message || 'Failed to load messages.'
  }
}

const selectContact = async (contact) => {
  messageStore.setDirectChat(contact)
  await loadConnectionStatus(contact.id)
  await router.replace({ path: `/message/${contact.id}` })
  messageStore.resetMessagesPagination()
  await loadMessages(contact.id, 1, false, 'direct')
}

const selectGroup = async (group) => {
  messageStore.setGroupChat(group)
  await router.replace({ path: '/message' })
  messageStore.resetMessagesPagination()
  await loadMessages(group.id, 1, false, 'group')
}

const loadOlderMessages = async () => {
  if (!activeChatId.value) return
  if (messagesPagination.value.current_page >= messagesPagination.value.last_page) return

  loadingOlder.value = true
  try {
    const nextPage = messagesPagination.value.current_page + 1
    await loadMessages(activeChatId.value, nextPage, true, activeChatType.value)
  } finally {
    loadingOlder.value = false
  }
}

const onFileChange = (event) => {
  form.value.mediaFile = event.target.files?.[0] || null
}

const sendMessage = async () => {
  if (!activeChatId.value) return
  clearFeedback()

  if (isDirectChat.value && connectionStatus.value !== 'accepted' && connectionStatus.value !== 'blocked') {
    errorMessage.value = 'You can only send messages to friends.'
    return
  }

  if (isDirectChat.value && blockedMe.value) {
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

    const endpoint = isDirectChat.value ? `/messages/${activeChatId.value}` : `/groups/${activeChatId.value}/messages`
    const response = await api.post(endpoint, formData)
    const sentMessage = response.data?.data
    if (sentMessage?.id) {
      const exists = messages.value.some((item) => Number(item.id) === Number(sentMessage.id))
      if (!exists) {
        messages.value = [...messages.value, sentMessage]
      }
    }

    form.value.content = ''
    form.value.mediaFile = null
    clearFeedback()
    successMessage.value = 'Message sent.'
    window.dispatchEvent(new Event('messages:updated'))
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send message.'
  } finally {
    sending.value = false
  }
}

const deleteMessage = async (messageId) => {
  if (!isDirectChat.value) return
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
  if (!isDirectChat.value) return
  editingMessageId.value = message.id
  editingContent.value = message.content || ''
  clearFeedback()
}

const cancelEdit = () => {
  editingMessageId.value = null
  editingContent.value = ''
}

const saveEditMessage = async (messageId) => {
  if (!isDirectChat.value) return
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
  if (!isDirectChat.value) return
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
  if (!isDirectChat.value) return
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
    connectMessageSocket()
    await Promise.all([loadContacts(1), loadSidebarConnections(), loadGroups()])

    const contactId = route.params.userId
    if (!contactId) return

    const contact = contacts.value.find((item) => String(item.id) === String(contactId))
    if (contact) {
      messageStore.setDirectChat(contact)
      await loadConnectionStatus(contact.id)
      await loadMessages(contact.id, 1, false, 'direct')
      return
    }

    const response = await api.get(`/users/${contactId}`)
    const resolvedUser = response.data?.data || null
    messageStore.setDirectChat(resolvedUser)
    if (selectedUser.value?.id) {
      await loadConnectionStatus(selectedUser.value.id)
      await loadMessages(selectedUser.value.id, 1, false, 'direct')
    }
  } catch {
    errorMessage.value = 'Failed to load messages.'
  }
})

onUnmounted(() => {
  shouldReconnectSocket = false
  if (reconnectTimer) {
    clearTimeout(reconnectTimer)
    reconnectTimer = null
  }

  if (messageSocket) {
    try {
      messageSocket.close()
    } catch {
      // ignore close errors
    }
    messageSocket = null
  }
})
</script>

<style scoped>
.chat-scroll,
.panel-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.55) transparent;
}

.chat-scroll::-webkit-scrollbar,
.panel-scroll::-webkit-scrollbar {
  width: 8px;
}

.chat-scroll::-webkit-scrollbar-track,
.panel-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.chat-scroll::-webkit-scrollbar-thumb,
.panel-scroll::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.55);
  border-radius: 999px;
}

.bubble-pop {
  animation: bubble-pop 0.2s ease-out;
}

@keyframes bubble-pop {
  from {
    transform: translateY(6px);
    opacity: 0.7;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
