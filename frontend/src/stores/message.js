import { defineStore } from 'pinia'
import api from '@/services/api'

const CONTACTS_PER_PAGE = 12
const MESSAGES_PER_PAGE = 20

const defaultPagination = (perPage) => ({
  current_page: 1,
  last_page: 1,
  per_page: perPage,
  total: 0,
})

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

export const useMessageStore = defineStore('messageStore', {
  state: () => ({
    me: null,
    unreadCount: 0,
    contacts: [],
    groups: [],
    selectedUser: null,
    selectedGroup: null,
    activeChatType: 'direct',
    messages: [],
    sideFriends: [],
    sideBlockedFriends: [],

    loadingContacts: false,
    loadingGroups: false,
    loadingMessages: false,
    loadingSidebar: false,

    connectionStatus: 'none',
    blockedByMe: false,
    blockedMe: false,

    contactsPagination: defaultPagination(CONTACTS_PER_PAGE),
    messagesPagination: defaultPagination(MESSAGES_PER_PAGE),
  }),

  actions: {
    recomputeUnreadCountFromContacts() {
      const total = (this.contacts || []).reduce((sum, contact) => {
        const count = Number(contact?.unread_count || 0)
        return sum + (Number.isFinite(count) && count > 0 ? count : 0)
      }, 0)
      this.setUnreadCount(total)
    },

    resetMessagesPagination() {
      this.messagesPagination = defaultPagination(MESSAGES_PER_PAGE)
    },

    async loadMe() {
      const response = await api.get('/me')
      this.me = response.data
    },

    setUnreadCount(count = 0) {
      const parsed = Number(count)
      this.unreadCount = Number.isFinite(parsed) && parsed > 0 ? parsed : 0
    },

    async loadUnreadCount() {
      try {
        const response = await api.get('/messages/unread-count', {
          headers: {
            'X-Skip-Loading': 'true',
          },
        })
        this.setUnreadCount(response.data?.data?.count || 0)
      } catch {
        this.setUnreadCount(0)
      }
    },

    async loadContacts(page = 1) {
      this.loadingContacts = true
      try {
        const response = await api.get('/messages/contacts', {
          params: { page, per_page: CONTACTS_PER_PAGE },
        })
        this.contacts = response.data?.data || []
        this.contactsPagination = normalizePagination(response.data, CONTACTS_PER_PAGE)
        this.recomputeUnreadCountFromContacts()
      } finally {
        this.loadingContacts = false
      }
    },

    async loadGroups() {
      this.loadingGroups = true
      try {
        const response = await api.get('/groups')
        this.groups = response.data?.data || []
      } catch {
        this.groups = []
      } finally {
        this.loadingGroups = false
      }
    },

    async loadConnectionStatus(userId) {
      try {
        const response = await api.get(`/connections/status/${userId}`)
        this.connectionStatus = response.data?.data?.status || 'none'
        this.blockedByMe = !!response.data?.data?.blocked_by_me
        this.blockedMe = !!response.data?.data?.blocked_me
      } catch {
        this.connectionStatus = 'none'
        this.blockedByMe = false
        this.blockedMe = false
      }
    },

    async loadSidebarConnections() {
      const meId = this.me?.id
      if (!meId) return

      this.loadingSidebar = true
      try {
        const [friendsRes, blockedRes] = await Promise.all([
          api.get('/connections/my', { params: { page: 1, per_page: 50 } }),
          api.get('/connections/blocked', { params: { page: 1, per_page: 50 } }),
        ])

        const friendRows = friendsRes.data?.data || []
        this.sideFriends = friendRows
          .map((row) => (row.requester_id === meId ? row.addressee : row.requester))
          .filter(Boolean)

        const blockedRows = blockedRes.data?.data || []
        this.sideBlockedFriends = blockedRows
          .map((row) => row.addressee)
          .filter(Boolean)
      } catch {
        this.sideFriends = []
        this.sideBlockedFriends = []
      } finally {
        this.loadingSidebar = false
      }
    },

    async loadMessages(
      targetId,
      page = 1,
      appendOlder = false,
      chatType = this.activeChatType,
      options = {},
    ) {
      const { markRead = chatType === 'direct' && (!appendOlder || page === 1) } = options
      this.loadingMessages = !appendOlder
      try {
        const endpoint = chatType === 'group' ? `/groups/${targetId}/messages` : `/messages/${targetId}`
        const response = await api.get(endpoint, {
          params: { page, per_page: MESSAGES_PER_PAGE },
        })
        const chunk = response.data?.data || []
        this.messages = appendOlder ? [...chunk, ...this.messages] : chunk
        this.messagesPagination = normalizePagination(response.data, MESSAGES_PER_PAGE)

        if (markRead) {
          await api.post(`/messages/${targetId}/read`)
          this.contacts = this.contacts.map((contact) => {
            if (Number(contact?.id) !== Number(targetId)) return contact
            return { ...contact, unread_count: 0 }
          })
          this.recomputeUnreadCountFromContacts()
        }
      } finally {
        this.loadingMessages = false
      }
    },

    setDirectChat(contact) {
      this.activeChatType = 'direct'
      this.selectedUser = contact
      this.selectedGroup = null
    },

    setGroupChat(group) {
      this.activeChatType = 'group'
      this.selectedGroup = group
      this.selectedUser = null
      this.blockedByMe = false
      this.blockedMe = false
      this.connectionStatus = 'none'
    },
  },
})
