# Frontend Implementation Plan - FBB Inspection PWA

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Khởi tạo Vue 3 Frontend với PWA, Pinia, Tailwind CSS, Dexie.js cho offline-first inspection app

**Architecture:** Vue 3 Composition API với Vite, state management qua Pinia, offline storage qua Dexie.js, PWA via vite-plugin-pwa

**Tech Stack:** Vue 3, Vite, Pinia, Tailwind CSS, Dexie.js, vite-plugin-pwa

---

## Chunk 1: Project Setup & Configuration

### Task 1: Initialize Vue 3 + Vite Project

**Files:**
- Create: `D:\DEV\Claude\inspection\frontend\package.json`
- Create: `D:\DEV\Claude\inspection\frontend\vite.config.js`
- Create: `D:\DEV\Claude\inspection\frontend\index.html`
- Create: `D:\DEV\Claude\inspection\frontend\tailwind.config.js`
- Create: `D:\DEV\Claude\inspection\frontend\postcss.config.js`

- [ ] **Step 1: Create package.json**

```json
{
  "name": "fbb-inspection-frontend",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.0",
    "pinia": "^2.1.0",
    "axios": "^1.6.0",
    "dexie": "^3.2.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0",
    "vite-plugin-pwa": "^0.19.0",
    "tailwindcss": "^3.4.0",
    "postcss": "^8.4.0",
    "autoprefixer": "^10.4.0"
  }
}
```

- [ ] **Step 2: Create vite.config.js**

```javascript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['favicon.ico', 'robots.txt'],
      manifest: {
        name: 'FBB Inspection',
        short_name: 'FBB Inspect',
        description: 'FBB Cable Cabinet Inspection App',
        theme_color: '#2563eb',
        background_color: '#ffffff',
        display: 'standalone',
        icons: [
          {
            src: '/icon-192.png',
            sizes: '192x192',
            type: 'image/png'
          },
          {
            src: '/icon-512.png',
            sizes: '512x512',
            type: 'image/png'
          }
        ]
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,json}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/api\..*/i,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              expiration: {
                maxEntries: 100,
                maxAgeSeconds: 60 * 60 * 24
              },
              networkTimeoutSeconds: 10
            }
          }
        ]
      }
    })
  ],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})
```

- [ ] **Step 3: Create index.html**

```html
<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#2563eb" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />
    <title>FBB Inspection</title>
  </head>
  <body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
  </body>
</html>
```

- [ ] **Step 4: Create tailwind.config.js**

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#2563eb',
        success: '#22c55e',
        danger: '#ef4444',
        warning: '#f59e0b',
      }
    },
  },
  plugins: [],
}
```

- [ ] **Step 5: Create postcss.config.js**

```javascript
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

- [ ] **Step 6: Create src directory structure**

Run:
```bash
mkdir -p src/assets src/components src/views src/router src/stores src/services src/db
```

- [ ] **Step 7: Create src/main.js**

```javascript
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
```

- [ ] **Step 8: Create src/style.css**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

body {
  -webkit-tap-highlight-color: transparent;
}
```

- [ ] **Step 9: Create src/App.vue**

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <router-view />
  </div>
</template>

<script setup>
</script>
```

- [ ] **Step 10: Create src/router/index.js**

```javascript
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/inspection/:planId',
    name: 'Inspection',
    component: () => import('../views/InspectionView.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/sync',
    name: 'Sync',
    component: () => import('../views/SyncView.vue'),
    meta: { requiresAuth: true }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
  } else if (to.path === '/login' && authStore.isAuthenticated) {
    next('/')
  } else {
    next()
  }
})

export default router
```

- [ ] **Step 11: Install dependencies**

Run:
```bash
cd D:/DEV/Claude/inspection/frontend && npm install
```

Expected: Dependencies installed successfully

- [ ] **Step 12: Test dev server starts**

Run:
```bash
cd D:/DEV/Claude/inspection/frontend && npm run dev
```

Expected: Server running on http://localhost:5173

