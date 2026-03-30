<template>
  <aside class="w-full bg-primary-900 text-white lg:w-64 lg:flex lg:flex-col">
    <div class="border-b border-primary-800 p-4 lg:p-6">
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

    <nav class="flex gap-2 overflow-x-auto px-3 py-3 lg:flex-1 lg:flex-col lg:py-6">
      <RouterLink
        v-for="item in menu"
        :key="item.to"
        :to="item.to"
        class="mb-0 inline-flex items-center gap-3 whitespace-nowrap rounded-lg px-4 py-2.5 transition hover:bg-primary-800 lg:mb-1"
        active-class="bg-primary-800"
      >
        <!-- You can use heroicons or lucide icons here -->
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>

    <div class="hidden border-t border-primary-800 p-4 text-sm opacity-70 lg:block">
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
