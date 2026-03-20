<template>
  <div class="space-y-6 pb-20 lg:pb-6">
    <!-- Back Button -->
    <button @click="goBack" class="flex items-center gap-2 text-gray-600 hover:text-gray-900">
      <span>←</span> Quay lại
    </button>

    <!-- Loading -->
    <div v-if="loading" class="card p-8 text-center">
      <p class="text-gray-500">Đang tải...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="card p-8 text-center">
      <p class="text-red-500">{{ error }}</p>
    </div>

    <!-- Cabinet Detail -->
    <div v-else-if="cabinet" class="space-y-4">
      <!-- Header -->
      <div class="card p-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ cabinet.cabinet_code }}</h1>
            <p class="text-gray-500 mt-1">{{ cabinet.bts_site }}</p>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit" class="btn-secondary min-h-[56px] px-4">
              Sửa
            </button>
            <button @click="confirmDelete" class="btn-primary bg-red-600 hover:bg-red-700 min-h-[56px] px-4">
              Xóa
            </button>
          </div>
        </div>
      </div>

      <!-- Info Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card p-4">
          <h3 class="font-semibold text-gray-700 mb-3">Thông tin tủ cáp</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">BTS Site:</span>
              <span class="font-medium">{{ cabinet.bts_site }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Tọa độ:</span>
              <span>{{ cabinet.lat }}, {{ cabinet.lng }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Ghi chú:</span>
              <span>{{ cabinet.note || '-' }}</span>
            </div>
          </div>
        </div>

        <div class="card p-4">
          <h3 class="font-semibold text-gray-700 mb-3">Trạng thái</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Ngày tạo:</span>
              <span>{{ formatDate(cabinet.created_at) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Cập nhật:</span>
              <span>{{ formatDate(cabinet.updated_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="editMode" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="editMode = false"></div>
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sửa tủ cáp</h3>

        <form @submit.prevent="saveCabinet" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">BTS Site</label>
            <input v-model="editForm.bts_site" class="input-field min-h-[56px]" required />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Vĩ độ</label>
              <input v-model.number="editForm.lat" type="number" step="0.000001" class="input-field min-h-[56px]" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Kinh độ</label>
              <input v-model.number="editForm.lng" type="number" step="0.000001" class="input-field min-h-[56px]" required />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú</label>
            <textarea v-model="editForm.note" rows="3" class="input-field min-h-[56px]"></textarea>
          </div>

          <div v-if="saveError" class="p-3 bg-red-50 text-red-600 text-sm rounded">
            {{ saveError }}
          </div>

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
      <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Xác nhận xóa</h3>
        <p class="text-gray-600 mb-4">Xóa tủ cáp {{ cabinet?.cabinet_code }}?</p>
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
