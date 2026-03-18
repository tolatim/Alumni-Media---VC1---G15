import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router/index.js'
import './style.css'
import Pusher from 'pusher-js'
import Echo from 'laravel-echo'

window.Pusher = Pusher

// ✅ Echo initialized without token — token will be set dynamically on login
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'http://localhost:8000/broadcasting/auth',
    auth: {
        headers: {
            // ✅ Use a getter so it always reads the latest token
            get Authorization() {
                return 'Bearer ' + localStorage.getItem('token');
            }
        },
    },
})

createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app')