<template>
  <Transition name="fade">
    <div v-if="isGlobalLoading" class="global-loader">
      <div class="global-loader__bar">
        <span class="global-loader__line"></span>
      </div>
      <div class="global-loader__veil"></div>
    </div>
  </Transition>
</template>

<script setup>
import { isGlobalLoading } from '@/services/loadingService'
</script>

<style scoped>
.global-loader {
  pointer-events: none;
  position: fixed;
  inset: 0;
  z-index: 9999;
}

.global-loader__bar {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(15, 23, 42, 0.12);
  overflow: hidden;
}

.global-loader__line {
  position: absolute;
  top: 0;
  left: -35%;
  width: 35%;
  height: 100%;
  background: linear-gradient(90deg, #0f766e, #14b8a6, #5eead4);
  animation: loading-slide 1s ease-in-out infinite;
}

.global-loader__veil {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at center, rgba(255, 255, 255, 0.16), rgba(255, 255, 255, 0.04));
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes loading-slide {
  0% {
    left: -35%;
  }
  100% {
    left: 100%;
  }
}
</style>
