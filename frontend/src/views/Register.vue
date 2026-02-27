<template>
  <div class="page">
    <!-- REGISTER CARD -->
    <div class="card">
      <h1>Create Account</h1>
      <p class="subtitle">
        Join the Alumini community today and reconnect with your network.
      </p>

      <form @submit.prevent="register">
        <div class="row">

          <div>
            <label>First Name</label>
            <input v-model="first_name" placeholder="e.g. John" required />
          </div>

          <div>
            <label>Last Name</label>
            <input v-model="last_name" placeholder="e.g. Doe" required />
          </div>
        </div>

        <label>Email Address</label>
        <input type="email" v-model="email" placeholder="name@email.com" required />

        <label>Password</label>
        <div class="password-field">
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            placeholder="Min. 8 characters"
            required
          />
          <span @click="togglePassword"><i style="font-size: small;" :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></span>
        </div>
        <small class="hint">
          Must include at least 8 characters, a number and a symbol.
        </small>

        <label>Confirm Password</label>
        <div class="password-field">
          <input
            :type="showConfirm ? 'text' : 'password'"
            v-model="confirmPassword"
            placeholder="Repeat password"
            required
          />
          <span @click="toggleConfirm"><i style="font-size:smaller;" :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i></span>
        </div>
        <p style="color: red;">{{ error }}</p>
        <!-- <label class="checkbox">
          <input type="checkbox" v-model="agree" />
          I agree to the <a href="#">Terms of Service</a> and
          <a href="#">Privacy Policy</a>.
        </label> -->

         <button
            class="primary-btn"
            type="submit"
            :class="{ loading: loading }"
            :disabled="loading"
          >
            <span class="spinner"></span>
            <span>{{ loading ? "Create Account..." : "Create Account" }}</span>
          </button>
        <!-- <button class="primary-btn">Create Account</button> -->
      </form>

      <div class="divider">Or sign up with</div>

      <p class="login-link">
        Already have an account?
        <a href="/login">Log in here</a>
      </p>
    </div>

    <footer class="footer">
      Need help? Contact Support
      <br />
      © 2024 Alumini Platform. All rights reserved.
    </footer>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { registerUser } from "@/services/authService"
import { useRouter } from "vue-router"

const router = useRouter()

const first_name = ref("")
const last_name = ref("")
const email = ref("")
const password = ref("")
const confirmPassword = ref("")
const error = ref("")
const loading = ref(false);

// reactive variables
const showPassword = ref(false);
const showConfirm = ref(false);
function togglePassword() {
  showPassword.value = !showPassword.value
}
function toggleConfirm() {
  showConfirm.value = !showConfirm.value
}
async function register() {
  error.value = "";
  loading.value = true;

  try {
    const res = await registerUser({
      first_name: first_name.value,
      last_name: last_name.value,
      email: email.value,
      password: password.value,
      password_confirmation: confirmPassword.value,
    })

    // save token
    localStorage.setItem("token", res.data.token)
    localStorage.setItem("user", JSON.stringify(res.data.user))

    alert("Registration successful!")

    router.push("/")

  } catch (err) {
    error.value = err.response?.data?.message || "Registration failed"
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.page {
  font-family: Arial, Helvetica, sans-serif;
}

/* NAVBAR */
.navbar {
  display: flex;
  justify-content: space-between;
  padding: 18px 40px;
  background: white;
  border-bottom: 1px solid #eee;
}

.logo {
  font-weight: bold;
  font-size: 18px;
  display: flex;
  gap: 8px;
  align-items: center;
}

nav {
  display: flex;
  gap: 25px;
  align-items: center;
}

nav a {
  text-decoration: none;
  color: #444;
  font-size: 14px;
}

.signin-btn {
  background: #2563eb;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
}

/* CARD */
.card {
  width: 420px;
  background: white;
  margin: 60px auto;
  padding: 35px;
  border-radius: 14px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

h1 {
  margin-bottom: 8px;
}

.subtitle {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 25px;
}

label {
  font-size: 13px;
  font-weight: 600;
  display: block;
  margin-top: 15px;
}

input {
  width: 100%;
  padding: 12px;
  margin-top: 6px;
  border-radius: 8px;
  border: 1px solid #ddd;
}

/* ROW */
.row {
  display: flex;
  gap: 10px;
}

.password-field {
  position: relative;
}

.password-field span {
  position: absolute;
  right: 12px;
  top: 12px;
  cursor: pointer;
}

.hint {
  font-size: 12px;
  color: #6b7280;
}

/* .checkbox {
  display: flex;
  gap: 8px;
  font-size: 13px;
  margin-top: 15px;
} */

.checkbox a {
  color: #2563eb;
  text-decoration: none;
}

.primary-btn {
  width: 100%;
  margin-top: 20px;
  padding: 14px;
  background: #3563d6;
  color: white;
  border: none;
  border-radius: 10px;
  font-size: 16px;
  cursor: pointer;
}

.divider {
  text-align: center;
  margin: 25px 0;
  color: #aaa;
  font-size: 13px;
}

.login-link {
  text-align: center;
  font-size: 14px;
}

.login-link a {
  color: #2563eb;
  text-decoration: none;
  font-weight: bold;
}

.footer {
  text-align: center;
  font-size: 12px;
  color: #888;
  padding-bottom: 30px;
}
button.loading .spinner {
  display: inline-block;
}

button.loading {
  opacity: 0.8;
  cursor: not-allowed;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  display: none;
  margin-right: 8px;
}

button.loading .spinner {
  display: inline-block;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>