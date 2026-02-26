<template>
    
    <Navbar />

  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-6xl mx-auto px-4">
      
      <!-- Profile Card -->
      <div class="bg-white rounded-xl shadow overflow-hidden">
        
        <!-- Cover Background -->
        <div class="h-60 w-full relative">
          <img
            v-if="user?.cover"
            :src="user.cover"
            class="w-full h-full object-cover"
          />
          <img
            v-else
            src="https://png.pngtree.com/thumb_back/fh260/background/20241018/pngtree-alumni-reunion-invitation-image_16224990.jpg"
            class="w-full h-full object-cover"
          />
        </div>

        <!-- Profile Section -->
        <div class="px-6 pb-6 relative">
          
          <!-- Profile Image -->
          <div class="absolute -top-16 left-6">
            <img
              v-if="user?.image"
              :src="user.image"
              class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md"
            />
            <img
              v-else
              src="https://i.pravatar.cc/150"
              class="w-32 h-32 rounded-full border-4 border-white object-cover shadow-md"
            />
          </div>

          <!-- User Info -->
          <div class="pt-20">
            <h1 class="text-2xl font-bold text-gray-800">
              {{ user?.name || "Alex Rivera" }}
            </h1>

            <p class="text-gray-500 mt-1">
              {{ user?.title || "Senior Full-Stack Developer | Open to Collaborations" }}
            </p>

            <p class="text-sm text-gray-400 mt-1">
              {{ user?.location || "San Francisco, CA" }}
            </p>

            <!-- Buttons -->
            <div class="flex gap-4 mt-4">
              <button
                class="bg-teal-500 hover:bg-teal-600 text-white px-6 py-2 rounded-lg font-medium transition"
              >
                Edit Profile
              </button>
              <button
                class="border border-teal-500 text-teal-500 hover:bg-teal-50 px-6 py-2 rounded-lg font-medium transition"
              >
                Add Profile Session
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- About Section -->
      <div class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">About</h2>
        <p class="text-gray-600 leading-relaxed">
          {{ user?.about || 
          "Passionate Full-Stack Developer with 8+ years of experience building scalable web applications. Specialized in React, Node.js, and cloud architecture." }}
        </p>
      </div>

      <!-- Experience Section -->
      <div class="bg-white rounded-xl shadow mt-6 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Experience</h2>

        <div class="mb-4">
          <h3 class="font-semibold text-gray-700">
            Senior Full-Stack Developer
          </h3>
          <p class="text-sm text-gray-500">
            Tech Corp • Jan 2021 – Present
          </p>
          <p class="text-gray-600 mt-2 text-sm">
            Leading development of enterprise dashboard using React and GraphQL.
          </p>
        </div>

        <div>
          <h3 class="font-semibold text-gray-700">
            Software Engineer
          </h3>
          <p class="text-sm text-gray-500">
            Startup Inc • Jun 2018 – Dec 2020
          </p>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
// component import
import Navbar from "../components/ui/nav.vue";

const user = ref(null);

onMounted(async () => {
  const response = await axios.get("http://127.0.0.1:8000/api/show");
  user.value = response.data.data;
});
</script>
