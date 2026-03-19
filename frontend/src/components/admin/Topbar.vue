<script setup lang="ts">
</script>

<template>
  <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-4">
      <input
        type="text"
        placeholder="Search alumni, posts, or reports..."
        class="w-96 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
    </div>

    <div class="flex items-center gap-6">
      <button class="relative">
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5">12</span>
        ⚠
      </button>
      <div class="flex items-center gap-3">
        <img src="https://i.pravatar.cc/40" class="w-9 h-9 rounded-full" alt="Admin" />
        <span>Admin Panel ▼</span>
      </div>
    </div>
  </header>
</template>