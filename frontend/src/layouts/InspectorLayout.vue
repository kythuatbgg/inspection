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
            <span class="text-sm font-medium">{{ $t(item.labelKey) }}</span>
          </router-link>
        </nav>

        <!-- Status and Logout -->
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-slate-200 bg-slate-50/50 space-y-3">
          <!-- Sync Status -->
          <div v-if="isSyncing" class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-lg text-primary-600">
            <Loader2 class="w-4 h-4 animate-spin shrink-0" />
            <span class="text-xs font-medium">{{ $t('inspector.syncing') }}</span>
          </div>
          <div v-else-if="isExhausted" class="space-y-2">
            <div class="flex items-start gap-2 px-3 py-2 bg-danger/10 border border-danger/20 rounded-lg text-danger">
              <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold leading-snug">
                  {{ syncError === 'token_expired' ? $t('sync.errorTokenExpired') : $t('sync.errorSyncFailed') }}
                </p>
                <p v-if="failedCount > 0" class="text-[10px] mt-0.5 opacity-75">
                  {{ $t('sync.failedCount', { n: failedCount }) }}
                </p>
              </div>
            </div>
            <!-- Retry / Re-login buttons — full-width mobile -->
            <div class="flex gap-2">
              <button
                v-if="syncError === 'token_expired'"
                @click="handleRelogin"
                class="flex-1 btn-primary text-xs text-center min-h-[40px]"
              >
                {{ $t('sync.relogin') }}
              </button>
              <button
                @click="retryManually"
                class="flex-1 btn-secondary text-xs text-center min-h-[40px] flex items-center justify-center gap-1"
              >
                <RefreshCw class="w-3.5 h-3.5" />
                {{ $t('sync.retry') }}
              </button>
            </div>
          </div>
          <div v-else-if="draftCount > 0" class="flex items-center gap-2 px-3 py-2 bg-amber-50 rounded-lg text-amber-600">
            <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
            <span class="text-xs font-medium">{{ $t('inspector.pendingSync', { n: draftCount }) }}</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
              <span class="text-primary-700 font-semibold">{{ userName.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-900 truncate tracking-tight">{{ userName }}</p>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-success' : 'bg-danger'"></span>
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-500">{{ isOnline ? $t('common.online') : $t('common.offline') }}</span>
              </div>
            </div>
            <button @click="handleLogout" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">
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
              <span class="text-[10px] font-bold uppercase tracking-wider">{{ isOnline ? $t('common.online') : $t('common.offline') }}</span>
            </div>
          </div>

          <h2 class="text-xl font-bold mt-5 text-slate-900 tracking-tight leading-tight">{{ pageTitle }}</h2>
        </header>

        <!-- PWA Install Banner -->
        <div v-if="canInstall && showBanner" class="fixed bottom-20 md:bottom-4 left-4 right-4 max-w-md md:left-auto md:right-6 md:max-w-sm mx-auto bg-primary-600 text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 z-50">
          <Smartphone class="w-6 h-6 shrink-0 mt-0.5" />
          <div class="flex-1 min-w-0">
            <p class="font-bold text-sm leading-snug">{{ $t('pwa.installTitle') }}</p>
            <p class="text-xs text-white/80 mt-0.5 leading-snug">{{ $t('pwa.installHint') }}</p>
          </div>
          <button @click="install" class="shrink-0 bg-white text-primary-600 font-bold text-xs px-3 py-1.5 rounded-xl">
            {{ $t('pwa.installCta') }}
          </button>
          <button @click="dismiss" class="shrink-0 text-white/60 hover:text-white p-1">
            <span class="text-sm leading-none">✕</span>
          </button>
        </div>

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
              <span class="text-[10px] font-semibold tracking-wide">{{ $t(item.labelKey) }}</span>
            </router-link>

            <!-- Logout -->
            <button @click="handleLogout" class="flex flex-col items-center justify-center flex-1 gap-0.5 text-slate-400 active:text-slate-600 transition-colors">
              <LogOut class="w-[22px] h-[22px]" :stroke-width="1.8" />
              <span class="text-[10px] font-semibold tracking-wide">{{ $t('nav.logout') }}</span>
            </button>
          </div>
        </nav>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { useSyncStore } from '@/stores/syncStore'
import { useOfflineSync } from '@/composables/useOfflineSync'
import { useInstallPrompt } from '@/composables/useInstallPrompt'
import { ShieldCheck, LogOut, Home, ListTodo, ClipboardEdit, Languages, FileBarChart, Loader2, Smartphone, AlertCircle, RefreshCw } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const syncStore = useSyncStore()
const { t, locale: currentLocale } = useI18n()

// Sync state from offline composable
const { isOnline, isSyncing, retryManually } = useOfflineSync()
const { draftCount, failedCount, isExhausted, syncError } = storeToRefs(syncStore)

// PWA install prompt
const { canInstall, showBanner, install, dismiss } = useInstallPrompt()

const navItems = [
  { path: '/inspector', labelKey: 'nav.home', icon: Home },
  { path: '/inspector/tasks', labelKey: 'nav.myBatches', icon: ListTodo },
  { path: '/inspector/proposals', labelKey: 'nav.proposals', icon: ClipboardEdit },
  { path: '/inspector/reports', labelKey: 'nav.reports', icon: FileBarChart }
]

const pageTitle = computed(() => {
  if (route.path === '/inspector') return t('inspector.overview')
  if (route.path === '/inspector/reports') return t('inspector.myReports')
  if (route.path.startsWith('/inspector/batch/')) return t('inspector.batchDetail')
  if (route.path === '/inspector/tasks') return t('inspector.myBatches')
  if (route.path === '/inspector/proposals') return t('inspector.myProposals')
  if (route.path.startsWith('/inspector/inspect/')) return t('inspector.inspection')
  return 'FBB Inspection'
})

const userName = computed(() => authStore.user?.name || t('inspector.defaultUser'))

const toggleLanguage = () => {
  authStore.setLanguage(currentLocale.value === 'vi' ? 'en' : 'vi')
}

const isActive = (path) => {
  if (path === '/inspector') {
    return route.path === '/inspector' || route.path === '/inspector/'
  }
  return route.path.startsWith(path)
}

const handleLogout = async () => {
  await authStore.logout()
  router.replace({ name: 'login' })
}

const handleRelogin = async () => {
  await authStore.logout()
  syncStore.resetAll()
  router.replace({ name: 'login' })
}
</script>
