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
    return api.patch("/user/profile", data, config)
}

// Load Profile

export const getProfile = (data) => {
    return api.get("/users/" + data)
}