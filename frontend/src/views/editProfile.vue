<template>
  <div
    class="min-h-screen bg-gradient-to-br from-gray-100 to-gray-200 py-10 px-4"
  >
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <!-- <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
          Edit Profile
        </h2>
      </div> -->
     <!-- Header -->
<div class="mb-8">
  <h2 class="text-3xl font-semibold text-gray-900 tracking-tight">
    Edit Profile
  </h2>
  <div class="mt-3 h-px w-24 bg-gray-300"></div>
</div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <!-- Cover + Avatar Upload -->
        <div class="relative">
          <div class="relative h-60 w-full group">
            <!-- Cover Image -->
            <img
              :src="previewCover || profile_photo.cover_url || defaultBackground"
              class="w-full h-full object-cover"
            />

            <!-- Overlay -->
            <label
              class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-pointer"
            >
              <span class="text-white font-medium">Change Cover</span>

              <input
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleCoverUpload"
              />
            </label>
          </div>
          <div class="absolute left-1/2 -bottom-12 transform -translate-x-1/2">
            <label class="cursor-pointer group relative block">
              <img
                :src="
                  previewImage || avatar.avatar_url || defaultAvatar
                "
                class="w-28 h-28 rounded-full border-4 border-white shadow-md object-cover transition group-hover:opacity-80"
              />

              <!-- Overlay -->
              <div
                class="absolute inset-0 bg-black bg-opacity-40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition"
              >
                <span class="text-white text-sm font-medium">Change</span>
              </div>

              <!-- Hidden File Input -->
              <input
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleImageUpload"
              />
            </label>
          </div>
        </div>

        <div class="pt-16 px-8 pb-8">
          <!-- Messages -->
          <p
            v-if="errorMessage"
            class="text-sm bg-red-50 text-red-600 px-4 py-2 rounded-lg mb-4"
          >
            {{ errorMessage }}
          </p>

          <p
            v-if="successMessage"
            class="text-sm bg-green-50 text-green-600 px-4 py-2 rounded-lg mb-4"
          >
            {{ successMessage }}
          </p>

          <!-- Grid Section -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="input-label">Headline / Job Title</label>
              <input v-model="form.headline" type="text" class="input-style" />
            </div>

            <div>
              <label class="input-label">Current Job</label>
              <input
                v-model="form.current_job"
                type="text"
                class="input-style"
              />
            </div>

            <div>
              <label class="input-label">Company</label>
              <input v-model="form.company" type="text" class="input-style" />
            </div>

            <div>
              <label class="input-label">Phone</label>
              <input v-model="form.phone" type="text" class="input-style" />
            </div>

            <div>
              <label class="input-label">Graduate Year</label>
              <input
                v-model.number="form.graduate_year"
                type="number"
                class="input-style"
              />
            </div>

            <div>
              <label class="input-label">Location</label>
              <input v-model="form.location" type="text" class="input-style" />
            </div>
          </div>

          <!-- Bio -->
          <div class="mt-8">
            <label class="input-label">About / Bio</label>
            <textarea
              v-model="form.bio"
              rows="4"
              class="input-style resize-none"
            ></textarea>
          </div>

          <!-- Skills -->
          <div class="mt-6">
            <label class="input-label">Skills</label>
            <input
              v-model="form.skills"
              type="text"
              placeholder="Example: Vue, Laravel, MySQL"
              class="input-style"
            />
            <p class="text-xs text-gray-500 mt-1">
              Use comma to separate skills.
            </p>
          </div>

          <!-- Buttons -->
          <div class="flex justify-end mt-8 space-x-3">
            <button
              @click="goBack"
              class="px-6 py-2 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 transition"
            >
              Cancel
            </button>

            <button
              @click="saveProfile"
              :disabled="loading"
              class="px-6 py-2 rounded-xl bg-gradient-to-r from-teal-500 to-emerald-600 text-white font-medium shadow-md hover:shadow-lg hover:scale-[1.02] transition disabled:opacity-60"
            >
              {{ loading ? "Saving..." : "Save Changes" }}
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
import { updateProfile } from "@/services/authService";
import { getProfile } from "@/services/authService";
import defaultBackground from "@/assets/images/3840x2160-white-solid-color-background.jpg";
import defaultAvatar from "@/assets/images/blank-profile-picture-973460_1280.webp";

const router = useRouter();
const loading = ref(false);
const errorMessage = ref("");
const successMessage = ref("");

// profile
const previewImage = ref(null);
const selectedImage = ref(null);

// cover
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
const avatar = reactive({
  avatar_url: "",
});
const profile_photo = reactive({
  cover_url: "",
});
const loadProfile = async () => {
  errorMessage.value = "";
  try {
    const userString = JSON.parse(localStorage.getItem("user"));
    if (!userString) throw new Error("No user in localStorage");

    const response = await getProfile(userString.id);
    const user = response.data.user;
    form.headline = user.headline || "Not provided";
    form.current_job = user.current_job || "Not provided";
    form.company = user.company || "Not provided";
    form.phone = user.phone || "Not provided";
    form.graduate_year = user.graduate_year || 2000;
    form.location = user.location || "Not provided";
    form.bio = user.bio || "Not provided";
    form.skills = user.skills || "Not provided";
    avatar.avatar_url = user.avatar_url || defaultAvatar;
    profile_photo.cover_url = user.cover_url || defaultBackground;
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
    formData.append("_method", "PATCH"); // simulate PATCH like Postman
    if (selectedImage.value) {
      formData.append("avatar", selectedImage.value);
    }
    if (selectedCover.value) {
      formData.append("profile_photo", selectedCover.value);
    }
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
        'Content-Type': 'multipart/form-data'
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
.input-label {
  @apply block text-sm font-medium text-gray-600 mb-2;
}

.input-style {
  @apply w-full border border-gray-300 rounded-xl px-4 py-2.5 
  focus:ring-2 focus:ring-teal-500 focus:border-teal-500 
  outline-none transition shadow-sm;
}
</style>