<template>
  <div class="min-h-screen bg-slate-50 flex justify-center font-sans">
    <!-- Responsive Container -->
    <div class="w-full md:max-w-[1440px] bg-slate-50 md:bg-transparent min-h-screen flex relative">

      <!-- Desktop Sidebar (Hidden on Mobile) -->
      <aside class="hidden md:flex flex-col w-64 bg-white border-r border-slate-200 h-screen sticky top-0 shrink-0 z-40">
        <!-- Header/Logo area -->
        <div class="h-16 flex items-center px-6 border-b border-slate-200">
          <ShieldCheck class="w-8 h-8 text-primary-600 shrink-0" />
          <h1 class="ml-3 text-lg font-bold text-slate-900 font-heading truncate">FBB Inspector</h1>
        </div>

        <!-- Nav items -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex items-center px-4 py-3 rounded-lg transition-colors"
            :class="isActive(item.path)
              ? 'bg-primary-50 text-primary-700 font-medium'
              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            <component :is="item.icon" class="w-5 h-5 mr-3" :class="isActive(item.path) ? 'text-primary-600' : 'text-slate-400'" :stroke-width="2" />
            <span class="text-sm font-medium">{{ item.label }}</span>
          </router-link>
        </nav>

        <!-- Status and Logout -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200 bg-slate-50/50">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
              <span class="text-primary-700 font-semibold">{{ userName.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-900 truncate tracking-tight">{{ userName }}</p>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ isOnline ? 'Online' : 'Offline' }}</span>
              </div>
            </div>
            <button @click="handleLogout" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
              <LogOut class="w-5 h-5" />
            </button>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0 max-w-md md:max-w-none mx-auto md:mx-0 w-full min-h-screen relative bg-white md:bg-transparent shadow-sm md:shadow-none border-x border-slate-200 md:border-none">
        
        <!-- Mobile Header (Hidden on Desktop) -->
        <header class="md:hidden bg-white border-b border-slate-200 px-5 pb-4 pt-5 sticky top-0 z-40">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <ShieldCheck class="w-7 h-7 text-primary-600" />
              <h1 class="text-lg font-bold text-slate-900 font-heading tracking-tight leading-none">FBB Inspector</h1>
            </div>

            <!-- Status -->
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg border"
              :class="isOnline
                ? 'bg-success/5 border-success/20 text-success'
                : 'bg-danger/5 border-danger/20 text-danger'"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
              <span class="text-[10px] font-bold uppercase tracking-wider">{{ isOnline ? 'Online' : 'Offline' }}</span>
            </div>
          </div>

          <h2 class="text-xl font-bold mt-5 text-slate-900 tracking-tight leading-tight">{{ pageTitle }}</h2>
        </header>

        <!-- Main Router View -->
        <main class="flex-1 h-full relative overflow-y-auto w-full md:pb-0 pb-24 z-10 md:bg-transparent">
          <router-view />
        </main>

        <!-- Mobile Bottom Navigation (Hidden on Desktop) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 max-w-md mx-auto bg-white border-t border-slate-100 z-50 safe-bottom">
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
