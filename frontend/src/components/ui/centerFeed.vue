<template>
  <div class="col-span-6 space-y-5">

    <!-- ─── Compose / Search Bar ─────────────────────────────────── -->
    <div class="feed-bar">

      <!-- Top row: avatar + search -->
      <div class="bar-top">
        <div class="bar-avatar-wrap">
          <img
            :src="currentUser?.profile?.avatar || fallbackAvatar"
            class="bar-avatar"
            alt="Your avatar"
          />
          <span class="bar-dot" />
        </div>

        <div class="search-wrap">
          <svg class="search-icon" viewBox="0 0 20 20" fill="none">
            <circle cx="9" cy="9" r="5.5" stroke="currentColor" stroke-width="1.5"/>
            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search posts…"
            class="search-input"
          />
          <button
            v-if="searchQuery"
            class="search-clear"
            @click="searchQuery = ''"
            aria-label="Clear search"
          >
            <svg viewBox="0 0 16 16" fill="none" width="12" height="12">
              <path d="M3 3l10 10M13 3L3 13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Divider -->
      <div class="bar-divider" />

      <!-- Bottom row: filters + create -->
      <div class="bar-bottom">
        <div class="filter-chips">
          <button
            v-for="f in filters"
            :key="f.value"
            class="chip"
            :class="{ 'chip--active': activeFilter === f.value }"
            @click="activeFilter = f.value"
          >
            <component :is="f.icon" class="chip-icon" />
            {{ f.label }}
          </button>
        </div>

        <RouterLink to="/post" class="create-btn">
          <svg viewBox="0 0 16 16" fill="none" width="14" height="14">
            <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          New Post
        </RouterLink>
      </div>
    </div>

    <!-- ─── Post Count Banner (when filtering) ────────────────────── -->
    <Transition name="fade">
      <div v-if="searchQuery" class="results-banner">
        <svg viewBox="0 0 16 16" fill="none" width="13" height="13">
          <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M7 6v4M7 5h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
        <span>
          {{ filteredPosts.length }} result{{ filteredPosts.length !== 1 ? 's' : '' }} for
          <strong>"{{ searchQuery }}"</strong>
        </span>
      </div>
    </Transition>

    <!-- ─── Posts ─────────────────────────────────────────────────── -->
    <TransitionGroup name="post-list" tag="div" class="posts-stack">
      <PostCard
        v-for="post in filteredPosts"
        :key="post.id"
        :post="post"
        :current-user="currentUser"
        @deleted="handlePostDeleted"
        @refresh-posts="emit('refreshPosts')"
      />
    </TransitionGroup>

    <!-- ─── Empty State ───────────────────────────────────────────── -->
    <Transition name="fade">
      <div v-if="!filteredPosts.length" class="empty-state">
        <div class="empty-illustration">
          <svg viewBox="0 0 80 80" fill="none" width="64" height="64">
            <rect x="12" y="18" width="56" height="44" rx="8" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M12 30h56" stroke="#cbd5e1" stroke-width="1.5"/>
            <rect x="22" y="40" width="20" height="2.5" rx="1.25" fill="#e2e8f0"/>
            <rect x="22" y="46" width="32" height="2.5" rx="1.25" fill="#e2e8f0"/>
            <rect x="22" y="52" width="14" height="2.5" rx="1.25" fill="#e2e8f0"/>
            <circle cx="22" cy="24" r="3" fill="#e2e8f0"/>
          </svg>
        </div>
        <p class="empty-title">
          {{ posts.length ? 'No matching posts' : 'Nothing here yet' }}
        </p>
        <p class="empty-sub">
          {{ posts.length ? 'Try a different keyword or clear the search.' : 'Be the first to start a conversation.' }}
        </p>
        <button
          v-if="searchQuery"
          class="empty-clear"
          @click="searchQuery = ''"
        >
          Clear search
        </button>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { computed, ref, defineComponent, h, watch } from 'vue'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import PostCard from '@/components/ui/PostCard.vue'
import api from '@/services/api'

const props = defineProps({
  posts: { type: Array, default: () => [] },
  currentUser: { type: Object, default: null },
})

const emit = defineEmits(['refreshPosts'])

const searchQuery = ref('')
const activeFilter = ref('all')
const deletedPostIds = ref([])
const minePosts = ref([])
const minePostsLoading = ref(false)
const minePostsLoadedForUserId = ref(null)

