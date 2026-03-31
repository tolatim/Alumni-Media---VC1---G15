<template>
  <div class="min-h-screen bg-slate-100 py-10">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-bold text-slate-900">Forgot Password</h1>
      <p class="mt-1 text-sm text-slate-600">
        Enter your email and we will send you a reset link.
      </p>

      <form class="mt-6 space-y-4" @submit.prevent="sendResetLink">
        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700" for="email">
            Email
          </label>
          <input
            id="email"
            v-model.trim="email"
            type="email"
            required
            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
            placeholder="you@example.com"
          >
        </div>

        <p v-if="errorMessage" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
          {{ successMessage }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="inline-flex w-full items-center justify-center rounded-xl bg-cyan-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyan-700 disabled:opacity-60"
        >
          {{ loading ? 'Sending...' : 'Send Reset Link' }}
        </button>
      </form>

      <RouterLink to="/login" class="mt-4 inline-block text-sm font-semibold text-cyan-700 hover:underline">
        Back to login
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/services/api'

const email = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const sendResetLink = async () => {
  errorMessage.value = ''
  successMessage.value = ''
  loading.value = true

  try {
    const response = await api.post('/forgot-password', {
      email: email.value,
    })
    successMessage.value = response.data?.message || 'Reset link sent.'
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to send reset link.'
  } finally {
    loading.value = false
  }
}
</script>
