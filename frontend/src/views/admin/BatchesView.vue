<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-0">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('batch.title') }}</h2>
      <!-- Desktop action hidden on mobile -->
      <button class="hidden md:flex btn-primary" @click="showFormModal = true">
        <Plus class="w-5 h-5 mr-2" />
        {{ $t('batch.createNew') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
      <button
        @click="filters.approval_status = ''; handleSearch()"
        class="px-4 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-colors"
        :class="!filters.approval_status ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
      >
        {{ $t('batch.allBatches') }}
      </button>
      <button
        @click="filters.approval_status = 'pending'; handleSearch()"
        class="px-4 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-colors"
        :class="filters.approval_status === 'pending' ? 'bg-primary-600 text-white shadow-sm shadow-primary-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'"
      >
        {{ $t('batch.proposalsPending') }}
      </button>
    </div>

    <!-- Filters container -->
    <div class="bg-white rounded-[20px] md:rounded-lg shadow-sm border border-slate-200 md:border-slate-200 p-3 md:p-4">
      <div class="flex flex-col md:flex-row gap-4">
        <!-- Search -->
        <div class="relative w-full md:max-w-xs">
          <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
            <Search class="w-5 h-5 text-slate-400" />
          </div>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="$t('batch.searchPlaceholder')"
            class="w-full pl-11 pr-4 py-3 min-h-[52px] md:min-h-[40px] bg-slate-50/80 md:bg-white border md:border-slate-300 md:focus:bg-white focus:bg-white focus:border-primary-500 rounded-[16px] md:rounded-lg text-slate-900 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all placeholder:text-slate-400 font-medium"
            @input="handleSearch"
          />
        </div>
        
        <!-- Status -->
        <MobileBottomSheet
          v-model="filters.status"
          :options="statusFilterOptions"
          :label="$t('batch.selectStatus')"
          :placeholder="$t('batch.allStatuses')"
          container-class="w-full md:max-w-xs"
          trigger-class="!min-h-[52px] md:!min-h-[40px] !bg-slate-50/80 md:!bg-white md:!rounded-lg !rounded-[16px]"
          @update:model-value="handleSearch"
        />
      </div>
    </div>

    <!-- Desktop Table (hidden on mobile) -->
    <div class="hidden md:block card overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-slate-500">
        {{ $t('common.loading') }}
      </div>
      <div v-else-if="error" class="p-8 text-center text-red-500">
        {{ error }}
      </div>
      <table v-else class="w-full">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">{{ $t('batch.batchCode') }}</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">{{ $t('batch.batchName') }}</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">{{ $t('batch.progress') }}</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">{{ $t('common.createdAt') }}</th>
            <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">{{ $t('common.status') }}</th>
            <th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">{{ $t('common.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="batch in batches" :key="batch.id" class="hover:bg-slate-50">
            <td class="px-4 py-3 font-medium text-slate-900">#{{ batch.id }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">
              {{ batch.name || batch.title }}
              <span v-if="batch.approval_status === 'pending'" class="ml-2 px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-warning/10 text-warning-700 border border-warning/20">{{ $t('status.proposal') }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ batch.completed_count || 0 }} / {{ batch.plans_count || 0 }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ formatDate(batch.created_at) }}</td>
            <td class="px-4 py-3">
              <span :class="getStatusClass(batch.status)">{{ getStatusLabel(batch.status) }}</span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="inline-flex items-center justify-center p-2 rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100 text-sm font-bold transition-colors">
                {{ $t('batch.detailButton') }}
              </button>
            </td>
          </tr>
          <tr v-if="batches.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
              {{ $t('common.noData') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Stacked Cards (hidden on desktop) -->
    <div class="md:hidden space-y-4">
      <div v-if="loading" class="text-center py-8 text-slate-500">{{ $t('common.loading') }}</div>
      <div v-else-if="error" class="text-center py-8 text-red-500">{{ error }}</div>
      
      <template v-else>
        <!-- Card -->
        <div v-for="batch in batches" :key="batch.id" class="bg-white rounded-[24px] p-5 shadow-sm border border-slate-200 relative overflow-hidden active:scale-[0.98] transition-all duration-200">
          
          <!-- Card Header -->
          <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-[16px] bg-primary-50 flex items-center justify-center">
              <Package class="w-6 h-6 text-primary-600" />
            </div>
            <div class="flex-1 pt-1">
              <h3 class="text-lg font-bold text-slate-900 tracking-tight leading-tight">
                {{ batch.name || batch.title }}
                <span v-if="batch.approval_status === 'pending'" class="ml-2 inline-block align-middle px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-warning/10 text-warning-700 border border-warning/20">{{ $t('status.proposal') }}</span>
              </h3>
              <p class="text-sm font-medium text-slate-500 mt-0.5">{{ $t('batch.batchCode') }}: #{{ batch.id }}</p>
            </div>
          </div>
          
          <!-- Grouped Metadata block -->
          <div class="bg-slate-50/80 rounded-[16px] p-3.5 mb-4 space-y-2.5">
            <div class="flex items-center text-sm">
                <div class="flex items-center text-slate-500 font-medium w-24">
                  <Server class="w-4 h-4 mr-1.5" />
                  Số tủ:
                </div>
                <span class="text-slate-900 font-semibold">{{ batch.completed_count || 0 }} / {{ batch.plans_count || 0 }}</span>
            </div>
            <div class="flex items-center text-sm">
                <div class="flex items-center text-slate-500 font-medium w-24">
                  <Calendar class="w-4 h-4 mr-1.5" />
                  Ngày tạo:
                </div>
                <span class="text-slate-900 font-semibold">{{ formatDate(batch.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between text-sm pt-1 mt-1 border-t border-slate-200/50">
              <span class="text-slate-500 font-medium">{{ $t('common.status') }}:</span>
              <span :class="getStatusClass(batch.status)" class="shrink-0">{{ getStatusLabel(batch.status) }}</span>
            </div>
          </div>
          
          <!-- Action row -->
          <button @click="$router.push({ name: 'admin-batch-detail', params: { id: batch.id } })" class="w-full flex items-center justify-center min-h-[48px] rounded-[14px] bg-primary-50 border border-primary-100 text-primary-700 font-bold hover:bg-primary-100 active:bg-primary-200 transition-colors">
              {{ $t('batch.detailBatchButton') }}
          </button>
        </div>

        <!-- Empty State Mobile -->
        <div v-if="batches.length === 0" class="flex flex-col items-center justify-center py-12 px-4 bg-white rounded-[24px] border border-slate-200 shadow-sm mt-4">
            <FileStack class="w-16 h-16 text-gray-300 mb-4" />
            <p class="text-slate-500 font-medium text-center">{{ $t('batch.noBatches') }}<br/><span class="text-sm text-slate-400 font-normal">{{ $t('batch.createToInspect') }}</span></p>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <template v-if="!loading && !error && batches.length > 0">
      <!-- Desktop Pagination -->
      <div v-if="pagination.last_page > 1" class="hidden md:flex bg-white rounded-lg shadow-sm border border-slate-200 p-6 items-center justify-between">
        <p class="text-sm text-slate-600 font-medium">
          {{ $t('common.showing') }} <span class="font-semibold text-slate-900">{{ pagination.from }}</span> - 
          <span class="font-semibold text-slate-900">{{ pagination.to }}</span> {{ $t('common.of') }} 
          <span class="font-semibold text-slate-900">{{ pagination.total }}</span> {{ $t('batch.batchItems') }}
        </p>
        
        <div class="flex items-center gap-1.5">
          <button
            @click="changePage(1)"
            :disabled="pagination.current_page === 1"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors items-center justify-center"
          >
            <ChevronsLeft class="w-4 h-4" />
          </button>
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors flex items-center justify-center"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <div class="px-4 font-semibold text-sm text-slate-700">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </div>
          
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors flex items-center justify-center"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            @click="changePage(pagination.last_page)"
            :disabled="pagination.current_page === pagination.last_page"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors items-center justify-center"
          >
            <ChevronsRight class="w-4 h-4" />
          </button>
        </div>
      </div>
      
      <!-- Mobile Pagination Pill: mb-24 pushes it above the FAB -->
      <div v-if="pagination.last_page > 1" class="md:hidden mt-8 mb-24">
        <div class="text-center mb-5">
          <div class="text-sm text-slate-500 font-medium">
            {{ $t('batch.title') }} <span class="text-slate-900 font-bold">{{ pagination.from }}</span> - <span class="text-slate-900 font-bold">{{ pagination.to }}</span><br/>
            <span class="text-xs text-slate-400 mt-1 inline-block">{{ $t('batch.inTotalBatches', { total: pagination.total }) }}</span>
          </div>
        </div>
        <div class="flex items-center justify-between bg-white p-2 rounded-[24px] shadow-sm border border-slate-200 mx-auto max-w-[320px]">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="group flex items-center justify-center w-14 h-14 rounded-full bg-slate-50/80 text-slate-600 active:bg-slate-100 active:scale-95 transition-all duration-200 disabled:opacity-40 disabled:active:scale-100 disabled:bg-transparent"
          >
            <ChevronLeft class="w-6 h-6 group-active:-translate-x-1 transition-transform" />
          </button>
          <div class="flex-1 flex flex-col items-center justify-center">
            <div class="flex items-baseline gap-1.5">
              <span class="text-xl font-bold tracking-tight text-slate-900">{{ pagination.current_page }}</span>
              <span class="text-sm font-semibold text-slate-400">/ {{ pagination.last_page }}</span>
            </div>
          </div>
          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="group flex items-center justify-center w-14 h-14 rounded-full text-white bg-primary-600 shadow-sm shadow-primary-500/30 active:bg-primary-700 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:active:scale-100 disabled:shadow-none"
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
        class="flex items-center justify-center w-[56px] h-[56px] rounded-full bg-primary-600 text-white shadow-sm shadow-primary-500/40 hover:bg-primary-700 active:scale-90 active:bg-primary-800 transition-all"
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

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getDateLocale } from '@/i18n'
import batchService from '@/services/batchService.js'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import BatchFormModal from '@/components/admin/BatchFormModal.vue'

const router = useRouter()
const { t } = useI18n()
const showFormModal = ref(false)

const statusFilterOptions = computed(() => [
  { value: '', label: t('batch.allStatuses') },
  { value: 'pending', label: t('status.pending') },
  { value: 'in_progress', label: t('status.in_progress') },
  { value: 'completed', label: t('status.completed') }
])

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
  status: '',
  approval_status: ''
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
  const map = {
    completed: 'status.completed',
    in_progress: 'status.in_progress',
    pending: 'status.pending',
    done: 'status.done',
    'in-progress': 'status.in_progress'
  }
  return t(map[status] || 'status.pending')
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString(getDateLocale())
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
    // Add approval_status
    if (filters.value.approval_status) {
      params.approval_status = filters.value.approval_status
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
    error.value = t('common.errorLoadData')
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
