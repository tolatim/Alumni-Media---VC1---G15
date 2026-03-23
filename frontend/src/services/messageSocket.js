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

  const clearReconnect = () => {
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
  }

  const scheduleReconnect = () => {
    clearReconnect()
    reconnectAttempts += 1
    const delay = Math.min(15000, 1000 * Math.pow(2, reconnectAttempts))
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
      if (onClose) onClose()
      scheduleReconnect()
    })

    socket.addEventListener('error', () => {
      if (onError) onError()
    })
  }

  const disconnect = () => {
    clearReconnect()
    if (socket) {
      socket.close()
      socket = null
    }
  }

  return {
    connect,
    disconnect,
  }
}
