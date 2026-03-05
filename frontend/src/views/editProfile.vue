<template>
  <div class="min-h-screen bg-slate-50/70">
    <!-- Cool modern navbar -->
    <Navbar />
    <div class="max-w-5xl mx-auto px-5 sm:px-8 lg:px-12 py-12 lg:py-16">
      <div
        class="bg-white rounded-3xl shadow-xl border border-gray-100/80 overflow-hidden"
      >
        <!-- Cover + avatar area -->
        <div class="relative">
          <div class="h-56 sm:h-64 lg:h-72 relative group">
            <img
              :src="
                previewCover || profile_photo.cover_url || defaultBackground
              "
              class="absolute inset-0 w-full h-full object-cover"
              alt="Cover"
            />
            <label
              class="absolute inset-0 bg-black/45 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 cursor-pointer"
            >
              <span
                class="text-white text-base sm:text-lg font-medium px-6 py-3 bg-black/40 rounded-full"
              >
                Change cover photo
              </span>
              <input
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleCoverUpload"
              />
            </label>
          </div>

          <div class="absolute left-6 sm:left-10 -bottom-14 sm:-bottom-16 z-10">
            <label class="cursor-pointer group block">
              <img
                :src="previewImage || avatar.avatar_url || defaultAvatar"
                class="w-28 h-28 sm:w-32 sm:h-32 rounded-full border-4 border-white shadow-lg object-cover transition group-hover:ring-4 group-hover:ring-indigo-400/40"
                alt="Avatar"
              />
              <div
                class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
              >
                <span class="text-white text-xs sm:text-sm font-medium"
                  >Edit</span
                >
              </div>
              <input
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleImageUpload"
              />
              
            </label>
           
          </div>
          
        </div>
        <!-- Main content – spacious -->
        <div class="pt-20 lg:pt-24 px-7 sm:px-10 lg:px-14 pb-16 lg:pb-20">
          <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
            Edit profile
          </h2>
          <p class="text-sm text-gray-600 mb-10">
            Update your professional details
          </p>

          <!-- Messages -->
          <div
            v-if="errorMessage"
            class="mb-8 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"
          >
            {{ errorMessage }}
          </div>
          <div
            v-if="successMessage"
            class="mb-8 p-4 bg-green-50 border border-green-200 rounded-xl text-sm text-green-700"
          >
            {{ successMessage }}
          </div>

          <div class="space-y-8 text-sm">
            <div>
              <label class="block text-gray-700 font-medium mb-2"
                >Headline</label
              >
              <input
                v-model="form.headline"
                type="text"
                class="input-modern"
                placeholder="Software Engineer | Vue.js & Laravel"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-medium mb-2"
                  >Current position</label
                >
                <input
                  v-model="form.current_job"
                  type="text"
                  placeholder="Your position"
                  class="input-modern"
                />
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2"
                  >Company</label
                >
                <input
                  v-model="form.company"
                  type="text"
                  placeholder="Your company"
                  class="input-modern"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-gray-700 font-medium mb-2"
                  >Phone</label
                >
                <input
                  v-model="form.phone"
                  type="tel"
                  class="input-modern"
                  placeholder="+855 ..."
                />
              </div>
              <div>
                <label class="block text-gray-700 font-medium mb-2"
                  >Graduate year</label
                >
                <input
                  v-model.number="form.graduate_year"
                  type="number"
                  placeholder="2026"
                  class="input-modern"
                />
              </div>
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2"
                >Location</label
              >
              <input
                v-model="form.location"
                type="text"
                class="input-modern"
                placeholder="Phnom Penh, Cambodia"
              />
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">About</label>
              <textarea
                v-model="form.bio"
                rows="5"
                class="input-modern min-h-[130px]"
                placeholder="Tell your professional story..."
              ></textarea>
            </div>

            <div>
              <label class="block text-gray-700 font-medium mb-2">Skills</label>
              <input
                v-model="form.skills"
                type="text"
                class="input-modern"
                placeholder="Vue, Laravel, Tailwind, JavaScript, Git, MySQL..."
              />
              <p class="mt-2 text-xs text-gray-500">Separate with commas</p>
            </div>
          </div>

          <!-- Buttons – bottom spaced -->
          <div class="mt-16 flex flex-col sm:flex-row gap-4 sm:justify-end">
            <button
              @click="goBack"
              class="px-8 py-3 rounded-2xl border border-gray-300 text-gray-700 font-medium text-sm hover:bg-gray-50 transition"
            >
              Cancel
            </button>
            <button
              @click="saveProfile"
              :disabled="loading"
              class="px-10 py-3 rounded-2xl bg-indigo-600 text-white font-medium text-sm hover:bg-indigo-700 transition disabled:opacity-60 shadow-md"
            >
              {{ loading ? "Saving…" : "Save" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/services/api";
import Navbar from "@/components/ui/nav.vue";
import { updateProfile } from "@/services/authService";
import { getProfile } from "@/services/authService";
import defaultBackground from "@/assets/images/3840x2160-white-solid-color-background.jpg";
import defaultAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const router = useRouter();
const loading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

const previewImage = ref(null);
const selectedImage = ref(null);
const previewCover = ref(null);
const selectedCover = ref(null);

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  selectedImage.value = file;
  previewImage.value = URL.createObjectURL(file);
};

const handleCoverUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;
  selectedCover.value = file;
  previewCover.value = URL.createObjectURL(file);
};

const form = reactive({
  headline: "",
  current_job: "",
  company: "",
  phone: "",
  graduate_year: null,
  location: "",
  bio: "",
  skills: "",
});

const avatar = reactive({ avatar_url: "" });
const profile_photo = reactive({ cover_url: "" });

const loadProfile = async () => {
  errorMessage.value = "";
  try {
    const userString = JSON.parse(localStorage.getItem("user"));
    if (!userString) throw new Error("No user in localStorage");
    const response = await getProfile(userString.id);
    const user = response.data.user;
    form.headline = user.headline || "";
    form.current_job = user.current_job || "";
    form.company = user.company || "";
    form.phone = user.phone || "";
    form.graduate_year = user.graduate_year || "";
    form.location = user.location || "";
    form.bio = user.bio || "";
    form.skills = user.skills || "";
    avatar.avatar_url = user.avatar_url || "";
    profile_photo.cover_url = user.cover_url || "";
  } catch (err) {
    errorMessage.value = "Failed to load your profile.";
  }
};

const saveProfile = async () => {
  loading.value = true;
  errorMessage.value = "";
  successMessage.value = "";
  try {
    const formData = new FormData();
    formData.append("_method", "PATCH");
    if (selectedImage.value) formData.append("avatar", selectedImage.value);
    if (selectedCover.value)
      formData.append("profile_photo", selectedCover.value);
    formData.append("current_job", form.current_job);
    formData.append("headline", form.headline);
    formData.append("company", form.company);
    formData.append("phone", form.phone);
    formData.append("graduate_year", form.graduate_year);
    formData.append("location", form.location);
    formData.append("bio", form.bio);
    formData.append("skills", form.skills);
    const token = localStorage.getItem("token");
    const response = await updateProfile(formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data",
      },
    });
    console.log(response.data.user);
    successMessage.value = response.data.message;
  } catch (err) {
    errorMessage.value = "Failed to save profile.";
  } finally {
    loading.value = false;
  }
};

const goBack = () => router.back();

onMounted(loadProfile);
</script>

<style scoped>
.input-modern {
  @apply w-full px-4 py-3 text-sm rounded-xl border border-gray-200
         focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100
         hover:border-gray-300 transition outline-none;
}
</style>