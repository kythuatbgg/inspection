<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-slate-800">{{ $t('login.title') }}</h1>
        <p class="text-slate-500 mt-2">{{ $t('login.subtitle') }}</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-slate-700 text-sm font-bold mb-2">{{ $t('login.username') }}</label>
          <input
            v-model="username"
            type="text"
            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="$t('login.usernamePlaceholder')"
            required
          />
        </div>

        <div class="mb-6">
          <label class="block text-slate-700 text-sm font-bold mb-2">{{ $t('login.password') }}</label>
          <input
            v-model="password"
            type="password"
            class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="$t('login.passwordPlaceholder')"
            required
          />
        </div>

        <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-danger rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
          <span v-else>{{ $t('login.submit') }}</span>
        </button>
      </form>

      <div class="mt-6 flex items-center justify-between text-sm text-slate-500">
        <div>
          <span>{{ $t('common.offlineMode') }}: </span>
          <span :class="isOnline ? 'text-success' : 'text-danger'">
            {{ isOnline ? $t('common.online') : $t('common.offline') }}
          </span>
        </div>
        <button @click="toggleLanguage" class="flex items-center gap-1.5 text-slate-500 hover:text-slate-700 transition-colors">
          <Languages class="w-4 h-4" />
          <span>{{ currentLocale === 'vi' ? 'EN' : 'VN' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Loader2, Languages } from 'lucide-vue-next'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../stores/auth'

const { t, locale: currentLocale } = useI18n()
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
      error.value = t('login.invalidCredentials')
    }
  } catch (e) {
    error.value = t('login.errorGeneric')
  } finally {
    loading.value = false
  }
}

const toggleLanguage = () => {
  authStore.setLanguage(currentLocale.value === 'vi' ? 'en' : 'vi')
}
</script>
