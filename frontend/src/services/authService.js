import api from './api'

export const registerUser = (data) => api.post('/register', data)

export const loginUser = (data) => api.post('/login', data)

export const logoutUser = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
}

export const updateProfile = (data, config) => api.post('/profile', data, config)

export const getProfile = (id) => api.get(`/users/${id}`)

export const getUser = (id) => api.get(`/users/${id}`)

export const createPost = (data) => api.post('/posts', data)

export const getPosts = () => api.get('/posts')
