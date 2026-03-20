<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <!-- On desktop, this behaves like a sidebar. On mobile, it's the full width screen -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200">
      
      <!-- Added pb-28 below to prevent content from hiding behind the FAB on mobile -->
      <div class="p-4 md:p-5 space-y-4 pb-28 md:pb-5">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between py-1">
          <div>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">Đề xuất kiểm tra</h2>
            <p class="text-xs text-slate-500 mt-1">Quản lý và theo dõi các lô đề xuất</p>
          </div>
          <!-- Desktop Add Button (Hidden on Mobile) -->
          <button @click="showForm = true" class="hidden md:flex items-center justify-center gap-1.5 bg-primary-600 text-white px-4 h-9 rounded-xl text-sm font-semibold active:scale-95 transition-all shadow-sm shadow-primary-600/20">
            <Plus class="w-4 h-4" /> Tạo đề xuất
          </button>
        </div>

        <!-- Segmented Filter -->
        <div class="bg-slate-100 rounded-2xl p-1 flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-[13px] font-semibold whitespace-nowrap transition-all"
            :class="activeTab === tab.value
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 active:bg-white/50'"
          >
            {{ tab.label }}
            <span
              v-if="!loading"
              class="min-w-[20px] h-5 px-1 rounded-full text-[10px] font-bold flex items-center justify-center leading-none"
              :class="activeTab === tab.value
                ? 'bg-primary-600 text-white'
                : 'bg-slate-200 text-slate-500'"
            >{{ getCount(tab.value) }}</span>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredBatches.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
          <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <ClipboardList class="w-7 h-7 text-slate-300" />
          </div>
          <p class="font-semibold text-slate-700">Không có đề xuất nào</p>
          <p class="text-sm text-slate-400 mt-1">{{ activeTab === 'pending' ? 'Chưa có dữ liệu chờ duyệt' : 'Bộ lọc rỗng' }}</p>
        </div>

        <!-- Batch List -->
        <div v-else class="space-y-3">
          <button
            v-for="batch in filteredBatches"
            :key="batch.id"
            class="w-full text-left rounded-2xl bg-white border border-slate-200 p-4 active:scale-[0.98] transition-all cursor-default"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900 truncate">{{ batch.name }}</h4>
                </div>
                <p v-if="batch.approval_status === 'rejected'" class="text-xs text-red-500 font-medium mt-1.5 truncate">Lý do từ chối: {{ batch.approval_note }}</p>
                <p v-else class="text-xs text-slate-400 mt-1.5 truncate">Đề xuất cập nhật lúc {{ formatDate(batch.updated_at || batch.created_at) }}</p>
              </div>
              
              <div class="shrink-0">
                <span
                  class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg"
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

            <div class="flex items-center gap-3 mt-2.5 text-[11px] text-slate-400">
              <span class="flex items-center gap-1">
                <Calendar class="w-3 h-3" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span class="flex items-center gap-1 font-semibold text-slate-600">
                 <ListTodo class="w-3 h-3" /> {{ batch.plans_count }} tủ
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL VIEW PLACEHOLDER (Desktop Only) -->
    <div class="hidden md:flex flex-1 flex-col items-center justify-center h-full text-center p-8 bg-slate-50 relative min-h-screen md:min-h-0">
      <div class="w-20 h-20 rounded-3xl bg-slate-200/50 flex items-center justify-center mb-5">
        <ClipboardEdit class="w-10 h-10 text-slate-400" />
      </div>
      <h3 class="font-bold text-slate-800 text-xl tracking-tight">Quản lý Đề xuất</h3>
      <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Chọn một đề xuất ở hệ thống bên trái hoặc tạo mới để bắt đầu.</p>
    </div>

    <!-- FAB (Mobile Only) -->
    <!-- Positioned safely above bottom navigation (safe-bottom area) -->
    <button
      @click="showForm = true"
      class="md:hidden fixed bottom-24 right-5 z-50 w-14 h-14 bg-primary-600 text-white rounded-[20px] flex items-center justify-center shadow-lg shadow-primary-600/30 active:scale-95 transition-transform"
    >
      <Plus class="w-6 h-6" />
    </button>

    <ProposalFormModal v-model="showForm" @saved="fetchData" />
  </div>
</template>

<script setup>
import { Plus, Calendar, ListTodo, ClipboardList, ClipboardEdit, Loader2 } from 'lucide-vue-next'
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
