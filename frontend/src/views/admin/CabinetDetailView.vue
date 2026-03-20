<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-6">
    <!-- Back Button -->
    <button @click="goBack" class="flex items-center gap-2 text-slate-500 hover:text-slate-900 min-h-[44px] -ml-1 px-2 rounded-lg transition-colors">
      <ArrowLeft class="w-5 h-5" />
      <span class="text-sm font-medium">Quay lại</span>
    </button>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <Loader2 class="w-8 h-8 text-slate-400 animate-spin mx-auto mb-3" />
      <p class="text-slate-500">Đang tải...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="card p-8 text-center">
      <AlertCircle class="w-10 h-10 text-red-400 mx-auto mb-3" />
      <p class="text-red-500 font-medium">{{ error }}</p>
    </div>

    <!-- Cabinet Detail -->
    <div v-else-if="cabinet" class="space-y-4">
      <!-- DESKTOP Header -->
      <div class="hidden md:block card p-6">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-lg bg-primary-50 flex items-center justify-center">
              <Server class="w-7 h-7 text-primary-600" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-slate-900">{{ cabinet.cabinet_code }}</h1>
              <p class="text-slate-500 mt-0.5 flex items-center gap-1.5">
                <MapPin class="w-4 h-4" />
                {{ cabinet.bts_site }}
              </p>
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit" class="btn-secondary min-h-[44px] px-5 flex items-center gap-2 text-sm font-semibold rounded-lg">
              <FileEdit class="w-4 h-4" /> Sửa
            </button>
            <button @click="confirmDelete" class="bg-danger/10 hover:bg-red-100 text-danger border border-red-200 min-h-[44px] px-5 flex items-center gap-2 text-sm font-semibold rounded-lg transition-colors">
              <Trash2 class="w-4 h-4" /> Xóa
            </button>
          </div>
        </div>
      </div>

      <!-- MOBILE Header -->
      <div class="md:hidden">
        <div class="bg-white rounded-[24px] p-5 shadow-sm border border-slate-200">
          <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-14 h-14 rounded-[18px] bg-primary-50 flex items-center justify-center">
              <Server class="w-7 h-7 text-primary-600" />
            </div>
            <div class="flex-1 pt-1">
              <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ cabinet.cabinet_code }}</h1>
              <p class="text-sm font-medium text-slate-500 flex items-center gap-1 mt-0.5">
                <MapPin class="w-4 h-4" />
                {{ cabinet.bts_site }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button @click="openEdit" class="flex-1 min-h-[48px] bg-white border border-slate-200 rounded-[14px] text-slate-700 font-semibold text-sm flex items-center justify-center gap-2 active:bg-slate-50 transition-colors">
              <FileEdit class="w-5 h-5 text-slate-400" /> Sửa
            </button>
            <button @click="confirmDelete" class="flex-1 min-h-[48px] bg-danger/10/50 border border-red-100 rounded-[14px] text-danger font-semibold text-sm flex items-center justify-center gap-2 active:bg-red-100 transition-colors">
              <Trash2 class="w-5 h-5" /> Xóa
            </button>
          </div>
        </div>
      </div>

      <!-- Info Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-[20px] md:rounded-lg p-5 shadow-sm border border-slate-200 md:border-slate-200">
          <h3 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <Info class="w-4 h-4 text-slate-400" />
            Thông tin tủ cáp
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-slate-500">BTS Site</span>
              <span class="font-semibold text-slate-900">{{ cabinet.bts_site }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Tọa độ</span>
              <div class="flex items-center gap-2">
                <span class="font-medium text-slate-700">{{ cabinet.lat }}, {{ cabinet.lng }}</span>
                <a
                  v-if="cabinet.lat && cabinet.lng"
                  :href="`https://www.google.com/maps/dir/?api=1&destination=${cabinet.lat},${cabinet.lng}`"
                  target="_blank"
                  rel="noopener"
                  class="hidden sm:inline-flex items-center gap-1 text-xs text-primary-600 font-semibold bg-primary-50 hover:bg-primary-100 px-2 py-1 rounded-lg transition-colors"
                  title="Mở Google Maps"
                >
                  <Navigation class="w-3.5 h-3.5" />
                  Chỉ đường
                </a>
              </div>
            </div>
            <div class="flex justify-between items-start">
              <span class="text-slate-500 pt-0.5">Ghi chú</span>
              <span class="text-slate-700 text-right max-w-[60%] leading-relaxed">{{ cabinet.note || '—' }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-[20px] md:rounded-lg p-5 shadow-sm border border-slate-200 md:border-slate-200">
          <h3 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
            <Clock class="w-4 h-4 text-slate-400" />
            Trạng thái
          </h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Ngày tạo</span>
              <span class="font-medium text-slate-700">{{ formatDate(cabinet.created_at) }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-500">Cập nhật</span>
              <span class="font-medium text-slate-700">{{ formatDate(cabinet.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Google Maps CTA (Mobile) -->
      <div v-if="cabinet.lat && cabinet.lng" class="md:hidden">
        <a
          :href="`https://www.google.com/maps/dir/?api=1&destination=${cabinet.lat},${cabinet.lng}`"
          target="_blank"
          rel="noopener"
          class="flex items-center gap-4 bg-primary-50 border border-blue-100 rounded-[20px] p-4 active:bg-primary-100 transition-colors"
        >
          <div class="w-12 h-12 rounded-[14px] bg-primary-100 flex items-center justify-center flex-shrink-0">
            <Navigation class="w-6 h-6 text-primary-600" />
          </div>
          <div class="flex-1">
            <p class="font-semibold text-blue-900 text-sm">Chỉ đường trên Google Maps</p>
            <p class="text-xs text-primary-600 mt-0.5">{{ cabinet.lat }}, {{ cabinet.lng }}</p>
          </div>
          <ChevronRight class="w-5 h-5 text-blue-400 flex-shrink-0" />
        </a>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editMode" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="editMode = false"></div>
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Sửa tủ cáp</h3>
        <form @submit.prevent="saveCabinet" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">BTS Site</label>
            <input v-model="editForm.bts_site" class="input-field min-h-[56px]" required />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Vĩ độ</label>
              <input v-model.number="editForm.lat" type="number" step="0.000001" class="input-field min-h-[56px]" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Kinh độ</label>
              <input v-model.number="editForm.lng" type="number" step="0.000001" class="input-field min-h-[56px]" required />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Ghi chú</label>
            <textarea v-model="editForm.note" rows="3" class="input-field min-h-[56px]"></textarea>
          </div>
          <div v-if="saveError" class="p-3 bg-danger/10 text-danger text-sm rounded">{{ saveError }}</div>
          <div class="flex gap-3">
            <button type="button" @click="editMode = false" class="btn-secondary flex-1 min-h-[56px]">Hủy</button>
            <button type="submit" :disabled="saving" class="btn-primary flex-1 min-h-[56px]">
              {{ saving ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showDelete = false"></div>
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-2">Xác nhận xóa</h3>
        <p class="text-slate-600 mb-4">Xóa tủ cáp {{ cabinet?.cabinet_code }}?</p>
        <div class="flex gap-3">
          <button @click="showDelete = false" class="btn-secondary flex-1 min-h-[56px]">Hủy</button>
          <button @click="deleteCabinet" :disabled="deleting" class="btn-primary bg-red-600 hover:bg-red-700 flex-1 min-h-[56px]">
            {{ deleting ? 'Đang xóa...' : 'Xóa' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ArrowLeft, Loader2, AlertCircle, Server, MapPin, FileEdit, Trash2, Info, Navigation, Clock, ChevronRight } from 'lucide-vue-next'
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import cabinetService from '@/services/cabinetService.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(null)
const cabinet = ref(null)
const editMode = ref(false)
const saving = ref(false)
const saveError = ref(null)
const showDelete = ref(false)
const deleting = ref(false)

const editForm = ref({
  bts_site: '',
  lat: 0,
  lng: 0,
  note: ''
})

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('vi-VN')
}

const goBack = () => router.back()

const fetchCabinet = async () => {
  loading.value = true
  try {
    const data = await cabinetService.getCabinetById(route.params.code)
    cabinet.value = data
  } catch (e) {
    error.value = 'Không tìm thấy tủ cáp'
  } finally {
    loading.value = false
  }
}

const openEdit = () => {
  editForm.value = {
    bts_site: cabinet.value.bts_site,
    lat: cabinet.value.lat,
    lng: cabinet.value.lng,
    note: cabinet.value.note || ''
  }
  editMode.value = true
}

const saveCabinet = async () => {
  saveError.value = null
  saving.value = true
  try {
    await cabinetService.updateCabinet(route.params.code, editForm.value)
    editMode.value = false
    fetchCabinet()
  } catch (e) {
    saveError.value = e.response?.data?.message || 'Lỗi lưu'
  } finally {
    saving.value = false
  }
}

const confirmDelete = () => {
  showDelete.value = true
}

const deleteCabinet = async () => {
  deleting.value = true
  try {
    await cabinetService.deleteCabinet(route.params.code)
    router.push('/admin/cabinets')
  } catch (e) {
    alert(e.response?.data?.message || 'Xóa thất bại')
  } finally {
    deleting.value = false
    showDelete.value = false
  }
}

onMounted(() => {
  fetchCabinet()
})
</script>
