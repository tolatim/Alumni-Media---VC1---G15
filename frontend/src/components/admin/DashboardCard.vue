<script setup lang="ts">
defineProps<{
  title: string
  value: string
  change: string
  color?: 'green' | 'red' | 'blue' | 'gray'
}>()

const colorMap = {
  green: 'text-green-600 dark:text-green-400',
  red:   'text-red-600 dark:text-red-400',
  blue:  'text-blue-600 dark:text-blue-400',
  gray:  'text-gray-600 dark:text-gray-400',
}
</script>

<template>
  <div class="bg-white dark:bg-gray-900 rounded-xl shadow p-6">
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">{{ title }}</p>
    <p class="text-2xl font-bold">{{ value }}</p>
    <p :class="['text-sm mt-2', colorMap[color || 'gray']]">
      {{ change }}
    </p>
  </div>
</template>