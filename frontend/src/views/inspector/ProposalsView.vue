<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <!-- On desktop, this behaves like a sidebar. On mobile, it's the full width screen -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200"
         :class="{ 'hidden md:block': isDetailOpen }">
      
      <!-- Added pb-28 below to prevent content from hiding behind the FAB on mobile -->
      <div class="p-4 md:p-5 space-y-4 pb-28 md:pb-5">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between py-1">
          <div>
            <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight font-heading">Đề xuất kiểm tra</h2>
            <p class="text-xs text-slate-500 mt-1">Quản lý và theo dõi các lô đề xuất</p>
          </div>
          <!-- Desktop Add Button (Hidden on Mobile) -->
          <button @click="showForm = true" class="hidden md:flex items-center justify-center gap-1.5 bg-primary-600 hover:bg-primary-700 text-white px-4 h-9 rounded-lg text-sm font-medium transition-all shadow-sm">
            <Plus class="w-4 h-4" /> Tạo đề xuất
          </button>
        </div>

        <!-- Segmented Filter -->
        <div class="bg-slate-100/80 p-1 flex gap-1 border border-slate-200 rounded-lg">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs md:text-[13px] font-semibold whitespace-nowrap transition-all"
            :class="activeTab === tab.value
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 hover:text-slate-700'"
          >
            {{ tab.label }}
            <span
              v-if="!loading"
              class="min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold flex items-center justify-center leading-none tracking-tight"
              :class="activeTab === tab.value
                ? 'bg-primary-600 text-white'
                : 'bg-slate-200/80 text-slate-500'"
            >{{ getCount(tab.value) }}</span>
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredBatches.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
          <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
            <ClipboardList class="w-7 h-7 text-slate-400" />
          </div>
          <p class="font-semibold text-slate-800">Không có đề xuất nào</p>
          <p class="text-sm text-slate-500 mt-1">{{ activeTab === 'pending' ? 'Chưa có dữ liệu chờ duyệt' : 'Bộ lọc rỗng' }}</p>
        </div>

        <!-- Batch List -->
        <div v-else class="space-y-3">
          <button
            v-for="batch in filteredBatches"
            :key="batch.id"
            @click="goToDetail(batch)"
            class="w-full text-left rounded-lg bg-white border border-slate-200 p-4 transition-all cursor-pointer hover:bg-slate-50"
            :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/10 hover:bg-primary-50/20': isActiveTask(batch.id) }"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900 truncate font-heading tracking-tight">{{ batch.name }}</h4>
                </div>
                <p v-if="batch.approval_status === 'rejected'" class="text-xs text-danger font-medium mt-1.5 truncate">Lý do từ chối: {{ batch.approval_note }}</p>
                <p v-else class="text-xs text-slate-500 mt-1 truncate font-medium">Đề xuất cập nhật lúc {{ formatDate(batch.updated_at || batch.created_at) }}</p>
              </div>
              
              <div class="shrink-0">
                <span
                  class="text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-md"
                  :class="{
                    'bg-warning/10 text-warning': batch.approval_status === 'pending',
                    'bg-success/10 text-success': batch.approval_status === 'approved',
                    'bg-danger/10 text-danger': batch.approval_status === 'rejected',
                  }"
                >
                  {{ getStatusText(batch.approval_status) }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-3 mt-3 text-[11px] text-slate-500 uppercase tracking-widest font-medium">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span class="flex items-center gap-1.5 font-bold text-slate-600">
                 <ListTodo class="w-3.5 h-3.5 text-slate-400" /> {{ batch.plans_count }} TỦ
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>

    <div class="flex-1 md:h-full md:overflow-y-auto bg-slate-50 md:bg-white relative min-h-screen md:min-h-0"
         :class="{ 'hidden md:flex flex-col': !isDetailOpen }">
      <router-view v-if="isDetailOpen" :key="$route.fullPath"></router-view>
      
      <!-- Desktop Placeholder (Visible when no detail route is selected) -->
      <div v-else class="hidden md:flex flex-1 flex-col items-center justify-center h-full text-center p-8 bg-slate-50 relative min-h-screen md:min-h-0">
        <div class="w-16 h-16 rounded-lg bg-slate-200 shadow-sm flex items-center justify-center mb-5 border border-slate-300">
          <ClipboardEdit class="w-8 h-8 text-slate-500" />
        </div>
        <h3 class="font-bold text-slate-900 text-xl tracking-tight font-heading">Quản lý Đề xuất</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Chọn một đề xuất ở hệ thống bên trái hoặc tạo mới để bắt đầu.</p>
      </div>
    </div>

    <!-- FAB (Mobile Only) -->
    <!-- Positioned safely above bottom navigation (safe-bottom area) -->
    <button
      @click="showForm = true"
      class="md:hidden fixed bottom-20 right-5 z-50 w-12 h-12 bg-primary-600 hover:bg-primary-700 text-white rounded-lg flex items-center justify-center shadow-sm active:scale-95 transition-transform"
    >
      <Plus class="w-6 h-6" />
    </button>

    <ProposalFormModal v-model="showForm" @saved="fetchData" />
  </div>
</template>

<script setup>
import { Plus, Calendar, ListTodo, ClipboardList, ClipboardEdit, Loader2 } from 'lucide-vue-next'
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'
import ProposalFormModal from '@/components/inspector/ProposalFormModal.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

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

const isDetailOpen = computed(() => !!route.params.id)
const isActiveTask = (id) => route.params.id == id

const goToDetail = (batch) => {
  router.push({ name: 'inspector-proposal-detail', params: { id: batch.id } })
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/batches', { params: { per_page: 100, created_by: authStore.user.id } })
    batches.value = res.data?.data || []
  } catch (e) {
    console.error('Failed to load proposals:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
