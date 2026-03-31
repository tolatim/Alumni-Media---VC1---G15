<template>
  <Navbar />
  <main class="min-h-screen bg-transparent py-6 md:py-8">
    <div class="mx-auto max-w-7xl px-4 sm:px-5">
      <div class="grid grid-cols-12 gap-5">
        <userLeftSideBar :user="currentUser" />

        <centerFeed
          :posts="displayPosts"
          :current-user="currentUser"
          @post-created="prependPost"
          @refresh-posts="refreshPosts"
          @open-trending="openTrendingTag"
        />

        <userRightSideBar
          :posts="posts"
          :suggestions="suggestions"
          :pending-requests="pendingRequests"
          @send-request="sendConnectionRequest"
          @accept-request="acceptConnectionRequest"
          @reject-request="rejectConnectionRequest"
          @open-trending="openTrendingTag"
        />
      </div>

      <div
        v-if="activeTrendingTag"
        class="mt-4 flex items-center justify-between rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-2"
      >
        <p class="text-sm font-semibold text-cyan-800">
          Trending filter: {{ activeTrendingTag }}
        </p>
        <button
          type="button"
          class="rounded-lg border border-cyan-300 bg-white px-3 py-1 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"
          @click="clearTrendingTag"
        >
          Clear
        </button>
      </div>

      <div v-if="!activeTrendingTag && loadingMore" class="mt-5 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Loading more posts...</div>
      <div v-else-if="!activeTrendingTag && !hasMorePosts && posts.length" class="mt-5 text-center text-xs font-semibold uppercase tracking-wide text-slate-400">No more posts</div>

      <p v-if="errorMessage" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-center text-sm font-medium text-rose-700">
        {{ errorMessage }}
      </p>
    </div>
  </main>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import Navbar from "@/components/ui/nav.vue";
import userLeftSideBar from "@/components/ui/userLeftSideBar.vue";
import centerFeed from "@/components/ui/centerFeed.vue";
import userRightSideBar from "@/components/ui/userRightSideBar.vue";
import api from "@/services/api";
import { useFeedStore } from "@/stores/feed";
import { subscribeToPostEvents, setPostHubUserId } from "@/utils/postHub";
import { useRoute } from "vue-router";


const feedStore = useFeedStore();
const route = useRoute();
const currentUser = ref(null);
const suggestions = ref([]);
const pendingRequests = ref([]);
const errorMessage = ref("");
const loadingMore = ref(false);
const activeTrendingTag = ref("");
const trendingPosts = ref([]);
const allPostsCache = ref([]);

const posts = computed(() => feedStore.posts);
const displayPosts = computed(() =>
  activeTrendingTag.value ? trendingPosts.value : posts.value
);
const hasMorePosts = computed(() => feedStore.page < feedStore.lastPage);

let unsubscribePostHub = null;
let sharePostRefreshTimer = null;
let homeRefreshTimer = null;
let postScrollTimer = null;
const HOME_REFRESH_INTERVAL_MS = 3000;

const scrollToTargetPostFromQuery = async () => {
  const targetPostId = Number(route.query.postId || route.query.post_id || 0);
  if (!targetPostId) return;

  await nextTick();
  const target = document.querySelector(`[data-post-id="${targetPostId}"]`);
  if (!target) return;
  target.scrollIntoView({ behavior: "smooth", block: "center" });
};

const queueScrollToTargetPost = () => {
  if (postScrollTimer) {
    window.clearTimeout(postScrollTimer);
  }

  postScrollTimer = window.setTimeout(() => {
    scrollToTargetPostFromQuery();
  }, 120);
};

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

const HASHTAG_PATTERN = /#[A-Za-z0-9_]+/g;

const normalizeTag = (value) => {
  const raw = String(value || "").trim().toLowerCase();
  if (!raw) return "";
  return raw.startsWith("#") ? raw : `#${raw}`;
};

const extractTags = (post) => {
  const text = `${post?.title || ""} ${post?.content || ""}`;
  return (text.match(HASHTAG_PATTERN) || []).map((tag) => tag.toLowerCase());
};

const postHasTag = (post, normalizedTag) => {
  if (!normalizedTag) return false;
  return extractTags(post).includes(normalizedTag);
};

const sortPostsByNewest = (list = []) =>
  [...list].sort(
    (a, b) => new Date(b?.created_at || 0).getTime() - new Date(a?.created_at || 0).getTime()
  );

const loadAllPostsForTrending = async () => {
  const response = await api.get("/posts");
  const payload = response.data;
  const rawPosts = Array.isArray(payload)
    ? payload
    : Array.isArray(payload?.data)
      ? payload.data
      : [];

  allPostsCache.value = sortPostsByNewest(rawPosts);
};

const applyTrendingTagFilter = (tag) => {
  const normalizedTag = normalizeTag(tag);
  if (!normalizedTag) {
    trendingPosts.value = [];
    return;
  }

  trendingPosts.value = allPostsCache.value.filter((post) =>
    postHasTag(post, normalizedTag)
  );
};

const openTrendingTag = async (tag) => {
  const normalizedTag = normalizeTag(tag);
  if (!normalizedTag) return;

  if (activeTrendingTag.value === normalizedTag) {
    clearTrendingTag();
    return;
  }

  activeTrendingTag.value = normalizedTag;
  try {
    await loadAllPostsForTrending();
    applyTrendingTagFilter(normalizedTag);
  } catch (error) {
    trendingPosts.value = [];
    errorMessage.value =
      error.response?.data?.message || "Failed to load trending posts.";
  }
};

const clearTrendingTag = () => {
  activeTrendingTag.value = "";
  trendingPosts.value = [];
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
  if (activeTrendingTag.value) return;
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
  if (activeTrendingTag.value) return;
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
  queueScrollToTargetPost();
});

watch(
  () => route.query.postId || route.query.post_id,
  () => {
    queueScrollToTargetPost();
  }
);

watch(
  () => posts.value.length,
  () => {
    queueScrollToTargetPost();
  }
);

watch(
  () => posts.value,
  () => {
    if (!activeTrendingTag.value) return;
    applyTrendingTagFilter(activeTrendingTag.value);
  },
  { deep: true }
);

onBeforeUnmount(() => {
  window.removeEventListener("scroll", onScroll);
  if (sharePostRefreshTimer) {
    window.clearTimeout(sharePostRefreshTimer);
    sharePostRefreshTimer = null;
  }
  if (unsubscribePostHub) {
    unsubscribePostHub();
    unsubscribePostHub = null;
  }
  if (postScrollTimer) {
    window.clearTimeout(postScrollTimer);
    postScrollTimer = null;
  }
});
</script>
