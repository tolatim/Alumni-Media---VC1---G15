<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm mb-6">
      <h2 class="font-semibold text-gray-700">Edit Profile</h2>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-4xl mx-auto">
      <div class="pt-4 p-8">
        <p v-if="errorMessage" class="text-sm text-red-500 mb-4">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-green-600 mb-4">{{ successMessage }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Headline / Job Title</label>
            <input v-model="form.headline" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Current Job</label>
            <input v-model="form.current_job" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Company</label>
            <input v-model="form.company" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Phone</label>
            <input v-model="form.phone" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Graduate Year</label>
            <input v-model.number="form.graduate_year" type="number" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>
        </div>

        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">Location</label>
          <input v-model="form.location" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
        </div>

        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">About / Bio</label>
          <textarea v-model="form.bio" rows="4" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
        </div>

        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">Skills</label>
          <input
            v-model="form.skills"
            type="text"
            placeholder="Example: Vue, Laravel, MySQL"
            class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
          />
          <p class="text-xs text-gray-500 mt-1">Use comma to separate skills.</p>
        </div>

        <div class="flex justify-end mt-6 space-x-2">
          <button @click="saveProfile" :disabled="loading" class="bg-teal-600 text-white px-5 py-2 rounded-md hover:bg-teal-700 disabled:opacity-60">
            {{ loading ? 'Saving...' : 'Save Changes' }}
          </button>
          <button @click="goBack" class="bg-gray-200 px-5 py-2 rounded-md hover:bg-gray-300">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { updateProfile } from '@/services/authService'
import { getProfile } from '@/services/authService'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
  headline: '',
  current_job: '',
  company: '',
  phone: '',
  graduate_year: null,
  location: '',
  bio: '',
  skills: '',
})

// Load current authenticated user's profile
// const loadProfile = async () => {
//   errorMessage.value = ''
//   try {
//     const token = localStorage.getItem('token')
//     const response = localStorage.getItem('user')
//     const user = response
//     form.headline = user['headline'] || ''
//     form.current_job = user.profile?.current_job || ''
//     form.company = user.profile?.company || ''
//     form.phone = user.profile?.phone || ''
//     form.graduate_year = user.profile?.graduate_year || null
//     form.location = user.profile?.location || ''
//     form.bio = user.profile?.bio || ''
//     form.skills = user.profile?.skills || ''
//   } catch (err) {
//     errorMessage.value = 'Failed to load your profile.'
//   }
// }

const loadProfile = async () => {
  errorMessage.value = ''

  try {
    const userString = JSON.parse(localStorage.getItem('user'))
    if (!userString) throw new Error('No user in localStorage')

    const response = await getProfile(userString.id)
    const user = response.data.user
    form.headline = user.headline || ''
    form.current_job = user.current_job || ''
    form.company = user.company || ''
    form.phone = user.phone || ''
    form.graduate_year = user.graduate_year || null
    form.location = user.location || ''
    form.bio = user.bio || ''
    form.skills = user.skills || ''
  } catch (err) {
    errorMessage.value = 'Failed to load your profile.'
  }
}

const saveProfile = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const token = localStorage.getItem('token')
    const payload = { ...form }

    const response = await updateProfile(payload, {
      headers: {
        Authorization: `Bearer ${token}`,
      }
    })

    successMessage.value = response.data.message || 'Profile updated successfully.'
    // optionally update localStorage with new user data
    localStorage.setItem('user', JSON.stringify(response.data.user))
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to save profile.'
  } finally {
    loading.value = false
  }
}

const goBack = () => router.back()

onMounted(loadProfile)
</script>