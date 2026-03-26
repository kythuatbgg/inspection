<template>
  <Teleport to="body">
    <Transition name="modal-backdrop">
      <div v-if="modelValue" class="fixed inset-0 z-50 bg-black/50" @click="close"></div>
    </Transition>

    <Transition name="modal-slide">
      <div v-if="modelValue" class="fixed inset-0 z-[51] flex items-end md:items-center justify-center">
        <div class="bg-white w-full md:max-w-xl md:rounded-lg rounded-t-[28px] shadow-2xl max-h-[90vh] flex flex-col overflow-hidden" @click.stop>
          <!-- Header -->
          <div class="flex items-center justify-between px-5 pt-5 pb-3 md:px-6 md:pt-6 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">{{ $t('batch.createNew') }}</h2>
            <button @click="close" class="w-10 h-10 rounded-lg flex items-center justify-center hover:bg-slate-100 transition-colors">
              <X class="w-5 h-5 text-slate-500" />
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto overscroll-contain p-5 md:p-6 space-y-5">

            <!-- Batch Type -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.batchType') }} <span class="text-red-500">*</span></label>
              <MobileBottomSheet
                v-model="form.batch_type"
                :options="batchTypeOptions"
                :label="$t('batch.selectBatchType')"
                placeholder="Chọn loại..."
                trigger-class="!min-h-[48px]"
              />
            </div>

            <!-- Tên lô -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.batchName') }} <span class="text-red-500">*</span></label>
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
              <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.inspector') }} <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.startDate') }} <span class="text-red-500">*</span></label>
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
                <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.endDate') }} <span class="text-red-500">*</span></label>
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

            <!-- ===== ACCOUNTS (Deployment only) ===== -->
            <div v-if="form.batch_type === 'deployment'" class="space-y-3">
              <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700">{{ $t('batch.accounts') }} <span class="text-red-500">*</span></label>
                <button
                  type="button"
                  @click="addAccount"
                  class="flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-semibold px-3 py-1.5 rounded-lg hover:bg-primary-50 transition-colors"
                >
                  <Plus class="w-4 h-4" />
                  {{ $t('batch.addAccount') }}
                </button>
              </div>

              <!-- Empty state -->
              <div v-if="form.batch_accounts.length === 0" class="text-sm text-slate-500 text-center py-6 bg-slate-50 rounded-lg border border-slate-200">
                {{ $t('batch.noAccountsHint', { action: $t('batch.addAccount') }) }}
              </div>

              <!-- Account rows -->
              <div
                v-for="(account, accIdx) in form.batch_accounts"
                :key="accIdx"
                class="border border-slate-200 rounded-lg overflow-hidden"
              >
                <!-- Account header -->
                <div class="flex items-center justify-between px-4 py-3 bg-slate-50 hover:bg-slate-100 transition-colors">
                  <div class="flex items-center gap-2 flex-1 min-w-0 cursor-pointer" @click="toggleAccount(accIdx)">
                    <ChevronDown
                      class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0"
                      :class="{ 'rotate-180': account.expanded }"
                    />
                    <span class="font-semibold text-slate-800 text-sm truncate">
                      {{ account.account_code || $t('batch.accountCode') }}
                    </span>
                    <span class="text-xs text-slate-500 bg-white px-2 py-0.5 rounded-full border border-slate-200 shrink-0">
                      {{ account.cabinet_codes.length }} tủ
                    </span>
                  </div>
                  <button
                    type="button"
                    @click.stop="removeAccount(accIdx)"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0 ml-2"
                    :title="$t('batch.removeAccount')"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>

                <!-- Account body -->
                <div v-if="account.expanded" class="px-4 pb-4 pt-2 space-y-3">
                  <!-- Account code input -->
                  <div>
                    <input
                      v-model="account.account_code"
                      type="text"
                      :placeholder="$t('batch.accountCode')"
                      class="w-full px-3 py-2.5 min-h-[44px] rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition-all text-sm"
                    />
                  </div>

                  <!-- Search cabinets -->
                  <div class="relative">
                    <input
                      v-model="account.search"
                      type="text"
                      :placeholder="$t('batch.searchCabinets')"
                      class="w-full pl-9 pr-4 py-2.5 min-h-[44px] rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 text-sm outline-none transition-all"
                    />
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  </div>

                  <!-- Cabinet list -->
                  <div class="border border-slate-200 rounded-lg max-h-[160px] overflow-y-auto overscroll-contain divide-y divide-gray-100">
                    <label
                      v-for="cab in filteredCabinetsForAccount(accIdx)"
                      :key="cab.cabinet_code"
                      class="flex items-center gap-3 px-3 py-2.5 min-h-[40px] cursor-pointer hover:bg-slate-50 transition-colors"
                    >
                      <input
                        type="checkbox"
                        :value="cab.cabinet_code"
                        v-model="account.cabinet_codes"
                        class="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                      <div class="flex-1 min-w-0">
                        <span class="font-medium text-slate-800 text-xs">{{ cab.cabinet_code }}</span>
                        <span v-if="cab.area" class="text-slate-400 text-xs ml-2">{{ cab.area }}</span>
                      </div>
                    </label>
                    <div v-if="filteredCabinetsForAccount(accIdx).length === 0" class="px-3 py-3 text-xs text-slate-500 text-center">
                      {{ $t('common.noCabinetsFound') }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ===== CABINETS (Inspection only) ===== -->
            <div v-if="form.batch_type === 'inspection'">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $t('batch.selectCabinets') }} <span class="text-red-500">*</span></label>
              <p class="text-xs text-slate-500 mb-2">{{ $t('batch.selectedCount', { count: form.cabinet_codes.length }) }}</p>

              <!-- Search cabinets -->
              <div class="relative mb-3">
                <input
                  v-model="cabinetSearch"
                  type="text"
                  :placeholder="$t('batch.searchCabinets')"
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
                  {{ $t('common.noCabinetsFound') }}
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
              {{ $t('common.cancel') }}
            </button>
            <button
              @click="handleSubmit"
              :disabled="submitting || !isFormValid"
              class="flex-1 min-h-[48px] rounded-lg bg-primary-600 text-white font-semibold hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center gap-2"
            >
              <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
              <span v-else>{{ $t('batch.createBatch') }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { Search, X, Plus, Loader2, ChevronDown } from 'lucide-vue-next'

import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false }
})

