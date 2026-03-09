<template>
  <div>
    <GlobalLoading />
    <router-view />
  </div>
</template>

<script setup>
import GlobalLoading from '@/components/ui/GlobalLoading.vue'
</script>
