import axios from "axios"
const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
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
  return Promise.reject(error)
})
export default api
