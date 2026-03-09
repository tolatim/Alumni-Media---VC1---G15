<template>
  <div class="min-h-screen bg-gray-100">
    <Nav />

    <div class="max-w-3xl mx-auto mt-8 px-4">
      <h2 class="text-2xl font-bold mb-6 text-gray-800">Notifications</h2>

      <div v-if="notifications.length" class="space-y-4">
        <div
          v-for="item in notifications"
          :key="item.id"
          class="flex items-start gap-4 bg-white rounded-2xl p-4 border border-gray-200 shadow-sm hover:shadow-lg transition duration-200"
          :class="!item.read_at ? 'bg-blue-50 border-blue-200' : ''"
        >
          <!-- Avatar -->
          <img
            :src="userAvatar"
            alt="profile"
            class="w-12 h-12 rounded-full object-cover"
          />

          <!-- Text -->
          <div class="flex-1">
            <p class="text-sm text-gray-800 leading-relaxed">
              <span class="font-semibold text-gray-900">
                Welcome {{ fullName }}!
              </span>
              {{ item?.data?.message || "You have a new notification." }}
            </p>

            <p class="text-xs text-gray-500 mt-2">
              {{ formatDate(item.created_at) }}
            </p>
          </div>

          <!-- Unread dot -->
          <div
            v-if="!item.read_at"
            class="w-3 h-3 bg-blue-500 rounded-full mt-2"
          ></div>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else
        class="text-center text-gray-500 text-sm mt-10 bg-white p-6 rounded-xl shadow"
      >
        🔔 No notifications yet
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import api from "@/services/api";
import Nav from "@/components/ui/nav.vue";

const notifications = ref([]);

const user = computed(() => JSON.parse(localStorage.getItem("user") || "{}"));

const fullName = computed(() =>
  `${user.value?.first_name || ""} ${user.value?.last_name || ""}`.trim() ||
  "User"
);

const userAvatar = computed(
  () =>
    user.value?.profile_photo ||
    user.value?.avatar ||
    "https://i.pravatar.cc/100"
);

const fetchNotification = async () => {
  try {
    const response = await api.get("/notifications");
    notifications.value = response.data.data || response.data;
  } catch (error) {
    console.error("Failed to fetch notifications:", error);
  }
};

const formatDate = (date) => new Date(date).toLocaleString();

onMounted(() => {
  fetchNotification();
  // Poll every 10 seconds
  setInterval(fetchNotification, 10000);
});
</script>