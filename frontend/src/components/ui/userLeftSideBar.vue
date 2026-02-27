<template>
  <div class="col-span-3 space-y-6">

    <!-- Profile Card -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
      
      <!-- Cover -->
      <div class="h-20 bg-blue-800 relative">
        <img
          src="https://i.pravatar.cc/150"
          class="w-20 h-20 rounded-full border-4 border-white absolute -bottom-10 left-1/2 -translate-x-1/2 object-cover"
        />
      </div>

      <!-- Info -->
      <div class="pt-12 pb-6 px-6 text-center">
        <h3 class="font-semibold text-lg text-gray-800">
          {{ user?.name }}
        </h3>

        <p class="text-sm text-gray-500">
          Senior Software Engineer at TechCorp
        </p>

        <div class="mt-4 flex justify-between text-sm text-gray-600">
          <span>Connections</span>
          <span class="font-semibold text-blue-600">
            1,240
          </span>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
      <h4 class="font-semibold text-gray-700">
        Quick Links
      </h4>

      <div class="space-y-2 text-gray-600">

        <p class="hover:text-blue-600 cursor-pointer transition">
          Groups
        </p>

        <p class="hover:text-blue-600 cursor-pointer transition">
          Events
        </p>

        <p class="hover:text-blue-600 cursor-pointer transition">
          Connections
        </p>

        <p 
          class="hover:text-red-600 cursor-pointer text-red-500 transition"
          @click="logout"
        >
          Logout
        </p>

      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router'

const router = useRouter()
const storedUser = localStorage.getItem('user');
const user = ref(
  storedUser ? JSON.parse(storedUser) : null
)

const logout = () => {
  // remove token (if using localStorage)
  localStorage.removeItem('token')
  localStorage.removeItem('user')

  // redirect to login
  router.push('/login')
}
</script>