- [ ] **Step 13: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/ && git commit -m "feat: initialize Vue 3 + Vite project with PWA"
```

---

## Chunk 2: State Management & API Services

### Task 2: Create Pinia Stores

**Files:**
- Create: `src/stores/auth.js`
- Create: `src/stores/batches.js`
- Create: `src/stores/checklists.js`
- Create: `src/stores/inspections.js`

- [ ] **Step 1: Create auth store**

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'
import { db } from '../db'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isInspector: (state) => state.user?.role === 'inspector',
    userLanguage: (state) => state.user?.lang_pref || 'vn'
  },

  actions: {
    async login(username, password) {
      this.loading = true
      try {
        const response = await axios.post(`${API_URL}/login`, { username, password })
        this.token = response.data.token
        this.user = response.data.user
        localStorage.setItem('token', this.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        return true
      } catch (error) {
        console.error('Login failed:', error)
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await axios.post(`${API_URL}/logout`)
      } catch (e) {
        // Ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    },

    async fetchUser() {
      if (!this.token) return
      try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const response = await axios.get(`${API_URL}/me`)
        this.user = response.data
      } catch (error) {
        this.logout()
      }
    }
  }
})
```

- [ ] **Step 2: Create batches store**

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useBatchesStore = defineStore('batches', {
  state: () => ({
    batches: [],
    currentBatch: null,
    loading: false
  }),

  getters: {
    activeBatches: (state) => state.batches.filter(b => b.status === 'active'),
    pendingPlans: (state) => state.currentBatch?.plan_details?.filter(p => p.status === 'planned') || []
  },

  actions: {
    async fetchBatches() {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/batches`)
        this.batches = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchBatch(id) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/batches/${id}`)
        this.currentBatch = response.data.data
      } finally {
        this.loading = false
      }
    },

    async createBatch(data) {
      const response = await axios.post(`${API_URL}/batches`, data)
      this.batches.push(response.data.data)
      return response.data.data
    },

    async updateBatchStatus(id, status) {
      const response = await axios.patch(`${API_URL}/batches/${id}`, { status })
      const index = this.batches.findIndex(b => b.id === id)
      if (index !== -1) this.batches[index] = response.data.data
      return response.data.data
    }
  }
})
```

- [ ] **Step 3: Create checklists store**

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useChecklistsStore = defineStore('checklists', {
  state: () => ({
    checklists: [],
    currentChecklist: null,
    loading: false
  }),

  getters: {
    getChecklistById: (state) => (id) => state.checklists.find(c => c.id === id)
  },

  actions: {
    async fetchChecklists() {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/checklists`)
        this.checklists = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchChecklist(id) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/checklists/${id}`)
        this.currentChecklist = response.data.data
        return this.currentChecklist
      } finally {
        this.loading = false
      }
    }
  }
})
```

- [ ] **Step 4: Create inspections store**

```javascript
import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useInspectionsStore = defineStore('inspections', {
  state: () => ({
    currentInspection: null,
    loading: false
  }),

  actions: {
    async createInspection(data) {
      this.loading = true
      try {
        const response = await axios.post(`${API_URL}/inspections`, data)
        this.currentInspection = response.data.data
        return this.currentInspection
      } finally {
        this.loading = false
      }
    },

    async getInspectionForPlan(planId) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/plans/${planId}/inspection`)
        this.currentInspection = response.data.data
        return this.currentInspection
      } catch (e) {
        return null
      } finally {
        this.loading = false
      }
    }
  }
})
```

- [ ] **Step 5: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/stores/ && git commit -m "feat: add Pinia stores for auth, batches, checklists, inspections"
```

---

## Chunk 3: Offline Database (Dexie.js)

### Task 3: Setup Dexie.js Database

**Files:**
- Create: `src/db/index.js`

- [ ] **Step 1: Create Dexie database schema**

