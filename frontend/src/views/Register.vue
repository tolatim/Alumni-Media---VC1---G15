<template>
  <div class="page">
    <!-- REGISTER CARD -->
    <div class="card">
      <h1>Create Your Account</h1>
      <p class="subtitle">
        Join the PNC Alumni community — reconnect, network, and unlock new opportunities.
      </p>

      <form @submit.prevent="register">
        <div class="row">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input
              id="first_name"
              v-model="first_name"
              placeholder="e.g. Tola"
              required
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
              id="last_name"
              v-model="last_name"
              placeholder="e.g. Kim"
              required
              :disabled="loading"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input
            id="email"
            type="email"
            v-model="email"
            placeholder="yourname@alumni.pnc"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="registrationKey">Registration Key</label>
          <input
            id="registrationKey"
            v-model="registrationKey"
            placeholder="Enter key from admin"
            required
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-field">
            <input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              v-model="password"
              placeholder="Min. 8 characters"
              required
              :disabled="loading"
            />
            <span @click="togglePassword" class="toggle-icon">
              <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
            </span>
          </div>
          <small class="hint">
            Must include at least 8 characters, a number, and a symbol.
          </small>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <div class="password-field">
            <input
              id="confirmPassword"
              :type="showConfirm ? 'text' : 'password'"
              v-model="confirmPassword"
              placeholder="Repeat password"
              required
              :disabled="loading"
            />
            <span @click="toggleConfirm" class="toggle-icon">
              <i :class="showConfirm ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
            </span>
          </div>
        </div>

        <p class="error-text">{{ error }}</p>

        <button
          class="primary-btn"
          type="submit"
          :class="{ loading: loading }"
          :disabled="loading"
        >
          <span class="spinner"></span>
          <span>{{ loading ? "Creating Account..." : "Create Account →" }}</span>
        </button>
      </form>

      <div class="divider">Or sign up with</div>

      <p class="login-link">
        Already have an account?
        <RouterLink to="/login" class="link">Log in here</RouterLink>
      </p>
    </div>

    <footer class="footer">
      Need help? Contact Support
      <br />
      © {{ new Date().getFullYear() }} PNC Alumni Platform. All rights reserved.
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
const registrationKey = ref("")
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
      registration_key: registrationKey.value,
    })

    // save token
    localStorage.setItem("token", res.data.token)
    localStorage.setItem("user", JSON.stringify(res.data.user))

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
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  background: url('https://www.passerellesnumeriques.org/wp-content/uploads/2025/05/488904386_1067237218774477_7287190704021364614_n-1.jpg')
              center/cover no-repeat;
  position: relative;
  padding: 2rem 1rem;
}

.page::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.94) 0%, rgba(248, 250, 252, 0.96) 100%);
  z-index: 1;
}

.header {
  position: relative;
  z-index: 2;
  margin-bottom: 2rem;
  text-align: center;
}

.logo-img {
  height: 56px;
  width: auto;
  filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.12));
}

.card {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 460px;
  background: white;
  padding: 3rem 2.5rem;
  border-radius: 16px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}

h1 {
  font-size: 2.25rem;
  font-weight: 700;
  color: #111827;
  margin-bottom: 0.75rem;
  text-align: center;
}

.subtitle {
  color: #6b7280;
  font-size: 1rem;
  text-align: center;
  margin-bottom: 2.5rem;
  line-height: 1.5;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  display: block;
  margin-bottom: 0.5rem;
}

input {
  width: 100%;
  padding: 0.9rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 10px;
  font-size: 1rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.8;
}

.row {
  display: flex;
  gap: 1.25rem;
  margin-bottom: 0.5rem;
}

.row .form-group {
  flex: 1;
}

.password-field {
  position: relative;
}

.toggle-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #6b7280;
  font-size: 1.125rem;
  transition: color 0.2s ease;
}

.toggle-icon:hover {
  color: #2563eb;
}

.hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.375rem;
  display: block;
}

.error-text {
  color: #ef4444;
  font-size: 0.875rem;
  text-align: center;
  min-height: 1.25rem;
  margin: 0.5rem 0 1.25rem;
}

.primary-btn {
  width: 100%;
  padding: 1rem;
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

.primary-btn:hover:not(:disabled) {
  background: #1d4ed8;
  transform: translateY(-1px);
}

.primary-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: none;
}

.primary-btn.loading .spinner {
  display: inline-block;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.divider {
  text-align: center;
  margin: 2rem 0;
  color: #9ca3af;
  font-size: 0.875rem;
  position: relative;
}

.divider::before,
.divider::after {
  content: '';
  position: absolute;
  top: 50%;
  width: 42%;
  height: 1px;
  background: #d1d5db;
  transform: translateY(-50%);
}

.divider::before { left: 0; }
.divider::after { right: 0; }

.login-link {
  text-align: center;
  font-size: 0.95rem;
  color: #6b7280;
  margin-top: 1.5rem;
}

.link {
  color: #2563eb;
  font-weight: 600;
  text-decoration: none;
}

.link:hover {
  text-decoration: underline;
}

.footer {
  position: relative;
  z-index: 2;
  margin-top: 3rem;
  text-align: center;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.6;
}

/* Responsive adjustments */
@media (max-width: 900px) {
  .page {
    padding: 1.25rem 0.9rem;
  }

  .card {
    max-width: 560px;
    padding: 2.25rem 1.75rem;
  }

  h1 {
    font-size: 1.9rem;
  }

  .subtitle {
    font-size: 0.95rem;
    margin-bottom: 1.75rem;
  }
}

@media (max-width: 640px) {
  .card {
    padding: 1.9rem 1.2rem;
    border-radius: 14px;
  }

  .card:hover {
    transform: none;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
  }

  .row {
    flex-direction: column;
    gap: 1rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .primary-btn {
    font-size: 1rem;
    padding: 0.9rem;
  }

  .footer {
    margin-top: 1.5rem;
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .card {
    padding: 1.5rem 1rem;
  }

  h1 {
    font-size: 1.6rem;
  }

  .subtitle {
    font-size: 0.9rem;
    margin-bottom: 1.3rem;
  }
}
</style>
