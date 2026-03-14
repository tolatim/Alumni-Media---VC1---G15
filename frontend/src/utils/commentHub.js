const subscribers = new Set()
const lastEventByPostId = new Map()

const subscribe = (handler) => {
  subscribers.add(handler)
  return () => {
    subscribers.delete(handler)
  }
}

const notify = (payload) => {
  if (!payload?.postId) return
  for (const handler of Array.from(subscribers)) {
    try {
      handler(payload)
    } catch (error) {
      console.error('commentHub handler failed', error)
    }
  }
  lastEventByPostId.set(String(payload.postId), payload)
}

const getLastEventForPost = (postId) => lastEventByPostId.get(String(postId)) || null

export { subscribe, notify, getLastEventForPost }
