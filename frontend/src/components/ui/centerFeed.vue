<template>
  <div class="col-span-6 space-y-5">

<<<<<<< HEAD
        <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
      </form>
    </div>
=======
    <!-- Create Post -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col gap-3 hover:shadow-md transition">
      <div class="flex items-center gap-3">
        <img
          :src="currentUser?.avatar_url || defaultAvatar"
          class="w-10 h-10 rounded-full object-cover"
        >
        <input
          v-model="postContent"
          type="text"
          placeholder="Share an update..."
          class="flex-1 bg-gray-100 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
        >
      </div>

      <p v-if="errorMessage" class="text-red-500 text-xs">{{ errorMessage }}</p>

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
>>>>>>> feature/admin-side
</template>

<script setup>
</script>