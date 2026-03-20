<template>
  <div class="min-h-screen flex bg-dark-bg">
    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 w-64 bg-dark-surface border-r border-gray-700/50 transform transition-transform duration-200 lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-gray-700/50">
        <ShieldCheck class="w-8 h-8 text-primary-400" />
        <span class="ml-3 text-lg font-bold text-gray-100 font-heading">FBB Inspection</span>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center px-4 py-3 text-gray-400 rounded-lg hover:bg-dark-elevated hover:text-gray-200 transition-colors"
          :class="{ 'bg-primary-500/10 text-primary-400 font-medium': isActive(item.path) }"
          @click="sidebarOpen = false"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" />
          {{ item.label }}
        </router-link>
      </nav>

      <!-- User Info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary-500/20 flex items-center justify-center">
            <span class="text-primary-400 font-medium">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-200 truncate">{{ userName }}</p>
            <p class="text-xs text-gray-500">Quản lý</p>
          </div>
          <button @click="handleLogout" class="p-2 text-gray-500 hover:text-gray-300 transition-colors">
            <LogOut class="w-5 h-5" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/70 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 lg:ml-64">
      <!-- Top Header -->
      <header class="h-16 bg-dark-surface/80 backdrop-blur-xl border-b border-gray-700/50 flex items-center justify-between px-4 lg:px-8">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" class="lg:hidden p-2 text-gray-400 hover:bg-dark-elevated rounded-lg">
          <Menu class="w-6 h-6" />
        </button>

        <!-- Page Title -->
        <h1 class="text-lg font-semibold text-gray-100 hidden lg:block font-heading">{{ pageTitle }}</h1>

        <!-- Right Actions -->
        <div class="flex items-center gap-4">
          <!-- Online Status -->
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-full" :class="isOnline ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'">
            <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-red-500'"></span>
            <span class="text-sm font-medium">{{ isOnline ? 'Online' : 'Offline' }}</span>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="p-4 lg:p-8">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ShieldCheck, Menu, LogOut, LayoutDashboard, FileStack, Server, Users, Settings, ClipboardList } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(false)
const isOnline = ref(navigator.onLine)
const navItems = [
  { path: '/admin', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/admin/batches', label: 'Lô kiểm tra', icon: FileStack },
  { path: '/admin/cabinets', label: 'Tủ cáp', icon: Server },
  { path: '/admin/checklists', label: 'Checklist', icon: ClipboardList },
  { path: '/admin/users', label: 'Người dùng', icon: Users },
  { path: '/admin/settings', label: 'Cài đặt', icon: Settings }
]

const pageTitle = computed(() => {
  const currentItem = navItems.find(item => isActive(item.path))
  return currentItem?.label || 'Dashboard'
})

const userName = computed(() => authStore.user?.name || 'Admin')
const userInitials = computed(() => {
  const name = userName.value
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const isActive = (path) => {
  if (path === '/admin') {
    return route.path === '/admin' || route.path === '/admin/'
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
