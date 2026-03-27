<template>
  <Navbar />
  <main class="min-h-screen bg-transparent py-6 md:py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-5">
      <section class="rounded-[26px] border border-slate-200 bg-white px-6 py-6 shadow-sm sm:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-cyan-600">Stay Updated</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Your Notifications</h1>
            <p class="mt-1 text-sm text-slate-500">
              {{ unreadCount }} unread of {{ totalCount }} total
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
              :class="filter === 'all' ? 'border-cyan-300 bg-cyan-100 text-cyan-800' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
              @click="filter = 'all'"
            >All</button>
            <button
              class="rounded-full border px-4 py-1.5 text-xs font-semibold transition"
              :class="filter === 'unread' ? 'border-cyan-300 bg-cyan-100 text-cyan-800' : 'border-slate-200 bg-white text-slate-500 hover:border-slate-300'"
              @click="filter = 'unread'"
            >Unread</button>
            <button
              class="rounded-full border border-slate-200 px-4 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
              @click="fetchNotifications"
              :disabled="loading"
            >{{ loading ? 'Refreshing...' : 'Refresh' }}</button>
          </div>
        </div>
      </section>

      <p v-if="errorMessage" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700">
        {{ errorMessage }}
      </p>

      <div v-if="!loading && filteredNotifications.length === 0 && !errorMessage" class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
        <p class="text-sm font-semibold text-slate-700">No notifications yet</p>
        <p class="mt-1 text-xs text-slate-500">Likes, comments, connections, and new posts will appear here.</p>
      </div>

      <div class="mt-6 space-y-3">
        <article
          v-for="item in filteredNotifications"
          :key="item.id"
          class="flex flex-col gap-3 rounded-2xl border px-5 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between"
          :class="item.read_at ? 'border-slate-200 bg-white' : 'border-cyan-200 bg-cyan-50/60'"
        >
          <div class="flex items-start gap-3">
            <span
              class="mt-1 grid h-10 w-10 place-items-center rounded-xl"
              :class="item.read_at ? 'bg-slate-100 text-slate-500' : 'bg-cyan-600 text-white'"
            >
              <i :class="iconFor(item)"></i>
            </span>
            <div>
              <p class="text-sm font-semibold text-slate-900">{{ item.message || fallbackMessage(item) }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ labelFor(item) }} • {{ formatTime(item.created_at) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span v-if="!item.read_at" class="rounded-full bg-cyan-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-white">New</span>
            <button
              v-if="!item.read_at"
              class="rounded-xl border border-cyan-200 bg-white px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50"
              @click="markAsRead(item)"
            >Mark as read</button>
          </div>
        </article>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import Navbar from "@/components/ui/nav.vue"
import { useRouter } from "vue-router"
import { useNotificationStore } from "@/stores/notifications"

const filter = ref("all")
const router = useRouter()
const notificationStore = useNotificationStore()

const filteredNotifications = computed(() => {
  if (filter.value === "unread") {
    return notificationStore.items.filter((item) => !item.read_at)
  }
  return notificationStore.items
})

const totalCount = computed(() => notificationStore.totalCount)
const unreadCount = computed(() => notificationStore.unreadItems.length)
const loading = computed(() => notificationStore.loading)
const errorMessage = computed(() => notificationStore.error)

const fetchNotifications = async () => {
  await notificationStore.fetchNotifications()
}

const markAsRead = async (item) => {
  await notificationStore.markAsRead(item.id)
  const destination = destinationFor(item)
  if (destination) router.push(destination)
}

const destinationFor = (item) => {
  const type = item.type
  if ((type === "like" || type === "comment" || type === "post") && item.related_id) {
    return { path: "/", query: { post: item.related_id } }
  }
  if (type === "message" && item.related_id) {
    return { name: "MessageWithUser", params: { userId: item.related_id } }
  }
  if (type === "connect" || type === "accept" || type === "reject") {
    return "/connection"
  }
  return null
}

const iconFor = (item) => {
  switch (item.type) {
    case "like":    return "fa-solid fa-heart"
    case "comment": return "fa-solid fa-comment"
    case "connect": return "fa-solid fa-user-plus"
    case "accept":  return "fa-solid fa-user-check"
    case "reject":  return "fa-solid fa-user-xmark"
    case "post":    return "fa-solid fa-feather-pointed"
    case "message": return "fa-solid fa-message"
    case "login":   return "fa-solid fa-right-to-bracket"
    case "system":  return "fa-solid fa-bell"
    default:        return "fa-solid fa-bell"
  }
}

const labelFor = (item) => {
  switch (item.type) {
    case "like":    return "Like"
    case "comment": return "Comment"
    case "connect": return "Connection Request"
    case "accept":  return "Connection Accepted"
    case "reject":  return "Connection Rejected"
    case "post":    return "New Post"
    case "message": return "Message"
    case "login":   return "Login"
    case "system":  return "System"
    default:        return "Notification"
  }
}

const fallbackMessage = (item) => {
  return item.message || "You have a new notification"
}

const formatTime = (dateStr) => {
  if (!dateStr) return ""
  const date = new Date(dateStr)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000)
  if (diff < 60) return "Just now"
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

onMounted(async () => {
  await notificationStore.fetchNotifications()
})
</script>
