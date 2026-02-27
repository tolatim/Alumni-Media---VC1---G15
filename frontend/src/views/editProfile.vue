<template>
  <div class="min-h-screen bg-gray-100 p-6">

    <!-- Header -->
    <div class="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm mb-6">
      <div class="flex items-center gap-2">
        <div class="bg-teal-100 p-2 rounded-md">
          <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M5.121 17.804A4 4 0 017 16h10a4 4 0 011.879.804M15 11a4 4 0 10-6 0m6 0a4 4 0 01-6 0" />
          </svg>
        </div>
        <h2 class="font-semibold text-gray-700">Edit Profile</h2>
      </div>

      <div class="space-x-2">
        <button class="bg-teal-600 text-white px-4 py-1.5 rounded-md hover:bg-teal-700">
          Save
        </button>
        <RouterLink to="/profile" class="bg-gray-200 px-4 py-1.5 rounded-md hover:bg-gray-300">
          Cancel
        </RouterLink>
      </div>
    </div>

    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden max-w-4xl mx-auto">

      <!-- Cover -->
      <div class="h-48 bg-gradient-to-r from-teal-800 to-teal-500 relative">
        <div class="absolute -bottom-12 left-8">
          <div class="relative">
            <img
              :src="form.avatar"
              class="w-24 h-24 rounded-full border-4 border-white object-cover"
            />
            <button
              class="absolute bottom-0 right-0 bg-teal-600 text-white p-1 rounded-full hover:bg-teal-700"
            >
              ✎
            </button>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="pt-16 p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <!-- Full Name -->
          <div>
            <label class="block text-sm text-gray-600 mb-1">Full Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <!-- Headline -->
          <div>
            <label class="block text-sm text-gray-600 mb-1">Headline / Job Title</label>
            <input
              v-model="form.headline"
              type="text"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <!-- Current Position -->
          <div>
            <label class="block text-sm text-gray-600 mb-1">Current Position</label>
            <input
              v-model="form.position"
              type="text"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <!-- Education -->
          <div>
            <label class="block text-sm text-gray-600 mb-1">Education</label>
            <input
              v-model="form.education"
              type="text"
              class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

        </div>

        <!-- Location -->
        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">Location</label>
          <input
            v-model="form.location"
            type="text"
            class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
          />
        </div>

        <!-- About -->
        <div class="mt-6">
          <label class="block text-sm text-gray-600 mb-1">About / Bio</label>
          <textarea
            v-model="form.bio"
            rows="4"
            class="w-full border rounded-md px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none"
          ></textarea>
        </div>

        <!-- Bottom Buttons -->
        <div class="flex justify-end mt-6 space-x-2">
          <button
            @click="saveProfile"
            class="bg-teal-600 text-white px-5 py-2 rounded-md hover:bg-teal-700"
          >
            Save Changes
          </button>

          <button
            @click="resetForm"
            class="bg-gray-200 px-5 py-2 rounded-md hover:bg-gray-300"
          >
            Cancel
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import { reactive } from 'vue'

const form = reactive({
  avatar: 'https://i.pravatar.cc/150?img=12',
  name: 'Alex Rivera',
  headline: 'Senior Product Designer',
  position: 'Design Lead at TechFlow',
  education: 'Stanford University',
  location: 'San Francisco, CA',
  bio: 'Passionate about creating user-centric digital experiences with over 8 years of experience in product design and brand strategy.'
})

const saveProfile = () => {
  console.log('Saved Data:', form)
  alert('Profile Saved!')
}

const resetForm = () => {
  window.location.reload()
}
</script>