// ── Inline SVG icon components ────────────────────────────────────
const IconAll = defineComponent({ render: () => h('svg', { viewBox: '0 0 16 16', fill: 'none', width: 13, height: 13 }, [
  h('rect', { x: 2, y: 2, width: 5, height: 5, rx: 1, stroke: 'currentColor', 'stroke-width': 1.4 }),
  h('rect', { x: 9, y: 2, width: 5, height: 5, rx: 1, stroke: 'currentColor', 'stroke-width': 1.4 }),
  h('rect', { x: 2, y: 9, width: 5, height: 5, rx: 1, stroke: 'currentColor', 'stroke-width': 1.4 }),
  h('rect', { x: 9, y: 9, width: 5, height: 5, rx: 1, stroke: 'currentColor', 'stroke-width': 1.4 }),
]) })

const IconLatest = defineComponent({ render: () => h('svg', { viewBox: '0 0 16 16', fill: 'none', width: 13, height: 13 }, [
  h('circle', { cx: 8, cy: 8, r: 6, stroke: 'currentColor', 'stroke-width': 1.4 }),
  h('path', { d: 'M8 5v3.5l2 1.5', stroke: 'currentColor', 'stroke-width': 1.4, 'stroke-linecap': 'round' }),
]) })

const IconMine = defineComponent({ render: () => h('svg', { viewBox: '0 0 16 16', fill: 'none', width: 13, height: 13 }, [
  h('circle', { cx: 8, cy: 5, r: 2.5, stroke: 'currentColor', 'stroke-width': 1.4 }),
  h('path', { d: 'M3 14c0-2.761 2.239-4 5-4s5 1.239 5 4', stroke: 'currentColor', 'stroke-width': 1.4, 'stroke-linecap': 'round' }),
]) })

const filters = [
  { label: 'All', value: 'all', icon: IconAll },
  { label: 'Latest', value: 'latest', icon: IconLatest },
  { label: 'Mine', value: 'mine', icon: IconMine },
]

const toNumericId = (value) => {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

const getPostOwnerId = (post) => toNumericId(post?.user_id ?? post?.user?.id)

const getPostTimestamp = (post) => {
  const time = new Date(post?.created_at || 0).getTime()
  return Number.isFinite(time) ? time : 0
}

const normalizeMediaEntry = (entry) => {
  if (typeof entry === 'string') {
    return { media_url: entry, file_path: entry }
  }

  if (!entry || typeof entry !== 'object') return null

  const fallbackPath =
    entry.media_url ||
    entry.file_path ||
    entry.path ||
    entry.url ||
    entry.src ||
    entry.original_url ||
    entry.preview_url ||
    entry?.pivot?.file_path ||
    ''

  return {
    ...entry,
    media_url: entry.media_url || fallbackPath,
    file_path: entry.file_path || fallbackPath,
  }
}

const normalizeMediaCollection = (input) => {
  if (!input) return []

  if (Array.isArray(input)) {
    return input.map(normalizeMediaEntry).filter(Boolean)
  }

  if (typeof input === 'string') {
    const trimmed = input.trim()
    if (!trimmed) return []
    if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
      try {
        return normalizeMediaCollection(JSON.parse(trimmed))
      } catch {
        return [normalizeMediaEntry(trimmed)].filter(Boolean)
      }
    }
    return [normalizeMediaEntry(trimmed)].filter(Boolean)
  }

  if (typeof input === 'object') {
    if (Array.isArray(input.data)) return normalizeMediaCollection(input.data)
    if (Array.isArray(input.media)) return normalizeMediaCollection(input.media)
    if (Array.isArray(input.attachments)) return normalizeMediaCollection(input.attachments)
  }

  return []
}

const normalizePostMedia = (post) => {
  const source = post?.media ?? post?.media_items ?? post?.attachments ?? []
  return {
    ...post,
    media: normalizeMediaCollection(source),
  }
}

const mergePostsById = (first = [], second = []) => {
  const merged = [...first]
  const seen = new Set(first.map((post) => post?.id))

  second.forEach((post) => {
    const id = post?.id
    if (!seen.has(id)) {
      seen.add(id)
      merged.push(post)
    }
  })

  return merged
}

const extractPostsList = (payload) => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.posts)) return payload.posts
  return []
}

const loadMinePosts = async () => {
  const currentUserId = toNumericId(props.currentUser?.id)
  if (currentUserId === null) return
  if (minePostsLoading.value) return
  if (minePostsLoadedForUserId.value === currentUserId) return

  minePostsLoading.value = true
  try {
    const response = await api.get('/posts')
    const allPosts = extractPostsList(response.data)
    minePosts.value = allPosts
      .map(normalizePostMedia)
      .filter((post) => getPostOwnerId(post) === currentUserId)
  } catch {
    minePosts.value = []
  } finally {
    minePostsLoadedForUserId.value = currentUserId
    minePostsLoading.value = false
  }
}

watch(activeFilter, (next) => {
  if (next === 'mine') {
    loadMinePosts()
  }
})

