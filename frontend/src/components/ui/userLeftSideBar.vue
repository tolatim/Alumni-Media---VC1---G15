<template>
  <div
    class="col-span-12 space-y-4 lg:col-span-3 lg:sticky lg:top-24 lg:h-[calc(100vh-6.5rem)] lg:overflow-y-auto lg:pr-2"
  >
    <!-- ─── Profile Card ─────────────────────────────────────────── -->
    <section class="profile-card">

      <!-- Cover -->
      <div class="cover-area">
        <img
          v-if="user?.profile?.cover"
          :src="user.profile.cover"
          class="cover-img"
        />
        <div v-else class="cover-gradient" />
        <div class="cover-shimmer" />
        <div class="cover-orb cover-orb--a" />
        <div class="cover-orb cover-orb--b" />
        <span class="alumni-badge">Alumni</span>
      </div>

      <!-- Avatar -->
      <div class="avatar-wrap">
        <div class="avatar-ring">
          <img
            v-if="user?.profile?.avatar"
            :src="user.profile.avatar || fallbackAvatar"
            class="avatar-img"
            alt="Profile avatar"
          />
          <div v-else class="avatar-initials">
            {{ initials }}
          </div>
        </div>
        <div class="online-dot" />
      </div>

      <!-- Info -->
      <div class="card-body">
        <h3 class="card-name">{{ displayName }}</h3>
        <p class="card-headline">
          {{
            user?.profile?.headline ||
            user?.profile?.current_job ||
            "Welcome to Alumni Media"
          }}
        </p>

        <!-- Stats row -->
        <div class="stats-row">
          <div class="stat">
            <span class="stat-label">Connections</span>
            <span class="stat-num">{{ user?.stats?.connections ?? 0 }}</span>
          </div>
          <div class="stat">
            <span class="stat-label">Posts</span>
            <span class="stat-num">{{ user?.stats?.posts ?? 0 }}</span>
          </div>
          <div class="stat">
            <span class="stat-label">Graduate Year</span>
            <span class="stat-num">{{ user?.profile?.graduate_year ?? user?.profile?.class_year ?? "—" }}</span>
          </div>
        </div>

        <!-- CTA -->
        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="view-btn"
        >
          View Profile
          <svg class="btn-arrow" viewBox="0 0 16 16" fill="none">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </RouterLink>
      </div>
    </section>

    <!-- ─── Navigation Card ──────────────────────────────────────── -->
    <section class="nav-card">
      <p class="nav-heading">Navigation</p>

      <nav class="nav-list">
        <RouterLink to="/" class="nav-item" active-class="nav-item--active">
          <span class="nav-icon">
            <svg viewBox="0 0 20 20" fill="none">
              <path d="M3 9.5L10 3l7 6.5V17a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
              <path d="M7 18v-6h6v6" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="nav-label">Home</span>
          <i class="fa-solid fa-chevron-right nav-chevron"></i>
        </RouterLink>

        <RouterLink to="/connection" class="nav-item" active-class="nav-item--active">
          <span class="nav-icon">
            <svg viewBox="0 0 20 20" fill="none">
              <circle cx="7" cy="7" r="3" stroke="currentColor" stroke-width="1.4"/>
              <circle cx="14" cy="7" r="2.5" stroke="currentColor" stroke-width="1.4"/>
              <path d="M1 17c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              <path d="M14 12c1.657 0 3 1.119 3 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="nav-label">Connections</span>
          <i class="fa-solid fa-chevron-right nav-chevron"></i>
        </RouterLink>

        <RouterLink to="/message" class="nav-item" active-class="nav-item--active">
          <span class="nav-icon">
            <svg viewBox="0 0 20 20" fill="none">
              <path d="M3 4h14a1 1 0 011 1v8a1 1 0 01-1 1H6l-4 3V5a1 1 0 011-1z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="nav-label">Messages</span>
          <i class="fa-solid fa-chevron-right nav-chevron"></i>
        </RouterLink>

        <RouterLink to="/favorites" class="nav-item" active-class="nav-item--active">
          <span class="nav-icon">
            <svg viewBox="0 0 20 20" fill="none">
              <path d="M5 3h10a1 1 0 011 1v13l-6-3.5L4 17V4a1 1 0 011-1z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
          </span>
          <span class="nav-label">Favorites</span>
          <i class="fa-solid fa-chevron-right nav-chevron"></i>
        </RouterLink>
      </nav>

      <button
        type="button"
        @click="logout"
        class="logout-btn"
      >
        <i class="fa-solid fa-right-from-bracket"></i>
        Sign out
      </button>
    </section>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import fallbackAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
});

const router = useRouter();

const displayName = computed(() => {
  const firstName = props.user?.first_name || "";
  const lastName = props.user?.last_name || "";
  const joined = `${firstName} ${lastName}`.trim();
  return joined || props.user?.name || "Guest User";
});

const initials = computed(() => {
  const first = props.user?.first_name?.[0] ?? "";
  const last = props.user?.last_name?.[0] ?? "";
  return (first + last).toUpperCase() || "?";
});

