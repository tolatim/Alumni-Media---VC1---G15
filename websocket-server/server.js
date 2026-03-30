const WebSocket = require('ws')
const express = require('express')
const bodyParser = require('body-parser')
const cors = require('cors')

const app = express()
app.use(cors())
app.use(bodyParser.json())

const server = app.listen(3000, () => {
  console.log('Event server listening on port 3000')
})

const wss = new WebSocket.Server({ server, path: '/ws' }, () => {
  console.log('WebSocket server running on port 3000 at /ws')
})

const BROADCAST_EVENTS = new Set([
  'post_created',
  'post_updated',
  'post_deleted',
  'post_comment_updated',
  'post_like_updated',
])

const clients = new Map()
const adminClients = new Set()

const broadcastToAllClients = (payload) => {
  const message = JSON.stringify(payload)
  let recipients = 0

  wss.clients.forEach((client) => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(message)
      recipients += 1
    }
  })

  return recipients
}

const sendToAdmins = (payload) => {
  const deadSockets = []

  adminClients.forEach((ws) => {
    if (ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(payload))
      return
    }

    deadSockets.push(ws)
  })

  deadSockets.forEach((ws) => adminClients.delete(ws))
}

const getRecipientIds = (type, data = {}) => {
  const explicitTargets = Array.isArray(data.target_user_ids)
    ? data.target_user_ids
    : data.target_user_id != null
      ? [data.target_user_id]
      : []

  if (explicitTargets.length) {
    return explicitTargets
      .map((value) => Number(value))
      .filter((value) => Number.isFinite(value))
  }

  switch (type) {
    case 'connection_request':
      return [Number(data.addressee_id)].filter(Number.isFinite)
    case 'accept_request':
    case 'reject':
    case 'unfriend':
      return [Number(data.requester_id)].filter(Number.isFinite)
    case 'block':
      return [Number(data.blocked_id ?? data.blocker_id)].filter(Number.isFinite)
    default:
      return []
  }
}

const sendToUserIds = (userIds, payload) => {
  const deliveredTo = []
  const failedTo = []
  const serialized = JSON.stringify(payload)

  userIds.forEach((userId) => {
    const sockets = clients.get(userId)
    if (!sockets || sockets.size === 0) {
      failedTo.push(userId)
      console.log('User offline or socket closed:', userId)
      return
    }

    let sent = false
    const deadSockets = []

    sockets.forEach((socket) => {
      if (socket.readyState === WebSocket.OPEN) {
        socket.send(serialized)
        sent = true
        return
      }

      deadSockets.push(socket)
    })

    deadSockets.forEach((socket) => sockets.delete(socket))
    if (sockets.size === 0) {
      clients.delete(userId)
    }

    if (sent) {
      deliveredTo.push(userId)
      console.log(`Event sent to user ${userId}`)
      return
    }

    failedTo.push(userId)
    console.log('User offline or socket closed:', userId)
  })

  return { deliveredTo, failedTo }
}

app.post('/event', (req, res) => {
  const { type, data = {}, audience } = req.body || {}

  if (BROADCAST_EVENTS.has(type)) {
    const recipients = broadcastToAllClients({ type, data })
    console.log(`Broadcast event '${type}' to ${recipients} clients`)
    return res.json({ status: 'event broadcasted', recipients })
  }

  if (audience === 'admins') {
    sendToAdmins({ type, data, audience: 'admins' })
    return res.json({ status: 'event processed', delivered_to: 'admins' })
  }

  const recipientIds = getRecipientIds(type, data)
  const { deliveredTo, failedTo } = sendToUserIds(recipientIds, { type, data })

  return res.json({
    status: 'event processed',
    delivered_to: deliveredTo,
    failed_to: failedTo,
  })
})

wss.on('connection', (ws) => {
  console.log('New client connected')

  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message.toString())
      console.log('Received:', data)

      if (data.type !== 'auth') return

      ws.user_id = Number(data.user_id)
      const existing = clients.get(ws.user_id) || new Set()
      existing.add(ws)
      clients.set(ws.user_id, existing)

      const role = String(data.role || '').toLowerCase()
      const channel = String(data.channel || '').toLowerCase()
      if (role === 'admin' || channel === 'admin') {
        ws.isAdmin = true
        adminClients.add(ws)
      }

      console.log('User authenticated:', ws.user_id)
    } catch (error) {
      console.log('Invalid message', error)
    }
  })

  ws.on('close', () => {
    if (ws.user_id != null) {
      const existing = clients.get(ws.user_id)
      if (existing) {
        existing.delete(ws)
        if (existing.size === 0) {
          clients.delete(ws.user_id)
        }
      }
      console.log(`User ${ws.user_id} disconnected`)
    }

    if (ws.isAdmin) {
      adminClients.delete(ws)
    }
  })
})
