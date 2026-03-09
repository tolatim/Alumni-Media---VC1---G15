import { computed, ref } from 'vue'

const apiLoadingCount = ref(0)
const routeLoadingCount = ref(0)

export const startApiLoading = () => {
  apiLoadingCount.value += 1
}

export const stopApiLoading = () => {
  apiLoadingCount.value = Math.max(0, apiLoadingCount.value - 1)
}

export const startRouteLoading = () => {
  routeLoadingCount.value += 1
}

export const stopRouteLoading = () => {
  routeLoadingCount.value = Math.max(0, routeLoadingCount.value - 1)
}

export const isGlobalLoading = computed(() => {
  return apiLoadingCount.value > 0 || routeLoadingCount.value > 0
})
