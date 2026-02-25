<template>
  <div class="login-container">
    <!-- LEFT SIDE -->
    <div class="left-panel">
      <div class="overlay">
        <h1>Reconnect with Excellence.</h1>
        <p>
          Join over 5,000 PNC former colleagues. Access exclusive industry insights,
          professional networking, and career opportunities designed for our alumni community.
        </p>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">
      <div class="login-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">
          Please enter your credentials to access the alumni portal.
        </p>

        <form @submit.prevent="login">
          <label>Email Address</label>
          <input
            type="email"
            v-model="email"
            placeholder="e.g. name@alumni.com"
            required
          />

          <label>Password</label>
          <input
            type="password"
            v-model="password"
            placeholder="••••••••"
            required
          />

          <div class="options">
            <label>
              <input type="checkbox" v-model="remember" />
              Remember me for 30 days
            </label>
            <a href="#">Forgot password?</a>
          </div>

          <button type="submit">Login to Portal →</button>
        </form>

        <div class="new-user">
          <strong>New here?</strong>
          <p>Contact admin to get your account.</p>
          <a href="#">Contact Admin</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from "../services/api"

export default {
  data() {
    return {
      email: "",
      password: "",
      error: ""
    }
  },
  methods: {
    async login() {
      try {
        const res = await api.post("/login", {
          email: this.email,
          password: this.password
        })

        // ✅ store token & user
        localStorage.setItem("token", res.data.token)
        localStorage.setItem("user", JSON.stringify(res.data.user))

        // redirect
        this.$router.push("/")
      } catch (err) {
        this.error = "Invalid email or password"
      }
    }
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  height: 100vh;
  font-family: Arial, Helvetica, sans-serif;
}

/* LEFT SIDE */
.left-panel {
  flex: 1;
  background: linear-gradient(rgba(37,99,235,0.85), rgba(37,99,235,0.85)),
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

/* RIGHT SIDE */
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
  box-shadow: 0 8px 25px rgba(0,0,0,0.08);
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

.options {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  margin: 15px 0;
}

button {
  width: 100%;
  padding: 14px;
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

.new-user {
  margin-top: 25px;
  font-size: 14px;
  text-align: center;
}

.new-user a {
  color: #2563eb;
  text-decoration: none;
  font-weight: bold;
}
</style>