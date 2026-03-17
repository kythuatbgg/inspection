<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-4">
        <button @click="goBack" class="text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div>
          <h1 class="text-xl font-bold text-gray-800">{{ batch?.name || 'Chi tiết đợt' }}</h1>
          <p class="text-sm text-gray-500">{{ formatDate(batch?.start_date) }} - {{ formatDate(batch?.end_date) }}</p>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Đang tải...</p>
      </div>

      <div v-else>
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-blue-500 text-white rounded-lg p-4 text-center">
            <p class="text-2xl font-bold">{{ plans.length }}</p>
            <p class="text-sm">Tổng tủ</p>
          </div>
          <div class="bg-green-500 text-white rounded-lg p-4 text-center">
            <p class="text-2xl font-bold">{{ completedCount }}</p>
            <p class="text-sm">Đã kiểm tra</p>
          </div>
          <div class="bg-yellow-500 text-white rounded-lg p-4 text-center">
            <p class="text-2xl font-bold">{{ pendingCount }}</p>
            <p class="text-sm">Chờ kiểm tra</p>
          </div>
        </div>

        <!-- Plans List -->
        <div class="space-y-3">
          <h2 class="text-lg font-semibold text-gray-800">Danh sách tủ cáp</h2>

          <div
            v-for="plan in plans"
            :key="plan.id"
            class="bg-white rounded-lg shadow p-4 flex items-center justify-between"
          >
            <div>
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ plan.cabinet_code }}</span>
                <span
                  :class="plan.status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                  class="px-2 py-0.5 text-xs rounded"
                >
                  {{ plan.status === 'done' ? 'Hoàn thành' : 'Chờ' }}
                </span>
              </div>
              <p v-if="plan.inspection" class="text-sm mt-1">
                Kết quả:
                <span :class="plan.inspection.final_result === 'PASS' ? 'text-green-600' : 'text-red-600'" class="font-medium">
                  {{ plan.inspection.final_result }}
                </span>
                ({{ plan.inspection.total_score }} điểm)
              </p>
            </div>

            <button
              v-if="plan.status !== 'done'"
              @click="startInspection(plan.id)"
              class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg"
            >
              Kiểm tra
            </button>
            <button
              v-else
              @click="viewInspection(plan.id)"
              class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg"
            >
              Xem lại
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBatchesStore } from '../stores/batches'

const route = useRoute()
const router = useRouter()
const batchesStore = useBatchesStore()

const loading = ref(true)

const batch = computed(() => batchesStore.currentBatch)
const plans = computed(() => batch.value?.plan_details || [])

const completedCount = computed(() => plans.value.filter(p => p.status === 'done').length)
const pendingCount = computed(() => plans.value.filter(p => p.status !== 'done').length)

onMounted(async () => {
  try {
    await batchesStore.fetchBatch(route.params.id)
  } catch (error) {
    console.error('Failed to load batch:', error)
    alert('Không thể tải dữ liệu')
  } finally {
    loading.value = false
  }
})

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN')
}

const startInspection = (planId) => {
  router.push(`/inspection/${planId}`)
}

const viewInspection = (planId) => {
  router.push(`/inspection/${planId}`)
}

const goBack = () => router.back()
</script>
