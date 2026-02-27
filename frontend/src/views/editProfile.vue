<template>
  <div class="min-h-screen bg-gray-100 p-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm mb-6">
      <h2 class="font-semibold text-gray-700">Edit Profile</h2>
      <div class="space-x-2">
        <button @click="saveProfile" :disabled="loading" class="bg-teal-600 text-white px-4 py-1.5 rounded-md hover:bg-teal-700 disabled:opacity-60">
          {{ loading ? 'Saving...' : 'Save' }}
        </button>
        <button @click="goBack" class="bg-gray-200 px-4 py-1.5 rounded-md hover:bg-gray-300">Cancel</button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-4xl mx-auto">
      <div class="h-48 relative">
        <img :src="coverPreview" class="w-full h-full object-cover">

        <div class="absolute -bottom-12 left-8">
          <img :src="avatarPreview" class="w-24 h-24 rounded-full border-4 border-white object-cover" />
        </div>
      </div>

      <div class="pt-16 p-8">
        <p v-if="errorMessage" class="text-sm text-red-500 mb-4">{{ errorMessage }}</p>
        <p v-if="successMessage" class="text-sm text-green-600 mb-4">{{ successMessage }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Full Name</label>
            <input v-model="form.name" type="text" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none" />
          </div>

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

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm text-gray-600 mb-1">Upload Profile Photo</label>
            <input type="file" accept="image/*" @change="onAvatarFileChange" class="w-full border rounded-md px-3 py-2 bg-white" />
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP (max 5MB)</p>
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">Upload Cover Photo</label>
            <input type="file" accept="image/*" @change="onCoverFileChange" class="w-full border rounded-md px-3 py-2 bg-white" />
            <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP (max 8MB)</p>
          </div>
        </div>

        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">About / Bio</label>
          <textarea v-model="form.bio" rows="4" class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"></textarea>
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const currentUserId = ref(null)
const defaultCover = 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=1600&auto=format&fit=crop'

const avatarFile = ref(null)
const coverFile = ref(null)
const avatarFilePreview = ref('')
const coverFilePreview = ref('')

const form = reactive({
  name: '',
  headline: '',
  current_job: '',
  company: '',
  phone: '',
  graduate_year: null,
  location: '',
  bio: '',
  currentAvatarUrl: '',
  currentCoverUrl: '',
})

const avatarPreview = computed(() => avatarFilePreview.value || form.currentAvatarUrl)
const coverPreview = computed(() => coverFilePreview.value || form.currentCoverUrl)

const onAvatarFileChange = (event) => {
  const file = event.target.files?.[0]
  avatarFile.value = file || null
  avatarFilePreview.value = file ? URL.createObjectURL(file) : ''
}

const onCoverFileChange = (event) => {
  const file = event.target.files?.[0]
  coverFile.value = file || null
  coverFilePreview.value = file ? URL.createObjectURL(file) : ''
}

const loadCurrentProfile = async () => {
  errorMessage.value = ''

  try {
    const response = await api.get('/me')
    const user = response.data

    currentUserId.value = user.id

    form.name = user.name || ''
    form.headline = user.profile?.headline || ''
    form.current_job = user.profile?.current_job || ''
    form.company = user.profile?.company || ''
    form.phone = user.profile?.phone || ''
    form.graduate_year = user.profile?.graduate_year || null
    form.location = user.profile?.location || ''
    form.bio = user.profile?.bio || ''
    form.currentAvatarUrl = user.profile?.avatar || ''
    form.currentCoverUrl = user.profile?.cover || ''
  } catch {
    errorMessage.value = 'Failed to load your profile.'
  }
}

const saveProfile = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('_method', 'PUT')
    formData.append('name', form.name || '')
    formData.append('headline', form.headline || '')
    formData.append('current_job', form.current_job || '')
    formData.append('company', form.company || '')
    formData.append('phone', form.phone || '')
    formData.append('location', form.location || '')
    formData.append('bio', form.bio || '')

    if (form.graduate_year) {
      formData.append('graduate_year', String(form.graduate_year))
    }

    if (avatarFile.value) {
      formData.append('avatar_file', avatarFile.value)
    }

    if (coverFile.value) {
      formData.append('cover_file', coverFile.value)
    }

    const response = await api.post('/profile', formData)
    localStorage.setItem('user', JSON.stringify(response.data.data))
    successMessage.value = 'Profile updated successfully.'

    if (currentUserId.value) {
      router.push(`/profile/${currentUserId.value}`)
    }
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to save profile.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  if (currentUserId.value) {
    router.push(`/profile/${currentUserId.value}`)
  } else {
    router.push('/')
  }
}

onMounted(loadCurrentProfile)
</script>
