<template>
  <Transition name="fade">
    <div v-if="isPageLoading" class="page-loader" aria-live="polite">
      <div class="page-loader__bar">
        <span class="page-loader__line"></span>
      </div>
      <div class="page-loader__content">
        <div class="page-loader__pulse"></div>
        <p class="page-loader__text">Loading content…</p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { isPageLoading } from '@/services/loadingService'
</script>

<style scoped>
.page-loader {
  position: sticky;
  top: 0;
  z-index: 20;
  padding: 0.75rem 1rem 0.5rem;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(248, 250, 252, 0.6));
  backdrop-filter: blur(6px);
  border-bottom: 1px solid rgba(148, 163, 184, 0.25);
}

.page-loader__bar {
  position: relative;
  height: 3px;
  background: rgba(15, 23, 42, 0.12);
  overflow: hidden;
  border-radius: 999px;
}

.page-loader__line {
  position: absolute;
  inset: 0;
  width: 35%;
  background: linear-gradient(90deg, #0f766e, #14b8a6, #5eead4);
  animation: loading-slide 1s ease-in-out infinite;
}

.page-loader__content {
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #0f172a;
}

.page-loader__pulse {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  background: #0f766e;
  box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.7);
  animation: pulse 1.2s ease-in-out infinite;
}

.page-loader__text {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #475569;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes loading-slide {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(300%);
  }
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.6);
  }
  70% {
    box-shadow: 0 0 0 8px rgba(20, 184, 166, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(20, 184, 166, 0);
  }
}
</style>
