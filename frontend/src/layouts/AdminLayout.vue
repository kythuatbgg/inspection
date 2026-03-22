<template>
  <div class="min-h-screen flex bg-slate-50 font-sans">
    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-slate-200 transform transition-transform duration-200 lg:translate-x-0"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-200">
        <ShieldCheck class="w-8 h-8 text-primary-600" />
        <span class="ml-3 text-lg font-bold text-slate-900 font-heading">FBB Inspection</span>
      </div>

      <!-- Navigation -->
      <nav class="p-4 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center px-4 py-3 text-slate-600 rounded-lg hover:bg-slate-50 hover:text-slate-900 transition-colors"
          :class="{ 'bg-primary-50 text-primary-700 font-medium': isActive(item.path) }"
          @click="sidebarOpen = false"
        >
          <component :is="item.icon" class="w-5 h-5 mr-3" :class="isActive(item.path) ? 'text-primary-600' : 'text-slate-400'" />
          <span class="text-sm font-medium">{{ $t(item.labelKey) }}</span>
        </router-link>
      </nav>

      <!-- User Info -->
      <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200 bg-slate-50/50 space-y-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
            <span class="text-primary-700 font-semibold">{{ userInitials }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-900 truncate">{{ userName }}</p>
            <p class="text-xs text-slate-500 font-medium">{{ $t('roles.' + (authStore.user?.role || 'admin')) }}</p>
          </div>
          <button @click="handleLogout" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200/50 rounded-lg transition-colors">
            <LogOut class="w-5 h-5" />
          </button>
        </div>
        <!-- Language Switcher -->
        <button @click="toggleLanguage" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-sm font-medium text-slate-600 transition-colors">
          <Languages class="w-4 h-4 text-slate-400" />
          <span>{{ currentLocale === 'vi' ? 'Tiếng Việt' : 'English' }}</span>
        </button>
      </div>
    </aside>

    <!-- Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <!-- Main Content -->
    <div class="flex-1 min-w-0 lg:ml-64">
      <!-- Top Header -->
      <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 shadow-sm">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = true" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg">
          <Menu class="w-6 h-6" />
        </button>

        <!-- Page Title -->
        <h1 class="text-xl font-semibold text-slate-900 hidden lg:block font-heading tracking-tight">{{ pageTitle }}</h1>

        <!-- Right Actions -->
        <div class="flex items-center gap-4">
          <!-- Online Status -->
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border" :class="isOnline ? 'bg-success/5 border-success/20 text-success' : 'bg-danger/5 border-danger/20 text-danger'">
            <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
            <span class="text-sm font-medium">{{ isOnline ? $t('common.online') : $t('common.offline') }}</span>
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
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { ShieldCheck, Menu, LogOut, LayoutDashboard, FileStack, Server, Users, Settings, ClipboardList, Languages, FileBarChart } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const { locale: currentLocale } = useI18n()

const sidebarOpen = ref(false)
const isOnline = ref(navigator.onLine)
const navItems = [
  { path: '/admin', labelKey: 'nav.dashboard', icon: LayoutDashboard },
  { path: '/admin/batches', labelKey: 'nav.batches', icon: FileStack },
  { path: '/admin/cabinets', labelKey: 'nav.cabinets', icon: Server },
  { path: '/admin/checklists', labelKey: 'nav.checklists', icon: ClipboardList },
  { path: '/admin/reports', labelKey: 'reports.title', icon: FileBarChart },
  { path: '/admin/users', labelKey: 'nav.users', icon: Users },
  { path: '/admin/settings', labelKey: 'nav.settings', icon: Settings }
]

const { t } = useI18n()
const pageTitle = computed(() => {
  const currentItem = navItems.find(item => isActive(item.path))
  return currentItem ? t(currentItem.labelKey) : 'Dashboard'
})

const toggleLanguage = () => {
  authStore.setLanguage(currentLocale.value === 'vi' ? 'en' : 'vi')
}

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

const handleLogout = async () => {
  await authStore.logout()
  router.replace({ name: 'login' })
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
