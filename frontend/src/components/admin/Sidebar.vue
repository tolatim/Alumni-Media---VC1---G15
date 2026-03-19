<template>
  <aside class="w-64 bg-primary-900 text-white flex flex-col">
    <div class="p-6 border-b border-primary-800">
      <div class="flex items-center gap-3">
        <img
          v-if="logoUrl"
          :src="logoUrl"
          alt="App logo"
          class="h-9 w-9 rounded-md bg-white/10 object-cover"
        >
        <span v-else class="text-2xl font-bold">🎓</span>
        <h1 class="text-xl font-semibold">AlumniAdmin</h1>
      </div>
    </div>

    <nav class="flex-1 px-3 py-6">
      <RouterLink
        v-for="item in menu"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-primary-800 transition mb-1"
        active-class="bg-primary-800"
      >
        <!-- You can use heroicons or lucide icons here -->
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>

    <div class="p-4 border-t border-primary-800 text-sm opacity-70">
      Admin Panel
    </div>
  </aside>
</template>
<script setup lang="ts">
import { RouterLink } from 'vue-router'

defineProps<{
  logoUrl?: string | null
}>()

const menu = [
  { icon: 'grid', label: 'Dashboard', to: '/admin' },
  { icon: 'users', label: 'Users',     to: '/users' },
  { icon: 'message-square', label: 'Posts', to: '/posts' },
  { icon: 'alert-triangle', label: 'Reports', to: '/reports' },
  { icon: 'settings', label: 'Settings', to: '/settings' },
]
</script>
