import { defineStore } from 'pinia'
import { ref, shallowRef } from 'vue'
import api from '@/services/api'
import { createMessageSocket } from '@/services/messageSocket'

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

const isTruthyString = (value) => typeof value === 'string' && value.trim().length > 0

const extractMessage = (payload) => {
  const data = payload?.data ?? payload?.message ?? payload?.payload ?? payload
  if (!data) return null
  const message = data?.message ?? data
  if (!message?.id) return null
  if (!message.sender_id && !message.senderId) return null
  return {
    ...message,
    sender_id: message.sender_id ?? message.senderId,
    receiver_id: message.receiver_id ?? message.receiverId,
  }
}

export const useMessageStore = defineStore('message', () => {
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

  const contactsPagination = ref(defaultPagination(CONTACTS_PER_PAGE))
  const messagesPagination = ref(defaultPagination(MESSAGES_PER_PAGE))

  const socket = shallowRef(null)
  const socketStatus = ref('idle')

  const clearFeedback = () => {
    errorMessage.value = ''
    successMessage.value = ''
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
    messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
    await loadConnectionStatus(contact.id)
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

  const sendMessage = async ({ content, mediaFile }) => {
    if (!selectedUser.value) return false
    clearFeedback()

    if (connectionStatus.value !== 'accepted' && connectionStatus.value !== 'blocked') {
      errorMessage.value = 'You can only send messages to friends.'
      return false
    }

    if (blockedMe.value) {
      errorMessage.value = 'You are blocked and cannot message this user.'
      return false
    }

    const hasContent = isTruthyString(content)
    const hasFile = !!mediaFile

    if (!hasContent && !hasFile) {
      errorMessage.value = 'Type a message or choose image/video.'
      return false
    }

    sending.value = true
    try {
      const formData = new FormData()
      if (hasContent) formData.append('content', content.trim())
      if (hasFile) formData.append('media_file', mediaFile)

      const response = await api.post(`/messages/${selectedUser.value.id}`, formData)

      if (messagesPagination.value.current_page === 1) {
        messages.value = [...messages.value, response.data.data]
      } else {
        messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
        await loadMessages(selectedUser.value.id, 1, false)
      }

      await loadContacts(contactsPagination.value.current_page)
      successMessage.value = 'Message sent.'
      return true
    } catch (error) {
      errorMessage.value = error.response?.data?.message || 'Failed to send message.'
      return false
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

  const ensureContactEntry = (contactId) => {
    if (!contactId) return
    const existing = contacts.value.find((contact) => String(contact.id) === String(contactId))
    if (!existing) return
    return existing
  }

  const bumpUnreadForContact = (contactId) => {
    const entry = ensureContactEntry(contactId)
    if (!entry) return
    const current = Number(entry.unread_count || 0)
    entry.unread_count = current + 1
  }

  const handleIncomingMessage = (message) => {
    const meId = me.value?.id
    if (!meId) return

    const senderId = message.sender_id
    const receiverId = message.receiver_id
    const counterpartId = senderId === meId ? receiverId : senderId
    if (!counterpartId) return

    const isActiveChat = selectedUser.value?.id && String(selectedUser.value.id) === String(counterpartId)
    const exists = messages.value.some((item) => item.id === message.id)

    if (isActiveChat && messagesPagination.value.current_page === 1) {
      if (!exists) {
        messages.value = [...messages.value, message]
      }
    } else if (!isActiveChat) {
      bumpUnreadForContact(counterpartId)
    }

    loadContacts(contactsPagination.value.current_page)
    window.dispatchEvent(new Event('messages:updated'))
  }

  const handleUpdatedMessage = (message) => {
    messages.value = messages.value.map((item) => (item.id === message.id ? message : item))
  }

  const handleDeletedMessage = (messageId) => {
    if (!messageId) return
    messages.value = messages.value.filter((item) => item.id !== messageId)
  }

  const handleSocketPayload = (payload) => {
    if (!payload) return
    const eventName = payload?.event || payload?.type || payload?.name || payload?.action
    const normalizedEvent = typeof eventName === 'string' ? eventName.toLowerCase() : ''
    const message = extractMessage(payload)

    if (normalizedEvent.includes('deleted') || normalizedEvent.includes('removed')) {
      const deletedId = payload?.message_id || payload?.id || payload?.data?.id
      handleDeletedMessage(deletedId)
      return
    }

    if (message) {
      if (normalizedEvent.includes('updated') || normalizedEvent.includes('edited')) {
        handleUpdatedMessage(message)
      } else {
        handleIncomingMessage(message)
      }
    }
  }

  const connectSocket = () => {
    if (socket.value) return
    socketStatus.value = 'connecting'
    socket.value = createMessageSocket({
      onOpen: () => {
        socketStatus.value = 'connected'
      },
      onClose: () => {
        socketStatus.value = 'closed'
      },
      onError: () => {
        socketStatus.value = 'error'
      },
      onMessage: handleSocketPayload,
      getAuthToken: () => localStorage.getItem('token'),
      getUserId: () => me.value?.id,
    })
    socket.value.connect()
  }

  const disconnectSocket = () => {
    if (socket.value) {
      socket.value.disconnect()
      socket.value = null
      socketStatus.value = 'idle'
    }
  }

  const initMessaging = async (routeContactId) => {
    try {
      await loadMe()
      await Promise.all([loadContacts(1), loadSidebarConnections()])

      if (!routeContactId) return
      const contact = contacts.value.find((item) => String(item.id) === String(routeContactId))
      if (contact) {
        await selectContact(contact)
        return
      }

      const response = await api.get(`/users/${routeContactId}`)
      selectedUser.value = response.data?.data || null
      if (selectedUser.value?.id) {
        await loadConnectionStatus(selectedUser.value.id)
        await loadMessages(selectedUser.value.id, 1, false)
      }
    } catch {
      errorMessage.value = 'Failed to load messages.'
    }
  }

  return {
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
    socketStatus,
    clearFeedback,
    loadMe,
    loadContacts,
    loadConnectionStatus,
    loadSidebarConnections,
    loadMessages,
    selectContact,
    loadOlderMessages,
    sendMessage,
    deleteMessage,
    startEdit,
    cancelEdit,
    saveEditMessage,
    blockSelectedUser,
    unblockSelectedUser,
    unblockUserFromSide,
    connectSocket,
    disconnectSocket,
    initMessaging,
  }
})
