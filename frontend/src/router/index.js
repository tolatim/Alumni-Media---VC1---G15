import { createRouter, createWebHistory } from "vue-router"
import Home from "../views/Home.vue"
import Login from "../views/Login.vue"
import profile from "../views/Profile.vue";

const routes = [
  { path: "/", component: Home, meta: { requiresAuth: true } },
  { path: "/login", component: Login },
  
  {
    path:"/show", component: profile
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard: protect pages
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token")
  
  if (to.meta.requiresAuth && !token) {
    next("/login")
  } else if (to.path === "/login" && token) {
    next("/") // redirect logged-in users away from login
  } else {
    next()
  }
})

export default router