const logout = () => {
  localStorage.removeItem("token");
  localStorage.removeItem("user");
  localStorage.removeItem("user_id");
  router.replace("/login");
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Lora:wght@600&family=DM+Sans:wght@400;500&display=swap');

/* ── Profile Card ───────────────────────────────────────── */
.profile-card {
  overflow: hidden;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  transition: box-shadow 0.25s ease;
}
.profile-card:hover {
  box-shadow: 0 10px 40px rgba(15, 76, 117, 0.10);
}

/* Cover */
.cover-area {
  position: relative;
  height: 108px;
  overflow: hidden;
}
.cover-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.cover-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #0c3d60 0%, #1565a8 50%, #0b8faa 100%);
}
.cover-shimmer {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 25% 60%, rgba(255,255,255,0.15) 0%, transparent 55%),
              radial-gradient(ellipse at 80% 15%, rgba(255,255,255,0.08) 0%, transparent 40%);
}
.cover-orb {
  position: absolute;
  border-radius: 50%;
  border: 16px solid rgba(255,255,255,0.06);
}
.cover-orb--a {
  width: 100px; height: 100px;
  bottom: -35px; right: -20px;
}
.cover-orb--b {
  width: 60px; height: 60px;
  bottom: -15px; right: 55px;
  border-width: 10px;
  border-color: rgba(255,255,255,0.04);
}
.alumni-badge {
  position: absolute;
  top: 12px; right: 12px;
  background: rgba(255,255,255,0.16);
  border: 0.5px solid rgba(255,255,255,0.35);
  border-radius: 20px;
  padding: 2px 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 10.5px;
  font-weight: 500;
  color: rgba(255,255,255,0.9);
  letter-spacing: 0.05em;
}

/* Avatar */
.avatar-wrap {
  display: flex;
  justify-content: center;
  margin-top: -34px;
  position: relative;
  z-index: 2;
}
.avatar-ring {
  padding: 3px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 12px rgba(0,0,0,0.10);
}
.avatar-img,
.avatar-initials {
  width: 68px;
  height: 68px;
  border-radius: 13px;
  display: block;
}
.avatar-img { object-fit: cover; }
.avatar-initials {
  background: linear-gradient(135deg, #1565a8, #0b8faa);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Lora', serif;
  font-size: 24px;
  font-weight: 600;
  color: #fff;
}
.online-dot {
  position: absolute;
  bottom: 2px;
  left: calc(50% + 26px);
  width: 11px;
  height: 11px;
  background: #22c55e;
  border: 2px solid #fff;
  border-radius: 50%;
}

/* Card body */
.card-body {
  padding: 10px 20px 20px;
  text-align: center;
  font-family: 'DM Sans', sans-serif;
}
.card-name {
  font-family: 'Lora', serif;
  font-size: 17px;
  font-weight: 600;
  color: #0f172a;
  margin: 8px 0 4px;
  letter-spacing: -0.01em;
  line-height: 1.2;
}
.card-headline {
  font-size: 12px;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 14px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 6px;
  padding: 0;
  margin-bottom: 12px;
}
.stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  padding: 7px 6px;
}
.stat-num {
  font-size: 16px;
  font-weight: 650;
  color: #0f172a;
  line-height: 1;
  letter-spacing: -0.02em;
}
.stat-label {
  font-size: 9px;
  color: #64748b;
  letter-spacing: 0.01em;
  font-weight: 600;
  white-space: nowrap;
}

/* CTA */
.view-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: 100%;
  padding: 9px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0c3d60 0%, #1565a8 55%, #0b8faa 100%);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  letter-spacing: 0.01em;
  transition: opacity 0.15s, transform 0.12s;
}
.view-btn:hover {
  opacity: 0.88;
  transform: translateY(-1px);
}
.view-btn:active {
  opacity: 1;
  transform: translateY(0);
}
.btn-arrow {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  transition: transform 0.15s;
}
.view-btn:hover .btn-arrow {
  transform: translateX(3px);
}

/* ── Navigation Card ────────────────────────────────────── */
.nav-card {
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  padding: 16px;
  font-family: 'DM Sans', sans-serif;
}
.nav-heading {
  font-size: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #94a3b8;
  margin: 0 0 10px 4px;
}
.nav-list {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 10px;
  text-decoration: none;
  color: #334155;
  font-size: 13.5px;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
}
.nav-item:hover {
  background: #f8fafc;
  color: #0f172a;
}
.nav-item--active {
  background: #eff6ff;
  color: #1d6fbd;
}
.nav-item--active .nav-icon svg {
  stroke: #1d6fbd;
}
.nav-item--active .nav-chevron {
  color: #93c5fd;
}
.nav-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s;
}
.nav-item:hover .nav-icon,
.nav-item--active .nav-icon {
  background: #dbeafe;
}
.nav-icon svg {
  width: 16px;
  height: 16px;
  stroke: #64748b;
  transition: stroke 0.15s;
}
.nav-item:hover .nav-icon svg {
  stroke: #1d6fbd;
}
.nav-label {
  flex: 1;
}
.nav-chevron {
  font-size: 9px;
  color: #cbd5e1;
  transition: color 0.15s;
}
.nav-item:hover .nav-chevron {
  color: #93c5fd;
}

/* Logout */
.logout-btn {
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 9px 14px;
  border-radius: 10px;
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.logout-btn:hover {
  background: #ffe4e6;
  border-color: #fda4af;
}

@media (max-width: 768px) {
  .card-body {
    padding: 8px 14px 14px;
  }

  .nav-card {
    padding: 14px;
  }
}

@media (max-width: 520px) {
  .cover-area {
    height: 96px;
  }

  .avatar-img,
  .avatar-initials {
    width: 62px;
    height: 62px;
    border-radius: 12px;
  }

  .online-dot {
    left: calc(50% + 23px);
  }

  .card-name {
    font-size: 16px;
  }

  .stats-row {
    gap: 5px;
  }

  .stat {
    padding: 6px 5px;
  }

  .stat-num {
    font-size: 15px;
  }

  .stat-label {
    white-space: normal;
    text-align: center;
    line-height: 1.1;
  }
}
</style>
