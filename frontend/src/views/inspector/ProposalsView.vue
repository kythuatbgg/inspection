<template>
  <div class="flex flex-col h-full space-y-5">
    
    <!-- Top Actions & Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-base font-bold text-slate-900 tracking-tight">Danh sách đề xuất</h3>
      <button @click="showForm = true" class="inline-flex items-center justify-center gap-1.5 bg-slate-900 text-white px-4 h-9 rounded-xl text-sm font-semibold shadow-sm active:scale-95 transition-all">
        <Plus class="w-4 h-4" />
        <span>Tạo mới</span>
      </button>
    </div>

    <!-- Scrollable Tabs -->
    <div class="-mx-4 px-4 overflow-x-auto scrollbar-hide">
      <div class="flex items-center gap-2 min-w-max pb-1">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          @click="activeTab = tab.value"
          class="h-9 px-4 rounded-full text-sm font-semibold transition-all duration-200"
          :class="activeTab === tab.value ? 'bg-slate-800 text-white shadow-sm' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50 active:bg-slate-100'"
        >
          {{ tab.label }}
          <span class="ml-1" :class="activeTab === tab.value ? 'opacity-70' : 'opacity-60'">({{ getCount(tab.value) }})</span>
        </button>
      </div>
    </div>

    <!-- Main Content List -->
    <div class="flex-1 relative">
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-16">
        <Loader2 class="w-8 h-8 animate-spin text-slate-400 mb-4" />
        <p class="text-sm font-medium text-slate-500">Đang tải dữ liệu...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredBatches.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-5">
          <ClipboardList class="w-8 h-8 text-slate-300" />
        </div>
        <p class="font-bold text-slate-800 text-lg tracking-tight">Không có đề xuất nào</p>
        <p class="text-sm text-slate-500 mt-1.5 max-w-xs leading-relaxed">Hiện tại danh sách trống. Bạn có thể tạo đề xuất mới để lưu trữ dữ liệu lô kiểm tra.</p>
      </div>

      <!-- Batch List -->
      <div v-else class="space-y-3 pb-8">
        <div
          v-for="batch in filteredBatches"
          :key="batch.id"
          class="rounded-2xl bg-white border border-slate-200 p-4.5 shadow-sm overflow-hidden relative"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0 pr-2">
              <h4 class="font-bold text-slate-900 truncate text-[15px] leading-tight">{{ batch.name }}</h4>
              <div class="flex items-center gap-3 mt-2.5 text-xs text-slate-500 font-medium">
                <span class="flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-slate-400" />
                  {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
                </span>
                <span class="flex items-center gap-1.5">
                  <ListTodo class="w-3.5 h-3.5 text-slate-400" />
                  {{ batch.plans_count }} tủ
                </span>
              </div>
            </div>
            
            <div class="shrink-0 pt-0.5">
              <span
                class="inline-flex text-[10px] font-bold uppercase tracking-wider px-2.5 py-1.5 rounded-lg"
                :class="{
                  'bg-amber-50 text-amber-600': batch.approval_status === 'pending',
                  'bg-emerald-50 text-emerald-600': batch.approval_status === 'approved',
                  'bg-red-50 text-red-600': batch.approval_status === 'rejected',
                }"
              >
                {{ getStatusText(batch.approval_status) }}
              </span>
            </div>
          </div>

          <!-- Rejection Note Alert -->
          <div v-if="batch.approval_status === 'rejected' && batch.approval_note" class="mt-4 p-3.5 bg-red-50/50 rounded-xl border border-red-100 flex gap-2.5">
            <div class="shrink-0 mt-0.5">
              <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
                <span class="text-red-600 font-bold text-xs">!</span>
              </div>
            </div>
            <div>
              <p class="text-[10px] font-bold text-red-700 uppercase tracking-widest mb-0.5">Lý do từ chối</p>
              <p class="text-xs text-red-600 leading-relaxed font-medium">{{ batch.approval_note }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ProposalFormModal v-model="showForm" @saved="fetchData" />
  </div>
</template>

<script setup>
import { Plus, Calendar, ListTodo, ClipboardList, Loader2 } from 'lucide-vue-next'
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api.js'
import ProposalFormModal from '@/components/inspector/ProposalFormModal.vue'

const loading = ref(true)
const showForm = ref(false)
const batches = ref([])
const activeTab = ref('pending')

const tabs = [
  { label: 'Chờ duyệt', value: 'pending' },
  { label: 'Đã duyệt', value: 'approved' },
  { label: 'Từ chối', value: 'rejected' }
]

const filteredBatches = computed(() => {
  return batches.value.filter(b => b.approval_status === activeTab.value)
})

const getCount = (status) => {
  return batches.value.filter(b => b.approval_status === status).length
}

const getStatusText = (status) => {
  const map = { pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối' }
  return map[status] || status
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}`
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/batches', { params: { per_page: 100 } })
    batches.value = res.data?.data || []
  } catch (e) {
    console.error('Failed to load proposals:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
