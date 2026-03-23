import axios from 'axios'

const baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

const isThemeMode = (value) => value === 'light' || value === 'dark'

export const applyThemeMode = (mode) => {
  const resolved = isThemeMode(mode) ? mode : 'light'
  const root = document.documentElement
  if (!root) return resolved

  root.classList.toggle('dark', resolved === 'dark')
  return resolved
}

export const fetchPublicAppearance = async () => {
  try {
    const response = await axios.get(`${baseURL}/settings/appearance`)
    const data = response?.data?.data || {}
    const themeMode = isThemeMode(data?.theme_mode) ? data.theme_mode : 'light'
    return {
      theme_mode: themeMode,
      logo_url: data?.logo_url || null,
    }
  } catch {
    return {
      theme_mode: 'light',
      logo_url: null,
    }
  }
}
