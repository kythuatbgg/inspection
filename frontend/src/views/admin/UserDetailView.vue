<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-0">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="$router.push('/admin/users')" class="min-w-[44px] min-h-[44px] flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-gray-700 hover:bg-gray-50 active:scale-95 transition-all">
          <ChevronLeft class="w-5 h-5" />
        </button>
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">Chi tiết người dùng</h2>
          <p class="text-xs md:text-sm text-gray-500 mt-0.5">Thông tin tài khoản và hoạt động</p>
        </div>
      </div>
      
      <button v-if="user" @click="openEditModal" class="btn-primary min-h-[44px] px-4 flex items-center gap-2 text-sm font-semibold rounded-xl">
        <FileEdit class="w-4 h-4" />
        <span class="hidden md:inline">Chỉnh sửa</span>
      </button>
    </div>

    <div v-if="loading" class="bg-white rounded-[24px] border border-gray-100 shadow-sm p-10 text-center text-gray-500">
      Đang tải dữ liệu...
    </div>

    <div v-else-if="error" class="bg-white rounded-[24px] border border-red-100 shadow-sm p-10 text-center">
      <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
        <AlertTriangle class="w-8 h-8" />
      </div>
      <p class="text-red-700 font-medium mb-4">{{ error }}</p>
      <button @click="fetchUser" class="btn-secondary px-6 min-h-[44px] rounded-xl">Thử lại</button>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
      
      <!-- Profile Card -->
      <div class="lg:col-span-1 bg-white rounded-[24px] md:rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col items-center p-6 text-center">
        <div class="w-24 h-24 rounded-full bg-primary-50 border-4 border-white shadow-md flex items-center justify-center mb-4">
          <span class="text-3xl font-bold text-primary-600">{{ getInitials(user.name) }}</span>
        </div>
        
        <h3 class="text-2xl font-bold text-gray-900 mb-1 tracking-tight">{{ user.name }}</h3>
        <p class="text-gray-500 font-medium mb-4">@{{ user.username }}</p>
        
        <span :class="getRoleClass(user.role)" class="mb-6 mx-auto">{{ getRoleLabel(user.role) }}</span>
        
        <div class="w-full h-px bg-gray-100 mb-6"></div>
        
        <div class="w-full space-y-4 text-left">
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Email</p>
            <p class="font-semibold text-gray-900 truncate">{{ user.email || 'Chưa cập nhật' }}</p>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 mb-1">Ngày tạo tài khoản</p>
            <p class="font-semibold text-gray-900">{{ formatDate(user.created_at) }}</p>
          </div>
          <div v-if="user.updated_at">
            <p class="text-sm font-medium text-gray-500 mb-1">Cập nhật lần cuối</p>
            <p class="font-semibold text-gray-900">{{ formatDate(user.updated_at) }}</p>
          </div>
        </div>
      </div>
      
      <!-- Activity/Details Area -->
      <div class="lg:col-span-2 space-y-4 md:space-y-6">
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
          <div class="bg-white rounded-[20px] md:rounded-xl border border-gray-100 shadow-sm p-4 relative overflow-hidden">
             <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-8 -mt-8 opacity-50"></div>
             <p class="text-sm text-gray-500 font-medium relative z-10">Lô kiểm tra</p>
             <p class="text-2xl font-bold text-gray-900 mt-2 relative z-10">0</p>
          </div>
          <div class="bg-white rounded-[20px] md:rounded-xl border border-gray-100 shadow-sm p-4 relative overflow-hidden">
             <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-8 -mt-8 opacity-50"></div>
             <p class="text-sm text-gray-500 font-medium relative z-10">Tủ cáp đã duyệt</p>
             <p class="text-2xl font-bold text-green-600 mt-2 relative z-10">0</p>
          </div>
          <div class="hidden md:block bg-white rounded-[20px] md:rounded-xl border border-gray-100 shadow-sm p-4 relative overflow-hidden">
             <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-8 -mt-8 opacity-50"></div>
             <p class="text-sm text-gray-500 font-medium relative z-10">Tủ cảnh báo</p>
             <p class="text-2xl font-bold text-amber-600 mt-2 relative z-10">0</p>
          </div>
        </div>
        
        <!-- Recent Activity Placeholder -->
        <div class="bg-white rounded-[24px] md:rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col">
          <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-gray-900">Hoạt động gần đây</h3>
          </div>
          <div class="p-8 flex flex-col items-center justify-center text-center">
            <Clock class="w-16 h-16 text-gray-200 mb-4" />
            <p class="text-gray-500 font-medium">Chưa có dữ liệu hoạt động</p>
            <p class="text-sm text-gray-400 mt-1">Người dùng này chưa thực hiện thao tác nào ghi nhận trên hệ thống.</p>
          </div>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { Clock, AlertTriangle, FileEdit, ChevronLeft } from 'lucide-vue-next'

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import userService from '@/services/userService.js'

const route = useRoute()
const router = useRouter()
const userId = route.params.id

const loading = ref(true)
const error = ref(null)
const user = ref(null)

const getRoleClass = (role) => {
  const classes = {
    admin: 'inline-flex items-center justify-center rounded-full bg-amber-50 px-4 py-1.5 text-sm font-bold text-amber-700',
    manager: 'inline-flex items-center justify-center rounded-full bg-sky-50 px-4 py-1.5 text-sm font-bold text-sky-700',
    inspector: 'inline-flex items-center justify-center rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-bold text-emerald-700',
    staff: 'inline-flex items-center justify-center rounded-full bg-gray-100 px-4 py-1.5 text-sm font-bold text-gray-700'
  }
  return classes[role] || classes.staff
}

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrator',
    manager: 'Quản lý',
    inspector: 'Nhân viên kiểm tra',
    staff: 'Nhân viên cơ bản'
  }
  return labels[role] || role
}

const getInitials = (name) => {
  if (!name) return ''
  return name.split(' ').map((part) => part[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('vi-VN', {
    default: 'numeric',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const fetchUser = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await userService.getUserById(userId)
    user.value = data
  } catch (err) {
    console.error(err)
    error.value = 'Không thể tải thông tin người dùng. Có thể tài khoản không tồn tại!'
  } finally {
    loading.value = false
  }
}

const openEditModal = () => {
  // Option to route back to /users and trigger edit if we had a persistent store,
  // or just navigate to an edit form. For now, we will handle this via User management page.
  router.push('/admin/users')
}

onMounted(() => {
  if (userId) {
    fetchUser()
  } else {
    error.value = 'Mã người dùng không hợp lệ'
    loading.value = false
  }
})
</script>
