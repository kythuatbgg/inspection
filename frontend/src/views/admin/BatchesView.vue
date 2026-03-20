<template>
  <!-- pb-24 safeguards the FAB from overlapping content at the bottom -->
  <div class="space-y-6 pb-24 md:pb-0">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-900">Danh sách lô kiểm tra</h2>
      <!-- Desktop action hidden on mobile -->
      <button class="hidden md:flex btn-primary" @click="showFormModal = true">
        <Plus class="w-5 h-5 mr-2" />
        Tạo lô mới
      </button>
    </div>

    <!-- Filters container: stacked on mobile, row on desktop -->
    <div class="card p-4">
      <div class="flex flex-col md:flex-row gap-4">
        <!-- Search -->
        <div class="relative w-full md:max-w-xs">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <Search class="w-5 h-5 text-gray-400" />
          </div>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Tìm kiếm mã lô, tên lô..."
            class="w-full pl-10 pr-4 py-3 md:py-2 min-h-[52px] md:min-h-[40px] rounded-[16px] md:rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
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
          trigger-class="!min-h-[52px] md:!min-h-[40px] md:!py-2 md:!rounded-xl"
          @update:model-value="handleSearch"
        />
      </div>
    </div>

    <!-- Desktop Table (hidden on mobile) -->
    <div class="hidden md:block card overflow-hidden">
      <!-- Loading -->
      <div v-if="loading" class="p-8 text-center text-gray-500">
        Đang tải...
      </div>

      <!-- Error -->
      <div v-else-if="error" class="p-8 text-center text-red-500">
        {{ error }}
      </div>

      <!-- Data -->
      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã lô</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên lô</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số tủ</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="batch in batches" :key="batch.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">#{{ batch.id }}</td>
            <td class="px-4 py-3 text-gray-600">{{ batch.name || batch.title }}</td>
            <td class="px-4 py-3 text-gray-600">{{ batch.plans_count || 0 }}</td>
            <td class="px-4 py-3 text-gray-600">{{ formatDate(batch.created_at) }}</td>
            <td class="px-4 py-3">
              <span :class="getStatusClass(batch.status)">{{ getStatusLabel(batch.status) }}</span>
            </td>
            <td class="px-4 py-3">
              <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="text-primary-600 hover:text-primary-800 font-medium text-sm">Chi tiết</button>
            </td>
          </tr>

          <!-- Empty Desktop -->
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
        <div v-for="batch in batches" :key="batch.id" class="bg-white rounded-[20px] p-5 shadow-sm border border-gray-100 flex flex-col gap-4">
          <div class="flex justify-between items-start">
             <div>
                <h3 class="font-bold text-gray-900 text-base leading-tight">{{ batch.name || batch.title }}</h3>
                <p class="text-gray-500 text-sm mt-1">Mã lô: #{{ batch.id }}</p>
             </div>
             <span :class="getStatusClass(batch.status)" class="shrink-0">{{ getStatusLabel(batch.status) }}</span>
          </div>
          
          <!-- Grouped Metadata block -->
          <div class="bg-gray-50/80 rounded-[14px] p-3 flex justify-between items-center">
              <div class="text-sm">
                  <span class="text-gray-500">Số tủ:</span>
                  <span class="ml-1 font-semibold text-gray-900">{{ batch.plans_count || 0 }}</span>
              </div>
              <div class="text-sm">
                  <span class="text-gray-500">Ngày tạo:</span>
                  <span class="ml-1 font-medium text-gray-900">{{ formatDate(batch.created_at) }}</span>
              </div>
          </div>
          
          <!-- Action row -->
          <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="w-full flex items-center justify-center min-h-[48px] rounded-[14px] bg-primary-50 text-primary-700 font-semibold hover:bg-primary-100 transition-colors">
              Chi tiết lô
          </button>
        </div>

        <!-- Empty State Mobile -->
        <div v-if="batches.length === 0" class="text-center py-12 flex flex-col items-center">
            <div class="w-16 h-16 rounded-full border-2 border-dashed border-gray-300 flex items-center justify-center mb-4 text-gray-400">
                <FileStack class="w-8 h-8" />
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">Chưa có lô nào</h3>
            <p class="text-sm text-gray-500 text-center max-w-xs">Hãy tạo lô kiểm tra mới để bắt đầu quá trình đánh giá.</p>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <template v-if="!loading && !error && batches.length > 0">
      <!-- Desktop Pagination -->
      <div v-if="pagination.last_page > 1" class="hidden md:flex bg-white rounded-xl shadow-sm border border-gray-200 p-6 items-center justify-between">
        <div class="flex items-center gap-2 text-sm text-gray-600 font-medium">
          <label>Hiển thị:</label>
          <MobileBottomSheet
            :model-value="pagination.per_page"
            :options="perPageOptions"
            label="Số mục mỗi trang"
            placeholder="10"
            container-class="w-24"
            trigger-class="!min-h-[40px] !py-1.5 !px-3 !rounded-lg text-sm"
            @update:model-value="(val) => { pagination.per_page = Number(val); changePage(1) }"
          />
          <span>/ trang</span>
        </div>
        
        <div class="flex items-center gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronLeft class="w-5 h-5" />
          </button>
          
          <div class="flex items-center px-4 py-2 min-h-[40px] border border-gray-300 rounded-lg bg-gray-50 text-sm font-medium text-gray-700">
            Trang {{ pagination.current_page }} / {{ pagination.last_page }}
          </div>
          
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 rounded-lg border border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <ChevronRight class="w-5 h-5" />
          </button>
        </div>
      </div>
      
      <!-- Mobile Pagination Pill: mb-24 pushes it above the FAB -->
      <div v-if="pagination.last_page > 1" class="md:hidden flex justify-center mt-6 mb-24">
        <div class="bg-white rounded-[24px] shadow-sm border border-gray-200 p-1.5 flex items-center gap-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="w-[44px] h-[44px] rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
          >
            <ChevronLeft class="w-6 h-6" />
          </button>
          
          <span class="px-4 text-[15px] font-semibold text-gray-900">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="w-[44px] h-[44px] rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-100 disabled:opacity-30 disabled:hover:bg-transparent transition-colors"
          >
            <ChevronRight class="w-6 h-6" />
          </button>
        </div>
      </div>
    </template>

    <!-- FAB for mobile (Fixed Bottom Right) -->
    <div class="md:hidden fixed bottom-6 right-6 z-40">
      <!-- Glow effect -->
      <div class="absolute inset-0 bg-primary-500 opacity-30 blur-lg rounded-full animate-pulse-slow"></div>
      <button 
        @click="showFormModal = true"
        class="relative flex items-center justify-center w-[60px] h-[60px] rounded-full bg-primary-600 text-white shadow-xl hover:bg-primary-700 active:scale-95 transition-all group"
      >
        <Plus class="w-8 h-8 transition-transform group-hover:rotate-90" />
      </button>
    </div>

    <!-- Batch Form Modal -->
    <BatchFormModal v-model="showFormModal" @saved="handleFormSaved" />
  </div>
</template>

<script setup>
import { Plus, ChevronRight, ChevronLeft, FileStack, Search } from 'lucide-vue-next'

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
