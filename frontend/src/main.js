import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './style.css'
import { createPinia } from 'pinia'
import { applyThemeMode, fetchPublicAppearance } from '@/services/appearanceService'

const appearance = await fetchPublicAppearance()
applyThemeMode(appearance.theme_mode)

createApp(App)
  .use(router)
  .use(createPinia())
  .mount('#app')
