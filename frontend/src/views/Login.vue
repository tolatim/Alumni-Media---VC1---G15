<template>
  <div class="login-container">
    <div class="left-panel">
      <div class="overlay">
        <h1>Reconnect with Excellence.</h1>
        <p>
          Join over 5,000 PNC former colleagues. Access exclusive industry
          insights, professional networking, and career opportunities designed
          for our alumni community.
        </p>
      </div>
    </div>

    <div class="right-panel">
      <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Please enter your credentials to access the alumni portal.</p>

        <form @submit.prevent="login">
          <label>Email Address</label>
          <input type="email" v-model="email" placeholder="e.g. name@alumni.com" required>

          <label>Password</label>
          <input type="password" v-model="password" placeholder="��������" required>

          <div class="options">
            <!-- <label>
              <input type="checkbox" v-model="remember" />
              Remember me for 30 days
            </label> -->
            <!-- <a href="#">Forgot password?</a> -->
            <p style="color: red">{{ error }}</p>
          </div>

          <button
            type="submit"
            :class="{ loading: loading }"
            :disabled="loading"
          >
            <span class="spinner"></span>
            <span>{{ loading ? "Logging in..." : "Login to Portal →" }}</span>
          </button>
        </form>

        <div class="new-user">
          <strong>Don't have an account?</strong>
          <a href="/register"> Register</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { loginUser } from "@/services/authService";
import { useRouter } from "vue-router";

const router = useRouter();

const email = ref("");
const password = ref("");
const error = ref("");
const loading = ref(false); // ✅ IMPORTANT

async function login() {
  error.value = "";
  loading.value = true;

  try {
    const res = await loginUser({
      email: email.value,
      password: password.value,
    });

    localStorage.setItem("token", res.data.token);
    localStorage.setItem("user", JSON.stringify(res.data.user));

    router.push("/");
  } catch (err) {
    error.value = err.response?.data?.message || "Invalid email or password";
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  height: 100vh;
  font-family: Arial, Helvetica, sans-serif;
}

.left-panel {
  flex: 1;
  background: linear-gradient(rgba(37, 99, 235, 0.85), rgba(37, 99, 235, 0.85)),
    url("https://images.unsplash.com/photo-1521737604893-d14cc237f11d")
      center/cover no-repeat;
  color: white;
  display: flex;
  align-items: center;
  padding: 60px;
}

.overlay h1 {
  font-size: 42px;
  margin-bottom: 20px;
}

.overlay p {
  max-width: 420px;
  line-height: 1.6;
  opacity: 0.9;
}

.right-panel {
  flex: 1;
  background: #f4f6f9;
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-card {
  width: 360px;
  background: white;
  padding: 35px;
  border-radius: 10px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}

h2 {
  margin-bottom: 6px;
}

.subtitle {
  font-size: 14px;
  color: #777;
  margin-bottom: 25px;
}

label {
  font-size: 13px;
  font-weight: bold;
  display: block;
  margin-top: 15px;
}

input {
  width: 100%;
  padding: 12px;
  margin-top: 6px;
  border-radius: 6px;
  border: 1px solid #ddd;
}

button {
  width: 100%;
  padding: 14px;
  margin-top: 18px;
  border: none;
  border-radius: 6px;
  background: #2563eb;
  color: white;
  font-size: 16px;
  cursor: pointer;
}

button:hover {
  background: #1e4ed8;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  color: #dc2626;
  font-size: 13px;
  margin-top: 10px;
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