```javascript
import Dexie from 'dexie'

export const db = new Dexie('FBBInspectionDB')

db.version(1).stores({
  cabinets: 'cabinet_code, bts_site, name',
  checklists: 'id, name',
  checklistItems: 'id, checklist_id, category',
  batches: 'id, user_id, status',
  planDetails: 'id, batch_id, cabinet_code, status',
  inspections: '++id, plan_detail_id, cabinet_code, sync_status, created_at',
  inspectionDetails: '++id, inspection_id, item_id'
})

// Helper functions
export const syncService = {
  async syncCabinets() {
    const { data } = await fetch('/api/cabinets').then(r => r.json())
    await db.cabinets.bulkPut(data)
    return data.length
  },

  async syncChecklists() {
    const { data } = await fetch('/api/checklists').then(r => r.json())
    await db.checklists.bulkPut(data)
    return data.length
  },

  async syncBatches() {
    const { data } = await fetch('/api/batches').then(r => r.json())
    await db.batches.bulkPut(data)
    return data.length
  },

  async syncPlanDetails(batchId) {
    const { data } = await fetch(`/api/batches/${batchId}/plans`).then(r => r.json())
    await db.planDetails.bulkPut(data)
    return data.length
  },

  async getPendingInspections() {
    return await db.inspections
      .where('sync_status')
      .equals('draft')
      .toArray()
  },

  async markAsSynced(inspectionId) {
    await db.inspections.update(inspectionId, { sync_status: 'synced' })
  },

  async clearAll() {
    await Promise.all([
      db.cabinets.clear(),
      db.checklists.clear(),
      db.checklistItems.clear(),
      db.batches.clear(),
      db.planDetails.clear(),
      db.inspections.clear(),
      db.inspectionDetails.clear()
    ])
  }
}
```

