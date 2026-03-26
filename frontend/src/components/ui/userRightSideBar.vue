<template>
  <div class="col-span-12 space-y-4 lg:col-span-3">

    <!-- ─── Connection Requests ───────────────────────────────────── -->
    <section class="side-card">
      <div class="card-header">
        <h4 class="card-title">Requests</h4>
        <span class="count-badge">{{ pendingRequests.length }}</span>
      </div>

      <TransitionGroup name="list" tag="div" class="requests-list">
        <article
          v-for="request in pendingRequests"
          :key="request.id"
          class="request-item"
        >
          <RouterLink
            :to="{ name: 'Profile', params: { id: request.requester?.id } }"
            class="request-profile"
          >
            <div class="req-avatar-wrap">
              <img
                v-if="request.requester?.profile?.avatar"
                :src="request.requester.profile.avatar"
                class="req-avatar-img"
                alt=""
              />
              <div v-else class="req-avatar-fallback">
                {{ (request.requester?.name || '?')[0].toUpperCase() }}
              </div>
            </div>
            <div class="req-info">
              <p class="req-name">{{ request.requester?.name || 'Unknown user' }}</p>
              <p class="req-sub">Wants to connect</p>
            </div>
          </RouterLink>

          <div class="req-actions">
            <button
              class="btn-accept"
              @click="$emit('accept-request', request.id)"
            >
              <svg viewBox="0 0 14 14" fill="none" width="12" height="12">
                <path d="M2 7l4 4 6-7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Accept
            </button>
            <button
              class="btn-reject"
              @click="$emit('reject-request', request.id)"
            >
              <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
                <path d="M3 3l8 8M11 3L3 11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
        </article>
      </TransitionGroup>

      <div v-if="!pendingRequests.length" class="empty-inline">
        <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
          <circle cx="10" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
          <path d="M4 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          <path d="M14 6l2 2 3-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        All caught up
      </div>
    </section>

    <!-- ─── Trending ──────────────────────────────────────────────── -->
    <section class="side-card">
      <div class="card-header">
        <h4 class="card-title">Trending</h4>
        <svg viewBox="0 0 16 16" fill="none" width="14" height="14" style="color:#0b8faa">
          <path d="M2 12l4-4 3 3 5-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <div class="tags-wrap">
        <span
          v-for="tag in trendingTags"
          :key="tag.label"
          class="tag"
          :style="`--tag-bg:${tag.bg};--tag-border:${tag.border};--tag-color:${tag.color}`"
        >
          {{ tag.label }}
          <span class="tag-count">{{ tag.count }}</span>
        </span>
      </div>
    </section>

    <!-- ─── Suggested Connections ─────────────────────────────────── -->
    <section class="side-card">
      <div class="card-header">
        <h4 class="card-title">People You May Know</h4>
        <span class="count-badge">{{ suggestions.length }}</span>
      </div>

      <div class="suggestions-list">
        <div
          v-for="person in suggestions"
          :key="person.id"
          class="suggestion-item"
        >
          <RouterLink
            :to="{ name: 'Profile', params: { id: person.id } }"
            class="suggestion-profile"
          >
            <div class="sug-avatar-wrap">
              <img
                v-if="person.profile?.avatar"
                :src="person.profile.avatar"
                class="sug-avatar-img"
                alt=""
              />
              <div v-else class="sug-avatar-fallback">
                {{ (person.name || '?')[0].toUpperCase() }}
              </div>
            </div>
            <div class="sug-info">
              <p class="sug-name">{{ person.name }}</p>
              <p class="sug-sub">{{ person.profile?.headline || 'Alumni member' }}</p>
            </div>
          </RouterLink>

          <button
            class="btn-connect"
            @click="$emit('send-request', person.id)"
          >
            <svg viewBox="0 0 14 14" fill="none" width="11" height="11">
              <path d="M7 2v10M2 7h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            Connect
          </button>
        </div>
      </div>

      <div v-if="!suggestions.length" class="empty-inline">
        <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
          <circle cx="7" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
          <circle cx="14" cy="7" r="2.5" stroke="currentColor" stroke-width="1.4"/>
          <path d="M1 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          <path d="M14 12c1.657 0 3 1.119 3 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
        No suggestions yet
      </div>
    </section>

  </div>
</template>

<script setup>
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'

defineProps({
  suggestions: { type: Array, default: () => [] },
  pendingRequests: { type: Array, default: () => [] },
})

defineEmits(['send-request', 'accept-request', 'reject-request'])

