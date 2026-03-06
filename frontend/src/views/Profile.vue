<template>
  <Navbar />

  <main class="bg-slate-100 min-h-screen py-10">
    <div class="max-w-5xl mx-auto px-6">

      <!-- Error -->
      <div
        v-if="errorMessage"
        class="bg-red-50 border border-red-200 text-red-600 rounded-xl p-4 mb-6"
      >
        {{ errorMessage }}
      </div>

      <!-- PROFILE CARD -->
      <div
        v-if="user"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
      >
        <!-- Cover -->
        <div class="h-64 w-full relative">
          <img :src="coverImage" class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-black/10"></div>
        </div>

        <!-- Content -->
        <div class="px-8 pb-8 relative">
          <!-- Avatar -->
          <div class="absolute -top-20 left-8">
            <img
              :src="user.avatar_url || '@/assets/images/blank-profile-picture-973460_1280.webp'"
              class="w-36 h-36 rounded-full border-4 border-white object-cover shadow-md"
            />
          </div>

          <div class="pt-24">
            <h1 class="text-3xl font-semibold text-gray-900">
              {{ user.first_name + " " + user.last_name }}
            </h1>

            <p class="text-gray-600 mt-2">
              {{ user.headline || user.current_job || "Add your headline" }}
            </p>

            <p class="text-sm text-gray-400 mt-1">
              {{ user.location || "No location added" }}
            </p>

            <div v-if="isOwnProfile" class="mt-6">
              <RouterLink
                to="/profile/edit"
                class="inline-flex items-center px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition"
              >
                Edit Profile
              </RouterLink>
            </div>
          </div>
        </div>
      </div>

      <!-- USER INFORMATION -->
      <div
        v-if="user"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 mt-8 p-8"
      >
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          User Information
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">First Name</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.first_name || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Last Name</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.last_name || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Email</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.email || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Phone</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.phone || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Current Job</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.current_job || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Company</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.company || "Not provided" }}
            </p>
          </div>

          <div class="bg-slate-50 rounded-lg p-4">
            <p class="text-gray-500 text-xs uppercase tracking-wide">Graduate Year</p>
            <p class="text-gray-900 font-medium mt-1">
              {{ user.graduate_year || "Not provided" }}
            </p>
          </div>

        </div>

        <!-- Skills -->
        <div class="mt-8">
          <p class="text-gray-500 text-xs uppercase tracking-wide mb-3">
            Skills
          </p>

          <div v-if="skillsList.length" class="flex flex-wrap gap-2">
            <span
              v-for="skill in skillsList"
              :key="skill"
              class="text-xs bg-teal-50 text-teal-700 px-3 py-1.5 rounded-full border border-teal-100"
            >
              {{ skill }}
            </span>
          </div>

          <p v-else class="text-sm text-gray-400">Not provided</p>
        </div>

        <!-- About -->
        <div class="mt-8">
          <p class="text-gray-500 text-xs uppercase tracking-wide mb-2">
            About
          </p>
          <p class="text-gray-700 text-sm leading-relaxed">
            {{ user.bio || "Not provided" }}
          </p>
        </div>

      </div>

      <!-- CHANGE PASSWORD -->
      <div
        v-if="user && isOwnProfile"
        class="bg-white rounded-2xl shadow-sm border border-gray-100 mt-8 p-8"
      >
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          Security
        </h2>

        <p v-if="passwordError" class="text-sm text-red-500 mb-4">
          {{ passwordError }}
        </p>
        <p v-if="passwordMessage" class="text-sm text-green-600 mb-4">
          {{ passwordMessage }}
        </p>

        <form @submit.prevent="changePassword" class="space-y-5">

          <div>
            <label class="block text-sm text-gray-600 mb-1">
              Old Password
            </label>
            <input
              v-model="oldPassword"
              type="password"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">
              New Password
            </label>
            <input
              v-model="newPassword"
              type="password"
              required
              minlength="6"
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-600 mb-1">
              Confirm New Password
            </label>
            <input
              v-model="newPasswordConfirmation"
              type="password"
              required
              minlength="6"
              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-teal-500 outline-none"
            />
          </div>

          <button
            type="submit"
            :disabled="passwordLoading"
            class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium transition disabled:opacity-60"
          >
            {{ passwordLoading ? "Updating..." : "Update Password" }}
          </button>

        </form>
      </div>

    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { useRoute } from "vue-router";
import Navbar from "@/components/ui/nav.vue";
import api from "@/services/api";
import { getUser } from "@/services/authService";
import defaultBackground from "@/assets/images/3840x2160-white-solid-color-background.jpg";
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