- [ ] **Step 2: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/db/ && git commit -m "feat: add Dexie.js offline database"
```

---

## Chunk 4: Views & Components

### Task 4: Create Login View

**Files:**
- Create: `src/views/LoginView.vue`
- Modify: `src/stores/auth.js` (add offline token handling)

- [ ] **Step 1: Create LoginView.vue**

```vue
<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-500 to-blue-700">
    <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-800">FBB Inspection</h1>
        <p class="text-gray-500 mt-2">Đăng nhập để tiếp tục</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Tên đăng nhập</label>
          <input
            v-model="username"
            type="text"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Nhập tên đăng nhập"
            required
          />
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 text-sm font-bold mb-2">Mật khẩu</label>
          <input
            v-model="password"
            type="password"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Nhập mật khẩu"
            required
          />
        </div>

        <div v-if="error" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50"
        >
          {{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
        </button>
      </form>

      <div class="mt-6 text-center text-sm text-gray-500">
        <span>Offline mode: </span>
        <span :class="isOnline ? 'text-green-600' : 'text-red-600'">
          {{ isOnline ? 'Online' : 'Offline' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const isOnline = ref(navigator.onLine)

onMounted(() => {
  window.addEventListener('online', () => isOnline.value = true)
  window.addEventListener('offline', () => isOnline.value = false)

  // Check if already logged in
  if (authStore.isAuthenticated) {
    router.push('/')
  }
})

const handleLogin = async () => {
  error.value = ''
  loading.value = true

  try {
    const success = await authStore.login(username.value, password.value)
    if (success) {
      router.push('/')
    } else {
      error.value = 'Tên đăng nhập hoặc mật khẩu không đúng'
    }
  } catch (e) {
    error.value = 'Có lỗi xảy ra. Vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>
```

- [ ] **Step 2: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/views/ frontend/src/stores/auth.js && git commit -m "feat: add LoginView with offline indicator"
```

### Task 5: Create Dashboard View

**Files:**
- Create: `src/views/DashboardView.vue`

- [ ] **Step 1: Create DashboardView.vue**

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <h1 class="text-xl font-bold text-gray-800">FBB Inspection</h1>
        <div class="flex items-center gap-4">
          <span class="text-sm" :class="isOnline ? 'text-green-600' : 'text-red-600'">
            {{ isOnline ? 'Online' : 'Offline' }}
          </span>
          <button @click="syncData" class="text-blue-600 hover:text-blue-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
          <button @click="logout" class="text-gray-600 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
          </button>
        </div>
      </div>
    </header>

    <!-- Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
      <!-- User Info -->
      <div class="bg-white rounded-lg shadow p-4 mb-6">
        <p class="text-gray-600">Xin chào, <span class="font-semibold">{{ user?.name }}</span></p>
        <p class="text-sm text-gray-500">Vai trò: {{ user?.role === 'admin' ? 'Quản lý' : 'Nhân viên' }}</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-500 text-white rounded-lg p-4 text-center">
          <p class="text-2xl font-bold">{{ totalPlans }}</p>
          <p class="text-sm">Tổng công việc</p>
        </div>
        <div class="bg-green-500 text-white rounded-lg p-4 text-center">
          <p class="text-2xl font-bold">{{ completedPlans }}</p>
          <p class="text-sm">Đã hoàn thành</p>
        </div>
        <div class="bg-yellow-500 text-white rounded-lg p-4 text-center">
          <p class="text-2xl font-bold">{{ pendingPlans }}</p>
          <p class="text-sm">Chờ thực hiện</p>
        </div>
      </div>

      <!-- Batch List -->
      <div class="space-y-4">
        <h2 class="text-lg font-semibold text-gray-800">Danh sách đợt kiểm tra</h2>

        <div v-if="loading" class="text-center py-8">
          <p class="text-gray-500">Đang tải...</p>
        </div>

        <div v-else-if="batches.length === 0" class="text-center py-8">
          <p class="text-gray-500">Không có đợt kiểm tra nào</p>
        </div>

        <div
          v-else
          v-for="batch in batches"
          :key="batch.id"
          class="bg-white rounded-lg shadow p-4"
        >
          <div class="flex justify-between items-start mb-3">
            <div>
              <h3 class="font-semibold text-gray-800">{{ batch.name }}</h3>
              <p class="text-sm text-gray-500">
                {{ formatDate(batch.start_date) }} - {{ formatDate(batch.end_date) }}
              </p>
            </div>
            <span
              :class="{
                'bg-yellow-100 text-yellow-800': batch.status === 'pending',
                'bg-green-100 text-green-800': batch.status === 'active',
                'bg-gray-100 text-gray-800': batch.status === 'completed'
              }"
              class="px-2 py-1 text-xs font-semibold rounded"
            >
              {{ batch.status === 'pending' ? 'Chờ' : batch.status === 'active' ? 'Đang thực hiện' : 'Hoàn thành' }}
            </span>
          </div>

          <div class="text-sm text-gray-600 mb-3">
            <p>Kế hoạch: {{ batch.plan_details?.length || 0 }} tủ</p>
            <p>Hoàn thành: {{ batch.plan_details?.filter(p => p.status === 'done').length || 0 }} tủ</p>
          </div>

          <button
            v-if="batch.status === 'active'"
            @click="viewBatch(batch.id)"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg"
          >
            Xem chi tiết
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useBatchesStore } from '../stores/batches'

const router = useRouter()
const authStore = useAuthStore()
const batchesStore = useBatchesStore()

const loading = ref(true)
const isOnline = ref(navigator.onLine)

const user = computed(() => authStore.user)
const batches = computed(() => batchesStore.batches)
const totalPlans = computed(() => {
  return batches.value.reduce((sum, b) => sum + (b.plan_details?.length || 0), 0)
})
const completedPlans = computed(() => {
  return batches.value.reduce((sum, b) => {
    return sum + (b.plan_details?.filter(p => p.status === 'done').length || 0)
  }, 0)
})
const pendingPlans = computed(() => totalPlans.value - completedPlans.value)

onMounted(async () => {
  window.addEventListener('online', () => isOnline.value = true)
  window.addEventListener('offline', () => isOnline.value = false)

  await batchesStore.fetchBatches()
  loading.value = false
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('vi-VN')
}

const viewBatch = (id) => {
  router.push(`/batch/${id}`)
}

const syncData = async () => {
  if (!isOnline.value) {
    alert('Không có kết nối mạng')
    return
  }
  await batchesStore.fetchBatches()
}

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}
</script>
```

- [ ] **Step 2: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/views/DashboardView.vue && git commit -m "feat: add DashboardView with batch list"
```

### Task 6: Create Inspection View

**Files:**
- Create: `src/views/InspectionView.vue`

- [ ] **Step 1: Create InspectionView.vue**

```vue
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center gap-4">
          <button @click="goBack" class="text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold text-gray-800">Kiểm tra: {{ cabinetCode }}</h1>
            <p class="text-sm text-gray-500">{{ checklistName }}</p>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Đang tải...</p>
      </div>

      <div v-else-if="items.length === 0" class="text-center py-8">
        <p class="text-gray-500">Không có câu hỏi nào</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="(item, index) in items"
          :key="item.id"
          class="bg-white rounded-lg shadow p-4"
        >
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-blue-600 font-semibold">{{ index + 1 }}</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-gray-500">{{ item.category }}</span>
                <span v-if="item.is_critical" class="px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded">
                  Critical
                </span>
              </div>
              <p class="text-gray-800 font-medium">{{ item.content }}</p>
              <p class="text-sm text-gray-500 mt-1">Điểm tối đa: {{ item.max_score }}</p>
            </div>
          </div>

          <div class="mt-4 flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                :name="`item-${item.id}`"
                :checked="!answers[item.id]?.is_failed"
                @change="setAnswer(item.id, false, item.max_score)"
                class="w-5 h-5 text-green-600"
              />
              <span class="text-green-600 font-medium">Đạt</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                :name="`item-${item.id}`"
                :checked="answers[item.id]?.is_failed"
                @change="handleFail(item.id, item)"
                class="w-5 h-5 text-red-600"
              />
              <span class="text-red-600 font-medium">Không đạt</span>
            </label>
          </div>

          <!-- Image capture for failed items -->
          <div v-if="answers[item.id]?.is_failed && item.is_critical" class="mt-4">
            <p class="text-sm text-red-600 mb-2">* Chụp ảnh minh chứng</p>
            <div v-if="!answers[item.id]?.image" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
              <input
                type="file"
                :id="`camera-${item.id}`"
                accept="image/*"
                capture="environment"
                class="hidden"
                @change="(e) => captureImage(item.id, e)"
              />
              <label :for="`camera-${item.id}`" class="cursor-pointer">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-gray-500 mt-2">Chụp ảnh</p>
              </label>
            </div>
            <div v-else class="relative inline-block">
              <img :src="answers[item.id].image" class="max-w-full h-48 rounded" />
              <button
                @click="removeImage(item.id)"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="sticky bottom-4">
          <button
            @click="submitInspection"
            :disabled="submitting || !canSubmit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg shadow-lg disabled:opacity-50"
          >
            {{ submitting ? 'Đang lưu...' : 'Hoàn thành kiểm tra' }}
          </button>
          <p v-if="!canSubmit" class="text-center text-sm text-red-600 mt-2">
            Vui lòng trả lời tất cả câu hỏi
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useChecklistsStore } from '../stores/checklists'
import { useInspectionsStore } from '../stores/inspections'
import { db } from '../db'
import { captureWatermark } from '../services/watermark'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const checklistsStore = useChecklistsStore()
const inspectionsStore = useInspectionsStore()

const loading = ref(true)
const submitting = ref(false)
const checklistId = ref(null)
const cabinetCode = ref('')
const items = ref([])
const answers = ref({})

const userLanguage = computed(() => authStore.userLanguage)

const canSubmit = computed(() => {
  return items.value.every(item => answers.value[item.id] !== undefined)
})

onMounted(async () => {
  const planId = route.params.planId
  // Fetch plan details and checklist
  // For now, use mock data - will integrate with API
  loading.value = false
})

const getContent = (item) => {
  const lang = userLanguage.value
  return item[`content_${lang}`] || item.content_vn
}

const setAnswer = (itemId, isFailed, score) => {
  answers.value[itemId] = {
    is_failed: isFailed,
    score_awarded: isFailed ? 0 : score,
    item_id: itemId
  }
}

const handleFail = (itemId, item) => {
  setAnswer(itemId, true, item.max_score)
}

const captureImage = async (itemId, event) => {
  const file = event.target.files[0]
  if (!file) return

  try {
    // Get GPS location
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000
      })
    })

    const watermarkImage = await captureWatermark(file, {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
      cabinetCode: cabinetCode.value,
      timestamp: new Date().toISOString()
    })

    answers.value[itemId].image = watermarkImage
  } catch (error) {
    console.error('Failed to capture image:', error)
    alert('Không thể chụp ảnh. Vui lòng kiểm tra quyền camera.')
  }
}

const removeImage = (itemId) => {
  delete answers.value[itemId].image
}

const goBack = () => {
  router.back()
}

const submitInspection = async () => {
  submitting.value = true

  try {
    const details = Object.values(answers.value).map(a => ({
      item_id: a.item_id,
      is_failed: a.is_failed,
      score_awarded: a.score_awarded,
      image_url: a.image || null
    }))

    // Save to local DB first
    const inspectionId = await db.inspections.add({
      plan_detail_id: route.params.planId,
      checklist_id: checklistId.value,
      cabinet_code: cabinetCode.value,
      details,
      sync_status: navigator.onLine ? 'synced' : 'draft',
      created_at: new Date().toISOString()
    })

    // Try to sync if online
    if (navigator.onLine) {
      try {
        await inspectionsStore.createInspection({
          plan_detail_id: route.params.planId,
          checklist_id: checklistId.value,
          cabinet_code: cabinetCode.value,
          lat: 0,
          lng: 0,
          details
        })
      } catch (e) {
        console.warn('Sync failed, saved locally')
      }
    }

    router.push('/')
  } catch (error) {
    console.error('Submit failed:', error)
    alert('Có lỗi xảy ra. Vui lòng thử lại.')
  } finally {
    submitting.value = false
  }
}
</script>
```

- [ ] **Step 2: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/views/InspectionView.vue && git commit -m "feat: add InspectionView with form and camera"
```

---

## Chunk 5: Watermark Service

### Task 7: Create Watermark Service

**Files:**
- Create: `src/services/watermark.js`

- [ ] **Step 1: Create watermark.js**

```javascript
/**
 * Capture image with GPS watermark
 * Adds lat/lng, cabinet code, and timestamp to image
 */

export async function captureWatermark(file, metadata) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    const reader = new FileReader()

    reader.onload = (e) => {
      img.onload = () => {
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')

        // Set canvas size to match image
        canvas.width = img.width
        canvas.height = img.height

        // Draw original image
        ctx.drawImage(img, 0, 0)

        // Watermark settings
        const fontSize = Math.max(16, Math.floor(img.width / 40))
        ctx.font = `${fontSize}px Arial`
        ctx.textAlign = 'right'

        // Semi-transparent background for text
        const textLines = [
          metadata.cabinetCode,
          `Lat: ${metadata.lat.toFixed(6)}`,
          `Lng: ${metadata.lng.toFixed(6)}`,
          new Date(metadata.timestamp).toLocaleString('vi-VN')
        ]

        const lineHeight = fontSize * 1.4
        const padding = fontSize
        const boxWidth = Math.max(...textLines.map(t => ctx.measureText(t).width)) + padding * 2
        const boxHeight = lineHeight * textLines.length + padding * 2

        // Draw watermark box (bottom right)
        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)'
        ctx.fillRect(
          canvas.width - boxWidth - 20,
          canvas.height - boxHeight - 20,
          boxWidth,
          boxHeight
        )

        // Draw text
        ctx.fillStyle = '#ffffff'
        textLines.forEach((line, index) => {
          const y = canvas.height - 20 - boxHeight + padding + (index + 1) * lineHeight - fontSize / 2
          ctx.fillText(line, canvas.width - 20 - padding, y)
        })

        // Compress and return as base64
        const base64 = canvas.toDataURL('image/jpeg', 0.6)
        resolve(base64)
      }

      img.onerror = reject
      img.src = e.target.result
    }

    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

/**
 * Get current GPS position
 */
export function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation not supported'))
      return
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    })
  })
}
```

- [ ] **Step 2: Commit**

```bash
cd D:/DEV/Claude/inspection && git add frontend/src/services/watermark.js && git commit -m "feat: add watermark service for GPS-tagged photos"
```

---

## Summary

**Total Tasks:** 7 tasks across 5 chunks
**Estimated Time:** 2-3 hours

### Files Created:
- `frontend/package.json` - Project dependencies
- `frontend/vite.config.js` - Vite + PWA config
- `frontend/index.html` - Entry HTML
- `frontend/tailwind.config.js` - Tailwind config
- `frontend/postcss.config.js` - PostCSS config
- `frontend/src/main.js` - Vue app entry
- `frontend/src/style.css` - Global styles
- `frontend/src/App.vue` - Root component
- `frontend/src/router/index.js` - Vue Router
- `frontend/src/db/index.js` - Dexie.js database
- `frontend/src/stores/auth.js` - Auth Pinia store
- `frontend/src/stores/batches.js` - Batches Pinia store
- `frontend/src/stores/checklists.js` - Checklists Pinia store
- `frontend/src/stores/inspections.js` - Inspections Pinia store
- `frontend/src/services/watermark.js` - Watermark service
- `frontend/src/views/LoginView.vue` - Login page
- `frontend/src/views/DashboardView.vue` - Dashboard page
- `frontend/src/views/InspectionView.vue` - Inspection form

### Next Steps After This Plan:
- Add SyncView for offline data management
- Add PWA icons
- Test offline functionality
- Build and deploy

---

**Plan complete and saved to `docs/superpowers/plans/2026-03-17-frontend-setup.md`. Ready to execute?**
