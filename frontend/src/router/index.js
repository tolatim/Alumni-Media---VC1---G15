import { createRouter, createWebHistory } from "vue-router"
import Home from "../views/Home.vue"
import Login from "../views/Login.vue"
import profile from "../views/Profile.vue";
import editProfile from "@/views/editProfile.vue";

const routes = [
  { path: "/", component: Home, meta: { requiresAuth: true } },
  { path: "/login", component: Login },
  
  {
    path:"/profile/:id",
    component: profile,
    name: 'Profile',
    meta: {requiresAuth: true}
  },

  {
    path: '/profile/edit',
    name: 'EditProfile',
    component: editProfile,
    meta: { requiresAuth: true }
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