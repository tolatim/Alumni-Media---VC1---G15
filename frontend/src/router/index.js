import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import Profile from '../views/Profile.vue'
import EditProfile from '@/views/editProfile.vue'
import Create from '../views/CreatePost.vue'
import Connect from '@/views/connect.vue'
import Message from '@/views/Message.vue'
import Notification from '@/views/Notification.vue'

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
    path: '/notification',
    name: 'Notification',
    component: Notification,
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
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
    name: "connection",
    meta: {
      requiresAuth: true
    }
  },
  {
    path: '/notification',
    name: 'Notification',
    component: Notification,
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
>>>>>>> 9eb295f1b2b0f25d84cd2398ff970217a8515370
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from) => {
  const token = localStorage.getItem('token')
<<<<<<< HEAD
  const user = JSON.parse(localStorage.getItem('user'))
  
=======

>>>>>>> feature/Auth_Post
  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  if ((to.path === '/login' || to.path === '/register') && token) {
    return '/'
  }

<<<<<<< HEAD
  if (to.meta.requiresAdmin) {
    if (!token) {
      return '/login'
    } else if (user.role) {
      if (user.role !== 'admin') {
        return '/'
      }
    }
  }

=======
>>>>>>> feature/Auth_Post
  return true
})
 





export default router

