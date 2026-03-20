<template>
  <div class="space-y-5 pb-28">
    <template v-if="!loading && plan">
      <!-- Header / Cabinet Info -->
      <div class="rounded-lg bg-white border border-slate-200 p-5 shadow-sm">
        <button @click="goBack" class="flex items-center gap-1 text-sm text-primary-600 font-medium mb-3 -ml-1 active:opacity-70">
          <ChevronLeft class="w-4 h-4" />
          Quay lại
        </button>
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-lg font-bold text-slate-900">{{ plan.cabinet_code }}</h2>
            <p v-if="plan.cabinet?.bts_site" class="text-sm text-slate-500 mt-1">{{ plan.cabinet.bts_site }}</p>
          </div>
          <span
            v-if="existingInspection"
            class="text-xs font-bold px-2.5 py-1 rounded-full text-center"
            :class="existingInspection.final_result?.toUpperCase() === 'PASS' ? 'bg-green-100 text-success' : 'bg-red-100 text-danger'"
          >
            {{ existingInspection.final_result?.toUpperCase() === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
          </span>
        </div>
      </div>

      <!-- Already inspected notice -->
      <div v-if="existingInspection" class="rounded-lg bg-success/10 border border-green-200 p-5">
        <p class="text-sm font-semibold text-green-800">✓ Tủ này đã được kiểm tra</p>
        <p class="text-sm text-success mt-1">Điểm: {{ existingInspection.total_score }} · Lỗi nghiêm trọng: {{ existingInspection.critical_errors_count }}</p>
      </div>

      <!-- Main Form Flow (if not inspected) -->
      <div v-if="!existingInspection && checklistItems.length > 0">
        
        <!-- Draft Status Indicator -->
        <div v-if="draftStatus" class="text-xs font-medium px-3 py-1.5 rounded-lg mb-4 flex items-center gap-1.5 transition-all"
             :class="{
               'bg-success/10 text-success': draftStatus === 'saved',
               'bg-primary-50 text-primary-600': draftStatus === 'restored',
               'bg-slate-50 text-slate-500': draftStatus === 'saving'
             }">
          <span v-if="draftStatus === 'saving'">⏳ Đang lưu nháp...</span>
          <span v-else-if="draftStatus === 'saved'">💾 Đã lưu nháp lúc {{ draftTime }}</span>
          <span v-else-if="draftStatus === 'restored'">🔄 Đã khôi phục bản nháp</span>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center gap-2 mb-6">
          <button @click="currentStep = 1" class="flex-1 pb-2 border-b-2 transition-colors font-bold text-sm" :class="currentStep === 1 ? 'border-primary-600 text-primary-600' : 'border-slate-200 text-slate-400'">1. Ảnh tổng thể</button>
          <button @click="goToStep2" class="flex-1 pb-2 border-b-2 transition-colors font-bold text-sm" :class="currentStep === 2 ? 'border-primary-600 text-primary-600' : 'border-slate-200 text-slate-400'" :disabled="!isValidStep1">2. Chi tiết Checklist</button>
        </div>

        <!-- STEP 1: Overall Photos -->
        <div v-show="currentStep === 1" class="space-y-4">
          <div class="bg-primary-50 text-primary-800 p-4 rounded-lg border border-blue-100 mb-4">
            <p class="text-sm font-medium">📷 Yêu cầu: Vui lòng chụp ít nhất 4 ảnh tổng thể bề ngoài của tủ trước khi tiến hành kiểm tra chi tiết.</p>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div v-for="(photo, index) in overallPhotos" :key="index" class="relative">
              <MobileImageUploader 
                v-model="overallPhotos[index]" 
                :existingHashes="photoHashes"
                @uploading="(v) => onUploadStateChange(index, v)"
                @hash="(h) => onPhotoHash(index, h)"
              />
              <div class="absolute -top-2 -left-2 w-6 h-6 bg-gray-900 text-white flex items-center justify-center rounded-full text-xs font-bold ring-2 ring-white z-10">{{ index + 1 }}</div>
            </div>
            
            <!-- Allow adding more than 4 if needed -->
            <button @click="addPhotoSlot" type="button" v-if="overallPhotos.length < 8 && overallPhotos.every(p => p)" class="aspect-square rounded-lg border-2 border-dashed border-slate-300 flex flex-col items-center justify-center text-slate-500 hover:bg-slate-50 hover:border-primary-400 hover:text-primary-500 transition-colors">
              <Plus class="w-8 h-8 mb-1" />
              <span class="text-xs font-medium">Thêm ảnh</span>
            </button>
          </div>

          <div class="pt-6">
            <button
              @click="currentStep = 2"
              :disabled="!isValidStep1 || isAnyUploading"
              class="w-full min-h-[48px] rounded-lg font-bold text-base transition-all active:scale-[0.98] flex items-center justify-center gap-2"
              :class="(isValidStep1 && !isAnyUploading) ? 'bg-primary-600 text-white hover:bg-primary-700 shadow-sm shadow-primary-600/30' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
            >
              <Loader2 v-if="isAnyUploading" class="animate-spin w-5 h-5" />
              <span v-else>Tiếp tục kiểm tra →</span>
            </button>
          </div>
        </div>

        <!-- STEP 2: Checklist Items -->
        <div v-show="currentStep === 2">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900">Danh sách kiểm tra ({{ checklistItems.length }} hạng mục)</h3>
            <span class="text-xs font-medium text-slate-500">{{ answeredCount }}/{{ checklistItems.length }} đã chấm</span>
          </div>

          <!-- Quick Actions -->
          <div class="mb-4">
            <button
              @click="markAllPass"
              type="button"
              class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg hover:bg-green-700 active:scale-95 transition-all flex items-center gap-2 shadow-sm"
            >
              <ShieldCheck class="w-4 h-4" />
              ✓ Đạt tất cả
            </button>
          </div>

          <div v-for="(group, category) in groupedItems" :key="category" class="mb-5">
            <!-- Category Header (Collapsible) -->
            <button @click="toggleCategory(category)" type="button" class="w-full bg-slate-50 px-4 py-3 rounded-lg mb-3 border border-slate-200 flex items-center justify-between hover:bg-slate-100 active:scale-[0.99] transition-all">
              <div class="flex items-center gap-2">
                <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wide">{{ category || 'Chung' }}</h4>
                <span class="text-xs font-medium text-slate-400">{{ getCategoryAnswered(group) }}/{{ group.length }}</span>
              </div>
              <ChevronDown class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': !collapsedCategories[category] }" />
            </button>

            <!-- Items -->
            <div v-show="!collapsedCategories[category]" class="space-y-4">
              <div
                v-for="item in group"
                :key="item.id"
                class="rounded-lg bg-white border border-slate-200 p-4 shadow-sm transition-all"
                :class="{'ring-2 ring-red-400': itemDetails[item.id]?.is_failed, 'ring-2 ring-green-400': itemDetails[item.id]?.is_failed === false}"
              >
                <div class="flex items-start gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <p class="text-sm font-semibold text-slate-900 leading-snug">{{ item.content }}</p>
                      <span v-if="item.is_critical" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-danger shrink-0">⚠ NGHIÊM TRỌNG</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Điểm tối đa: {{ item.max_score }}</p>
                  </div>
                </div>

                <!-- Pass/Fail Toggle -->
                <div class="flex items-center gap-3 mt-4">
                  <button
                    type="button"
                    @click="setResult(item.id, false)"
                    class="flex-1 py-2.5 rounded-lg font-bold text-sm transition-all active:scale-95"
                    :class="itemDetails[item.id]?.is_failed === false
                      ? 'bg-green-600 text-white shadow-sm shadow-green-600/20'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                  >
                    ✓ Đạt
                  </button>
                  <button
                    type="button"
                    @click="setResult(item.id, true)"
                    class="flex-1 py-2.5 rounded-lg font-bold text-sm transition-all active:scale-95"
                    :class="itemDetails[item.id]?.is_failed === true
                      ? 'bg-red-600 text-white shadow-sm shadow-red-600/20'
                      : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                  >
                    ✗ Không đạt
                  </button>
                </div>

                <!-- Missing info warning -->
                <p v-if="itemDetails[item.id]?.is_failed && (!itemDetails[item.id]?.image_url || !itemDetails[item.id]?.note?.trim())" 
                   class="text-[11px] font-medium text-red-500 mt-2 text-center animate-pulse">
                  * Yêu cầu bắt buộc phải đính kèm ảnh và ghi chú lỗi
                </p>

                <!-- Expanded Box for Failure Notes & Image -->
                <div v-if="itemDetails[item.id]?.is_failed" class="mt-4 p-4 bg-danger/10/50 rounded-lg border border-red-100 flex flex-col gap-4">
                  <div>
                    <label class="block text-xs font-bold text-red-800 mb-2 uppercase tracking-wide">1. Đính kèm minh chứng lỗi <span class="text-red-500">*</span></label>
                    <div class="w-32">
                      <MobileImageUploader v-model="itemDetails[item.id].image_url" />
                    </div>
                  </div>
                  
                  <div>
                    <label class="block text-xs font-bold text-red-800 mb-2 uppercase tracking-wide">2. Ghi chú lỗi <span class="text-red-500">*</span></label>
                    <textarea 
                      v-model="itemDetails[item.id].note" 
                      rows="3"
                      class="w-full text-sm border-slate-300 rounded-lg p-3 focus:ring-red-500 focus:border-red-500 shadow-sm" 
                      placeholder="Mô tả chi tiết vấn đề gặp phải..."></textarea>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="submitError" class="rounded-lg bg-danger/10 border border-red-200 p-4 mt-4">
        <p class="text-sm text-danger font-medium">{{ submitError }}</p>
      </div>
    </template>

    <div v-else-if="!loading && !plan" class="text-center py-10">
      <p class="text-slate-500">Không thể tải dữ liệu nhiệm vụ.</p>
      <button @click="fetchData" class="mt-3 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold">Thử lại</button>
    </div>

    <!-- Bottom Scoring Summary + Submit (Fixed) -->
    <div v-if="!loading && plan && !existingInspection && checklistItems.length > 0 && currentStep === 2" class="fixed bottom-16 left-0 right-0 max-w-md mx-auto z-40">
      <div class="bg-white border-t border-slate-200 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.1)] px-4 py-3 rounded-t-3xl">
        <!-- Score summary -->
        <div class="flex items-center justify-between text-sm mb-3 px-2">
          <div class="flex items-center gap-4">
            <span class="text-slate-600">Điểm: <span class="font-bold text-base" :class="scoreSummary.willPass ? 'text-success' : 'text-danger'">{{ scoreSummary.totalScore }}/{{ scoreSummary.maxScore }}</span></span>
            <span v-if="scoreSummary.criticalCount > 0" class="text-danger font-bold bg-danger/10 px-2 py-0.5 rounded-md">⚠ {{ scoreSummary.criticalCount }} Lỗi</span>
          </div>
          <span
            class="text-xs font-bold px-3 py-1.5 rounded-lg"
            :class="scoreSummary.willPass ? 'bg-green-100 text-success' : 'bg-red-100 text-danger'"
          >
            {{ scoreSummary.willPass ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
          </span>
        </div>

        <!-- Submit -->
        <button
          @click="handleSubmit"
          :disabled="!isValidToSubmit || submitting"
          class="w-full min-h-[50px] rounded-lg font-bold text-base transition-all active:scale-[0.98] flex items-center justify-center gap-2"
          :class="isValidToSubmit ? 'bg-primary-600 text-white hover:bg-primary-700 shadow-sm shadow-primary-600/20' : 'bg-slate-200 text-slate-500 cursor-not-allowed'"
        >
          <Loader2 v-if="submitting" class="animate-spin w-5 h-5" />
          <span v-else>{{ isValidToSubmit ? 'Lưu kết quả kiểm tra' : `Hoàn tất thông tin (${answeredCount}/${checklistItems.length})` }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Loader2, ShieldCheck, Plus, ChevronLeft, ChevronDown } from 'lucide-vue-next'

import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import api from '@/services/api.js'
import MobileImageUploader from '@/components/common/MobileImageUploader.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const submitting = ref(false)
const submitError = ref('')
const plan = ref(null)
const checklistItems = ref([])
const existingInspection = ref(null)

const currentStep = ref(1)

// Store at least 4 overall photos
const overallPhotos = ref([null, null, null, null])

const addPhotoSlot = () => {
  overallPhotos.value.push(null)
}

// Upload state tracking (#7)
const uploadingSlots = ref({})
const isAnyUploading = computed(() => Object.values(uploadingSlots.value).some(v => v))
const onUploadStateChange = (index, isUploading) => {
  uploadingSlots.value = { ...uploadingSlots.value, [index]: isUploading }
}

// File hash for duplicate detection (#3)
const photoHashes = ref([])
const onPhotoHash = (index, hash) => {
  const newHashes = [...photoHashes.value]
  newHashes[index] = hash
  photoHashes.value = newHashes.filter(Boolean)
}

const goToStep2 = () => {
  if (isValidStep1.value && !isAnyUploading.value) currentStep.value = 2
}

const isValidStep1 = computed(() => {
  return overallPhotos.value.length >= 4 && overallPhotos.value.slice(0, 4).every(url => !!url)
})

// Detailed state for each item: { itemId: { is_failed: boolean, image_url: string, note: string } }
const itemDetails = ref({})

const markAllPass = () => {
  const updated = { ...itemDetails.value }
  checklistItems.value.forEach(item => {
    updated[item.id] = { is_failed: false, image_url: null, note: '' }
  })
  itemDetails.value = updated
}

const groupedItems = computed(() => {
  const groups = {}
  checklistItems.value.forEach(item => {
    const cat = item.category || 'Chung'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(item)
  })
  return groups
})

const collapsedCategories = ref({})
const toggleCategory = (category) => {
  collapsedCategories.value = { ...collapsedCategories.value, [category]: !collapsedCategories.value[category] }
}
const getCategoryAnswered = (group) => {
  return group.filter(item => itemDetails.value[item.id] != null).length
}

const setResult = (itemId, isFailed) => {
  if (!itemDetails.value[itemId]) {
    itemDetails.value[itemId] = { is_failed: isFailed, image_url: null, note: '' }
  } else {
    itemDetails.value[itemId].is_failed = isFailed
  }
  // Force reactivity
  itemDetails.value = { ...itemDetails.value }
}

const answeredCount = computed(() => {
  return Object.keys(itemDetails.value).length
})

const allItemsAnswered = computed(() => {
  return answeredCount.value === checklistItems.value.length
})

const isValidToSubmit = computed(() => {
  if (!isValidStep1.value) return false
  if (!allItemsAnswered.value) return false

  for (const itemId in itemDetails.value) {
    const detail = itemDetails.value[itemId]
    if (detail.is_failed) {
      if (!detail.image_url || !detail.note || !detail.note.trim()) {
        return false
      }
    }
  }

  return true
})

const scoreSummary = computed(() => {
  let totalScore = 0, maxScore = 0, criticalCount = 0

  checklistItems.value.forEach(item => {
    maxScore += item.max_score
    const detail = itemDetails.value[item.id]
    if (detail && detail.is_failed === false) {
      totalScore += item.max_score
    }
    if (detail && detail.is_failed === true && item.is_critical) {
      criticalCount++
    }
  })

  const minPassScore = plan.value?.batch?.checklist?.min_pass_score ?? 70
  const maxCriticalAllowed = plan.value?.batch?.checklist?.max_critical_allowed ?? 0
  const willPass = totalScore >= minPassScore && criticalCount <= maxCriticalAllowed

  return { totalScore, maxScore, criticalCount, willPass }
})

const goBack = () => {
  if (plan.value?.batch_id) {
    router.push({ name: 'inspector-batch-detail', params: { id: plan.value.batch_id } })
  } else {
    router.back()
  }
}

const handleSubmit = async () => {
  if (!isValidToSubmit.value || submitting.value) return

  submitting.value = true
  submitError.value = ''

  try {
    const payloadDetails = checklistItems.value.map(item => {
      const detail = itemDetails.value[item.id]
      return {
        item_id: item.id,
        is_failed: detail.is_failed,
        score_awarded: detail.is_failed ? 0 : item.max_score,
        image_url: detail.is_failed ? detail.image_url : null,
        note: detail.is_failed ? detail.note : null,
      }
    })
    
    const validPhotos = overallPhotos.value.filter(url => !!url)

    await api.post('/inspections', {
      plan_detail_id: plan.value.id,
      checklist_id: plan.value.batch?.checklist_id,
      cabinet_code: plan.value.cabinet_code,
      overall_photos: validPhotos,
      lat: null,
      lng: null,
      details: payloadDetails
    })

    goBack()
    clearDraft()
  } catch (e) {
    window.scrollTo({ top: 0, behavior: 'smooth' })
    submitError.value = e.response?.data?.message || 'Không thể lưu kết quả kiểm tra. Vui lòng thử lại.'
    console.error(e)
  } finally {
    submitting.value = false
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const planId = route.params.planId

    // ✅ OPTIMIZED: Single API call với full nested data
    const res = await api.get(`/plans/${planId}/inspection`)

    // Get inspection (if exists)
    existingInspection.value = res.data?.data || null

    // Get plan với nested batch -> checklist -> items
    const planData = res.data?.plan || res.data?.data

    if (planData) {
      plan.value = planData

      // ✅ Direct access: checklist items đã có sẵn trong response
      if (planData.batch?.checklist?.items) {
        checklistItems.value = planData.batch.checklist.items
      } else if (res.data?.checklist_items) {
        checklistItems.value = res.data.checklist_items
      }

      // Restore draft after data loaded (only if not already inspected)
      if (!existingInspection.value) {
        restoreDraft()
      }
    }
  } catch (e) {
    console.error('Failed to load inspection data:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

// ─── Auto Draft (localStorage) ───
const DRAFT_KEY = computed(() => `inspection_draft_${route.params.planId}`)
const draftStatus = ref('')
const draftTime = ref('')
let draftTimer = null

const getDraftData = () => ({
  currentStep: currentStep.value,
  overallPhotos: overallPhotos.value,
  itemDetails: itemDetails.value,
  savedAt: new Date().toISOString()
})

const saveDraft = () => {
  if (existingInspection.value || !plan.value) return
  try {
    draftStatus.value = 'saving'
    localStorage.setItem(DRAFT_KEY.value, JSON.stringify(getDraftData()))
    draftTime.value = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    draftStatus.value = 'saved'
  } catch (e) {
    console.warn('Draft save failed:', e)
  }
}

const debouncedSave = () => {
  if (draftTimer) clearTimeout(draftTimer)
  draftTimer = setTimeout(saveDraft, 2000)
}

const restoreDraft = () => {
  try {
    const raw = localStorage.getItem(DRAFT_KEY.value)
    if (!raw) return

    const data = JSON.parse(raw)
    const savedDate = new Date(data.savedAt)
    const daysDiff = (Date.now() - savedDate.getTime()) / (1000 * 60 * 60 * 24)
    if (daysDiff > 7) {
      localStorage.removeItem(DRAFT_KEY.value)
      return
    }

    const timeStr = savedDate.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    const dateStr = savedDate.toLocaleDateString('vi-VN')
    if (!confirm(`Có bản nháp từ ${dateStr} lúc ${timeStr}. Khôi phục?`)) {
      localStorage.removeItem(DRAFT_KEY.value)
      return
    }

    if (data.currentStep) currentStep.value = data.currentStep
    if (data.overallPhotos) overallPhotos.value = data.overallPhotos
    if (data.itemDetails) itemDetails.value = data.itemDetails
    draftStatus.value = 'restored'
    draftTime.value = timeStr
  } catch (e) {
    console.warn('Draft restore failed:', e)
  }
}

const clearDraft = () => {
  localStorage.removeItem(DRAFT_KEY.value)
  draftStatus.value = ''
}

// Watch for changes → debounced save
watch([currentStep, overallPhotos, itemDetails], debouncedSave, { deep: true })

// Save on route leave
onBeforeRouteLeave(() => {
  if (!existingInspection.value && plan.value) saveDraft()
})

// Save on page close/refresh
const handleBeforeUnload = () => {
  if (!existingInspection.value && plan.value) {
    localStorage.setItem(DRAFT_KEY.value, JSON.stringify(getDraftData()))
  }
}
onMounted(() => window.addEventListener('beforeunload', handleBeforeUnload))
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
  if (draftTimer) clearTimeout(draftTimer)
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
