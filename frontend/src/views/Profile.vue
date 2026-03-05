<template>
  <Navbar />

  <main class="bg-[#F3F2EF] min-h-screen pb-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Error -->
      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6 shadow-sm"
      >
        {{ errorMessage }}
      </div>

      <!-- Main Profile Card – LinkedIn style -->
      <div
        v-if="user"
        class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden"
      >
        <!-- Cover -->
        <div class="relative h-40 sm:h-52 lg:h-64">
          <img
            :src="coverImage"
            class="absolute inset-0 w-full h-full object-cover"
            alt="Cover photo"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-black/10 to-transparent pointer-events-none"></div>
        </div>

        <!-- Content -->
        <div class="relative px-5 sm:px-8 lg:px-10 pb-10 pt-16 sm:pt-20">
          <!-- Avatar -->
          <div class="absolute -top-16 sm:-top-20 left-5 sm:left-8">
            <img
              :src="user.avatar_url || defaultAvatar"
              class="w-28 h-28 sm:w-36 sm:h-36 rounded-full border-4 border-white shadow-lg object-cover"
              alt="Profile picture"
            />
          </div>

          <div class="pt-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#000000] tracking-tight">
              {{ user.first_name }} {{ user.last_name }}
            </h1>

            <p class="text-base sm:text-lg text-[#000000] font-medium mt-1">
              {{ user.headline || user.current_job || "Add your headline" }}
            </p>

            <div class="mt-1 flex items-center gap-1.5 text-sm text-[#585858]">
              <span>{{ user.location || "Location not added" }}</span>
              <span class="text-gray-400">•</span>
              <span class="text-gray-500">Contact info</span>
            </div>

            <div v-if="isOwnProfile" class="mt-5">
              <RouterLink
                to="/profile/edit"
                class="inline-flex items-center px-5 py-2 bg-[#0A66C2] hover:bg-[#004182] 
                     text-white text-sm font-medium rounded-full transition-colors"
              >
                Edit profile
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- About + Info + Skills – card style like LinkedIn sections -->
      <div
        v-if="user"
        class="mt-6 bg-white rounded-xl shadow border border-gray-200 p-6 sm:p-8"
      >
        <h2 class="text-xl font-semibold text-[#000000] mb-5">About</h2>
        <p class="text-sm sm:text-base text-[#191919] leading-relaxed whitespace-pre-line">
          {{ user.bio || "No about information added yet." }}
        </p>

        <!-- Skills -->
        <div class="mt-10">
          <h3 class="text-lg font-semibold text-[#000000] mb-4">Skills</h3>
          <div v-if="skillsList.length" class="flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="inline-flex items-center px-3.5 py-1.5 bg-[#E9EEF6] text-[#0A66C2] text-sm font-medium rounded-full"
            >
              {{ skill }}
            </span>
          </div>
          <p v-else class="text-sm text-gray-500 italic">No skills added</p>
        </div>

        <!-- Quick info grid -->
        <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="item in quickInfo" :key="item.label" class="bg-[#F9FAFB] rounded-lg p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500 font-medium mb-1">
              {{ item.label }}
            </p>
            <p class="text-sm font-medium text-[#191919]">
              {{ item.value || "—" }}
            </p>
          </div>
        </div>
      </div>

      <!-- Change Password – discreet LinkedIn-like security section -->
      <div
        v-if="user && isOwnProfile"
        class="mt-6 bg-white rounded-xl shadow border border-gray-200 p-6 sm:p-8"
      >
        <h2 class="text-xl font-semibold text-[#000000] mb-6">Change Password</h2>

        <p v-if="passwordError" class="text-sm text-red-600 bg-red-50 p-4 rounded-lg mb-6">
          {{ passwordError }}
        </p>
        <p v-if="passwordMessage" class="text-sm text-green-600 bg-green-50 p-4 rounded-lg mb-6">
          {{ passwordMessage }}
        </p>

        <form @submit.prevent="changePassword" class="space-y-5 max-w-md">
          <div>
            <label class="block text-sm text-gray-600 mb-1.5">Current password</label>
            <input
              v-model="oldPassword"
              type="password"
              required
              class="w-full border border-gray-300 rounded-md px-4 py-2.5 focus:border-[#0A66C2] focus:ring-2 focus:ring-blue-100 outline-none transition"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1.5">New password</label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="6"
              class="w-full border border-gray-300 rounded-md px-4 py-2.5 focus:border-[#0A66C2] focus:ring-2 focus:ring-blue-100 outline-none transition"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1.5">Confirm new password</label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              minlength="6"
              class="w-full border border-gray-300 rounded-md px-4 py-2.5 focus:border-[#0A66C2] focus:ring-2 focus:ring-blue-100 outline-none transition"
            />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="mt-4 px-6 py-2.5 bg-[#0A66C2] hover:bg-[#004182] text-white font-medium rounded-full transition disabled:opacity-60"
          >
            {{ passwordLoading ? "Updating..." : "Update password" }}
          </button>
        </form>
      </div>

    </div>
  </main>
</template>

<script setup>
// ────────────────────────────────────────────────
// Your original script remains 100% unchanged
// ────────────────────────────────────────────────

import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import Navbar from "@/components/ui/nav.vue";
import api from "@/services/api";
import { getUser } from "@/services/authService";
import defaultBackground from "@/assets/images/3840x2160-white-solid-color-background.jpg";
import defaultAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const route = useRoute();
const user = ref(null);
const errorMessage = ref("");
const loggedInUser = ref(null);

const oldPassword = ref("");
const newPassword = ref("");
const newPasswordConfirmation = ref("");
const passwordLoading = ref(false);
const passwordError = ref("");
const passwordMessage = ref("");

const coverImage = computed(() => {
  return user.value?.cover_url || defaultBackground;
});

const isOwnProfile = computed(() => {
  return loggedInUser.value?.id === user.value?.id;
});

const skillsList = computed(() => {
  const skills = user.value?.skills;
  if (!skills) return [];
  return skills
    .split(",")
    .map((item) => item.trim())
    .filter(Boolean);
});

const quickInfo = computed(() => [
  { label: "First Name", value: user.value?.first_name },
  { label: "Last Name", value: user.value?.last_name },
  { label: "Email", value: user.value?.email },
  { label: "Phone", value: user.value?.phone },
  { label: "Current Job", value: user.value?.current_job },
  { label: "Company", value: user.value?.company },
  { label: "Graduate Year", value: user.value?.graduate_year },
]);

const loadLoggedInUser = async () => {
  const user_id = JSON.parse(localStorage.getItem("user")).id;
  try {
    const response = await getUser(user_id);
    loggedInUser.value = response.data.user;
  } catch {
    loggedInUser.value = null;
  }
};

const loadProfile = async (id) => {
  errorMessage.value = "";

  try {
    const response = await getUser(id);
    user.value = response.data.user;
  } catch {
    user.value = null;
    errorMessage.value = "Profile not found or failed to load.";
  }
};

const changePassword = async () => {
  passwordError.value = "";
  passwordMessage.value = "";

  if (newPassword.value !== newPasswordConfirmation.value) {
    passwordError.value = "New password confirmation does not match.";
    return;
  }

  passwordLoading.value = true;
  try {
    await api.post("/profile/change-password", {
      old_password: oldPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirmation.value,
    });

    oldPassword.value = "";
    newPassword.value = "";
    newPasswordConfirmation.value = "";
    passwordMessage.value = "Password changed successfully.";
  } catch (error) {
    passwordError.value =
      error.response?.data?.message || "Failed to change password.";
  } finally {
    passwordLoading.value = false;
  }
};

watch(
  () => route.params.id,
  (id) => {
    if (id) loadProfile(id);
  }
);

onMounted(async () => {
  await loadLoggedInUser();
  if (route.params.id) {
    await loadProfile(route.params.id);
  }
});
</script>