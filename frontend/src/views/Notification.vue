<template>
  <div>
    <Nav />

    <div class="max-w-3xl mx-auto mt-6 px-4">
      <h2 class="text-xl font-semibold mb-4 text-gray-800">Notifications</h2>

      <div v-if="notifications.length" class="space-y-3">
        <div
          v-for="item in notifications"
          :key="item.id"
          class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
              <img
                :src="userAvatar"
                alt="profile"
                class="w-11 h-11 rounded-full object-cover shrink-0"
              />

              <div class="min-w-0">
                <p class="text-sm text-gray-900">
                  <span class="font-bold">Welcome {{ fullName }}!</span>
                  {{ item?.data?.message || 'New notification.' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  {{ formatDate(item.created_at) }}
                </p>
              </div>
            </div>

            <span
              v-if="!item.read_at"
              class="w-2 h-2 bg-blue-500 rounded-full mt-2 shrink-0"
            />
          </div>
        </div>
      </div>

      <div v-else class="text-center text-gray-500 text-sm mt-6">
        No notifications
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import Nav from '@/components/ui/nav.vue'

const notifications = ref([])

const user = computed(() => JSON.parse(localStorage.getItem('user') || '{}'))
const fullName = computed(() =>
  `${user.value?.first_name || ''} ${user.value?.last_name || ''}`.trim() || 'User'
)
const userAvatar = computed(
  () => user.value?.profile_photo || user.value?.avatar || 'https://i.pravatar.cc/100'
)

const fetchNotification = async () => {
  try {
    const response = await api.get('/notifications')
    notifications.value = response.data
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  }
}

const formatDate = (date) => new Date(date).toLocaleString()

onMounted(fetchNotification)
</script>
