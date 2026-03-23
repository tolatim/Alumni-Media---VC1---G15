<template>
  <button
    class="save-btn"
    :class="{ saved: isSaved }"
    @click.prevent="store.toggleSave(postId)"
    :title="isSaved ? 'Remove from saved' : 'Save post'"
  >
    <svg
      width="16" height="16" viewBox="0 0 24 24"
      :fill="isSaved ? 'currentColor' : 'none'"
      stroke="currentColor" stroke-width="2"
      stroke-linecap="round" stroke-linejoin="round"
    >
      <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
    </svg>
    <span>{{ isSaved ? 'Saved' : 'Save' }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue'
import { useSavedPostStore } from '@/stores/savedPostStore'

const props = defineProps({
  postId: { type: Number, required: true }
})

const store   = useSavedPostStore()
const isSaved = computed(() => store.isSaved(props.postId))
</script>

<style scoped>
.save-btn {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  padding: .4rem .85rem;
  border-radius: 8px;
  border: 1px solid #e4e4e7;
  background: transparent;
  color: #71717a;
  font-size: .83rem;
  font-weight: 500;
  cursor: pointer;
  transition: all .2s;
}
.save-btn:hover {
  border-color: #5b4cf5;
  color: #5b4cf5;
  background: #ede9fe;
}
.save-btn.saved {
  background: #ede9fe;
  border-color: #5b4cf5;
  color: #5b4cf5;
}
</style>