<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-slate-800">FBB Inspection</h1>
        <p class="text-slate-500 mt-2">Đăng nhập để tiếp tục</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-slate-700 text-sm font-bold mb-2">Tên đăng nhập</label>
          <input
            v-model="username"
            type="text"
            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Nhập tên đăng nhập"
            required
          />
        </div>

        <div class="mb-6">
          <label class="block text-slate-700 text-sm font-bold mb-2">Mật khẩu</label>
          <input
            v-model="password"
            type="password"
            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Nhập mật khẩu"
            required
          />
        </div>

        <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-danger rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50"
        >
          {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
      </form>

      <div class="mt-6 text-center text-sm text-slate-500">
        <span>Offline mode: </span>
        <span :class="isOnline ? 'text-success' : 'text-danger'">
          {{ isOnline ? 'Online' : 'Offline' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const isOnline = ref(navigator.onLine)

onMounted(() => {
  window.addEventListener('online', () => isOnline.value = true)
  window.addEventListener('offline', () => isOnline.value = false)

  if (authStore.isAuthenticated) {
    router.push(authStore.isInspector ? '/inspector' : '/admin')
  }
})

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const success = await authStore.login(username.value, password.value)
    if (success) {
      router.push(authStore.isInspector ? '/inspector' : '/admin')
    } else {
      error.value = 'Tên đăng nhập hoặc mật khẩu không đúng'
    }
  } catch (e) {
    error.value = 'Có lỗi xảy ra. Vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>
