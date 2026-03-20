<template>
  <Teleport to="body">
    <Transition name="modal-backdrop">
      <div v-if="modelValue" class="fixed inset-0 z-50 bg-black/50" @click="close"></div>
    </Transition>

    <Transition name="modal-slide">
      <div v-if="modelValue" class="fixed inset-0 z-[51] flex items-end md:items-center justify-center">
        <div class="bg-white w-full md:max-w-lg md:rounded-lg rounded-t-[28px] shadow-2xl max-h-[90vh] flex flex-col overflow-hidden" @click.stop>
          <!-- Header -->
          <div class="flex items-center justify-between px-5 pt-5 pb-3 md:px-6 md:pt-6 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Tạo lô kiểm tra mới</h2>
            <button @click="close" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
              <X class="w-5 h-5 text-slate-500" />
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto overscroll-contain p-5 md:p-6 space-y-5">
            <!-- Tên lô -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Tên lô kiểm tra <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="VD: Lô kiểm tra Q1-2026 Khu vực A"
                class="w-full px-4 py-3 min-h-[48px] rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all"
              />
            </div>

            <!-- Người kiểm tra -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Người kiểm tra <span class="text-red-500">*</span></label>
              <MobileBottomSheet
                v-model="form.user_id"
                :options="userOptions"
                label="Chọn người kiểm tra"
                placeholder="Chọn inspector..."
                trigger-class="!min-h-[48px]"
              />
            </div>

            <!-- Checklist -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Checklist <span class="text-red-500">*</span></label>
              <MobileBottomSheet
                v-model="form.checklist_id"
                :options="checklistOptions"
                label="Chọn checklist"
                placeholder="Chọn checklist..."
                trigger-class="!min-h-[48px]"
              />
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Ngày bắt đầu <span class="text-red-500">*</span></label>
                <MobileDatePicker
                  v-model="form.start_date"
                  label="Ngày bắt đầu"
                  placeholder="Chọn ngày..."
                  :min-date="todayDateString"
                  trigger-class="!min-h-[48px]"
                  :error="!!fieldErrors?.start_date"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Ngày kết thúc <span class="text-red-500">*</span></label>
                <MobileDatePicker
                  v-model="form.end_date"
                  label="Ngày kết thúc"
                  placeholder="Chọn ngày..."
                  :min-date="form.start_date || todayDateString"
                  trigger-class="!min-h-[48px]"
                  :error="!!fieldErrors?.end_date"
                />
              </div>
            </div>

            <!-- Cabinets (Multi-select) -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Chọn tủ cáp <span class="text-red-500">*</span></label>
              <p class="text-xs text-slate-500 mb-2">Đã chọn: {{ form.cabinet_codes.length }} tủ</p>
              
              <!-- Search cabinets -->
              <div class="relative mb-3">
                <input
                  v-model="cabinetSearch"
                  type="text"
                  placeholder="Tìm kiếm mã tủ..."
                  class="w-full pl-9 pr-4 py-2.5 min-h-[44px] rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 text-sm outline-none transition-all"
                />
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              </div>

              <!-- Cabinet list -->
              <div class="border border-slate-200 rounded-lg max-h-[200px] overflow-y-auto overscroll-contain divide-y divide-gray-100">
                <label
                  v-for="cab in filteredCabinets"
                  :key="cab.cabinet_code"
                  class="flex items-center gap-3 px-4 py-3 min-h-[44px] cursor-pointer hover:bg-slate-50 transition-colors"
                >
                  <input
                    type="checkbox"
                    :value="cab.cabinet_code"
                    v-model="form.cabinet_codes"
                    class="w-5 h-5 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                  />
                  <div class="flex-1 min-w-0">
                    <span class="font-medium text-slate-900 text-sm">{{ cab.cabinet_code }}</span>
                    <span v-if="cab.area" class="text-slate-500 text-xs ml-2">{{ cab.area }}</span>
                  </div>
                </label>
                <div v-if="filteredCabinets.length === 0" class="px-4 py-3 text-sm text-slate-500 text-center">
                  Không tìm thấy tủ nào
                </div>
              </div>
            </div>

            <!-- Validation Errors -->
            <div v-if="formError" class="p-3 bg-danger/10 border border-red-200 rounded-lg space-y-1">
              <template v-if="Object.keys(fieldErrors).length > 0">
                <p v-for="(msgs, field) in fieldErrors" :key="field" class="text-sm text-danger">
                  • {{ Array.isArray(msgs) ? msgs[0] : msgs }}
                </p>
              </template>
              <p v-else class="text-sm text-danger">{{ formError }}</p>
            </div>
          </form>

          <!-- Footer -->
          <div class="px-5 py-4 md:px-6 border-t border-slate-200 flex gap-3">
            <button type="button" @click="close" class="flex-1 min-h-[48px] rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition-colors">
              Hủy
            </button>
            <button
              @click="handleSubmit"
              :disabled="submitting || !isFormValid"
              class="flex-1 min-h-[48px] rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ submitting ? 'Đang tạo...' : 'Tạo lô' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { Search, X } from 'lucide-vue-next'

