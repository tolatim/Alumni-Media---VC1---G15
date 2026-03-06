<template>
  <div class="login-container">

    <!-- Left Panel: Marketing / Banner -->
    <div class="left-panel">
      <div class="overlay">
        <h1>Reconnect with Excellence</h1>
        <p>
          Join over 2,000 PNC alumni. Unlock exclusive industry insights, meaningful networking, 
          and career opportunities built for our global Cambodian tech community.
        </p>
      </div>
    </div>

    <!-- Right Panel: Login Form -->
    <div class="right-panel">
      <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to access the PNC Alumni Portal</p>

        <form @submit.prevent="login">
          <label for="email">Email Address</label>
          <input id="email" type="email" v-model="email" placeholder="yourname@alumni.pnc" required :disabled="loading">

          <label for="password">Password</label>
          <input id="password" type="password" v-model="password" placeholder="••••••••" required :disabled="loading">

          <div class="forgot-link">
            <RouterLink to="/forgot-password" class="text-sm text-blue-600 hover:underline">Forgot password?</RouterLink>
          </div>

          <p id="error-message" class="error-text">{{ error }}</p>

          <button type="submit" :class="{ loading: loading }" :disabled="loading">
            <span class="spinner"></span>
            <span>{{ loading ? "Signing in..." : "Sign In →" }}</span>
          </button>
        </form>

        <div class="new-user">
          <strong>Don't have an account yet?</strong>
          <RouterLink to="/register" class="link">Create one now</RouterLink>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.login-container {
  display: flex;
  height: 100vh;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow: hidden;
  background: #f8fafc;
}

/* ── Left Panel ──────────────────────────────────────── */
.left-panel {
  flex: 1;
  position: relative;
  background: url('https://images.globalgiving.org/pfil/66543/pict_original.jpg?w=460&h=306&auto=compress,enhance&fit=crop&crop=faces,center&format=auto&dpr=2') 
              center/cover no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 5rem 4rem;
  color: white;
  overflow: hidden;
}

.left-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.65) 0%, rgba(59, 130, 246, 0.45) 50%, rgba(99, 102, 241, 0.35) 100%);
  z-index: 1;
}

.overlay {
  position: relative;
  z-index: 2;
  max-width: 480px;
  text-align: left;
}

.logo-container {
  margin-bottom: 2.5rem;
}

.pnc-logo {
  height: 64px;
  width: auto;
  filter: brightness(1.1) drop-shadow(0 2px 8px rgba(0,0,0,0.3));
}

.overlay h1 {
  font-size: clamp(2.5rem, 5vw, 3.5rem);
  font-weight: 800;
  line-height: 1.1;
  margin-bottom: 1.5rem;
  background: linear-gradient(90deg, #ffffff, #e0f2fe);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: -0.02em;
}

.overlay p {
  font-size: 1.125rem;
  line-height: 1.7;
  opacity: 0.95;
  font-weight: 400;
}

/* ── Right Panel ─────────────────────────────────────── */
.right-panel {
  flex: 1;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.login-card {
  width: 100%;
  max-width: 420px;
  padding: 3rem 2.5rem;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08), 0 4px 12px rgba(0, 0, 0, 0.06);
  background: white;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-card:hover {
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}

.login-card h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #6b7280;
  font-size: 0.95rem;
  margin-bottom: 2rem;
}

label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  display: block;
}

input {
  width: 100%;
  padding: 0.9rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.2s ease;
}

input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}

input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.forgot-link {
  text-align: right;
  margin: 0.75rem 0 1.25rem;
  font-size: 0.875rem;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
  min-height: 1.25rem;
  margin: 0.5rem 0 1rem;
}

button {
  width: 100%;
  padding: 1rem;
  margin-top: 1rem;
  background: #2563eb;
  color: white;
  font-weight: 600;
  font-size: 1.05rem;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  transition: all 0.25s ease;
}

button:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: none;
}

button.loading .spinner {
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.new-user {
  text-align: center;
  margin-top: 1.75rem;
  font-size: 0.95rem;
  color: #6b7280;
}

.link {
  color: #2563eb;
  font-weight: 600;
  margin-left: 0.4rem;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .left-panel { padding: 4rem 2rem; }
  .login-card { padding: 2.5rem 2rem; max-width: 380px; }
}

@media (max-width: 768px) {
  .login-container { flex-direction: column; }
  .left-panel { flex: none; height: 45vh; padding: 3rem 1.5rem; }
  .right-panel { flex: none; padding: 2rem 1rem; }
}
</style>

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

    const token = res?.data?.token;
    const user = res?.data?.user;

    if (!token || !user) {
      throw new Error("Invalid login response");
    }

    localStorage.setItem("token", token);
    localStorage.setItem("user", JSON.stringify(user));

    // Role may come as object ({ name: 'alumni' }) or string ('alumni')
    const roleName = typeof user.role === "string" ? user.role : user.role?.name;
    const target = roleName === "admin" ? "/admin" : "/";
    const resolved = router.resolve(target);
    await router.push(resolved.matched.length ? target : "/");
  } catch (err) {
    error.value = err.response?.data?.message || err.message || "Login failed. Please try again.";
  } finally {
    loading.value = false;
  }
}
</script>
