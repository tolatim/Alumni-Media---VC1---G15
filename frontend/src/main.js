import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
import { createEcho } from './services/realtime'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.mount('#app')

createEcho()
