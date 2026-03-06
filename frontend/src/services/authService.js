import { data } from "autoprefixer";
import api from "./api";

// Register
export const registerUser = (data) => {
    return api.post("/register", data)
}

// Login

export const loginUser = (data) => {
    return api.post("/login", data)
}

// Logout

export const logoutUser = () => {
    return api.post("/logout")
}

// Update Profile

export const updateProfile = (data, config) => {
    return api.post("/user/profile", data, config)
}

// Load Profile

export const getProfile = (data) => {
    return api.get("/users/" + data)
}

// Get user
export const getUser = (data) => {
    return api.get("/users/" + data)
}
// posts
export const createPosts = (data, config) => {
    return api.post("/posts", data, config)
}
// getpost
export const getPosts = (page = 1) => {
  return api.get(`/posts?page=${page}`);
};