const { t } = useI18n()

const emit = defineEmits(['update:modelValue', 'saved'])

const submitting = ref(false)
const formError = ref('')
const fieldErrors = ref({})
const cabinetSearch = ref('')

const todayDateString = new Date().toISOString().split('T')[0]

const form = ref({
  batch_type: 'inspection',
  name: '',
  user_id: '',
  checklist_id: '',
  start_date: '',
  end_date: '',
  cabinet_codes: [],
  // Deployment: array of { account_code, cabinet_codes, search, expanded }
  batch_accounts: []
})

const userOptions = ref([])
const checklistOptions = ref([])
const allCabinets = ref([])

const batchTypeOptions = computed(() => [
  { value: 'inspection', label: t('batch.batchTypeInspection') },
  { value: 'deployment', label: t('batch.batchTypeDeployment') },
])

const filteredCabinets = computed(() => {
  if (!cabinetSearch.value) return allCabinets.value
  const q = cabinetSearch.value.toLowerCase()
  return allCabinets.value.filter(c =>
    c.cabinet_code.toLowerCase().includes(q) ||
    (c.area && c.area.toLowerCase().includes(q))
  )
})

const filteredCabinetsForAccount = (accIdx) => {
  const account = form.value.batch_accounts[accIdx]
  if (!account) return []
  if (!account.search) return allCabinets.value
  const q = account.search.toLowerCase()
  return allCabinets.value.filter(c =>
    c.cabinet_code.toLowerCase().includes(q) ||
    (c.area && c.area.toLowerCase().includes(q))
  )
}

const isFormValid = computed(() => {
  if (!form.value.name || !form.value.user_id || !form.value.checklist_id || !form.value.start_date || !form.value.end_date) {
    return false
  }
  if (form.value.batch_type === 'inspection') {
    return form.value.cabinet_codes.length > 0
  }
  if (form.value.batch_type === 'deployment') {
    return form.value.batch_accounts.length > 0 &&
      form.value.batch_accounts.every(acc =>
        acc.account_code.trim() !== '' && acc.cabinet_codes.length > 0
      )
  }
  return false
})

// Account management
const addAccount = () => {
  form.value.batch_accounts.push({
    account_code: '',
    cabinet_codes: [],
    search: '',
    expanded: true
  })
}

const removeAccount = (idx) => {
  form.value.batch_accounts.splice(idx, 1)
}

const toggleAccount = (idx) => {
  form.value.batch_accounts[idx].expanded = !form.value.batch_accounts[idx].expanded
}

// Reset appropriate section when batch type changes
watch(() => form.value.batch_type, (newType) => {
  if (newType === 'inspection') {
    form.value.batch_accounts = []
  } else {
    form.value.cabinet_codes = []
    cabinetSearch.value = ''
  }
})

const loadFormData = async () => {
  try {
    const [usersRes, checklistsRes, cabinetsRes] = await Promise.all([
      api.get('/users', { params: { per_page: 100 } }),
      api.get('/checklists'),
      api.get('/cabinets', { params: { per_page: 500 } })
    ])

    const usersPayload = usersRes.data?.data
    const users = Array.isArray(usersPayload) ? usersPayload : (usersPayload?.data || [])
    userOptions.value = users
      .filter(u => u.role === 'inspector')
      .map(u => ({ value: u.id, label: u.name }))

    if (userOptions.value.length === 0) {
      userOptions.value = users.map(u => ({ value: u.id, label: `${u.name} (${u.role})` }))
    }

    const checklists = checklistsRes.data?.data || []
    checklistOptions.value = checklists.map(c => ({ value: c.id, label: c.name }))

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
    batch_type: 'inspection',
    name: '',
    user_id: '',
    checklist_id: '',
    start_date: '',
    end_date: '',
    cabinet_codes: [],
    batch_accounts: []
  }
  formError.value = ''
  fieldErrors.value = {}
  cabinetSearch.value = ''
}

const handleSubmit = async () => {
  if (!isFormValid.value || submitting.value) return

  submitting.value = true
  formError.value = ''
  fieldErrors.value = {}

  try {
    const payload = {
      type: form.value.batch_type,
      name: form.value.name,
      user_id: Number(form.value.user_id),
      checklist_id: Number(form.value.checklist_id),
      start_date: form.value.start_date,
      end_date: form.value.end_date,
    }

    if (form.value.batch_type === 'inspection') {
      payload.cabinet_codes = form.value.cabinet_codes
    } else {
      payload.accounts = form.value.batch_accounts.map(acc => ({
        account_code: acc.account_code.trim(),
        cabinet_codes: acc.cabinet_codes
      }))
    }

    await batchService.createBatch(payload)

    emit('saved')
    close()
    resetForm()
  } catch (e) {
    const res = e.response?.data
    if (res?.errors) {
      fieldErrors.value = res.errors
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
