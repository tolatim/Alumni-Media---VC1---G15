<template>
  <Navbar />
  <main class="min-h-screen bg-slate-100 py-6 md:py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-5">
      <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-800">Notifications</h1>
        <button
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
          :disabled="!unreadCount"
          @click="markAllAsSeen"
        >
          Mark all as read
        </button>
      </div>

      <div v-if="loading" class="space-y-2">
        <div v-for="n in 5" :key="`skeleton-${n}`" class="h-16 animate-pulse rounded-xl bg-slate-200"></div>
      </div>

      <div v-else class="space-y-2">
        <article
          v-for="item in notifications"
          :key="item.id"
          class="rounded-xl border p-4 transition"
          :class="item.seen ? 'border-slate-200 bg-white' : 'border-cyan-200 bg-cyan-50/70'"
          @click="openNotification(item)"
        >
          <p class="text-sm font-medium text-slate-800">
            {{ item.data?.message || readableType(item.type) }}
          </p>
          <p class="mt-1 text-xs text-slate-500">
            {{ formatDate(item.created_at) }}
          </p>
        </article>

        <p v-if="!notifications.length" class="rounded-xl border border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-500">
          No notifications yet.
        </p>
      </div>
    </div>
  </main>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import { useNotificationStore } from '@/stores/notifications'

const router = useRouter()
const notificationStore = useNotificationStore()
const { notifications, unreadCount } = storeToRefs(notificationStore)
const loading = ref(false)

const readableType = (type) => {
  const normalized = String(type || '').replaceAll('_', ' ').trim()
  if (!normalized) return 'New notification'
  return normalized.charAt(0).toUpperCase() + normalized.slice(1)
}

const formatDate = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toLocaleString()
}

const markAsSeen = async (item) => {
  if (!item || item.seen) return
  await notificationStore.markSeen(item.id)
}

const markAllAsSeen = async () => {
  if (!unreadCount.value) return
  await notificationStore.markAllSeen()
}

const openNotification = async (item) => {
  if (!item) return

  await markAsSeen(item)

  const postId = Number(item?.data?.post_id || 0)
  const postOwnerId = Number(item?.data?.post_owner_id || 0)

  if (postId > 0 && postOwnerId > 0) {
    await router.push({
      name: 'Profile',
      params: { id: String(postOwnerId) },
      query: { postId: String(postId) },
    })
    return
  }

  if (postId > 0) {
    await router.push({
      path: '/',
      query: { postId: String(postId) },
    })
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await Promise.all([
      notificationStore.loadNotifications(),
      notificationStore.loadUnreadCount(),
    ])
  } finally {
    loading.value = false
  }
})
</script>
