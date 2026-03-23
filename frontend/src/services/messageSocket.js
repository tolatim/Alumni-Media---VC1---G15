const resolveWsUrl = () => {
  const explicit = import.meta.env.VITE_WS_URL
  if (explicit) return explicit

  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  try {
    const url = new URL(apiUrl)
    url.protocol = url.protocol === 'https:' ? 'wss:' : 'ws:'
    url.pathname = url.pathname.replace(/\/api\/?$/, '') + '/ws'
    return url.toString()
  } catch {
    return null
  }
}

export const createMessageSocket = ({
  onOpen,
  onClose,
  onError,
  onMessage,
  getAuthToken,
  getUserId,
}) => {
  let socket = null
  let reconnectTimer = null
  let reconnectAttempts = 0
  let manuallyDisconnected = false

  const clearReconnect = () => {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
  }

  const scheduleReconnect = () => {
    clearReconnect()
    reconnectAttempts += 1
    const delay = Math.min(5000, 400 * Math.pow(2, reconnectAttempts - 1))
    reconnectTimer = setTimeout(connect, delay)
  }

  const sendAuth = () => {
    const token = getAuthToken?.()
    const userId = getUserId?.()
    if (!socket || socket.readyState !== WebSocket.OPEN) return
    if (!token) return
    socket.send(JSON.stringify({ type: 'auth', token, user_id: userId }))
  }

  const connect = () => {
    const wsUrl = resolveWsUrl()
    if (!wsUrl) return
    if (!getAuthToken?.() || !getUserId?.()) return
    if (socket && (socket.readyState === WebSocket.OPEN || socket.readyState === WebSocket.CONNECTING)) return

    manuallyDisconnected = false
    clearReconnect()
    socket = new WebSocket(wsUrl)

    socket.addEventListener('open', () => {
      reconnectAttempts = 0
      sendAuth()
      if (onOpen) onOpen()
    })

    socket.addEventListener('message', (event) => {
      if (!event?.data) return
      let parsed = null
      try {
        parsed = JSON.parse(event.data)
      } catch {
        parsed = null
      }
      if (onMessage) onMessage(parsed || event.data)
    })

    socket.addEventListener('close', () => {
      socket = null
      if (onClose) onClose()
      if (!manuallyDisconnected) {
        scheduleReconnect()
      }
    })

    socket.addEventListener('error', () => {
      if (onError) onError()
    })
  }

  const disconnect = () => {
    manuallyDisconnected = true
    clearReconnect()
    if (socket) {
      socket.close()
    }
  }

  return {
    connect,
    disconnect,
  }
}
