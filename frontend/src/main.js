import { createApp } from "vue"
import { createPinia } from "pinia"
import App from "./App.vue"
import router from "./router"
import "./style.css"
import { applyThemeMode, fetchPublicAppearance } from "@/services/appearanceService"
import { createEcho } from "@/services/realtime"

const bootstrap = async () => {
  const appearance = await fetchPublicAppearance()
  applyThemeMode(appearance.theme_mode)

  const app = createApp(App)
  app.use(createPinia())
  app.use(router)
  app.mount("#app")

  createEcho()
}

bootstrap()
