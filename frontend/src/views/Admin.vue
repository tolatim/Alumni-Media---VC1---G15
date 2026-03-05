<template>
  <div :class="dark ? 'dark' : ''">
    <div class="min-h-screen flex bg-gray-100 dark:bg-[#0f172a] transition-colors duration-300">

      <!-- Mobile Overlay -->
      <div
        v-if="mobileOpen"
        @click="mobileOpen = false"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 lg:hidden"
      ></div>

      <!-- Sidebar -->
      <aside
        :class="[
          'fixed lg:static z-50 h-full transition-all duration-300 flex flex-col backdrop-blur-xl border-r',
          dark
            ? 'bg-white/5 border-white/10'
            : 'bg-white/70 border-gray-200',
          collapsed ? 'w-20' : 'w-64',
          mobileOpen ? 'left-0' : '-left-64 lg:left-0'
        ]"
      >

        <!-- Logo -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-inherit">
          <span v-if="!collapsed" class="font-bold text-lg bg-gradient-to-r from-indigo-500 to-purple-500 bg-clip-text text-transparent">
            Admin
          </span>

          <button @click="toggleSidebar" class="p-1 rounded hover:bg-white/20 transition">
            <PanelLeft size="20" />
          </button>
        </div>

        <!-- Menu -->
        <nav class="flex-1 p-3 space-y-2">

          <SidebarLink to="/admin" :collapsed="collapsed">
            <LayoutDashboard size="18" />
            <span>Dashboard</span>
          </SidebarLink>

          <SidebarLink to="/admin/users" :collapsed="collapsed">
            <Users size="18" />
            <span>Users</span>
          </SidebarLink>

          <SidebarLink to="/admin/posts" :collapsed="collapsed">
            <FileText size="18" />
            <span>Posts</span>
          </SidebarLink>

        </nav>

        <!-- Bottom -->
        <div class="p-4 border-t border-inherit space-y-3">

          <!-- Dark Mode Toggle -->
          <button
            @click="dark = !dark"
            class="flex items-center gap-3 w-full px-3 py-2 rounded-xl hover:bg-white/20 transition"
          >
            <Moon v-if="!dark" size="18" />
            <Sun v-else size="18" />
            <span v-if="!collapsed">Theme</span>
          </button>

          <button
            class="flex items-center gap-3 w-full px-3 py-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:opacity-90 transition"
          >
            <LogOut size="18" />
            <span v-if="!collapsed">Logout</span>
          </button>

        </div>
      </aside>

      <!-- Main -->
      <div class="flex-1 flex flex-col">

        <!-- Topbar -->
        <header class="h-16 flex items-center justify-between px-6 backdrop-blur-xl border-b border-gray-200 dark:border-white/10 bg-white/60 dark:bg-white/5 transition">

          <button
            @click="mobileOpen = true"
            class="lg:hidden"
          >
            <Menu size="22" />
          </button>

          <h2 class="font-semibold text-gray-800 dark:text-gray-200">
            Admin Panel
          </h2>

          <div></div>

        </header>

        <!-- Content -->
        <main class="flex-1 p-6 text-gray-800 dark:text-gray-200">
          <router-view />
        </main>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRoute } from "vue-router"
import {
  LayoutDashboard,
  Users,
  FileText,
  LogOut,
  PanelLeft,
  Moon,
  Sun,
  Menu
} from "lucide-vue-next"

const collapsed = ref(false)
const mobileOpen = ref(false)
const dark = ref(false)

const toggleSidebar = () => {
  collapsed.value = !collapsed.value
}

const route = useRoute()

// Auto close mobile on route change
watch(route, () => {
  mobileOpen.value = false
})
</script>