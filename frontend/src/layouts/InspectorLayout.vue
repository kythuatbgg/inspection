<template>
  <div class="min-h-screen bg-slate-50 flex justify-center font-sans">
    <!-- Mobile Container - Centered on desktop -->
    <div class="w-full max-w-md bg-white shadow-sm border-x border-slate-200 min-h-screen flex flex-col relative">

      <!-- Top Header -->
      <header class="bg-white border-b border-slate-200 px-4 pb-4 pt-5 sticky top-0 z-40">
        <div class="flex items-center justify-between">
          <!-- Logo & User -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-50 border border-primary-100 flex items-center justify-center">
              <ShieldCheck class="w-6 h-6 text-primary-600" />
            </div>
            <div>
              <h1 class="text-base font-bold text-slate-900 font-heading tracking-tight">FBB Inspection</h1>
              <p class="text-xs text-slate-500 font-medium">{{ userName }}</p>
            </div>
          </div>

          <!-- Status Badge -->
          <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-[11px] font-bold uppercase tracking-wide" :class="isOnline ? 'bg-success/5 border-success/20 text-success' : 'bg-danger/5 border-danger/20 text-danger'">
            <span class="w-1.5 h-1.5 rounded-full" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
            <span>{{ isOnline ? 'Online' : 'Offline' }}</span>
          </div>
        </div>

        <!-- Page Title -->
        <h2 class="text-xl font-bold mt-5 text-slate-900 font-heading tracking-tight">{{ pageTitle }}</h2>
      </header>

      <!-- Main Content -->
      <main class="flex-1 px-4 py-6 pb-24 overflow-y-auto bg-slate-50/50">
        <router-view />
      </main>

      <!-- Bottom Navigation -->
      <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-slate-200 z-50 safe-bottom">
        <div class="flex items-center justify-around h-16">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex flex-col items-center justify-center flex-1 h-full min-h-touch transition-colors"
            :class="isActive(item.path) ? 'text-primary-600' : 'text-slate-400 hover:text-slate-600'"
          >
            <component :is="item.icon" class="w-5 h-5 mb-1" :class="isActive(item.path) ? 'stroke-[2.5px]' : 'stroke-2'" />
            <span class="text-[10px] font-semibold tracking-wide uppercase">{{ item.label }}</span>
          </router-link>

          <!-- Logout -->
          <button @click="handleLogout" class="flex flex-col items-center justify-center flex-1 h-full text-slate-400 hover:text-slate-600 transition-colors">
            <LogOut class="w-5 h-5 mb-1 stroke-2" />
            <span class="text-[10px] font-semibold tracking-wide uppercase">Đăng xuất</span>
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