const trendingTags = [
  { label: '#AlumniUpdates', count: '2.4k', bg: '#eff6ff', border: '#bfdbfe', color: '#1d6fbd' },
  { label: '#HiringNow',    count: '1.8k', bg: '#f0fdf4', border: '#bbf7d0', color: '#16a34a' },
  { label: '#CareerGrowth', count: '987',  bg: '#faf5ff', border: '#e9d5ff', color: '#7c3aed' },
  { label: '#Networking',   count: '754',  bg: '#fff7ed', border: '#fed7aa', color: '#c2410c' },
  { label: '#ClassOf2019',  count: '412',  bg: '#f0fdfa', border: '#99f6e4', color: '#0f766e' },
]
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Lora:wght@600&display=swap');

/* ── Shared card ─────────────────────────────────────────────── */
.side-card {
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  padding: 16px;
  font-family: 'DM Sans', sans-serif;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.card-title {
  font-family: 'Lora', serif;
  font-size: 14px;
  font-weight: 600;
  color: #0f172a;
}
.count-badge {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 1px 9px;
  font-size: 11px;
  font-weight: 500;
  color: #64748b;
}

/* ── Empty inline ────────────────────────────────────────────── */
.empty-inline {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  font-size: 12.5px;
  color: #94a3b8;
}

/* ── Connection Requests ─────────────────────────────────────── */
.requests-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.request-item {
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  background: #fafafa;
  padding: 10px 12px;
  transition: border-color 0.15s;
}
.request-item:hover {
  border-color: #e2e8f0;
}

.request-profile {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  margin-bottom: 10px;
}

.req-avatar-wrap { position: relative; flex-shrink: 0; }
.req-avatar-img,
.req-avatar-fallback {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: block;
}
.req-avatar-img { object-fit: cover; }
.req-avatar-fallback {
  background: linear-gradient(135deg, #1565a8, #0b8faa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Lora', serif;
  font-size: 14px;
  color: #fff;
  font-weight: 600;
}

.req-info { min-width: 0; }
.req-name {
  font-size: 13px;
  font-weight: 500;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.req-sub {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 1px;
}

.req-actions {
  display: flex;
  gap: 6px;
}

.btn-accept {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 6px 10px;
  border-radius: 8px;
  border: none;
  background: linear-gradient(135deg, #0c3d60, #1565a8 60%, #0b8faa);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.12s;
}
.btn-accept:hover {
  opacity: 0.88;
  transform: translateY(-1px);
}

.btn-reject {
  width: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s;
}
.btn-reject:hover {
  background: #ffe4e6;
}

/* ── Trending Tags ────────────────────────────────────────────── */
.tags-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}
.tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 11px;
  border-radius: 20px;
  border: 1px solid var(--tag-border);
  background: var(--tag-bg);
  color: var(--tag-color);
  font-size: 11.5px;
  font-weight: 500;
  cursor: default;
  transition: filter 0.15s;
}
.tag:hover { filter: brightness(0.96); }
.tag-count {
  font-size: 10px;
  font-weight: 400;
  opacity: 0.7;
}

/* ── Suggested Connections ───────────────────────────────────── */
.suggestions-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 10px;
  border-radius: 12px;
  border: 1px solid transparent;
  transition: background 0.15s, border-color 0.15s;
}
.suggestion-item:hover {
  background: #f8fafc;
  border-color: #f1f5f9;
}

.suggestion-profile {
  display: flex;
  align-items: center;
  gap: 9px;
  text-decoration: none;
  flex: 1;
  min-width: 0;
}

.sug-avatar-wrap { flex-shrink: 0; }
.sug-avatar-img,
.sug-avatar-fallback {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: block;
}
.sug-avatar-img { object-fit: cover; }
.sug-avatar-fallback {
  background: linear-gradient(135deg, #7c3aed, #0b8faa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Lora', serif;
  font-size: 13px;
  color: #fff;
  font-weight: 600;
}

.sug-info { min-width: 0; }
.sug-name {
  font-size: 13px;
  font-weight: 500;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.3;
}
.sug-sub {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-connect {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #1d6fbd;
  font-family: 'DM Sans', sans-serif;
  font-size: 11.5px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
  transition: background 0.15s, border-color 0.15s;
}
.btn-connect:hover {
  background: #dbeafe;
  border-color: #93c5fd;
}

/* ── Transitions ─────────────────────────────────────────────── */
.list-enter-active,
.list-leave-active { transition: opacity 0.2s, transform 0.2s; }
.list-enter-from { opacity: 0; transform: translateY(6px); }
.list-leave-to { opacity: 0; transform: translateY(-4px); height: 0; margin: 0; padding: 0; overflow: hidden; }
.list-move { transition: transform 0.25s ease; }
</style>