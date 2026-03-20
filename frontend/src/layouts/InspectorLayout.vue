<template>
  <div class="min-h-screen bg-gray-50 flex justify-center">
    <!-- Mobile Container - Centered on desktop -->
    <div class="w-full max-w-md bg-white shadow-lg min-h-screen flex flex-col relative">

      <!-- Top Header -->
      <header class="bg-primary-600 text-white px-4 pb-6 pt-4 sticky top-0 z-40">
        <div class="flex items-center justify-between">
          <!-- Logo & User -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
              <ShieldCheck class="w-6 h-6" />
            </div>
            <div>
              <h1 class="text-lg font-bold">FBB Inspection</h1>
              <p class="text-xs text-white/80">{{ userName }}</p>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium" :class="isOnline ? 'bg-white/20' : 'bg-red-500'">
            <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-green-400' : 'bg-white'"></span>
            <span>{{ isOnline ? 'Online' : 'Offline' }}</span>
          </div>
        </div>

        <!-- Page Title -->
        <h2 class="text-xl font-bold mt-4">{{ pageTitle }}</h2>
      </header>

      <!-- Main Content -->
      <main class="flex-1 px-4 py-4 pb-24 overflow-y-auto">
        <router-view />
      </main>

      <!-- Bottom Navigation -->
      <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-gray-200 shadow-lg z-50 safe-bottom">
        <div class="flex items-center justify-around h-16">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex flex-col items-center justify-center flex-1 h-full min-h-touch transition-colors"
            :class="isActive(item.path) ? 'text-primary-600' : 'text-gray-400'"
          >
            <component :is="item.icon" class="w-6 h-6" />
            <span class="text-xs font-medium mt-1">{{ item.label }}</span>
          </router-link>

          <!-- Logout -->
          <button @click="handleLogout" class="flex flex-col items-center justify-center flex-1 h-full text-gray-400">
            <LogOut class="w-6 h-6" />
            <span class="text-xs font-medium mt-1">Đăng xuất</span>
          </button>
        </div>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ShieldCheck, LogOut, Home, ListTodo } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isOnline = ref(navigator.onLine)

const navItems = [
  { path: '/inspector', label: 'Trang chủ', icon: Home },
  { path: '/inspector/tasks', label: 'Nhiệm vụ', icon: ListTodo }
]

const pageTitle = computed(() => {
  if (route.path === '/inspector') return 'Tổng quan'
  if (route.path.startsWith('/inspector/batch/')) return 'Chi tiết lô'
  if (route.path === '/inspector/tasks') return 'Danh sách nhiệm vụ'
  if (route.path.startsWith('/inspector/inspect/')) return 'Kiểm tra'
  return 'FBB Inspection'
})

const userName = computed(() => authStore.user?.name || 'Nhân viên')

const isActive = (path) => {
  if (path === '/inspector') {
    return route.path === '/inspector' || route.path === '/inspector/'
  }
  return route.path.startsWith(path)
}

const handleLogout = () => {
  authStore.logout()
  router.push({ name: 'login' })
}

const updateOnlineStatus = () => {
  isOnline.value = navigator.onLine
}

onMounted(() => {
  window.addEventListener('online', updateOnlineStatus)
  window.addEventListener('offline', updateOnlineStatus)
})

onUnmounted(() => {
  window.removeEventListener('online', updateOnlineStatus)
  window.removeEventListener('offline', updateOnlineStatus)
})
</script>
