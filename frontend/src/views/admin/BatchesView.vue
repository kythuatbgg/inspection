<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-0">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <h2 class="text-2xl font-bold tracking-tight text-gray-100">Lô kiểm tra</h2>
      <!-- Desktop action hidden on mobile -->
      <button class="hidden md:flex btn-primary" @click="showFormModal = true">
        <Plus class="w-5 h-5 mr-2" />
        Tạo lô mới
      </button>
    </div>

    <!-- Filters container -->
    <div class="bg-dark-surface rounded-[20px] md:rounded-xl shadow-lg shadow-black/10 border border-gray-700/30 md:border-gray-700/50 p-3 md:p-4">
      <div class="flex flex-col md:flex-row gap-4">
        <!-- Search -->
        <div class="relative w-full md:max-w-xs">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <Search class="w-5 h-5 text-gray-500" />
          </div>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm kiếm mã lô, tên lô..."
            class="w-full pl-11 pr-4 py-3 min-h-[52px] md:min-h-[40px] bg-dark-elevated/80 md:bg-dark-surface border md:border-gray-600 md:focus:bg-dark-surface focus:bg-dark-surface focus:border-primary-500 rounded-[16px] md:rounded-xl text-gray-100 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all placeholder:text-gray-500 font-medium"
            @input="handleSearch"
          />
        </div>
        
        <!-- Status -->
        <MobileBottomSheet
          v-model="filters.status"
          :options="statusFilterOptions"
          label="Chọn trạng thái"
          placeholder="Tất cả trạng thái"
          container-class="w-full md:max-w-xs"
          trigger-class="!min-h-[52px] md:!min-h-[40px] !bg-dark-elevated/80 md:!bg-dark-surface md:!rounded-xl !rounded-[16px]"
          @update:model-value="handleSearch"
        />
      </div>
    </div>

    <!-- Desktop Table (hidden on mobile) -->
    <div class="hidden md:block card overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-500">
        Đang tải...
      </div>
      <div v-else-if="error" class="p-8 text-center text-red-500">
        {{ error }}
      </div>
      <table v-else class="w-full">
        <thead class="bg-dark-bg border-b border-gray-700/50">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Mã lô</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Tên lô</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Số tủ</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Ngày tạo</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Trạng thái</th>
            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="batch in batches" :key="batch.id" class="hover:bg-dark-bg">
            <td class="px-4 py-3 font-medium text-gray-100">#{{ batch.id }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ batch.name || batch.title }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ batch.plans_count || 0 }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ formatDate(batch.created_at) }}</td>
            <td class="px-4 py-3">
              <span :class="getStatusClass(batch.status)">{{ getStatusLabel(batch.status) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="inline-flex items-center justify-center p-2 rounded-lg bg-primary-500/10 text-primary-400 hover:bg-primary-100 text-sm font-bold transition-colors">
                Chi tiết
              </button>
            </td>
          </tr>
          <tr v-if="batches.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
              Không có dữ liệu
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Stacked Cards (hidden on desktop) -->
    <div class="md:hidden space-y-4">
      <div v-if="loading" class="text-center py-8 text-gray-500">Đang tải...</div>
      <div v-else-if="error" class="text-center py-8 text-red-500">{{ error }}</div>
      
      <template v-else>
        <!-- Card -->
        <div v-for="batch in batches" :key="batch.id" class="bg-dark-surface rounded-[24px] p-5 shadow-lg shadow-black/10 border border-gray-700/30 relative overflow-hidden active:scale-[0.98] transition-all duration-200">
          
          <!-- Card Header -->
          <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-[16px] bg-primary-500/10 flex items-center justify-center">
              <Package class="w-6 h-6 text-primary-400" />
            </div>
            <div class="flex-1 pt-1">
              <h3 class="text-lg font-bold text-gray-100 tracking-tight leading-tight">{{ batch.name || batch.title }}</h3>
              <p class="text-sm font-medium text-gray-500 mt-0.5">Mã lô: #{{ batch.id }}</p>
            </div>
          </div>
          
          <!-- Grouped Metadata block -->
          <div class="bg-dark-elevated/80 rounded-[16px] p-3.5 mb-4 space-y-2.5">
            <div class="flex items-center text-sm">
                <div class="flex items-center text-gray-500 font-medium w-24">
                  <Server class="w-4 h-4 mr-1.5" />
                  Số tủ:
                </div>
                <span class="text-gray-100 font-semibold">{{ batch.plans_count || 0 }}</span>
            </div>
            <div class="flex items-center text-sm">
                <div class="flex items-center text-gray-500 font-medium w-24">
                  <Calendar class="w-4 h-4 mr-1.5" />
                  Ngày tạo:
                </div>
                <span class="text-gray-100 font-semibold">{{ formatDate(batch.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm pt-1 mt-1 border-t border-gray-700/50/50">
              <span class="text-gray-500 font-medium">Trạng thái:</span>
              <span :class="getStatusClass(batch.status)" class="shrink-0">{{ getStatusLabel(batch.status) }}</span>
            </div>
          </div>
          
          <!-- Action row -->
          <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="w-full flex items-center justify-center min-h-[48px] rounded-[14px] bg-primary-500/10 border border-primary-100 text-primary-700 font-bold hover:bg-primary-100 active:bg-primary-200 transition-colors">
              Chi tiết lô
          </button>
        </div>

        <!-- Empty State Mobile -->
        <div v-if="batches.length === 0" class="flex flex-col items-center justify-center py-12 px-4 bg-dark-surface rounded-[24px] border border-gray-700/30 shadow-lg shadow-black/10 mt-4">
            <FileStack class="w-16 h-16 text-gray-300 mb-4" />
            <p class="text-gray-500 font-medium text-center">Chưa có lô nào<br/><span class="text-sm text-gray-500 font-normal">Tạo lô mới để kiểm tra</span></p>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <template v-if="!loading && !error && batches.length > 0">
      <!-- Desktop Pagination -->
      <div v-if="pagination.last_page > 1" class="hidden md:flex bg-dark-surface rounded-xl shadow-lg shadow-black/10 border border-gray-700/50 p-6 items-center justify-between">
        <p class="text-sm text-gray-500 font-medium">
          Hiển thị <span class="font-semibold text-gray-100">{{ pagination.from }}</span> - 
          <span class="font-semibold text-gray-100">{{ pagination.to }}</span> trong 
          <span class="font-semibold text-gray-100">{{ pagination.total }}</span> lô
        </p>
        
        <div class="flex items-center gap-1.5">
          <button
            @click="changePage(1)"
            :disabled="pagination.current_page === 1"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors items-center justify-center"
          >
            <ChevronsLeft class="w-4 h-4" />
          </button>
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors flex items-center justify-center"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <div class="px-4 font-semibold text-sm text-gray-300">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </div>
          
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors flex items-center justify-center"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            @click="changePage(pagination.last_page)"
            :disabled="pagination.current_page === pagination.last_page"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors items-center justify-center"
          >
            <ChevronsRight class="w-4 h-4" />
          </button>
        </div>
      </div>
      
      <!-- Mobile Pagination Pill: mb-24 pushes it above the FAB -->
      <div v-if="pagination.last_page > 1" class="md:hidden mt-8 mb-24">
        <div class="text-center mb-5">
          <div class="text-sm text-gray-500 font-medium">
            Lô kiểm tra <span class="text-gray-100 font-bold">{{ pagination.from }}</span> - <span class="text-gray-100 font-bold">{{ pagination.to }}</span><br/>
            <span class="text-xs text-gray-500 mt-1 inline-block">trong tổng số {{ pagination.total }} lô</span>
          </div>
        </div>
        <div class="flex items-center justify-between bg-dark-surface p-2 rounded-[24px] shadow-lg shadow-black/10 border border-gray-700/30 mx-auto max-w-[320px]">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="group flex items-center justify-center w-14 h-14 rounded-full bg-dark-elevated/80 text-gray-500 active:bg-dark-elevated active:scale-95 transition-all duration-200 disabled:opacity-40 disabled:active:scale-100 disabled:bg-transparent"
          >
            <ChevronLeft class="w-6 h-6 group-active:-translate-x-1 transition-transform" />
          </button>
          <div class="flex-1 flex flex-col items-center justify-center">
            <div class="flex items-baseline gap-1.5">
              <span class="text-xl font-bold tracking-tight text-gray-100">{{ pagination.current_page }}</span>
              <span class="text-sm font-semibold text-gray-500">/ {{ pagination.last_page }}</span>
            </div>
          </div>
          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="group flex items-center justify-center w-14 h-14 rounded-full text-white bg-primary-500 shadow-lg shadow-primary-500/30 active:bg-primary-700 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:active:scale-100 disabled:shadow-none"
          >
            <ChevronRight class="w-6 h-6 group-active:translate-x-1 transition-transform" />
          </button>
        </div>
      </div>
    </template>

    <!-- FAB for mobile (Fixed Bottom Right) -->
    <div class="md:hidden fixed bottom-6 right-6 z-40">
      <button 
        @click="showFormModal = true"
        class="flex items-center justify-center w-[56px] h-[56px] rounded-full bg-primary-500 text-white shadow-lg shadow-primary-500/40 hover:bg-primary-400 active:scale-90 active:bg-primary-800 transition-all"
      >
        <Plus class="w-7 h-7" />
      </button>
    </div>

    <!-- Batch Form Modal -->
    <BatchFormModal v-model="showFormModal" @saved="handleFormSaved" />
  </div>
</template>

<script setup>
import { Plus, ChevronRight, ChevronLeft, ChevronsRight, ChevronsLeft, FileStack, Search, Package, Server, Calendar } from 'lucide-vue-next'

import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import batchService from '@/services/batchService.js'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import BatchFormModal from '@/components/admin/BatchFormModal.vue'

const router = useRouter()
const showFormModal = ref(false)

const statusFilterOptions = [
  { value: '', label: 'Tất cả trạng thái' },
  { value: 'pending', label: 'Chờ xử lý' },
  { value: 'in_progress', label: 'Đang kiểm tra' },
  { value: 'completed', label: 'Hoàn thành' }
]

const perPageOptions = [
  { value: 10, label: '10' },
  { value: 20, label: '20' },
  { value: 50, label: '50' },
  { value: 100, label: '100' }
]

const loading = ref(true)
const error = ref(null)
const batches = ref([])

const pagination = ref({
  current_page: 1,
  per_page: 20,
  total: 0,
  last_page: 1,
  from: 0,
  to: 0
})

const filters = ref({
  search: '',
  status: ''
})

let searchTimeout = null

const getStatusClass = (status) => {
  const classes = {
    completed: 'badge-success',
    in_progress: 'badge-warning',
    pending: 'badge-info',
    done: 'badge-success',
    'in-progress': 'badge-warning'
  }
  return classes[status] || 'badge-info'
}

const getStatusLabel = (status) => {
  const labels = {
    completed: 'Hoàn thành',
    in_progress: 'Đang kiểm tra',
    pending: 'Chờ xử lý',
    done: 'Hoàn thành',
    'in-progress': 'Đang kiểm tra'
  }
  return labels[status] || 'Chờ xử lý'
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('vi-VN')
}

const fetchBatches = async () => {
  loading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page,
    }
    
    // Add search
    if (filters.value.search) {
      params.search = filters.value.search
    }
    // Add status
    if (filters.value.status) {
      params.status = filters.value.status
    }

    const res = await batchService.getBatches(params)
    
    // Handle standard paginated structure
    if (res && res.data) {
      batches.value = res.data
      pagination.value = {
        current_page: res.current_page || 1,
        per_page: res.per_page || 20,
        total: res.total || 0,
        last_page: res.last_page || 1,
        from: res.from || 0,
        to: res.to || 0
      }
    } else {
      // Fallback in case backend hasn't updated yet or returns array
      batches.value = Array.isArray(res) ? res : []
      pagination.value.last_page = 1
    }
  } catch (e) {
    error.value = 'Không thể tải dữ liệu'
    console.error(e)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    // Reset to page 1 on new search
    pagination.value.current_page = 1
    fetchBatches()
  }, 300)
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  fetchBatches()
}

const handleFormSaved = () => {
  pagination.value.current_page = 1
  fetchBatches()
}

onMounted(() => {
  fetchBatches()
})
</script>
