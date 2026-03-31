<template>
  <div class="min-h-screen bg-slate-100 py-10">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-bold text-slate-900">Reset Password</h1>
      <p class="mt-1 text-sm text-slate-600">
        Set a new password for your account.
      </p>

      <form class="mt-6 space-y-4" @submit.prevent="submitReset">
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

        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700" for="password">
            New Password
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            minlength="6"
            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
            placeholder="Minimum 6 characters"
          >
        </div>

        <div>
          <label class="mb-1 block text-sm font-semibold text-slate-700" for="password_confirmation">
            Confirm Password
          </label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
            required
            minlength="6"
            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-100"
            placeholder="Repeat your new password"
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
          {{ loading ? 'Updating...' : 'Reset Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

const token = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const submitReset = async () => {
  errorMessage.value = ''
  successMessage.value = ''

  if (!token.value) {
    errorMessage.value = 'Invalid or missing reset token.'
    return
  }

  if (password.value !== passwordConfirmation.value) {
    errorMessage.value = 'Password confirmation does not match.'
    return
  }

  loading.value = true
  try {
    const response = await api.post('/reset-password', {
      token: token.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    })

    successMessage.value = response.data?.message || 'Password reset successfully.'
    setTimeout(() => {
      router.push('/login')
    }, 1200)
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Failed to reset password.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  token.value = String(route.query.token || '')
  email.value = String(route.query.email || '')
})
</script>
