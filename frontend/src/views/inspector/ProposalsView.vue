<template>
  <div class="space-y-4">
    <!-- Create button -->
    <div class="flex items-center justify-between">
      <p class="text-xs text-slate-400 font-medium">Quản lý đề xuất kiểm tra</p>
      <button @click="showForm = true" class="bg-primary-600 text-white px-4 py-2 text-sm font-semibold rounded-xl flex items-center gap-2 active:scale-95 transition-all">
        <Plus class="w-4 h-4" /> Tạo đề xuất
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto scrollbar-hide -mx-1 px-1">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="activeTab = tab.value"
        class="px-4 py-2 rounded-full text-sm font-semibold whitespace-nowrap transition-colors"
        :class="activeTab === tab.value
          ? 'bg-primary-600 text-white'
          : 'bg-white border border-slate-200 text-slate-500 active:bg-slate-50'"
      >
        {{ tab.label }}
        <span class="ml-0.5 opacity-70">({{ getCount(tab.value) }})</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
    </div>

    <!-- List -->
    <div v-else class="space-y-3">
      <div
        v-for="batch in filteredBatches"
        :key="batch.id"
        class="rounded-2xl bg-white border border-slate-200 p-4"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <h4 class="font-bold text-slate-900 truncate leading-tight">{{ batch.name }}</h4>
            <div class="flex items-center gap-3 mt-2 text-[11px] text-slate-400">
              <span class="flex items-center gap-1">
                <Calendar class="w-3 h-3" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span class="flex items-center gap-1">
                <ListTodo class="w-3 h-3" />
                {{ batch.plans_count }} tủ
              </span>
            </div>
          </div>
          <span
            class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
            :class="{
              'bg-amber-50 text-amber-600': batch.approval_status === 'pending',
              'bg-emerald-50 text-emerald-600': batch.approval_status === 'approved',
              'bg-red-50 text-red-600': batch.approval_status === 'rejected',
            }"
          >
            {{ getStatusText(batch.approval_status) }}
          </span>
        </div>

        <!-- Rejection reason -->
        <div v-if="batch.approval_status === 'rejected' && batch.approval_note" class="mt-3 p-3 bg-red-50 rounded-xl text-sm text-red-700 border border-red-100">
          <p class="text-[10px] font-bold text-red-500 uppercase tracking-wide mb-1">Lý do từ chối</p>
          <p class="text-xs">{{ batch.approval_note }}</p>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredBatches.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
          <ClipboardList class="w-7 h-7 text-slate-300" />
        </div>
        <p class="font-semibold text-slate-700">Không có đề xuất nào</p>
        <p class="text-sm text-slate-400 mt-1">Tạo đề xuất mới để bắt đầu</p>
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
