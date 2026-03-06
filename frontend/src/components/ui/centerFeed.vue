<template>
  <div class="col-span-6 space-y-6">
    <div class="bg-white rounded-xl shadow p-4">
      <form @submit.prevent="submitPost">
        <div class="flex items-center gap-3">
          <img
            :src="currentUser?.profile?.avatar || fallbackAvatar"
            class="w-10 h-10 rounded-full object-cover"
          />
          <input
            v-model="postContent"
            type="text"
            placeholder="Share an update with your alumni network..."
            class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none"
          />
        </div>
      </form>

      <!-- Create Post -->
      <div
        class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-3 hover:shadow-md transition"
      >
        <div class="flex items-center gap-3">
          <img
            :src="posts.user?.profile?.avatar || fallbackAvatar"
            class="w-10 h-10 rounded-full object-cover"
          />
          <div>
            <h4 class="font-semibold">
              {{ posts.user?.name || "Unknown user" }}
            </h4>
            <p class="text-xs text-gray-500">
              {{ formatDate(posts.created_at) }} � Public
            </p>
          </div>
        </div>

        <p v-if="errorMessage" class="text-red-500 text-xs">
          {{ errorMessage }}
        </p>

        <div class="flex justify-end">
          <button
            @click="post"
            class="bg-blue-600 text-white px-4 py-1.5 rounded-full hover:bg-blue-700 transition text-sm font-medium"
          >
            Post
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import api from "@/services/api";
import fallbackAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const props = defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
  currentUser: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["post-created"]);

const postContent = ref("");
const isPosting = ref(false);
const errorMessage = ref("");

const submitPost = async () => {
  errorMessage.value = "";

  if (!postContent.value.trim()) {
    errorMessage.value = "Post content is required.";
    return;
  }

  isPosting.value = true;
  try {
    const response = await api.post("/posts", {
      post_content: postContent.value.trim(),
    });

    emit("post-created", response.data.data);
    postContent.value = "";
  } catch {
    errorMessage.value = "Failed to create post. Please try again.";
  } finally {
    isPosting.value = false;
  }
};

const formatDate = (value) => {
  if (!value) return "Unknown time";
  return new Date(value).toLocaleString();
};
</script>
