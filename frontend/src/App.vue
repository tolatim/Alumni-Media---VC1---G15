<template>
  <div>
    <nav v-if="user">
      Welcome, {{ user.name }} |
      <button @click="logout">Logout</button>
    </nav>
    <router-view />
  </div>
</template>

<script>
export default {
  data() {
    return {
      user: null
    }
  },
  mounted() {
    const savedUser = localStorage.getItem("user")
    if (savedUser) {
      this.user = JSON.parse(savedUser)
    }
  },
  methods: {
    logout() {
      localStorage.removeItem("token")
      localStorage.removeItem("user")
      this.user = null
      this.$router.push("/login")
    }
  }
}
</script>