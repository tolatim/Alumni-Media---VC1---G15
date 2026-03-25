import axios from "axios"
const defaultApiHost =
  typeof window !== "undefined" && window.location?.hostname
    ? window.location.hostname
    : "localhost";
const baseURL =
  import.meta.env.VITE_API_URL || `http://${defaultApiHost}:8000/api`;
import { startApiLoading, stopApiLoading } from './loadingService'



const api = axios.create({
  baseURL: baseURL,
  withCredentials: true, // Send credentials with requests
})

// Attach token automatically
api.interceptors.request.use(config => {
  const skipLoading = config?.headers?.['X-Skip-Loading'] === 'true'
  if (!skipLoading) {
    startApiLoading()
    config.__trackLoading = true
  }

  if (typeof window !== 'undefined' && window.Echo && typeof window.Echo.socketId === 'function') {
    config.headers['X-Socket-ID'] = window.Echo.socketId()
  }

  const token = localStorage.getItem("token")
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, error => {
  stopApiLoading()
  return Promise.reject(error)
})

api.interceptors.response.use(response => {
  if (response.config?.__trackLoading) {
    stopApiLoading()
  }
  return response
}, error => {
  if (error.config?.__trackLoading) {
    stopApiLoading()
  }

  const status = error?.response?.status
  if (status === 401) {
    localStorage.removeItem("token")
    localStorage.removeItem("user")
    if (window.location.pathname !== "/login") {
      window.location.href = "/login"
    }
  }

  return Promise.reject(error)
})
export default api
