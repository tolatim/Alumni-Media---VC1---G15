<template>
  <Navbar />
  <div>
    <h1 class="text-white font-bold">Home Page</h1>
    <p>Welcome! You are logged in.</p>
    <RouterLink to="/show">
      Show detail information
    </RouterLink>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
// component import
import Navbar from "../components/ui/nav.vue";

// const user = ref(null);

// onMounted(async () => {
//   const response = await axios.get("http://127.0.0.1:8000/api/show");
//   user.value = response.data.data;
// });
</script>
