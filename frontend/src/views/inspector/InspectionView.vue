<template>
  <div class="space-y-4 pb-28 md:pb-6 p-4 md:p-6 md:min-h-full">
    <template v-if="!loading && plan">
      <!-- Header / Cabinet Info -->
      <div class="rounded-2xl bg-white border border-slate-200 p-5">
        <button @click="goBack" class="md:hidden flex items-center gap-1 text-sm text-primary-600 font-medium mb-3 -ml-1 active:opacity-70">
          <ChevronLeft class="w-4 h-4" />
          {{ $t('common.back') }}
        </button>
        <div class="flex items-start justify-between">
          <div>
            <h2 class="text-lg font-bold text-slate-900 leading-tight">{{ plan.cabinet_code }}</h2>
            <p v-if="plan.cabinet?.bts_site" class="text-xs text-slate-400 mt-1">{{ plan.cabinet.bts_site }}</p>
          </div>
          <span
            v-if="existingInspection"
            class="text-[10px] font-bold px-3 py-1 rounded-full"
            :class="existingInspection.final_result?.toUpperCase() === 'PASS' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
          >
            {{ existingInspection.final_result?.toUpperCase() === 'PASS' ? $t('inspection.resultPass') : $t('inspection.resultFail') }}
          </span>
        </div>
      </div>

      <!-- Already inspected -->
      <div v-if="existingInspection" class="rounded-2xl bg-emerald-50 border border-emerald-100 p-5 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
          <Check class="w-4 h-4 text-emerald-600" />
        </div>
        <div>
          <p class="text-sm font-bold text-emerald-800">{{ $t('inspection.alreadyInspected') }}</p>
          <p class="text-xs text-emerald-600 mt-0.5">{{ $t('inspection.scoreLabel', { score: existingInspection.total_score }) }} · {{ $t('inspection.criticalErrors', { count: existingInspection.critical_errors_count }) }}</p>
        </div>
      </div>

      <!-- Main Form Flow -->
      <div v-if="!existingInspection && checklistItems.length > 0">
        
        <!-- Draft status -->
        <div v-if="draftStatus" class="text-xs font-medium px-3 py-2 rounded-xl mb-4 flex items-center gap-2 transition-all"
             :class="{
               'bg-emerald-50 text-emerald-600': draftStatus === 'saved',
               'bg-primary-50 text-primary-600': draftStatus === 'restored',
               'bg-slate-50 text-slate-400': draftStatus === 'saving'
             }">
          <Save v-if="draftStatus === 'saved'" class="w-3.5 h-3.5" />
          <Loader2 v-else-if="draftStatus === 'saving'" class="w-3.5 h-3.5 animate-spin" />
          <RefreshCw v-else class="w-3.5 h-3.5" />
          <span v-if="draftStatus === 'saving'">{{ $t('inspection.savingDraft') }}</span>
          <span v-else-if="draftStatus === 'saved'">{{ $t('inspection.draftSaved', { time: draftTime }) }}</span>
          <span v-else-if="draftStatus === 'restored'">{{ $t('inspection.draftRestored') }}</span>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center gap-2 mb-5">
          <button @click="currentStep = 1" class="flex-1 pb-2.5 border-b-2 transition-colors font-bold text-sm" :class="currentStep === 1 ? 'border-primary-600 text-primary-600' : 'border-slate-200 text-slate-400'">{{ $t('inspection.step1') }}</button>
          <button @click="goToStep2" class="flex-1 pb-2.5 border-b-2 transition-colors font-bold text-sm" :class="currentStep === 2 ? 'border-primary-600 text-primary-600' : 'border-slate-200 text-slate-400'" :disabled="!isValidStep1">{{ $t('inspection.step2') }}</button>
        </div>

        <!-- STEP 1: Overall Photos -->
        <div v-show="currentStep === 1" class="space-y-4">
          <div class="bg-primary-50 text-primary-800 p-4 rounded-xl border border-primary-100">
            <div class="flex items-start gap-3">
              <Camera class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
              <p class="text-sm font-medium leading-relaxed">{{ $t('inspection.photoGuide') }}</p>
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div v-for="(photo, index) in overallPhotos" :key="index" class="relative">
              <MobileImageUploader 
                v-model="overallPhotos[index]" 
                :existingHashes="photoHashes"
                @uploading="(v) => onUploadStateChange(index, v)"
                @hash="(h) => onPhotoHash(index, h)"
              />
              <div class="absolute -top-2 -left-2 w-6 h-6 bg-slate-900 text-white flex items-center justify-center rounded-full text-xs font-bold ring-2 ring-white z-10">{{ index + 1 }}</div>
            </div>
            
            <button @click="addPhotoSlot" type="button" v-if="overallPhotos.length < 8 && overallPhotos.every(p => p)" class="aspect-square rounded-xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center text-slate-400 active:bg-slate-50 transition-colors">
              <Plus class="w-7 h-7 mb-1" />
              <span class="text-xs font-medium">{{ $t('inspection.addPhoto') }}</span>
            </button>
          </div>

          <div class="pt-4">
            <button
              @click="currentStep = 2"
              :disabled="!isValidStep1 || isAnyUploading"
              class="w-full min-h-[48px] rounded-xl font-bold text-sm transition-all active:scale-[0.98] flex items-center justify-center gap-2"
              :class="(isValidStep1 && !isAnyUploading) ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
            >
              <Loader2 v-if="isAnyUploading" class="animate-spin w-5 h-5" />
              <template v-else>
                <span>{{ $t('inspection.continueInspection') }}</span>
                <ArrowRight class="w-4 h-4" />
              </template>
            </button>
          </div>
        </div>

        <!-- STEP 2: Checklist Items -->
        <div v-show="currentStep === 2">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-900">{{ $t('inspection.checklistTitle', { count: checklistItems.length }) }}</h3>
            <div class="flex items-center gap-2">
              <span class="text-xs text-slate-400">{{ $t('inspection.answeredCount', { answered: answeredCount, total: checklistItems.length }) }}</span>
              <!-- Language Selector -->
              <select
                :value="currentLang"
                @change="currentLang = $event.target.value"
                class="h-9 pl-3 pr-8 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white cursor-pointer appearance-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2212%22%20height%3D%2212%22%20viewBox%3D%220%200%2012%2012%22%3E%3Cpath%20fill%3D%22%236b7280%22%20d%3D%22M2%204l4%204%204-4%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 8px center;"
              >
                <option v-for="opt in LANG_OPTIONS" :key="opt.value" :value="opt.value">
                  {{ opt.flag }} {{ opt.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="mb-4">
            <button
              @click="markAllPass"
              type="button"
              class="px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-xl active:scale-95 transition-all flex items-center gap-2"
            >
              <ShieldCheck class="w-4 h-4" />
              {{ $t('inspection.passAll') }}
            </button>
          </div>

          <div v-for="(group, category) in groupedItems" :key="category" class="mb-5">
            <!-- Category Header -->
            <button @click="toggleCategory(category)" type="button" class="w-full bg-slate-50 px-4 py-3 rounded-xl mb-3 border border-slate-200 flex items-center justify-between active:bg-slate-100 transition-colors">
              <div class="flex items-center gap-2">
                <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wide">{{ category || $t('inspection.general') }}</h4>
                <span class="text-[10px] font-semibold text-slate-400">{{ getCategoryAnswered(group) }}/{{ group.length }}</span>
              </div>
              <ChevronDown class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': !collapsedCategories[category] }" />
            </button>

            <!-- Items -->
            <div v-show="!collapsedCategories[category]" class="space-y-3">
              <div
                v-for="item in group"
                :key="item.id"
                class="rounded-2xl bg-white border p-4 transition-all"
                :class="{
                  'border-emerald-200 bg-emerald-50/30': itemDetails[item.id]?.is_failed === false,
                  'border-red-200 bg-red-50/30': itemDetails[item.id]?.is_failed === true,
                  'border-slate-200': itemDetails[item.id]?.is_failed == null
                }"
              >
                <div class="flex items-start gap-3">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <p class="text-sm font-semibold text-slate-900 leading-snug">{{ getContent(item) }}</p>
                      <span v-if="item.is_critical" class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-600 shrink-0 uppercase tracking-wide">{{ $t('inspection.critical') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">{{ $t('inspection.maxScoreLabel', { score: item.max_score }) }}</p>
                  </div>
                </div>

                <!-- Pass/Fail Toggle -->
                <div class="flex items-center gap-2.5 mt-3">
                  <button
                    type="button"
                    @click="setResult(item.id, false)"
                    class="flex-1 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95"
                    :class="itemDetails[item.id]?.is_failed === false
                      ? 'bg-emerald-600 text-white'
                      : 'bg-slate-100 text-slate-500 active:bg-slate-200'"
                  >
                    {{ $t('inspection.pass') }}
                  </button>
                  <button
                    type="button"
                    @click="setResult(item.id, true)"
                    class="flex-1 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95"
                    :class="itemDetails[item.id]?.is_failed === true
                      ? 'bg-red-600 text-white'
                      : 'bg-slate-100 text-slate-500 active:bg-slate-200'"
                  >
                    {{ $t('inspection.fail') }}
                  </button>
                </div>

                <!-- Missing info warning -->
                <p v-if="itemDetails[item.id]?.is_failed && (!itemDetails[item.id]?.image_url || !itemDetails[item.id]?.note?.trim())" 
                   class="text-[11px] font-medium text-red-500 mt-2 text-center">
                  {{ $t('inspection.requiredPhotoNote') }}
                </p>

                <!-- Failure detail box -->
                <div v-if="itemDetails[item.id]?.is_failed" class="mt-3 p-4 bg-red-50 rounded-xl border border-red-100 flex flex-col gap-4">
                  <div>
                    <label class="block text-[10px] font-bold text-red-700 mb-2 uppercase tracking-wide">{{ $t('inspection.evidencePhoto') }} <span class="text-red-500">*</span></label>
                    <div class="w-32">
                      <MobileImageUploader v-model="itemDetails[item.id].image_url" />
                    </div>
                  </div>
                  
                  <div>
                    <label class="block text-[10px] font-bold text-red-700 mb-2 uppercase tracking-wide">{{ $t('inspection.errorNote') }} <span class="text-red-500">*</span></label>
                    <textarea 
                      v-model="itemDetails[item.id].note" 
                      rows="3"
                      class="w-full text-sm border border-red-200 rounded-xl p-3 focus:ring-red-500 focus:border-red-500 bg-white" 
                      :placeholder="$t('inspection.errorPlaceholder')"></textarea>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="submitError" class="rounded-xl bg-red-50 border border-red-200 p-4 mt-4">
        <p class="text-sm text-red-600 font-medium">{{ submitError }}</p>
      </div>
    </template>

    <div v-else-if="!loading && !plan" class="flex flex-col items-center justify-center py-14 text-center">
      <p class="text-slate-500">{{ $t('inspection.cannotLoadTask') }}</p>
      <button @click="fetchData" class="mt-3 px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold active:scale-95 transition-all">{{ $t('inspector.retry') }}</button>
    </div>

    <!-- Bottom Scoring Summary + Submit (Fixed on mobile, sticky on desktop) -->
    <div v-if="!loading && plan && !existingInspection && checklistItems.length > 0 && currentStep === 2" class="fixed md:sticky bottom-16 md:bottom-0 left-0 right-0 max-w-md md:max-w-none w-full mx-auto md:mx-0 z-40">
      <div class="bg-white border-t border-slate-100 shadow-[0_-8px_16px_-8px_rgba(0,0,0,0.08)] md:shadow-none px-5 py-3 rounded-t-2xl md:rounded-b-2xl md:mt-4">
        <!-- Score summary -->
        <div class="flex items-center justify-between text-sm mb-3">
          <div class="flex items-center gap-3">
            <span class="text-slate-500 text-xs">{{ $t('inspection.scoreDisplay') }} <span class="text-base font-bold" :class="scoreSummary.willPass ? 'text-emerald-600' : 'text-red-600'">{{ scoreSummary.totalScore }}/{{ scoreSummary.maxScore }}</span></span>
            <span v-if="scoreSummary.criticalCount > 0" class="text-[10px] font-bold px-2 py-0.5 rounded-lg bg-red-50 text-red-600 flex items-center gap-1">
              <AlertTriangle class="w-3 h-3" />
              {{ scoreSummary.criticalCount }}
            </span>
          </div>
          <span
            class="text-[10px] font-bold px-3 py-1.5 rounded-full"
            :class="scoreSummary.willPass ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
          >
            {{ scoreSummary.willPass ? $t('inspection.resultPass') : $t('inspection.resultFail') }}
          </span>
        </div>

        <!-- Submit -->
        <button
          @click="handleSubmit"
          :disabled="!isValidToSubmit || submitting"
          class="w-full min-h-[48px] rounded-xl font-bold text-sm transition-all active:scale-[0.98] flex items-center justify-center gap-2"
          :class="isValidToSubmit ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
        >
          <Loader2 v-if="submitting" class="animate-spin w-5 h-5" />
          <span v-else>{{ isValidToSubmit ? $t('inspection.saveResult') : $t('inspection.completeCount', { answered: answeredCount, total: checklistItems.length }) }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Loader2, ShieldCheck, Plus, ChevronLeft, ChevronDown, AlertTriangle, Check, Camera, ArrowRight, Save, RefreshCw } from 'lucide-vue-next'

import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api.js'
import MobileImageUploader from '@/components/common/MobileImageUploader.vue'
import { useInspectionLang } from '@/composables/useInspectionLang.js'

const { currentLang, LANG_OPTIONS, getContent, getCategory } = useInspectionLang()
const { t } = useI18n()

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const submitting = ref(false)
const submitError = ref('')
const plan = ref(null)
const checklistItems = ref([])
const existingInspection = ref(null)

const currentStep = ref(1)

const overallPhotos = ref([null, null, null, null])

const addPhotoSlot = () => {
  overallPhotos.value.push(null)
}

const uploadingSlots = ref({})
const isAnyUploading = computed(() => Object.values(uploadingSlots.value).some(v => v))
const onUploadStateChange = (index, isUploading) => {
  uploadingSlots.value = { ...uploadingSlots.value, [index]: isUploading }
}

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
    const cat = getCategory(item) || t('inspection.general')
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
    submitError.value = e.response?.data?.message || t('inspection.submitError')
    console.error(e)
  } finally {
    submitting.value = false
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const planId = route.params.planId

    const res = await api.get(`/plans/${planId}/inspection`)

    existingInspection.value = res.data?.data || null

    const planData = res.data?.plan || res.data?.data

    if (planData) {
      plan.value = planData

      if (planData.batch?.checklist?.items) {
        checklistItems.value = planData.batch.checklist.items
      } else if (res.data?.checklist_items) {
        checklistItems.value = res.data.checklist_items
      }

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

// Draft
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
    if (!confirm(t('inspection.draftConfirm', { date: dateStr, time: timeStr }))) {
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

watch([currentStep, overallPhotos, itemDetails], debouncedSave, { deep: true })

onBeforeRouteLeave(() => {
  if (!existingInspection.value && plan.value) saveDraft()
})

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
