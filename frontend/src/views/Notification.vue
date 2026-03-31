<template>
  <Navbar />
  <main class="min-h-screen bg-gradient-to-b from-slate-100 via-slate-100 to-cyan-50/60 py-6 md:py-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-5">
      <section class="mb-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Notifications</h1>
            <p class="mt-1 text-sm text-slate-500">
              Stay updated with likes, comments, replies, and connection activity.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">
              {{ unreadCount }} unread
            </span>
            <button
              class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
              :disabled="!notifications.length || deletingAll"
              @click="markAllAsSeen"
            >
              <svg v-if="deletingAll" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-30" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7z" />
              </svg>
              {{ deletingAll ? 'Clearing...' : 'Clear all' }}
            </button>
          </div>
        </div>
      </section>

      <div v-if="loading" class="space-y-3">
        <div
          v-for="n in 5"
          :key="`skeleton-${n}`"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
        >
          <div class="animate-pulse">
            <div class="mb-3 flex items-center gap-3">
              <div class="h-10 w-10 rounded-xl bg-slate-200"></div>
              <div class="h-4 w-32 rounded bg-slate-200"></div>
            </div>
            <div class="h-3 w-11/12 rounded bg-slate-200"></div>
            <div class="mt-2 h-3 w-1/3 rounded bg-slate-200"></div>
          </div>
        </div>
      </div>

      <div v-else class="space-y-3">
        <article
          v-for="item in notifications"
          :key="item.id"
          class="group relative overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
          :class="item.seen ? 'border-slate-200' : 'border-cyan-200 ring-1 ring-cyan-100'"
          @click="openNotification(item)"
        >
          <div class="flex items-start gap-3">
            <div
              class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
              :class="notificationTheme(item.type).iconBg"
            >
              <svg
                v-if="notificationTheme(item.type).icon === 'heart'"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54z"/>
              </svg>
              <svg
                v-else-if="notificationTheme(item.type).icon === 'comment'"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
              </svg>
              <svg
                v-else-if="notificationTheme(item.type).icon === 'share'"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path d="m4 12 8-8 8 8" />
                <path d="M12 4v12" />
                <path d="M5 20h14" />
              </svg>
              <svg
                v-else
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
              >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>

            <div class="min-w-0 flex-1">
              <div class="mb-1 flex items-start justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {{ readableType(item.type) }}
                </p>
                <span
                  v-if="!item.seen"
                  class="rounded-full border border-cyan-200 bg-cyan-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-cyan-700"
                >
                  New
                </span>
              </div>
              <p class="text-sm font-medium leading-5 text-slate-800">
                {{ item.data?.message || readableType(item.type) }}
              </p>
              <p class="mt-2 text-xs text-slate-500">
                {{ relativeTime(item.created_at) }} • {{ formatDate(item.created_at) }}
              </p>
            </div>
          </div>
        </article>

        <div
          v-if="!notifications.length"
          class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center shadow-sm"
        >
          <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
              <path d="M9 17a3 3 0 0 0 6 0" />
            </svg>
          </div>
          <p class="text-sm font-semibold text-slate-700">No notifications yet</p>
          <p class="mt-1 text-xs text-slate-500">When people interact with your posts, updates will appear here.</p>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import { useNotificationStore } from '@/stores/notifications'

const router = useRouter()
const notificationStore = useNotificationStore()
const { notifications } = storeToRefs(notificationStore)
const loading = ref(false)
const deletingAll = ref(false)
const unreadCount = computed(() => notifications.value.filter((item) => !item?.seen).length)

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

const relativeTime = (value) => {
  if (!value) return 'Just now'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Just now'

  const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000)
  const abs = Math.abs(diffSeconds)
  const rtf = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' })

  if (abs < 60) return rtf.format(diffSeconds, 'second')
  if (abs < 3600) return rtf.format(Math.round(diffSeconds / 60), 'minute')
  if (abs < 86400) return rtf.format(Math.round(diffSeconds / 3600), 'hour')
  return rtf.format(Math.round(diffSeconds / 86400), 'day')
}

const notificationTheme = (type) => {
  const key = String(type || '').toLowerCase()

  if (key.includes('like')) {
    return { icon: 'heart', iconBg: 'bg-pink-50 text-pink-600' }
  }

  if (key.includes('share')) {
    return { icon: 'share', iconBg: 'bg-indigo-50 text-indigo-600' }
  }

  if (key.includes('comment') || key.includes('reply')) {
    return { icon: 'comment', iconBg: 'bg-cyan-50 text-cyan-600' }
  }

  return { icon: 'user', iconBg: 'bg-emerald-50 text-emerald-600' }
}

const markAsSeen = async (item) => {
  if (!item || item.seen) return
  await notificationStore.markSeen(item.id)
}

const markAllAsSeen = async () => {
  if (!notifications.value.length) return
  deletingAll.value = true
  try {
    await notificationStore.markAllSeen()
  } finally {
    deletingAll.value = false
  }
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