watch(
  () => props.currentUser?.id,
  (nextId, prevId) => {
    if (toNumericId(nextId) === toNumericId(prevId)) return
    minePosts.value = []
    minePostsLoadedForUserId.value = null
    if (activeFilter.value === 'mine') {
      loadMinePosts()
    }
  }
)

// ── Computed ──────────────────────────────────────────────────────
const filteredPosts = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  let list = props.posts.map(normalizePostMedia)

  if (activeFilter.value === 'latest') {
    list = [...list].sort((a, b) => getPostTimestamp(b) - getPostTimestamp(a))
  } else if (activeFilter.value === 'mine') {
    const currentUserId = toNumericId(props.currentUser?.id)
    if (currentUserId === null) {
      list = []
    } else {
      const combined = mergePostsById(list, minePosts.value)
      list = combined.filter((p) => getPostOwnerId(p) === currentUserId)
    }
  }

  list = list.filter((p) => !deletedPostIds.value.includes(p.id))

  if (!query) return list
  return list.filter(p => {
    const title = (p?.title || '').toLowerCase()
    const content = (p?.content || '').toLowerCase()
    return title.includes(query) || content.includes(query)
  })
})

const handlePostDeleted = (postId) => {
  deletedPostIds.value = [...deletedPostIds.value, postId]
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&display=swap');

/* ── Feed Bar ──────────────────────────────────────────────────── */
.feed-bar {
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  padding: 14px 16px;
  font-family: 'DM Sans', sans-serif;
}

.bar-top {
  display: flex;
  align-items: center;
  gap: 10px;
}

.bar-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.bar-avatar {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
  display: block;
}
.bar-dot {
  position: absolute;
  bottom: 1px;
  right: 1px;
  width: 9px;
  height: 9px;
  background: #22c55e;
  border: 2px solid #fff;
  border-radius: 50%;
}

.search-wrap {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 12px;
  color: #94a3b8;
  width: 15px;
  height: 15px;
  pointer-events: none;
  flex-shrink: 0;
}
.search-input {
  width: 100%;
  padding: 9px 36px 9px 36px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  font-family: 'DM Sans', sans-serif;
  font-size: 13.5px;
  color: #334155;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
}
.search-input::placeholder { color: #94a3b8; }
.search-input:focus {
  border-color: #60a5fa;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
}
.search-clear {
  position: absolute;
  right: 10px;
  background: #e2e8f0;
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  padding: 0;
  transition: background 0.15s;
}
.search-clear:hover { background: #cbd5e1; }

.bar-divider {
  margin: 12px 0;
  border: none;
  border-top: 1px solid #f1f5f9;
}

.bar-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.filter-chips {
  display: flex;
  gap: 6px;
}
.chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  background: transparent;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.chip:hover {
  background: #f1f5f9;
  color: #334155;
}
.chip--active {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1d6fbd;
}
.chip-icon {
  flex-shrink: 0;
}

.create-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0c3d60 0%, #1565a8 55%, #0b8faa 100%);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 500;
  text-decoration: none;
  white-space: nowrap;
  transition: opacity 0.15s, transform 0.12s;
}
.create-btn:hover {
  opacity: 0.88;
  transform: translateY(-1px);
}

/* ── Results Banner ────────────────────────────────────────────── */
.results-banner {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 14px;
  border-radius: 10px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  color: #1d6fbd;
}
.results-banner strong { font-weight: 500; }

/* ── Posts stack ────────────────────────────────────────────────── */
.posts-stack {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* ── Empty State ───────────────────────────────────────────────── */
.empty-state {
  border-radius: 18px;
  border: 1.5px dashed #cbd5e1;
  background: #fafafa;
  padding: 48px 24px;
  text-align: center;
  font-family: 'DM Sans', sans-serif;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}
.empty-illustration {
  margin-bottom: 4px;
  opacity: 0.7;
}
.empty-title {
  font-size: 14px;
  font-weight: 500;
  color: #475569;
}
.empty-sub {
  font-size: 13px;
  color: #94a3b8;
  max-width: 260px;
  line-height: 1.5;
}
.empty-clear {
  margin-top: 6px;
  padding: 6px 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.empty-clear:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

/* ── Transitions ───────────────────────────────────────────────── */
.fade-enter-active,
.fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-enter-from,
.fade-leave-to { opacity: 0; transform: translateY(4px); }

.post-list-enter-active { transition: opacity 0.25s, transform 0.25s; }
.post-list-leave-active { transition: opacity 0.2s, transform 0.2s; }
.post-list-enter-from { opacity: 0; transform: translateY(8px); }
.post-list-leave-to { opacity: 0; transform: translateY(-4px); }
.post-list-move { transition: transform 0.3s ease; }
</style>
