<template>
  <Navbar />
  <main class="min-h-screen bg-transparent py-6 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-5">
      <div class="grid grid-cols-12 gap-5">
        <userLeftSideBar :user="currentUser" />

        <centerFeed
          :posts="posts"
          :current-user="currentUser"
          @post-created="prependPost"
          @refresh-posts="refreshPosts"
        />

        <userRightSideBar
          :suggestions="suggestions"
          :pending-requests="pendingRequests"
          @send-request="sendConnectionRequest"
          @accept-request="acceptConnectionRequest"
          @reject-request="rejectConnectionRequest"
        />
      </div>

      <div v-if="loadingMore" class="mt-5 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Loading more posts...</div>
      <div v-else-if="!hasMorePosts && posts.length" class="mt-5 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">No more posts</div>

      <p v-if="errorMessage" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center text-sm font-medium text-rose-700">
        {{ errorMessage }}
      </p>
    </div>
  </main>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import Navbar from "@/components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import centerFeed from "@/components/ui/centerFeed.vue";
import userRightSideBar from "@/components/ui/userRightSideBar.vue";
import api from "@/services/api";
import { useFeedStore } from "@/stores/feed";
import { subscribeToPostEvents, setPostHubUserId } from "@/utils/postHub";


const feedStore = useFeedStore();
const currentUser = ref(null);
const suggestions = ref([]);
const pendingRequests = ref([]);
const errorMessage = ref("");
const loadingMore = ref(false);

const posts = computed(() => feedStore.posts);
const hasMorePosts = computed(() => feedStore.page < feedStore.lastPage);

let unsubscribePostHub = null;
let sharePostRefreshTimer = null;
let homeRefreshTimer = null;
const HOME_REFRESH_INTERVAL_MS = 3000;

const queueSharePostRefresh = (postId) => {
  const normalizedPostId = Number(postId);
  if (!Number.isFinite(normalizedPostId)) return;

  const isVisibleSharePost = posts.value.some(
    (post) => Number(post?.id) === normalizedPostId && Number(post?.shared_post_id) > 0
  );

  if (!isVisibleSharePost) return;
  if (sharePostRefreshTimer) return;

  sharePostRefreshTimer = window.setTimeout(async () => {
    sharePostRefreshTimer = null;
    await refreshPosts();
  }, 250);
};

const handlePostEvent = (payload) => {
  if (!payload) return;
  if (payload.type === "post_created" && payload.data?.post) {
    feedStore.addPost(payload.data.post);
  } else if (payload.type === "post_updated" && payload.data?.post) {
    feedStore.replacePost(payload.data.post);
  } else if (payload.type === "post_like_updated") {
    const postId = payload.data?.post_id || payload.data?.postId;
    if (postId) {
      feedStore.updatePostLike(postId, payload.data, currentUser.value?.id ?? null);
      queueSharePostRefresh(postId);
    }
  } else if (payload.type === "post_comment_updated") {
    const postId = payload.data?.post_id || payload.data?.postId;
    if (postId) {
      feedStore.updatePostComments(postId, payload.data);
      queueSharePostRefresh(postId);
    }
  } else if (payload.type === "post_deleted") {
    const postId = payload.data?.post_id || payload.data?.postId;
    if (postId) {
      feedStore.removePost(postId);
    }
  }
};

const loadHomeData = async () => {
  errorMessage.value = "";

  try {
    const response = await api.get("/me");
    currentUser.value = response.data;
    localStorage.setItem("user", JSON.stringify(currentUser.value));
    setPostHubUserId(currentUser.value?.id ?? null);
  } catch (error) {
    currentUser.value = null;
    errorMessage.value =
      error.response?.data?.message || "Failed to load your account.";
    setPostHubUserId(null);
    return;
  }

  try {
    await feedStore.load(1);
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Failed to load posts.";
  }

  try {
    const response = await api.get("/users/suggestions");
    suggestions.value = response.data?.data || [];
  } catch {
    suggestions.value = [];
  }

  try {
    const response = await api.get("/connections/pending");
    pendingRequests.value = response.data?.data || [];
  } catch {
    pendingRequests.value = [];
  }
};

const loadMorePosts = async () => {
  if (loadingMore.value || !hasMorePosts.value) return;
  loadingMore.value = true;
  try {
    await feedStore.load(feedStore.page + 1, true);
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Failed to load more posts.";
  } finally {
    loadingMore.value = false;
  }
};

const onScroll = () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const viewportHeight =
    window.innerHeight || document.documentElement.clientHeight;
  const fullHeight = document.documentElement.scrollHeight;

  if (scrollTop + viewportHeight >= fullHeight - 280) {
    loadMorePosts();
  }
};

const prependPost = (newPost) => {
  feedStore.addPost(newPost);
};

const refreshPosts = async () => {
  try {
    await feedStore.load(1);
  } catch (error) {
    console.error(error.response?.data || error);
  }
};

const stopHomeRefreshLoop = () => {
  if (!homeRefreshTimer) return;
  window.clearInterval(homeRefreshTimer);
  homeRefreshTimer = null;
};

const startHomeRefreshLoop = () => {
  stopHomeRefreshLoop();
  homeRefreshTimer = window.setInterval(async () => {
    if (document.visibilityState !== "visible") return;
    await refreshPosts();
  }, HOME_REFRESH_INTERVAL_MS);
};

const sendConnectionRequest = async (userId) => {
  try {
    await api.post("/connections/request", { user_id: userId });
    suggestions.value = suggestions.value.filter(
      (person) => person.id !== userId
    );
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Failed to send connection request.";
  }
};

const acceptConnectionRequest = async (requestId) => {
  try {
    await api.post(`/connections/${requestId}/accept`);
    pendingRequests.value = pendingRequests.value.filter(
      (request) => request.id !== requestId
    );
    await refreshSuggestions();
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Failed to accept request.";
  }
};

const rejectConnectionRequest = async (requestId) => {
  try {
    await api.post(`/connections/${requestId}/reject`);
    pendingRequests.value = pendingRequests.value.filter(
      (request) => request.id !== requestId
    );
    await refreshSuggestions();
  } catch (error) {
    errorMessage.value =
      error.response?.data?.message || "Failed to reject request.";
  }
};

const refreshSuggestions = async () => {
  try {
    const response = await api.get("/users/suggestions");
    suggestions.value = response.data?.data || [];
  } catch {
    suggestions.value = [];
  }
};

onMounted(() => {
  loadHomeData();
  window.addEventListener("scroll", onScroll, { passive: true });
  unsubscribePostHub = subscribeToPostEvents(handlePostEvent);
  startHomeRefreshLoop();
});

onBeforeUnmount(() => {
  window.removeEventListener("scroll", onScroll);
  stopHomeRefreshLoop();
  if (sharePostRefreshTimer) {
    window.clearTimeout(sharePostRefreshTimer);
    sharePostRefreshTimer = null;
  }
  if (unsubscribePostHub) {
    unsubscribePostHub();
    unsubscribePostHub = null;
  }
});
</script>
