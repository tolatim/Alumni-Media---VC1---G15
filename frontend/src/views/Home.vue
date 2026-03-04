<template>
  <Navbar />
  <main class="bg-gray-100 min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-6 grid grid-cols-12 gap-6">
      <userLeftSideBar :user="currentUser" />

      <centerFeed
        :posts="posts"
        :current-user="currentUser"
        @post-created="prependPost"
      />
      <userRightSideBar :suggestions="suggestions" />
    </div>

    <p v-if="errorMessage" class="text-center text-red-500 mt-4">
      {{ errorMessage }}
    </p>
  </main>
</template>

<script setup>
import { onMounted, ref } from "vue";
import Navbar from "@/components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import centerFeed from "@/components/ui/centerFeed.vue";
import userRightSideBar from "@/components/ui/userRightSideBar.vue";
import api from "@/services/api";
import { getUser } from "@/services/authService";

const currentUser = ref(null);
const posts = ref([]);
const suggestions = ref([]);
const errorMessage = ref("");

const loadHomeData = async () => {
  errorMessage.value = "";

  try {
    const user_id = JSON.parse(localStorage.getItem("user")).id;
    if (!user_id) {
      throw new Error("No user in localStorage");
    }

    // Fetch the current user
    const ress = await getUser(user_id);
    currentUser.value = ress.data.user;

    // Update localStorage with fresh user data

    // localStorage.setItem('user', JSON.stringify({ user: currentUser.value }))
  } catch (err) {
    console.error(err);
    errorMessage.value = "Failed to load home page data.";
  }
};

const prependPost = (newPost) => {
  posts.value = [newPost, ...posts.value];
};

onMounted(loadHomeData);
</script>
