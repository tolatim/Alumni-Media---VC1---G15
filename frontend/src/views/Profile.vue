<template>
  <Navbar />

  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
      <div v-if="errorMessage" class="bg-red-50 text-red-600 rounded-lg p-4 mb-4">
        {{ errorMessage }}
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow overflow-hidden">
        <div class="h-60 w-full relative">
          <img :src="coverImage" class="w-full h-full object-cover">
        </div>

        <div class="px-6 pb-6 relative">
          <div class="absolute -top-16 left-6">
            <img :src="user.profile?.avatar || 'https://i.pravatar.cc/150'" class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md">
          </div>

          <div class="pt-20">
            <h1 class="text-2xl font-bold text-gray-800">{{ user.name }}</h1>

            <p class="text-gray-500 mt-1">
              {{ user.profile?.headline || user.profile?.current_job || 'Add your headline' }}
            </p>

            <p class="text-sm text-gray-400 mt-1">
              {{ user.profile?.location || 'No location added' }}
            </p>

            <div class="flex gap-4 mt-4" v-if="isOwnProfile">
              <RouterLink
                to="/profile/edit"
                class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Edit Profile
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <div v-if="user" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">User Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Name</p>
            <p class="text-gray-800 font-medium">{{ user.name || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Email</p>
            <p class="text-gray-800 font-medium">{{ user.email || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Phone</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.phone || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Current Job</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.current_job || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Company</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.company || 'Not provided' }}</p>
          </div>

          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-gray-500">Graduate Year</p>
            <p class="text-gray-800 font-medium">{{ user.profile?.graduate_year || 'Not provided' }}</p>
          </div>
        </div>

        <div class="mt-4 bg-gray-50 rounded-lg p-3">
          <p class="text-gray-500 mb-2">Skills</p>
          <div v-if="skillsList.length" class="flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="text-xs bg-teal-100 text-teal-800 px-2 py-1 rounded-full"
            >
              {{ skill }}
            </span>
          </div>
          <p v-else class="text-gray-800 font-medium text-sm">Not provided</p>
        </div>
      </div>

      <div v-if="user && isOwnProfile" class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>

        <p v-if="passwordError" class="text-sm text-red-500 mb-3">{{ passwordError }}</p>
        <p v-if="passwordMessage" class="text-sm text-green-600 mb-3">{{ passwordMessage }}</p>

        <form @submit.prevent="changePassword" class="space-y-4">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Old Password</label>
            <input
              v-model="oldPassword"
              type="password"
              required
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">New Password</label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="6"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Confirm New Password</label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              minlength="6"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 rounded-md disabled:opacity-60"
          >
            {{ passwordLoading ? 'Updating...' : 'Change Password' }}
          </button>
        </form>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/ui/nav.vue'
import api from '@/services/api'

const route = useRoute()
const user = ref(null)
const errorMessage = ref('')
const loggedInUser = ref(null)

const oldPassword = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const passwordLoading = ref(false)
const passwordError = ref('')
const passwordMessage = ref('')

const coverImage = computed(() => {
  return (
    user.value?.profile?.cover ||
    'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=1600&auto=format&fit=crop'
  )
})

const isOwnProfile = computed(() => {
  return loggedInUser.value?.id === user.value?.id
})

const skillsList = computed(() => {
  const skills = user.value?.profile?.skills
  if (!skills) return []
  return skills
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean)
})

const loadLoggedInUser = async () => {
  try {
    const response = await api.get('/me')
    loggedInUser.value = response.data
  } catch {
    loggedInUser.value = null
  }
}

const loadProfile = async (id) => {
  errorMessage.value = ''

  try {
    const response = await api.get(`/profiles/${id}`)
    user.value = response.data.data
  } catch (error) {
    const isOwnProfile =
      loggedInUser.value?.id &&
      String(loggedInUser.value.id) === String(id)

    if (isOwnProfile) {
      user.value = loggedInUser.value
      return
    }

    user.value = null
    errorMessage.value =
      error?.response?.data?.message || 'Profile not found or failed to load.'
  }
}

const changePassword = async () => {
  passwordError.value = ''
  passwordMessage.value = ''

  if (newPassword.value !== newPasswordConfirmation.value) {
    passwordError.value = 'New password confirmation does not match.'
    return
  }

  passwordLoading.value = true
  try {
    await api.post('/profile/change-password', {
      old_password: oldPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    })

    oldPassword.value = ''
    newPassword.value = ''
    newPasswordConfirmation.value = ''
    passwordMessage.value = 'Password changed successfully.'
  } catch (error) {
    passwordError.value = error.response?.data?.message || 'Failed to change password.'
  } finally {
    passwordLoading.value = false
  }
}

watch(
  () => route.params.id,
  (id) => {
    if (id) loadProfile(id)
  }
)

onMounted(async () => {
  await loadLoggedInUser()
  if (route.params.id) {
    await loadProfile(route.params.id)
  }
})
</script>
