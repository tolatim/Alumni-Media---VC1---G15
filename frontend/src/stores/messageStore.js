import { defineStore } from 'pinia'
import { ref, shallowRef } from 'vue'
import api from '@/services/api'
import { createEcho, disconnectEcho } from '@/services/realtime'

const CONTACTS_PER_PAGE = 12
const MESSAGES_PER_PAGE = 20
const LIVE_REFRESH_INTERVAL_MS = 900
const SOCKET_FALLBACK_DELAY_MS = 1200

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

const toMessageKey = (value) => String(value)

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
  const echoChannel = shallowRef(null)
  const socketStatus = ref('idle')
  let hasBootstrapped = false
  let bootstrapPromise = null
  let contactRefreshTimer = null
  let liveRefreshTimer = null
  let fallbackStartTimer = null
  let lastContactRefreshAt = 0
  let lastReadMarkAt = 0
  let lastReadTargetId = null
  let lastSyncAt = 0
  let messageIds = new Set()
  let latestMessageId = 0

  const resetMessageState = () => {
    messages.value = []
    messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
    messageIds = new Set()
    latestMessageId = 0
  }

  const replaceMessages = (nextMessages) => {
    const nextList = Array.isArray(nextMessages) ? nextMessages : []
    messages.value = nextList
    messageIds = new Set()
    latestMessageId = 0

    for (const item of nextList) {
      if (!item?.id) continue
      messageIds.add(toMessageKey(item.id))
      latestMessageId = Math.max(latestMessageId, Number(item.id) || 0)
    }
  }

  const appendMessages = (nextMessages) => {
    const incoming = Array.isArray(nextMessages) ? nextMessages : []
    if (!incoming.length) return

    const fresh = []
    for (const item of incoming) {
      const key = item?.id ? toMessageKey(item.id) : null
      if (!key || messageIds.has(key)) continue
      messageIds.add(key)
      latestMessageId = Math.max(latestMessageId, Number(item.id) || 0)
      fresh.push(item)
    }

    if (fresh.length) {
      messages.value = [...messages.value, ...fresh]
    }
  }

  const prependMessages = (nextMessages) => {
    const incoming = Array.isArray(nextMessages) ? nextMessages : []
    if (!incoming.length) return

    const fresh = []
    for (const item of incoming) {
      const key = item?.id ? toMessageKey(item.id) : null
      if (!key || messageIds.has(key)) continue
      messageIds.add(key)
      latestMessageId = Math.max(latestMessageId, Number(item.id) || 0)
      fresh.push(item)
    }

    if (fresh.length) {
      messages.value = [...fresh, ...messages.value]
    }
  }

  const upsertMessage = (message) => {
    if (!message?.id) return
    const key = toMessageKey(message.id)

    const existingIndex = messages.value.findIndex((item) => toMessageKey(item.id) === key)
    if (existingIndex === -1) {
      appendMessages([message])
      return
    }

    const nextMessages = messages.value.slice()
    nextMessages[existingIndex] = message
    messages.value = nextMessages
    messageIds.add(key)
    latestMessageId = Math.max(latestMessageId, Number(message.id) || 0)
  }

  const removeMessage = (messageId) => {
    const key = messageId ? toMessageKey(messageId) : null
    if (!key || !messageIds.has(key)) return
    messages.value = messages.value.filter((item) => toMessageKey(item.id) !== key)
    messageIds.delete(key)
    if (Number(messageId) === latestMessageId) {
      latestMessageId = Number(messages.value.at(-1)?.id || 0)
    }
  }

  const clearFeedback = () => {
    errorMessage.value = ''
    successMessage.value = ''
  }

  const withSilentLoading = (config = {}) => ({
    ...config,
    headers: {
      ...(config.headers || {}),
      'X-Skip-Loading': 'true',
    },
  })

  const loadMe = async () => {
    const response = await api.get('/me', withSilentLoading())
    me.value = response.data
  }

  const bootstrapMessaging = async ({ force = false } = {}) => {
    if (hasBootstrapped && !force) return
    if (bootstrapPromise && !force) return bootstrapPromise

    bootstrapPromise = (async () => {
      await loadMe()
      await Promise.all([loadContacts(1), loadSidebarConnections()])
      hasBootstrapped = true
    })()

    try {
      await bootstrapPromise
    } finally {
      bootstrapPromise = null
    }
  }

  const loadContacts = async (page = 1, options = {}) => {
    const { silent = false } = options
    if (!silent) {
      loadingContacts.value = true
    }
    try {
      const response = await api.get('/messages/contacts', {
        params: { page, per_page: CONTACTS_PER_PAGE },
        headers: { 'X-Skip-Loading': 'true' },
      })
      contacts.value = response.data?.data || []
      contactsPagination.value = normalizePagination(response.data, CONTACTS_PER_PAGE)
    } finally {
      if (!silent) {
        loadingContacts.value = false
      }
    }
  }

  const scheduleContactsRefresh = (page = contactsPagination.value.current_page, immediate = false) => {
    if (contactRefreshTimer) {
      clearTimeout(contactRefreshTimer)
      contactRefreshTimer = null
    }

    const now = Date.now()
    if (immediate && now - lastContactRefreshAt > 400) {
      lastContactRefreshAt = now
      loadContacts(page, { silent: true })
      return
    }

    contactRefreshTimer = setTimeout(() => {
      lastContactRefreshAt = Date.now()
      loadContacts(page, { silent: true })
    }, 450)
  }

  const loadConnectionStatus = async (userId) => {
    try {
      const response = await api.get(`/connections/status/${userId}`, withSilentLoading())
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

    const shouldShowSidebarLoader = sideFriends.value.length === 0 && sideBlockedFriends.value.length === 0
    if (shouldShowSidebarLoader) {
      loadingSidebar.value = true
    }
    try {
      const [friendsRes, blockedRes] = await Promise.all([
        api.get('/connections/my', withSilentLoading({ params: { page: 1, per_page: 50 } })),
        api.get('/connections/blocked', withSilentLoading({ params: { page: 1, per_page: 50 } })),
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
      if (shouldShowSidebarLoader) {
        loadingSidebar.value = false
      }
    }
  }

  const loadMessages = async (userId, page = 1, appendOlder = false, options = {}) => {
    const { silent = false } = options

    loadingMessages.value = !appendOlder && !silent
    if (!silent) {
      clearFeedback()
    }
    try {
      const response = await api.get(`/messages/${userId}`, {
        params: { page, per_page: MESSAGES_PER_PAGE },
        headers: { 'X-Skip-Loading': 'true' },
      })
      const chunk = response.data?.data || []
      if (appendOlder) {
        prependMessages(chunk)
      } else {
        replaceMessages(chunk)
      }
      messagesPagination.value = normalizePagination(response.data, MESSAGES_PER_PAGE)

      if (!appendOlder || page === 1) {
        await api.post(`/messages/${userId}/read`, {}, {
          headers: { 'X-Skip-Loading': 'true' },
        })
      }

      scheduleContactsRefresh(contactsPagination.value.current_page, true)
      window.dispatchEvent(new Event('messages:updated'))
    } catch (error) {
      if (!appendOlder) {
        messages.value = []
        messagesPagination.value = defaultPagination(MESSAGES_PER_PAGE)
      }
      if (!silent) {
        errorMessage.value = error.response?.data?.message || 'Failed to load messages.'
      }
    } finally {
      if (!appendOlder) {
        loadingMessages.value = false
      }
    }
  }

  const syncLatestMessages = async (userId) => {
    try {
      const response = await api.get(`/messages/${userId}/sync`, {
        params: { after_id: latestMessageId },
        headers: { 'X-Skip-Loading': 'true' },
      })

      const incoming = Array.isArray(response.data?.data) ? response.data.data : []
      if (!incoming.length) return

      appendMessages(incoming)
      if (selectedUser.value?.id) {
        clearUnreadForContact(selectedUser.value.id)
        markReadForContact(selectedUser.value.id)
      }
      scheduleContactsRefresh(contactsPagination.value.current_page, true)
      window.dispatchEvent(new Event('messages:updated'))
    } catch {
      // keep the fast path silent; regular loads still surface errors
    }
  }

  const selectContact = async (contact) => {
    selectedUser.value = contact
    resetMessageState()
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

      const response = await api.post(`/messages/${selectedUser.value.id}`, formData, withSilentLoading())

      if (messagesPagination.value.current_page === 1) {
        appendMessages([response.data.data])
      } else {
        resetMessageState()
        await loadMessages(selectedUser.value.id, 1, false)
      }

      scheduleContactsRefresh(contactsPagination.value.current_page, true)
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
      await api.delete(`/messages/item/${messageId}`, withSilentLoading())
      removeMessage(messageId)
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
      const response = await api.put(`/messages/item/${messageId}`, { content }, withSilentLoading())
      const updated = response.data?.data
      upsertMessage(updated)
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
      await api.post(`/connections/user/${selectedUser.value.id}/block`, {}, withSilentLoading())
      await loadConnectionStatus(selectedUser.value.id)
      scheduleContactsRefresh(contactsPagination.value.current_page, true)
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
      await api.post(`/connections/user/${selectedUser.value.id}/unblock`, {}, withSilentLoading())
      await loadConnectionStatus(selectedUser.value.id)
      scheduleContactsRefresh(contactsPagination.value.current_page, true)
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
      await api.post(`/connections/user/${userId}/unblock`, {}, withSilentLoading())
      await loadSidebarConnections()
      scheduleContactsRefresh(contactsPagination.value.current_page, true)
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
    if (!contactId) return null
    const existing = contacts.value.find((contact) => String(contact.id) === String(contactId))
    if (!existing) return null
    return existing
  }

  const bumpUnreadForContact = (contactId) => {
    const entry = ensureContactEntry(contactId)
    if (!entry) return
    const current = Number(entry.unread_count || 0)
    entry.unread_count = current + 1
  }

  const clearUnreadForContact = (contactId) => {
    const entry = ensureContactEntry(contactId)
    if (!entry) return
    entry.unread_count = 0
  }

  const markReadForContact = async (contactId) => {
    if (!contactId) return
    const now = Date.now()
    if (lastReadTargetId === contactId && now - lastReadMarkAt < 600) return
    lastReadTargetId = contactId
    lastReadMarkAt = now
    try {
      await api.post(`/messages/${contactId}/read`, {}, withSilentLoading())
    } catch {
      // ignore read failures; will refresh later
    }
  }

  const handleIncomingMessage = (message) => {
    const meId = me.value?.id
    if (!meId) return

    const senderId = message.sender_id
    const receiverId = message.receiver_id
    const counterpartId = senderId === meId ? receiverId : senderId
    if (!counterpartId) return

    const isActiveChat = selectedUser.value?.id && String(selectedUser.value.id) === String(counterpartId)

    if (isActiveChat && messagesPagination.value.current_page === 1) {
      appendMessages([message])
      clearUnreadForContact(counterpartId)
      markReadForContact(counterpartId)
    } else if (!isActiveChat) {
      bumpUnreadForContact(counterpartId)
    }

    scheduleContactsRefresh(contactsPagination.value.current_page)
    window.dispatchEvent(new Event('messages:updated'))
  }

  const handleUpdatedMessage = (message) => {
    upsertMessage(message)
  }

  const handleDeletedMessage = (messageId) => {
    removeMessage(messageId)
  }

  const startLiveRefresh = () => {
    if (liveRefreshTimer) return

    liveRefreshTimer = setInterval(() => {
      const activeUserId = selectedUser.value?.id
      if (!activeUserId) return
      if (typeof document !== 'undefined' && document.hidden) return
      if (loadingMessages.value || loadingOlder.value || sending.value || savingEdit.value || deletingMessageId.value) return
      if (Date.now() - lastSyncAt < LIVE_REFRESH_INTERVAL_MS - 50) return

      lastSyncAt = Date.now()
      syncLatestMessages(activeUserId)
    }, LIVE_REFRESH_INTERVAL_MS)
  }

  const stopLiveRefresh = () => {
    if (!liveRefreshTimer) return
    clearInterval(liveRefreshTimer)
    liveRefreshTimer = null
  }

  const clearFallbackStartTimer = () => {
    if (!fallbackStartTimer) return
    clearTimeout(fallbackStartTimer)
    fallbackStartTimer = null
  }

  const scheduleFallbackStart = () => {
    clearFallbackStartTimer()
    fallbackStartTimer = setTimeout(() => {
      if (socketStatus.value !== 'connected') {
        startLiveRefresh()
      }
      fallbackStartTimer = null
    }, SOCKET_FALLBACK_DELAY_MS)
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

    const echo = createEcho()
    if (!echo || !me.value?.id) {
      socketStatus.value = 'error'
      startLiveRefresh()
      return
    }

    socket.value = echo
    echoChannel.value = echo.private(`user.${me.value.id}`)
    scheduleFallbackStart()

    const pusherConnection = echo.connector?.pusher?.connection
    if (pusherConnection && typeof pusherConnection.bind === 'function') {
      pusherConnection.bind('connected', () => {
        socketStatus.value = 'connected'
        stopLiveRefresh()
      })

      pusherConnection.bind('disconnected', () => {
        socketStatus.value = 'disconnected'
        startLiveRefresh()
      })

      pusherConnection.bind('unavailable', () => {
        socketStatus.value = 'error'
        startLiveRefresh()
      })
    }

    if (typeof echoChannel.value?.subscribed === 'function') {
      echoChannel.value.subscribed(() => {
        socketStatus.value = 'connected'
        clearFallbackStartTimer()
        stopLiveRefresh()
      })
    }

    if (typeof echoChannel.value?.error === 'function') {
      echoChannel.value.error(() => {
        socketStatus.value = 'error'
        startLiveRefresh()
      })
    }

    echoChannel.value.listen('.MessageCreated', (payload) => {
      const message = extractMessage(payload)
      if (message) handleIncomingMessage(message)
    })

    echoChannel.value.listen('.MessageUpdated', (payload) => {
      const message = extractMessage(payload)
      if (message) handleUpdatedMessage(message)
    })

    echoChannel.value.listen('.MessageDeleted', (payload) => {
      const deletedId = payload?.message_id || payload?.id || payload?.data?.id
      handleDeletedMessage(deletedId)
    })
  }

  const disconnectSocket = () => {
    clearFallbackStartTimer()
    stopLiveRefresh()
    if (echoChannel.value) {
      echoChannel.value.stopListening('.MessageCreated')
      echoChannel.value.stopListening('.MessageUpdated')
      echoChannel.value.stopListening('.MessageDeleted')
      echoChannel.value = null
    }
    if (socket.value) {
      disconnectEcho()
      socket.value = null
      socketStatus.value = 'idle'
    }
    if (contactRefreshTimer) {
      clearTimeout(contactRefreshTimer)
      contactRefreshTimer = null
    }
  }

  const initMessaging = async (routeContactId) => {
    try {
      await bootstrapMessaging()

      if (!routeContactId) {
        selectedUser.value = null
        resetMessageState()
        return
      }
      const contact = contacts.value.find((item) => String(item.id) === String(routeContactId))
      if (contact) {
        await selectContact(contact)
        return
      }

      const response = await api.get(`/users/${routeContactId}`, withSilentLoading())
      selectedUser.value = response.data?.data || null
      if (selectedUser.value?.id) {
        resetMessageState()
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
    syncLatestMessages,
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
    startLiveRefresh,
    stopLiveRefresh,
    connectSocket,
    disconnectSocket,
    initMessaging,
  }
})
