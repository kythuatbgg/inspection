<template>
  <div class="min-h-screen bg-slate-50 flex justify-center font-sans">
    <!-- Mobile Container -->
    <div class="w-full max-w-md bg-white shadow-sm border-x border-slate-200 min-h-screen flex flex-col relative">

      <!-- Top Header -->
      <header class="bg-white border-b border-slate-100 px-5 pb-4 pt-5 sticky top-0 z-40">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary-600 flex items-center justify-center">
              <ShieldCheck class="w-5 h-5 text-white" />
            </div>
            <div>
              <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-none">FBB Inspection</h1>
              <p class="text-[11px] text-slate-400 font-medium mt-0.5">{{ userName }}</p>
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
            :class="isOnline
              ? 'bg-emerald-50 text-emerald-600'
              : 'bg-red-50 text-red-500'"
          >
            <span class="w-1.5 h-1.5 rounded-full" :class="isOnline ? 'bg-emerald-500' : 'bg-red-500'"></span>
            {{ isOnline ? 'Online' : 'Offline' }}
          </div>
        </div>

        <h2 class="text-xl font-bold mt-5 text-slate-900 tracking-tight leading-tight">{{ pageTitle }}</h2>
      </header>

      <!-- Main Content -->
      <main class="flex-1 px-4 py-5 pb-24 overflow-y-auto bg-slate-50/50">
        <router-view />
      </main>

      <!-- Bottom Navigation -->
      <nav class="fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-slate-100 z-50 safe-bottom">
        <div class="flex items-stretch h-[60px]">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex flex-col items-center justify-center flex-1 gap-0.5 transition-colors relative"
            :class="isActive(item.path)
              ? 'text-primary-600'
              : 'text-slate-400 active:text-slate-600'"
          >
            <!-- Active indicator line -->
            <span v-if="isActive(item.path)" class="absolute top-0 left-1/2 -translate-x-1/2 w-8 h-[3px] rounded-full bg-primary-600"></span>
            <component :is="item.icon" class="w-[22px] h-[22px]" :stroke-width="isActive(item.path) ? 2.5 : 1.8" />
            <span class="text-[10px] font-semibold tracking-wide">{{ item.label }}</span>
          </router-link>

          <!-- Logout -->
          <button @click="handleLogout" class="flex flex-col items-center justify-center flex-1 gap-0.5 text-slate-400 active:text-slate-600 transition-colors">
            <LogOut class="w-[22px] h-[22px]" :stroke-width="1.8" />
            <span class="text-[10px] font-semibold tracking-wide">Thoát</span>
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
import { ShieldCheck, LogOut, Home, ListTodo, ClipboardEdit } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isOnline = ref(navigator.onLine)

const navItems = [
  { path: '/inspector', label: 'Trang chủ', icon: Home },
  { path: '/inspector/tasks', label: 'Nhiệm vụ', icon: ListTodo },
  { path: '/inspector/proposals', label: 'Đề xuất', icon: ClipboardEdit }
]

const pageTitle = computed(() => {
  if (route.path === '/inspector') return 'Tổng quan'
  if (route.path.startsWith('/inspector/batch/')) return 'Chi tiết lô'
  if (route.path === '/inspector/tasks') return 'Danh sách nhiệm vụ'
  if (route.path === '/inspector/proposals') return 'Đề xuất của tôi'
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
