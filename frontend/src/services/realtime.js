import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance = null

const getApiRoot = () => {
  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  return apiUrl.replace(/\/api\/?$/, '')
}

export const createEcho = () => {
  if (echoInstance) return echoInstance

  const token = localStorage.getItem('token')
  if (!token) return null

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'local',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    wssPort: Number(import.meta.env.VITE_PUSHER_PORT || 6001),
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${getApiRoot()}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })

  return echoInstance
}

export const disconnectEcho = () => {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}
