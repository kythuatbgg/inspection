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
