<template>
  <Navbar />
  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-12 gap-6">

      <!-- LEFT SIDEBAR -->
      <userLeftSideBar />

      <!-- CENTER FEED -->
      <centerFeed />

      <!-- RIGHT SIDEBAR -->
      <userRightSideBar />

    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
// component import
import Navbar from "../components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import centerFeed from "@/components/ui/centerFeed.vue";
import userRightSideBar from "@/components/ui/userRightSideBar.vue";

// const user = ref(null);

// onMounted(async () => {
//   const response = await axios.get("http://127.0.0.1:8000/api/show");
//   user.value = response.data.data;
// });
</script>
