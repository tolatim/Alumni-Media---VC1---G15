import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { createPinia } from 'pinia'

createApp(App)
  .use(createPinia())
  .use(router)
  .use(createPinia())
  .mount('#app')