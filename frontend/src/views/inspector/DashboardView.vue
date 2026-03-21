<template>
  <div class="w-full h-full md:absolute md:inset-0 md:bg-slate-50 relative overflow-y-auto">
    <!-- Responsive layout chassis: Tight on mobile, wide on desktop -->
    <div class="p-4 md:p-8 space-y-5 md:space-y-8 max-w-5xl mx-auto pb-28 flex flex-col min-w-0">
      
      <!-- Sync: Stats Cards like Admin Dashboard -->
      <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
        
        <!-- Stat 1: Lô đề xuất -->
        <div class="rounded-lg bg-white border border-slate-200 p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center shrink-0">
              <FileStack class="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900 tracking-tight font-heading">{{ stats.totalBatches }}</p>
              <p class="text-sm text-slate-500 font-medium">{{ $t('inspector.proposedBatches') }}</p>
            </div>
          </div>
        </div>

        <!-- Stat 2: Tổng số tủ -->
        <div class="rounded-lg bg-white border border-slate-200 p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
              <ListTodo class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900 tracking-tight font-heading">{{ stats.totalPlans }}</p>
              <p class="text-sm text-slate-500 font-medium">{{ $t('inspector.totalCabinets') }}</p>
            </div>
          </div>
        </div>

        <!-- Stat 3: Đã kiểm tra -->
        <div class="rounded-lg bg-white border border-slate-200 p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
              <Check class="w-5 h-5 text-success" />
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900 tracking-tight font-heading">{{ stats.done }}</p>
              <p class="text-sm text-slate-500 font-medium">{{ $t('inspector.inspected') }}</p>
            </div>
          </div>
        </div>

        <!-- Stat 4: Tiến độ tổng thể -->
        <div class="rounded-lg bg-white border border-slate-200 p-4 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-center">
          <div class="flex items-center gap-3 w-full">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
              <ShieldCheck class="w-5 h-5 text-warning" />
            </div>
            <div class="flex-1 overflow-hidden pr-2">
              <div class="flex items-end gap-2 mb-1">
                <p class="text-2xl font-bold text-slate-900 tracking-tight font-heading" :class="completionPercent === 100 ? 'text-success' : ''">{{ completionPercent }}%</p>
              </div>
              <!-- Progress mini bar -->
              <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700 ease-out flex"
                  :class="completionPercent === 100 ? 'bg-success' : 'bg-primary-600'"
                  :style="{ width: completionPercent + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Active Batches Section -->
      <div>
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 tracking-tight font-heading">{{ $t('inspector.activeBatches') }}</h3>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-12 md:py-20">
          <Loader2 class="w-8 h-8 animate-spin text-slate-400 mb-4" />
          <p class="text-sm font-medium text-slate-500">{{ $t('inspector.loadingCharts') }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="activeBatches.length === 0" class="flex flex-col items-center justify-center py-16 md:py-24 text-center">
          <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-5">
            <FileStack class="w-8 h-8 text-slate-300" />
          </div>
          <p class="font-bold text-slate-800 text-lg md:text-xl tracking-tight">{{ $t('inspector.noBatches') }}</p>
          <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">{{ $t('inspector.noBatchesHint') }}</p>
        </div>

        <!-- Batches List -->
        <div v-else class="space-y-3.5 md:space-y-4">
          <button
            v-for="batch in activeBatches"
            :key="batch.id"
            @click="goToBatch(batch.id)"
            class="w-full text-left rounded-lg bg-white border border-slate-200 p-5 md:p-6 active:scale-[0.98] transition-all cursor-pointer shadow-sm hover:bg-slate-50 flex flex-col"
          >
            <!-- Batch Header -->
            <div class="flex items-start justify-between gap-4 w-full">
              <div class="flex-1 min-w-0 pr-3">
                <h4 class="font-bold text-[15px] md:text-lg text-slate-900 truncate leading-tight font-heading">{{ batch.name }}</h4>
                <p class="text-[13px] md:text-sm text-slate-500 font-medium mt-1.5 truncate">{{ batch.checklist?.name || $t('inspector.standardChecklist') }}</p>
              </div>
              <span
                class="shrink-0 text-[10px] md:text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg"
                :class="batch.status === 'completed'
                  ? 'bg-success/10 text-success'
                  : 'bg-warning/10 text-warning'"
              >
                {{ statusLabel(batch.status) }}
              </span>
            </div>

            <!-- Crisp Progress Bar -->
            <div class="mt-5 md:mt-6 w-full">
              <div class="flex items-center justify-between text-[11px] md:text-xs mb-2">
                <span class="text-slate-500 font-bold uppercase tracking-widest">{{ $t('inspector.progress') }}</span>
                <span class="font-bold tracking-tight" :class="batchProgress(batch) === 100 ? 'text-success' : 'text-primary-600'">
                  {{ batchProgress(batch) }}%
                </span>
              </div>
              <div class="w-full h-1.5 md:h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700 ease-out"
                  :class="batchProgress(batch) === 100 ? 'bg-success' : 'bg-primary-600'"
                  :style="{ width: batchProgress(batch) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Meta Data (Date & Count) -->
            <div class="flex items-center gap-4 mt-4 md:mt-5 text-[11px] md:text-sm text-slate-500 font-medium uppercase tracking-wide">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span class="flex items-center gap-1.5">
                <FileStack class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400" />
                {{ batch.plan_details?.length || 0 }} {{ $t('inspector.cabinet') }}
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FileStack, Calendar, ShieldCheck, Check, Clock, ListTodo, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import batchService from '@/services/batchService.js'

const router = useRouter()
const { t } = useI18n()

const loading = ref(true)
const batches = ref([])

const activeBatches = computed(() => {
  return batches.value.filter(b => b.status !== 'completed')
})

const stats = computed(() => {
  let totalPlans = 0, planned = 0, done = 0

  batches.value.forEach(b => {
    const plans = b.plan_details || []
    totalPlans += plans.length
    planned += plans.filter(p => p.status === 'planned').length
    done += plans.filter(p => p.status === 'done').length
  })

  return {
    totalBatches: batches.value.length,
    totalPlans,
    planned,
    done
  }
})

const completionPercent = computed(() => {
  if (stats.value.totalPlans === 0) return 0
  return Math.round((stats.value.done / stats.value.totalPlans) * 100)
})

const batchProgress = (batch) => {
  const plans = batch.plan_details || []
  if (plans.length === 0) return 0
  const done = plans.filter(p => p.status === 'done').length
  return Math.round((done / plans.length) * 100)
}

const statusLabel = (status) => {
  const map = { pending: 'inspector.waiting', active: 'inspector.running', completed: 'status.completed' }
  return t(map[status] || 'status.unknown')
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getDate()}/${d.getMonth() + 1}`
}

const goToBatch = (id) => {
  router.push({ name: 'inspector-batch-detail', params: { id } })
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await batchService.getBatches({ per_page: 50, approval_status: 'approved' })
    batches.value = res.data || res || []

    const detailPromises = batches.value.map(b => batchService.getBatchById(b.id))
    const details = await Promise.all(detailPromises)
    
    details.forEach((detail, i) => {
      if (detail && batches.value[i]) {
        batches.value[i].plan_details = detail.plan_details || []
        batches.value[i].checklist = detail.checklist || batches.value[i].checklist
      }
    })
  } catch (e) {
    console.error('Failed to fetch batches:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
