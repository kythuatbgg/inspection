<template>
  <div class="min-h-screen bg-slate-50 flex justify-center font-sans">
    <!-- Responsive Container -->
    <div class="w-full md:max-w-[1440px] bg-slate-50 md:bg-transparent min-h-screen flex relative">

      <!-- Desktop Sidebar (Hidden on Mobile) -->
      <aside class="hidden md:flex flex-col w-64 bg-white border-r border-slate-200 h-screen sticky top-0 shrink-0 z-40">
        <!-- Header/Logo area -->
        <div class="p-5 border-b border-slate-100 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-primary-600 flex items-center justify-center shrink-0">
            <ShieldCheck class="w-5 h-5 text-white" />
          </div>
          <div class="min-w-0">
            <h1 class="text-sm font-bold text-slate-900 tracking-tight leading-none truncate">FBB Inspection</h1>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5 truncate">{{ userName }}</p>
          </div>
        </div>

        <!-- Nav items -->
        <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
          <router-link
            v-for="item in navItems"
            :key="item.path"
            :to="item.path"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors"
            :class="isActive(item.path)
              ? 'bg-primary-50 text-primary-700 font-semibold'
              : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900 font-medium'"
          >
            <component :is="item.icon" class="w-5 h-5 shrink-0" :stroke-width="isActive(item.path) ? 2.5 : 2" />
            <span class="text-sm truncate">{{ item.label }}</span>
          </router-link>
        </nav>

        <!-- Status and Logout -->
        <div class="p-4 border-t border-slate-100 space-y-3">
          <div class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider"
            :class="isOnline ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'"
          >
            <span class="w-2 h-2 rounded-full shrink-0" :class="isOnline ? 'bg-emerald-500' : 'bg-red-500'"></span>
            {{ isOnline ? 'Online' : 'Offline' }}
          </div>
          <button @click="handleLogout" class="flex items-center gap-3 px-3 py-2.5 w-full rounded-xl text-slate-500 hover:bg-slate-50 hover:text-slate-900 transition-colors font-medium">
            <LogOut class="w-5 h-5 shrink-0" :stroke-width="2" />
            <span class="text-sm">Đăng xuất</span>
          </button>
        </div>
      </aside>

      <!-- Main Content Area -->
      <div class="flex-1 flex flex-col min-w-0 max-w-md md:max-w-none mx-auto md:mx-0 w-full min-h-screen relative bg-white md:bg-transparent shadow-sm md:shadow-none border-x border-slate-200 md:border-none">
        
        <!-- Mobile Header (Hidden on Desktop) -->
        <header class="md:hidden bg-white border-b border-slate-100 px-5 pb-4 pt-5 sticky top-0 z-40">
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
