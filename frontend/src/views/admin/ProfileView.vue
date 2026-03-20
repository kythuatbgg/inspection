<template>
  <div class="max-w-2xl mx-auto space-y-6">
    <h2 class="text-lg font-semibold text-gray-900">Hồ sơ cá nhân</h2>

    <!-- Profile Info -->
    <div class="card p-6">
      <div class="flex items-center gap-4 mb-6">
        <div class="w-20 h-20 rounded-full bg-primary-100 flex items-center justify-center">
          <span class="text-2xl font-bold text-primary-600">{{ getInitials(authStore.user?.name) }}</span>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-gray-900">{{ authStore.user?.name }}</h3>
          <p class="text-gray-500">{{ authStore.user?.email }}</p>
          <span :class="getRoleClass(authStore.user?.role)" class="mt-1 inline-block">{{ getRoleLabel(authStore.user?.role) }}</span>
        </div>
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="updateProfile" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Họ tên</label>
          <input
            v-model="form.name"
            type="text"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tên đăng nhập</label>
          <input
            :value="authStore.user?.username"
            type="text"
            class="input-field min-h-touch"
            disabled
          />
          <p class="text-xs text-gray-500 mt-1">Tên đăng nhập không thể thay đổi</p>
        </div>

        <!-- Error -->
        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>

        <!-- Success -->
        <div v-if="success" class="p-3 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-sm text-green-600">{{ success }}</p>
        </div>

        <button type="submit" :disabled="saving" class="btn-primary w-full min-h-touch">
          {{ saving ? 'Đang lưu...' : 'Lưu thông tin' }}
        </button>
      </form>
    </div>

    <!-- Change Password -->
    <div class="card p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Đổi mật khẩu</h3>

      <form @submit.prevent="changePassword" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
          <input
            v-model="passwordForm.current_password"
            type="password"
            class="input-field min-h-touch"
            required
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
          <input
            v-model="passwordForm.password"
            type="password"
            class="input-field min-h-touch"
            required
            minlength="6"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
          <input
            v-model="passwordForm.password_confirmation"
            type="password"
            class="input-field min-h-touch"
            required
            minlength="6"
          />
        </div>

        <!-- Error -->
        <div v-if="passwordError" class="p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ passwordError }}</p>
        </div>

        <!-- Success -->
        <div v-if="passwordSuccess" class="p-3 bg-green-50 border border-green-200 rounded-lg">
          <p class="text-sm text-green-600">{{ passwordSuccess }}</p>
        </div>

        <button type="submit" :disabled="changingPassword" class="btn-primary w-full min-h-touch">
          {{ changingPassword ? 'Đang đổi...' : 'Đổi mật khẩu' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api.js'

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
  const labels = {
    admin: 'Admin',
    manager: 'Quản lý',
    inspector: 'Inspector',
    staff: 'Staff'
  }
  return labels[role] || role
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

    success.value = 'Cập nhật thông tin thành công'
    setTimeout(() => success.value = '', 3000)
  } catch (e) {
    error.value = e.response?.data?.message || 'Có lỗi xảy ra'
  } finally {
    saving.value = false
  }
}

const changePassword = async () => {
  passwordError.value = ''
  passwordSuccess.value = ''

  if (passwordForm.password !== passwordForm.password_confirmation) {
    passwordError.value = 'Mật khẩu xác nhận không khớp'
    return
  }

  changingPassword.value = true

  try {
    await api.post('/users/change-password', {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation
    })

    passwordSuccess.value = 'Đổi mật khẩu thành công'
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    setTimeout(() => passwordSuccess.value = '', 3000)
  } catch (e) {
    passwordError.value = e.response?.data?.message || 'Có lỗi xảy ra'
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
  @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700;
}
</style>
