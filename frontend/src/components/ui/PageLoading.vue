<template>
  <Transition name="fade">
    <div v-if="isPageLoading" class="page-loader" aria-live="polite">
      <div class="page-loader__backdrop"></div>
      <div class="page-loader__popup" role="status" aria-atomic="true">
        <div class="page-loader__badge">Please wait</div>
        <div class="page-loader__orbital">
          <span class="page-loader__ring page-loader__ring--outer"></span>
          <span class="page-loader__ring page-loader__ring--inner"></span>
          <span class="page-loader__core"></span>
        </div>
        <p class="page-loader__title">{{ currentMessage.title }}</p>
        <p class="page-loader__text">{{ currentMessage.text }}</p>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { isPageLoading } from '@/services/loadingService'

const loadingMessages = [
  { title: 'Getting things ready', text: 'We are preparing your content so it feels smooth and fast.' },
  { title: 'Almost there', text: 'Thanks for waiting. We are pulling in the latest updates.' },
  { title: 'Finishing up', text: 'Just a quick moment while everything settles into place.' },
]

const messageIndex = ref(0)
let rotateTimer = null

const currentMessage = computed(() => loadingMessages[messageIndex.value] || loadingMessages[0])

const stopRotation = () => {
  if (!rotateTimer) return
  clearInterval(rotateTimer)
  rotateTimer = null
}

const startRotation = () => {
  stopRotation()
  rotateTimer = setInterval(() => {
    messageIndex.value = (messageIndex.value + 1) % loadingMessages.length
  }, 1800)
}

watch(isPageLoading, (loading) => {
  if (!loading) {
    stopRotation()
    messageIndex.value = 0
    return
  }

  startRotation()
}, { immediate: true })

onBeforeUnmount(() => {
  stopRotation()
})
</script>

<style scoped>
.page-loader {
  position: fixed;
  inset: 0;
  z-index: 90;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  pointer-events: none;
}

.page-loader__backdrop {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at top, rgba(34, 197, 94, 0.08), transparent 38%),
    radial-gradient(circle at bottom, rgba(14, 165, 233, 0.08), transparent 42%),
    rgba(15, 23, 42, 0.18);
  backdrop-filter: blur(10px);
}

.page-loader__popup {
  position: relative;
  width: min(100%, 22rem);
  padding: 1.25rem 1.25rem 1.1rem;
  border-radius: 1.5rem;
  border: 1px solid rgba(226, 232, 240, 0.9);
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 24px 80px rgba(15, 23, 42, 0.18);
  text-align: center;
}

.page-loader__badge {
  display: inline-flex;
  margin-bottom: 0.85rem;
  border-radius: 999px;
  background: linear-gradient(90deg, #dcfce7, #dbeafe);
  padding: 0.35rem 0.7rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #0f766e;
}

.page-loader__orbital {
  position: relative;
  width: 4.5rem;
  height: 4.5rem;
  margin: 0 auto 0.9rem;
}

.page-loader__ring {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  border: 4px solid transparent;
}

.page-loader__ring--outer {
  border-top-color: #14b8a6;
  border-right-color: #38bdf8;
  animation: spin 1.05s linear infinite;
}

.page-loader__ring--inner {
  inset: 0.55rem;
  border-bottom-color: #0f766e;
  border-left-color: #67e8f9;
  animation: spin-reverse 0.9s linear infinite;
}

.page-loader__core {
  position: absolute;
  inset: 1.5rem;
  border-radius: 999px;
  background: radial-gradient(circle, #67e8f9 0%, #14b8a6 55%, #0f766e 100%);
  box-shadow: 0 0 0 10px rgba(20, 184, 166, 0.08);
  animation: breathe 1.4s ease-in-out infinite;
}

.page-loader__title {
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.page-loader__text {
  margin-top: 0.4rem;
  font-size: 0.86rem;
  line-height: 1.45;
  color: #475569;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.22s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

@keyframes spin-reverse {
  0% {
    transform: rotate(360deg);
  }
  100% {
    transform: rotate(0deg);
  }
}

@keyframes breathe {
  0% {
    transform: scale(0.94);
    box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.16);
  }
  50% {
    transform: scale(1);
    box-shadow: 0 0 0 12px rgba(20, 184, 166, 0.06);
  }
  100% {
    transform: scale(0.94);
    box-shadow: 0 0 0 0 rgba(20, 184, 166, 0.16);
  }
}
</style>
