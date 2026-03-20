<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50" @click="close"></div>

    <!-- Modal Content -->
    <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900">{{ isEdit ? 'Sửa tủ cáp' : 'Thêm tủ cáp mới' }}</h3>
        <button @click="close" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
          <X class="w-5 h-5 text-slate-500" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
        <!-- Cabinet Code -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Mã tủ cáp *</label>
          <input
            v-model="form.cabinet_code"
            type="text"
            class="input-field min-h-[56px]"
            :disabled="isEdit"
            placeholder="VD: CAB-001"
            required
          />
        </div>

        <!-- BTS Site -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">BTS Site *</label>
          <input
            v-model="form.bts_site"
            type="text"
            class="input-field min-h-[56px]"
            placeholder="VD: BTS-HCM-001"
            required
          />
        </div>

        <!-- Lat/Lng -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Vĩ độ (Lat) *</label>
            <input
              v-model="form.lat"
              type="text"
              class="input-field min-h-[56px]"
              placeholder="10.8231"
              required
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Kinh độ (Lng) *</label>
            <input
              v-model="form.lng"
              type="text"
              class="input-field min-h-[56px]"
              placeholder="106.6297"
              required
            />
          </div>
        </div>

        <!-- Note -->
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Ghi chú</label>
          <textarea
            v-model="form.note"
            rows="3"
            class="input-field min-h-[56px]"
            placeholder="Nhập ghi chú (nếu có)"
          ></textarea>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="p-3 bg-danger/10 border border-red-200 rounded-lg">
          <p class="text-sm text-danger">{{ error }}</p>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 pt-4">
          <button type="button" @click="close" class="btn-secondary flex-1 min-h-[56px]">Hủy</button>
          <button type="submit" :disabled="loading" class="btn-primary flex-1 min-h-[56px] flex items-center justify-center gap-2">
            <Loader2 class="animate-spin w-5 h-5" />
            <span>{{ isEdit ? 'Cập nhật' : 'Thêm mới' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { Loader2, X } from 'lucide-vue-next'

import { ref, computed, watch } from 'vue'
import cabinetService from '@/services/cabinetService'

const props = defineProps({
  visible: { type: Boolean, default: false },
  cabinet: { type: Object, default: null }
})

const emit = defineEmits(['update:visible', 'saved'])

const form = ref({
  cabinet_code: '',
  bts_site: '',
  lat: '',
  lng: '',
  note: ''
})

const loading = ref(false)
const error = ref('')

const isEdit = computed(() => !!props.cabinet)

// Reset form when cabinet changes
watch(() => props.cabinet, (newCabinet) => {
  if (newCabinet) {
    form.value = {
      cabinet_code: newCabinet.cabinet_code || '',
      bts_site: newCabinet.bts_site || '',
      lat: newCabinet.lat || '',
      lng: newCabinet.lng || '',
      note: newCabinet.note || ''
    }
  } else {
    form.value = {
      cabinet_code: '',
      bts_site: '',
      lat: '',
      lng: '',
      note: ''
    }
  }
}, { immediate: true })

const close = () => {
  emit('update:visible', false)
  error.value = ''
}

const handleSubmit = async () => {
  error.value = ''
  loading.value = true

  try {
    const data = {
      ...form.value,
      lat: form.value.lat ? parseFloat(form.value.lat) : null,
      lng: form.value.lng ? parseFloat(form.value.lng) : null
    }

    if (isEdit.value) {
      await cabinetService.updateCabinet(props.cabinet.cabinet_code, data)
    } else {
      await cabinetService.createCabinet(data)
    }

    emit('saved')
    close()
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.error || 'Có lỗi xảy ra. Vui lòng thử lại.'
  } finally {
    loading.value = false
  }
}
</script>
