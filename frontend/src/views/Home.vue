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
import { onBeforeUnmount, onMounted, ref } from "vue";
import Navbar from "@/components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import centerFeed from "@/components/ui/centerFeed.vue";
import userRightSideBar from "@/components/ui/userRightSideBar.vue";
import api from "@/services/api";
import { createEcho } from "@/services/realtime";

const currentUser = ref(null);
const posts = ref([]);
const suggestions = ref([]);
const pendingRequests = ref([]);
const errorMessage = ref("");
const loadingMore = ref(false);
const feedPage = ref(1);
const feedLastPage = ref(1);
const FEED_PER_PAGE = 8;

const hasMorePosts = ref(true);

const loadFeedPage = async (page = 1, append = false) => {
  const response = await api.get("/feed", {
    params: { page, per_page: FEED_PER_PAGE },
  });

  const items = response.data?.data || [];
  const pagination = response.data?.pagination || {};

  feedPage.value = Number(pagination.current_page || page);
  feedLastPage.value = Number(pagination.last_page || page);
  hasMorePosts.value = feedPage.value < feedLastPage.value;

  posts.value = append ? [...posts.value, ...items] : items;
};

const loadHomeData = async () => {
  errorMessage.value = "";

  const [meRes, feedRes, suggestionRes, pendingRes] = await Promise.allSettled([
    api.get("/me"),
    api.get("/feed", { params: { page: 1, per_page: FEED_PER_PAGE } }),
    api.get("/users/suggestions"),
    api.get("/connections/pending"),
  ]);

  if (meRes.status === "fulfilled") {
    currentUser.value = meRes.value.data;
    localStorage.setItem("user", JSON.stringify(meRes.value.data));
  } else {
    currentUser.value = null;
    errorMessage.value =
      meRes.reason?.response?.data?.message || "Failed to load your account.";
    return;
  }

  if (feedRes.status === "fulfilled") {
    const pagination = feedRes.value.data?.pagination || {};
    posts.value = feedRes.value.data?.data || [];
    feedPage.value = Number(pagination.current_page || 1);
    feedLastPage.value = Number(pagination.last_page || 1);
    hasMorePosts.value = feedPage.value < feedLastPage.value;
  } else {
    posts.value = [];
    feedPage.value = 1;
    feedLastPage.value = 1;
    hasMorePosts.value = false;
  }

  suggestions.value =
    suggestionRes.status === "fulfilled"
      ? suggestionRes.value.data?.data || []
      : [];
  pendingRequests.value =
    pendingRes.status === "fulfilled" ? pendingRes.value.data?.data || [] : [];

  if (suggestionRes.status === "rejected") {
    try {
      const [fallbackUsersRes, myConnectionsRes, pendingRes, blockedRes] = await Promise.allSettled([
        api.get("/users"),
        api.get("/connections/my", { params: { page: 1, per_page: 200 } }),
        api.get("/connections/pending", { params: { page: 1, per_page: 200 } }),
        api.get("/connections/blocked", { params: { page: 1, per_page: 200 } }),
      ]);

      const allUsers = fallbackUsersRes.status === "fulfilled" ? (fallbackUsersRes.value.data?.data || []) : [];
      const myRows = myConnectionsRes.status === "fulfilled" ? (myConnectionsRes.value.data?.data || []) : [];
      const pendingRows = pendingRes.status === "fulfilled" ? (pendingRes.value.data?.data || []) : [];
      const blockedRows = blockedRes.status === "fulfilled" ? (blockedRes.value.data?.data || []) : [];
      const meId = Number(currentUser.value?.id || 0);
      const existingIds = new Set();

      [...myRows, ...pendingRows, ...blockedRows].forEach((row) => {
        const requesterId = Number(row.requester_id || 0);
        const addresseeId = Number(row.addressee_id || 0);
        if (requesterId === meId && addresseeId) existingIds.add(addresseeId);
        if (addresseeId === meId && requesterId) existingIds.add(requesterId);
      });

      suggestions.value = allUsers
        .filter((user) => Number(user.id) !== meId && !existingIds.has(Number(user.id)))
        .slice(0, 8);
    } catch {
      suggestions.value = [];
    }
  }

  if (feedRes.status === "rejected") {
    errorMessage.value = "Feed failed to load.";
  } else if (pendingRes.status === "rejected") {
    errorMessage.value = "Pending requests failed to load.";
  }
};

const loadMorePosts = async () => {
  if (loadingMore.value || !hasMorePosts.value) return;
  loadingMore.value = true;
  try {
    await loadFeedPage(feedPage.value + 1, true);
  } catch (error) {
    errorMessage.value = error.response?.data?.message || "Failed to load more posts.";
  } finally {
    loadingMore.value = false;
  }
};

let scrollTicking = false;
let lastScrollLoadAt = 0;

const onScroll = () => {
  const scrollTop = window.scrollY || document.documentElement.scrollTop;
  const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
  const fullHeight = document.documentElement.scrollHeight;
  if (scrollTicking) return;
  scrollTicking = true;

  window.requestAnimationFrame(() => {
    if (scrollTop + viewportHeight >= fullHeight - 280) {
      const now = Date.now();
      if (now - lastScrollLoadAt > 400) {
        lastScrollLoadAt = now;
        loadMorePosts();
      }
    }
    scrollTicking = false;
  });
};

const prependPost = (newPost) => {
  posts.value = [newPost, ...posts.value];
};

const refreshPosts = async () => {
  try {
    await loadFeedPage(1, false);
  } catch (error) {
    console.error(error.response?.data || error);
  }
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

const attachFeedRealtime = () => {
  const echo = createEcho();
  if (!echo) return;

  echo.channel('feed').listen('.PostCreated', (payload) => {
    const post = payload?.post || payload;
    if (!post?.id) return;
    if (posts.value.some((item) => item.id === post.id)) return;
    posts.value = [post, ...posts.value];
  });
};

const detachFeedRealtime = () => {
  if (typeof window !== 'undefined' && window.Echo) {
    window.Echo.leave('feed');
  }
};

onMounted(() => {
  loadHomeData();
  attachFeedRealtime();
  window.addEventListener("scroll", onScroll, { passive: true });
});

onBeforeUnmount(() => {
  detachFeedRealtime();
  window.removeEventListener("scroll", onScroll);
});
</script>
