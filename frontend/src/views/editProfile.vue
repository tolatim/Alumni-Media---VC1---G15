<template>
  <div class="min-h-screen bg-[#f0f4f8] py-8">
    <div class="mx-auto max-w-4xl px-4 space-y-5">

      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button @click="goBack" class="flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 shadow-sm hover:bg-slate-50 transition">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
          </button>
          <div>
            <h1 class="text-lg font-bold text-slate-900 tracking-tight">Edit Profile</h1>
            <p class="text-xs text-slate-400">Update your personal information</p>
          </div>
        </div>
        <div class="flex gap-2">
          <button @click="goBack" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition">
            Cancel
          </button>
          <button @click="saveProfile" :disabled="loading"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-md hover:from-cyan-600 hover:to-blue-700 disabled:opacity-60 transition-all">
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
            </svg>
            {{ loading ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
        {{ successMessage }}
      </div>

      <!-- Cover & Avatar -->
      <div class="overflow-hidden rounded-3xl bg-white border border-slate-100 shadow-lg">
        <!-- Cover -->
        <div class="h-52 relative group">
          <img :src="coverPreview || defaultCover" class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all flex items-center justify-center">
            <label class="cursor-pointer opacity-0 group-hover:opacity-100 transition-all">
              <div class="flex items-center gap-2 rounded-xl bg-white/90 backdrop-blur px-4 py-2 text-sm font-semibold text-slate-700 shadow-lg hover:bg-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/>
                </svg>
                Change Cover
              </div>
              <input type="file" accept="image/*" @change="onCoverFileChange" class="hidden" />
            </label>
          </div>
        </div>

        <!-- Avatar -->
        <div class="relative px-8 pb-7">
          <div class="absolute -top-12 left-8">
            <div class="relative group/avatar">
              <img :src="avatarPreview || fallbackAvatar" class="w-24 h-24 rounded-full border-4 border-white object-cover shadow-xl" />
              <label class="absolute inset-0 flex items-center justify-center rounded-full bg-black/0 group-hover/avatar:bg-black/40 transition-all cursor-pointer">
                <svg class="w-6 h-6 text-white opacity-0 group-hover/avatar:opacity-100 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0ZM18.75 10.5h.008v.008h-.008V10.5Z"/>
                </svg>
                <input type="file" accept="image/*" @change="onAvatarFileChange" class="hidden" />
              </label>
            </div>
          </div>
          <div class="pt-14">
            <p class="text-xs text-slate-400 font-medium">Hover over your photo or cover to change it</p>
            <p class="text-xs text-slate-300 mt-0.5">Avatar: JPG, PNG, WEBP (max 5MB) · Cover: JPG, PNG, WEBP (max 8MB)</p>
          </div>
        </div>
      </div>

      <!-- Basic Info -->
      <div class="rounded-3xl bg-white border border-slate-100 shadow-lg p-7">
        <div class="flex items-center gap-2 mb-6">
          <div class="w-1 h-5 rounded-full bg-gradient-to-b from-cyan-500 to-blue-600"></div>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">Basic Information</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">First Name</label>
            <input v-model="form.first_name" type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Name</label>
            <input v-model="form.last_name" type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Headline / Job Title</label>
            <input v-model="form.headline" type="text" placeholder="e.g. Senior Software Engineer at Acme"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Current Job</label>
            <input v-model="form.current_job" type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Company</label>
            <input v-model="form.company" type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</label>
            <input v-model="form.phone" type="text"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Graduate Year</label>
            <input v-model.number="form.graduate_year" type="number" placeholder="e.g. 2020"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Location</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
              </svg>
              <input v-model="form.location" type="text" placeholder="e.g. San Francisco, CA"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-10 pr-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
            </div>
          </div>
        </div>
      </div>

      <!-- Bio & Skills -->
      <div class="rounded-3xl bg-white border border-slate-100 shadow-lg p-7">
        <div class="flex items-center gap-2 mb-6">
          <div class="w-1 h-5 rounded-full bg-gradient-to-b from-cyan-500 to-blue-600"></div>
          <h2 class="text-base font-bold text-slate-800 tracking-tight">About & Skills</h2>
        </div>

        <div class="space-y-5">
          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Bio</label>
            <textarea v-model="form.bio" rows="4" placeholder="Tell people a bit about yourself..."
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition resize-none"></textarea>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-semibold text-slate-500 uppercase tracking-wide">Skills</label>
            <input v-model="form.skills" type="text" placeholder="e.g. Vue, Laravel, MySQL, Figma"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-cyan-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-100 transition" />
            <p class="mt-2 text-xs text-slate-400">Separate skills with commas</p>

            <!-- Live skill preview -->
            <div v-if="skillsList.length" class="mt-3 flex flex-wrap gap-2">
              <span v-for="skill in skillsList" :key="skill"
                class="rounded-full bg-gradient-to-r from-cyan-50 to-blue-50 border border-cyan-200 px-3 py-1 text-xs font-semibold text-cyan-700">
                {{ skill }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Save -->
      <div class="flex justify-end gap-2 pb-4">
        <button @click="goBack" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 transition">
          Cancel
        </button>
        <button @click="saveProfile" :disabled="loading"
          class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-md hover:from-cyan-600 hover:to-blue-700 disabled:opacity-60 transition-all">
          <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
          </svg>
          {{ loading ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import fallbackAvatar from '@/assets/images/blank-profile-picture-973460_1280.webp'
import defaultCover from '@/assets/images/3840x2160-white-solid-color-background.jpg'

const router = useRouter()
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const currentUserId = ref(null)

const avatarFile = ref(null)
const coverFile = ref(null)
const avatarFilePreview = ref('')
const coverFilePreview = ref('')

const form = reactive({
  first_name: '',
  last_name: '',
  headline: '',
  current_job: '',
  company: '',
  phone: '',
  graduate_year: null,
  location: '',
  bio: '',
  skills: '',
  currentAvatarUrl: '',
  currentCoverUrl: '',
})

const avatarPreview = computed(() => avatarFilePreview.value || form.currentAvatarUrl)
const coverPreview = computed(() => coverFilePreview.value || form.currentCoverUrl)

const skillsList = computed(() => {
  if (!form.skills) return []
  return form.skills.split(',').map((s) => s.trim()).filter(Boolean)
})

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
    form.first_name = user.first_name || ''
    form.last_name = user.last_name || ''
    form.headline = user.profile?.headline || ''
    form.current_job = user.profile?.current_job || ''
    form.company = user.profile?.company || ''
    form.phone = user.profile?.phone || ''
    form.graduate_year = user.profile?.graduate_year || null
    form.location = user.profile?.location || ''
    form.bio = user.profile?.bio || ''
    form.skills = user.profile?.skills || ''
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
    formData.append('first_name', form.first_name || '')
    formData.append('last_name', form.last_name || '')
    formData.append('headline', form.headline || '')
    formData.append('current_job', form.current_job || '')
    formData.append('company', form.company || '')
    formData.append('phone', form.phone || '')
    formData.append('location', form.location || '')
    formData.append('bio', form.bio || '')
    formData.append('skills', form.skills || '')
    if (form.graduate_year) formData.append('graduate_year', String(form.graduate_year))
    if (avatarFile.value) formData.append('avatar_file', avatarFile.value)
    if (coverFile.value) formData.append('cover_file', coverFile.value)

    const response = await api.post('/profile', formData)
    localStorage.setItem('user', JSON.stringify(response.data.data))
    successMessage.value = 'Profile updated successfully.'
    if (currentUserId.value) router.push(`/profile/${currentUserId.value}`)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to save profile.'
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  if (currentUserId.value) router.push(`/profile/${currentUserId.value}`)
  else router.push('/')
}

onMounted(loadCurrentProfile)
</script>