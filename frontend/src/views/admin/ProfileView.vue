<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <h2 class="text-lg font-semibold text-slate-900">{{ $t('profile.title') }}</h2>

    <!-- Profile Info -->
    <div class="card p-6">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center">
          <span class="text-2xl font-bold text-primary-600">{{ getInitials(authStore.user?.name) }}</span>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-slate-900">{{ authStore.user?.name }}</h3>
          <p class="text-slate-500">{{ authStore.user?.email }}</p>
          <span :class="getRoleClass(authStore.user?.role)" class="mt-1 inline-block">{{ getRoleLabel(authStore.user?.role) }}</span>
        </div>
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="updateProfile" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.fullName') }}</label>
          <input
            v-model="form.name"
            type="text"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.email') }}</label>
          <input
            v-model="form.email"
            type="email"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.username') }}</label>
          <input
            :value="authStore.user?.username"
            type="text"
            class="input-field min-h-touch"
            disabled
          />
          <p class="text-xs text-slate-500 mt-1">{{ $t('profile.usernameHint') }}</p>
        </div>

        <!-- Error -->
        <div v-if="error" class="p-3 bg-danger/10 border border-red-200 rounded-lg">
          <p class="text-sm text-danger">{{ error }}</p>
        </div>

        <!-- Success -->
        <div v-if="success" class="p-3 bg-success/10 border border-green-200 rounded-lg">
          <p class="text-sm text-success">{{ success }}</p>
        </div>

        <button type="submit" :disabled="saving" class="btn-primary w-full min-h-touch">
          {{ saving ? $t('profile.saving') : $t('profile.saveInfo') }}
        </button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ $t('profile.changePassword') }}</h3>

      <form @submit.prevent="changePassword" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.currentPassword') }}</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.newPassword') }}</label>
          <input
            v-model="passwordForm.password"
            type="password"
            class="input-field min-h-touch"
            required
            minlength="6"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('profile.confirmPassword') }}</label>
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            class="input-field min-h-touch"
            required
            minlength="6"
          />
        </div>

        <!-- Error -->
        <div v-if="passwordError" class="p-3 bg-danger/10 border border-red-200 rounded-lg">
          <p class="text-sm text-danger">{{ passwordError }}</p>
        </div>

        <!-- Success -->
        <div v-if="passwordSuccess" class="p-3 bg-success/10 border border-green-200 rounded-lg">
          <p class="text-sm text-success">{{ passwordSuccess }}</p>
        </div>

        <button type="submit" :disabled="changingPassword" class="btn-primary w-full min-h-touch">
          {{ changingPassword ? $t('profile.changingPassword') : $t('profile.changePasswordBtn') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api.js'

const { t } = useI18n()
const authStore = useAuthStore()

const form = reactive({
  name: '',
  email: ''
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const saving = ref(false)
const error = ref('')
const success = ref('')

const changingPassword = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

const getRoleClass = (role) => {
  const classes = {
    admin: 'badge-info',
    manager: 'badge-warning',
    inspector: 'badge-success',
    staff: 'badge-neutral'
  }
  return classes[role] || 'badge-info'
}

const getRoleLabel = (role) => {
  return t('roles.' + (role || 'admin'))
}

const getInitials = (name) => {
  if (!name) return ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const updateProfile = async () => {
  error.value = ''
  success.value = ''
  saving.value = true

  try {
    const response = await api.put(`/users/${authStore.user.id}`, {
      name: form.name,
      email: form.email
    })

    authStore.user.name = form.name
    authStore.user.email = form.email

    success.value = t('profile.updateSuccess')
    setTimeout(() => success.value = '', 3000)
  } catch (e) {
    error.value = e.response?.data?.message || t('common.errorOccurred')
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordError.value = t('profile.passwordMismatch')
    return
  }

  changingPassword.value = true

  try {
    await api.post('/users/change-password', {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation
    })

    passwordSuccess.value = t('profile.passwordSuccess')
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    setTimeout(() => passwordSuccess.value = '', 3000)
  } catch (e) {
    passwordError.value = e.response?.data?.message || t('common.errorOccurred')
  } finally {
    changingPassword.value = false
  }
}

onMounted(() => {
  form.name = authStore.user?.name || ''
  form.email = authStore.user?.email || ''
})
</script>

<style scoped>
.badge-neutral {
  @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-slate-100 text-slate-700;
}
</style>
