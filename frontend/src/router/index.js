import { createRouter, createWebHistory } from 'vue-router'
import { startRouteLoading, stopRouteLoading } from '@/services/loadingService'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import Profile from '../views/Profile.vue'
import EditProfile from '@/views/editProfile.vue'
import Create from '../views/CreatePost.vue'
import Connect from '@/views/connect.vue'
import Message from '@/views/Message.vue'

const routes = [
  { path: '/', component: Home, meta: { requiresAuth: true } },
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  {
    path: '/profile',
    redirect: () => {
      const savedUser = localStorage.getItem('user')
      const user = savedUser ? JSON.parse(savedUser) : null
      return user?.id ? `/profile/${user.id}` : '/'
    },
    meta: { requiresAuth: true },
  },
  {
    path: '/connection',
    name: 'Connection',
    component: Connect,
    meta: { requiresAuth: true },
  },
  {
    path: '/message',
    name: 'Message',
    component: Message,
    meta: { requiresAuth: true },
  },
  {
    path: '/message/:userId',
    name: 'MessageWithUser',
    component: Message,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/edit',
    name: 'EditProfile',
    component: EditProfile,
    meta: { requiresAuth: true },
  },
  {
    path: '/post',
    name: 'Create',
    component: Create,
    meta: { requiresAuth: true },
  },
  {
    path: '/post/:id',
    name: 'EditPost',
    component: Create,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile/:id',
    component: Profile,
    name: 'Profile',
    meta: { requiresAuth: true },
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  startRouteLoading()

  const token = localStorage.getItem('token')
  let user = null

  try {
    const savedUser = localStorage.getItem('user')
    user = savedUser ? JSON.parse(savedUser) : null
  } catch {
    user = null
  }

  if (to.meta.requiresAuth && !token) {
    next('/login')
    return
  }

  if ((to.path === '/login' || to.path === '/register') && token) {
    next('/')
    return
  }

  if (to.meta.requiresAdmin) {
    if (!token) {
      next('/login')
      return
    } else if (user?.role) {
      const roleName = typeof user.role === 'string' ? user.role : user.role?.name
      if (roleName !== 'admin') {
        next('/')
        return
      }
    }
  }

  next()
})
 





router.afterEach(() => {
  stopRouteLoading()
})

router.onError(() => {
  stopRouteLoading()
})

export default router

