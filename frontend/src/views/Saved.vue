<template>
  <Navbar />
  <main class="min-h-screen bg-transparent py-6 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-5">
      <div class="grid grid-cols-12 gap-5">
        <userLeftSideBar :user="currentUser" />

        <section class="col-span-12 lg:col-span-9 space-y-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center gap-3">
              <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <i class="fa-solid fa-bookmark text-lg"></i>
              </span>
              <div>
                <h1 class="text-lg font-semibold text-slate-900">Saved items</h1>
                <p class="text-sm text-slate-500">Posts you bookmarked appear here.</p>
              </div>
            </div>
          </div>

          <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">Loading saved posts...</div>
          <p v-else-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ errorMessage }}</p>
          <p v-else-if="!savedPosts.length" class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600">You have no saved posts yet.</p>

          <div v-else class="space-y-4">
            <p v-if="feedback" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ feedback }}</p>
            <PostCard
              v-for="post in savedPosts"
              :key="post.id"
              :post="post"
              :current-user="currentUser"
              :show-save-label="true"
              @unsaved="handleUnsave(post.id)"
            />
          </div>
        </section>
      </div>
    </div>
  </main>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import Navbar from "@/components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import PostCard from "@/components/ui/PostCard.vue";
import api from "@/services/api";

const currentUser = ref(null);
const savedPosts = ref([]);
const loading = ref(true);
const errorMessage = ref("");
const feedback = ref("");
let feedbackTimer = null;

const loadCurrentUser = async () => {
  try {
    const response = await api.get("/me");
    currentUser.value = response.data;
    localStorage.setItem("user", JSON.stringify(response.data));
  } catch {
    currentUser.value = null;
  }
};

const loadSaved = async () => {
  loading.value = true;
  errorMessage.value = "";
  try {
    const response = await api.get("/saved");
    const data = response.data;
    savedPosts.value = Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : [];
  } catch (error) {
    errorMessage.value = error.response?.data?.message || "Failed to load saved posts.";
    savedPosts.value = [];
  } finally {
    loading.value = false;
  }
};

const handleUnsave = (postId) => {
  savedPosts.value = savedPosts.value.filter((post) => post.id !== postId);
};

onMounted(async () => {
  await Promise.all([loadCurrentUser(), loadSaved()]);
});

onUnmounted(() => {
  if (feedbackTimer) {
    clearTimeout(feedbackTimer);
    feedbackTimer = null;
  }
});
</script>