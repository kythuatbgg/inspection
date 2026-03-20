<template>
  <div class="space-y-4">
    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="activeTab = tab.value"
        class="px-4 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-colors active:scale-95"
        :class="activeTab === tab.value
          ? 'bg-primary-600 text-white shadow-md shadow-primary-600/20'
          : 'bg-gray-100 text-gray-600'"
      >
        {{ tab.label }}
        <span v-if="tab.count !== undefined" class="ml-1 opacity-80">({{ tab.count }})</span>
      </button>
    </div>

    <!-- Task List -->
    <div v-if="!loading" class="space-y-3">
      <button
        v-for="task in filteredTasks"
        :key="task.planId"
        @click="goToInspection(task)"
        class="w-full text-left rounded-2xl bg-white border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md active:scale-[0.98] transition-all"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h4 class="font-bold text-gray-900">{{ task.cabinetCode }}</h4>
              <span
                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                :class="task.status === 'done'
                  ? 'bg-green-50 text-green-700'
                  : 'bg-amber-50 text-amber-700'"
              >
                {{ task.status === 'done' ? '✓ Đã kiểm tra' : 'Chưa kiểm tra' }}
              </span>
            </div>
            <p class="text-sm text-gray-500 mt-1 truncate">{{ task.batchName }}</p>
          </div>
          <ChevronRight class="w-5 h-5 text-gray-400 shrink-0" />
        </div>

        <!-- Meta -->
        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
          <span v-if="task.btsSite" class="flex items-center gap-1">
            <MapPin class="w-3.5 h-3.5" />
            {{ task.btsSite }}
          </span>
          <span class="flex items-center gap-1">
            <Calendar class="w-3.5 h-3.5" />
            {{ task.dateRange }}
          </span>
        </div>

        <!-- Result if done -->
        <div v-if="task.result" class="mt-2 pt-2 border-t border-gray-100">
          <span
            class="text-xs font-bold px-2 py-0.5 rounded-full"
            :class="task.result === 'pass' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
          >{{ task.result === 'pass' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}</span>
          <span v-if="task.score !== null" class="text-xs text-gray-500 ml-2">Điểm: {{ task.score }}</span>
        </div>
      </button>

      <!-- Empty State -->
      <div v-if="filteredTasks.length === 0" class="text-center py-10">
        <ClipboardIcon class="w-16 h-16 mx-auto text-gray-300" />
        <p class="font-semibold text-gray-700 mt-4">Không có nhiệm vụ nào</p>
        <p class="text-sm text-gray-500 mt-1">
          {{ activeTab === 'planned' ? 'Tất cả tủ đã được kiểm tra!' : 'Chưa có nhiệm vụ trong mục này' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ClipboardIcon, Calendar, MapPin, ChevronRight } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api.js'
import batchService from '@/services/batchService.js'

const router = useRouter()
const loading = ref(true)
const activeTab = ref('all')
const allTasks = ref([])

const tabs = computed(() => [
  { label: 'Tất cả', value: 'all', count: allTasks.value.length },
  { label: 'Chưa kiểm tra', value: 'planned', count: allTasks.value.filter(t => t.status === 'planned').length },
  { label: 'Đã hoàn thành', value: 'done', count: allTasks.value.filter(t => t.status === 'done').length }
])

const filteredTasks = computed(() => {
  if (activeTab.value === 'all') return allTasks.value
  return allTasks.value.filter(t => t.status === activeTab.value)
})

const goToInspection = (task) => {
  router.push({ name: 'inspector-inspection', params: { planId: task.planId } })
}

const formatDateRange = (start, end) => {
  const fmt = (d) => {
    if (!d) return ''
    const dt = new Date(d)
    return `${dt.getDate()}/${dt.getMonth() + 1}`
  }
  return `${fmt(start)} — ${fmt(end)}`
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await batchService.getBatches({ per_page: 100 })
    const batches = res.data || res || []
    
    const tasks = []
    
    // Fetch plan details for each batch
    await Promise.all(
      batches.map(async (batch) => {
        try {
          const plansRes = await api.get(`/batches/${batch.id}/plans`)
          const plans = plansRes.data?.data || plansRes.data || []
          
          for (const plan of plans) {
            // Try to get inspection result
            let result = null, score = null
            if (plan.status === 'done') {
              try {
                const inspRes = await api.get(`/plans/${plan.id}/inspection`)
                const insp = inspRes.data?.data
                if (insp) {
                  result = insp.final_result
                  score = insp.total_score
                }
              } catch { /* no inspection yet */ }
            }

            tasks.push({
              planId: plan.id,
              batchId: batch.id,
              batchName: batch.name,
              cabinetCode: plan.cabinet_code,
              btsSite: plan.cabinet?.bts_site || '',
              status: plan.status,
              dateRange: formatDateRange(batch.start_date, batch.end_date),
              result,
              score
            })
          }
        } catch (e) {
          console.error(`Failed to load plans for batch ${batch.id}:`, e)
        }
      })
    )

    allTasks.value = tasks
  } catch (e) {
    console.error('Failed to load tasks:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
