<template>
  <nav
    class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur"
  >
    <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3">
      <RouterLink to="/" class="flex items-center gap-3">
        <span
          class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-600 to-blue-700 text-white shadow-sm"
        >
          <i class="fa-solid fa-graduation-cap text-sm"></i>
        </span>
        <div>
          <p class="text-sm font-bold uppercase tracking-wide text-slate-900">
            Alumni Media
          </p>
          <p class="text-[11px] font-medium text-slate-500">
            Professional Network
          </p>
        </div>
      </RouterLink>

      <div class="flex items-center gap-2 md:gap-3">
        <RouterLink to="/" :class="navClass('/')">
          <i class="fa-solid fa-house"></i>
          <span>Home</span>
        </RouterLink>

        <RouterLink to="/connection" :class="navClass('/connection')">
          <i class="fa-solid fa-user-group"></i>
          <span>Connection</span>
        </RouterLink>

        <RouterLink
          to="/message"
          :class="navClass('/message')"
          class="relative"
        >
          <i class="fa-solid fa-message"></i>
          <span>Message</span>
          <span
            v-if="unreadCount > 0"
            class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-rose-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
          >
            {{ unreadCount > 99 ? "99+" : unreadCount }}
          </span>
        </RouterLink>

        <RouterLink
          to="/notification"
          :class="navClass('/notification')"
          class="relative"
        >
          <i class="fa-solid fa-bell"></i>
          <span>Notification</span>
          <span
            v-if="notificationUnread > 0"
            class="absolute -right-1 -top-1 min-w-[18px] rounded-full bg-amber-500 px-1 text-center text-[10px] font-semibold leading-[18px] text-white"
          >
            {{ notificationUnread > 99 ? "99+" : notificationUnread }}
          </span>
        </RouterLink>

        <RouterLink
          v-if="user"
          :to="{ name: 'Profile', params: { id: user.id } }"
          class="ml-1 overflow-hidden rounded-xl border border-cyan-200 bg-white p-0.5 shadow-sm"
        >
          <img
            :src="user.profile?.avatar || fallbackAvatar"
            alt="User Profile"
            class="h-9 w-9 rounded-lg object-cover"
          />
        </RouterLink>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import api from "@/services/api";
import { useNotificationStore } from "@/stores/notifications";
import { useRoute } from "vue-router";
import fallbackAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const route = useRoute();
const notificationStore = useNotificationStore();
const user = ref(null);
const unreadCount = ref(0);
const notificationUnread = computed(() => notificationStore.unreadCount);
let unreadTimer = null;
let notificationTimer = null;

const handleMessagesUpdated = () => {
  fetchUnreadCount();
};

const navClass = (prefix) => {
  const base =
    "inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition";
  const isActive = route.path === prefix || route.path.startsWith(`${prefix}/`);
  return isActive
    ? `${base} border-cyan-200 bg-cyan-50 text-cyan-700`
    : `${base} border-transparent bg-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900`;
};

const fetchMe = async () => {
  try {
    const response = await api.get("/me", {
      headers: { "X-Skip-Loading": "true" },
    });
    user.value = response.data;
    localStorage.setItem("user", JSON.stringify(response.data));
    notificationStore.connect(user.value?.id);
    await notificationStore.fetchNotifications(); // ✅ load from DB
  } catch {
    user.value = null;
  }
};

const fetchUnreadCount = async () => {
  try {
    const response = await api.get("/messages/unread-count", {
      headers: {
        "X-Skip-Loading": "true",
      },
    });
    unreadCount.value = response.data?.data?.count || 0;
  } catch {
    unreadCount.value = 0;
  }
};

watch(
  () => route.fullPath,
  async () => {
    await fetchUnreadCount();
    await notificationStore.refreshUnreadCount();
  }
);

onMounted(async () => {
  await fetchMe();
  await fetchUnreadCount();
  await notificationStore.refreshUnreadCount();
  unreadTimer = setInterval(fetchUnreadCount, 15000);
  notificationTimer = setInterval(
    () => notificationStore.refreshUnreadCount(),
    15000
  );
  window.addEventListener("messages:updated", handleMessagesUpdated);
});

onUnmounted(() => {
  if (unreadTimer) {
    clearInterval(unreadTimer);
  }
  if (notificationTimer) {
    clearInterval(notificationTimer);
  }
  notificationStore.disconnect();
  window.removeEventListener("messages:updated", handleMessagesUpdated);
});
</script>