import { ref, computed, onMounted, watch } from 'vue'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'saved'])

const submitting = ref(false)
const formError = ref('')
const cabinetSearch = ref('')

const todayDateString = new Date().toISOString().split('T')[0]

const form = ref({
  name: '',
  user_id: '',
  checklist_id: '',
  start_date: '',
  end_date: '',
  cabinet_codes: []
})

const userOptions = ref([])
const checklistOptions = ref([])
const allCabinets = ref([])

const filteredCabinets = computed(() => {
  if (!cabinetSearch.value) return allCabinets.value
  const q = cabinetSearch.value.toLowerCase()
  return allCabinets.value.filter(c =>
    c.cabinet_code.toLowerCase().includes(q) ||
    (c.area && c.area.toLowerCase().includes(q))
  )
})

const isFormValid = computed(() => {
  return form.value.name &&
    form.value.user_id &&
    form.value.checklist_id &&
    form.value.start_date &&
    form.value.end_date &&
    form.value.cabinet_codes.length > 0
})

const loadFormData = async () => {
  try {
    const [usersRes, checklistsRes, cabinetsRes] = await Promise.all([
      api.get('/users', { params: { per_page: 100 } }),
      api.get('/checklists'),
      api.get('/cabinets', { params: { per_page: 500 } })
    ])

    // Users: { message, data: { data: [...], current_page... } }
    // The paginator is nested inside response.data.data, and the actual array is response.data.data.data
    const usersPayload = usersRes.data?.data
    const users = Array.isArray(usersPayload) ? usersPayload : (usersPayload?.data || [])
    userOptions.value = users
      .filter(u => u.role === 'inspector')
      .map(u => ({ value: u.id, label: u.name }))

    // If no inspectors found, show all users as fallback
    if (userOptions.value.length === 0) {
      userOptions.value = users.map(u => ({ value: u.id, label: `${u.name} (${u.role})` }))
    }

    // Checklists: { data: [...] }
    const checklists = checklistsRes.data?.data || []
    checklistOptions.value = checklists.map(c => ({ value: c.id, label: c.name }))

    // Cabinets: { data: [...], current_page... } (paginator directly)
    const cabinets = cabinetsRes.data?.data || []
    allCabinets.value = cabinets
  } catch (e) {
    console.error('Failed to load form data:', e)
    formError.value = 'Không thể tải dữ liệu. Vui lòng thử lại.'
  }
}

const close = () => {
  emit('update:modelValue', false)
}

const resetForm = () => {
  form.value = {
    name: '',
    user_id: '',
    checklist_id: '',
    start_date: '',
    end_date: '',
    cabinet_codes: []
  }
  formError.value = ''
  fieldErrors.value = {}
  cabinetSearch.value = ''
}

const fieldErrors = ref({})

const handleSubmit = async () => {
  if (!isFormValid.value || submitting.value) return

  submitting.value = true
  formError.value = ''
  fieldErrors.value = {}

  try {
    await batchService.createBatch({
      name: form.value.name,
      user_id: Number(form.value.user_id),
      checklist_id: Number(form.value.checklist_id),
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      cabinet_codes: form.value.cabinet_codes
    })

    emit('saved')
    close()
    resetForm()
  } catch (e) {
    const res = e.response?.data
    if (res?.errors) {
      fieldErrors.value = res.errors
      // Combine all error messages
      const allErrors = Object.values(res.errors).flat()
      formError.value = allErrors.join('\n')
    } else {
      formError.value = res?.message || 'Không thể tạo lô kiểm tra'
    }
    console.error(e)
  } finally {
    submitting.value = false
  }
}

watch(() => props.modelValue, (val) => {
  if (val) {
    loadFormData()
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.modal-backdrop-enter-active,
.modal-backdrop-leave-active {
  transition: opacity 0.25s ease;
}
.modal-backdrop-enter-from,
.modal-backdrop-leave-to {
  opacity: 0;
}

.modal-slide-enter-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1), opacity 0.2s ease;
}
.modal-slide-leave-active {
  transition: transform 0.2s ease, opacity 0.15s ease;
}
.modal-slide-enter-from {
  transform: translateY(20px);
  opacity: 0;
}
.modal-slide-leave-to {
  transform: translateY(10px);
  opacity: 0;
}
</style>
