import { computed, ref } from 'vue'

const apiLoadingCount = ref(0)
const routeLoadingCount = ref(0)
const visibleLoading = ref(false)
const LOADER_DELAY_MS = 180
let showLoaderTimer = null

const getActiveLoadingCount = () => apiLoadingCount.value + routeLoadingCount.value

const syncVisibleLoading = () => {
  const hasActiveLoading = getActiveLoadingCount() > 0

  if (!hasActiveLoading) {
    if (showLoaderTimer) {
      clearTimeout(showLoaderTimer)
      showLoaderTimer = null
    }
    visibleLoading.value = false
    return
  }

  if (visibleLoading.value || showLoaderTimer) {
    return
  }

  showLoaderTimer = setTimeout(() => {
    showLoaderTimer = null
    visibleLoading.value = getActiveLoadingCount() > 0
  }, LOADER_DELAY_MS)
}

export const startApiLoading = () => {
  apiLoadingCount.value += 1
  syncVisibleLoading()
}

export const stopApiLoading = () => {
  apiLoadingCount.value = Math.max(0, apiLoadingCount.value - 1)
  syncVisibleLoading()
}

export const startRouteLoading = () => {
  routeLoadingCount.value += 1
  syncVisibleLoading()
}

export const stopRouteLoading = () => {
  routeLoadingCount.value = Math.max(0, routeLoadingCount.value - 1)
  syncVisibleLoading()
}

export const isGlobalLoading = computed(() => visibleLoading.value)
