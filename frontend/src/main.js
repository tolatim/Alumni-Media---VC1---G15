import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { createEcho } from './services/realtime'
import { applyThemeMode, fetchPublicAppearance } from '@/services/appearanceService'

const appearance = await fetchPublicAppearance()
applyThemeMode(appearance.theme_mode)

createApp(App)
  .use(createPinia())
  .use(router)
  .mount('#app')

createEcho()
