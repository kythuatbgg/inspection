<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex items-center gap-4">
          <button @click="goBack" class="text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold text-gray-800">Kiểm tra: {{ cabinetCode }}</h1>
            <p class="text-sm text-gray-500">{{ checklistName }}</p>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      <div v-if="loading" class="text-center py-8">
        <p class="text-gray-500">Đang tải...</p>
      </div>

      <div v-else-if="items.length === 0" class="text-center py-8">
        <p class="text-gray-500">Không có câu hỏi nào</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="(item, index) in items"
          :key="item.id"
          class="bg-white rounded-lg shadow p-4"
        >
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
              <span class="text-blue-600 font-semibold">{{ index + 1 }}</span>
            </div>
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs text-gray-500">{{ item.category }}</span>
                <span v-if="item.is_critical" class="px-2 py-0.5 bg-red-100 text-red-600 text-xs rounded">
                  Critical
                </span>
              </div>
              <p class="text-gray-800 font-medium">{{ item.content }}</p>
              <p class="text-sm text-gray-500 mt-1">Điểm tối đa: {{ item.max_score }}</p>
            </div>
          </div>

          <div class="mt-4 flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                :name="`item-${item.id}`"
                :checked="!answers[item.id]?.is_failed"
                @change="setAnswer(item.id, false, item.max_score)"
                class="w-5 h-5 text-green-600"
              />
              <span class="text-green-600 font-medium">Đạt</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                type="radio"
                :name="`item-${item.id}`"
                :checked="answers[item.id]?.is_failed"
                @change="handleFail(item.id, item)"
                class="w-5 h-5 text-red-600"
              />
              <span class="text-red-600 font-medium">Không đạt</span>
            </label>
          </div>

          <!-- Image capture for failed items -->
          <div v-if="answers[item.id]?.is_failed && item.is_critical" class="mt-4">
            <p class="text-sm text-red-600 mb-2">* Chụp ảnh minh chứng</p>
            <div v-if="!answers[item.id]?.image" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
              <input
                type="file"
                :id="`camera-${item.id}`"
                accept="image/*"
                capture="environment"
                class="hidden"
                @change="(e) => captureImage(item.id, e)"
              />
              <label :for="`camera-${item.id}`" class="cursor-pointer">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-gray-500 mt-2">Chụp ảnh</p>
              </label>
            </div>
            <div v-else class="relative inline-block">
              <img :src="answers[item.id].image" class="max-w-full h-48 rounded" />
              <button
                @click="removeImage(item.id)"
                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="sticky bottom-4">
          <button
            @click="submitInspection"
            :disabled="submitting || !canSubmit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-4 rounded-lg shadow-lg disabled:opacity-50"
          >
            {{ submitting ? 'Đang lưu...' : 'Hoàn thành kiểm tra' }}
          </button>
          <p v-if="!canSubmit" class="text-center text-sm text-red-600 mt-2">
            Vui lòng trả lời tất cả câu hỏi
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useBatchesStore } from '../stores/batches'
import { useChecklistsStore } from '../stores/checklists'
import { useInspectionsStore } from '../stores/inspections'
import { db } from '../db'
import { captureWatermark } from '../services/watermark'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const batchesStore = useBatchesStore()
const checklistsStore = useChecklistsStore()
const inspectionsStore = useInspectionsStore()

const loading = ref(true)
const submitting = ref(false)
const checklistId = ref(null)
const cabinetCode = ref('')
const checklistName = ref('')
const items = ref([])
const answers = ref({})

const userLanguage = computed(() => authStore.userLanguage)

const canSubmit = computed(() => {
  return items.value.every(item => answers.value[item.id] !== undefined)
})

onMounted(async () => {
  const planId = route.params.planId

  try {
    // Fetch plan details from batch
    const plan = await batchesStore.fetchPlan(planId)
    if (!plan) {
      alert('Không tìm thấy kế hoạch')
      router.push('/')
      return
    }

    cabinetCode.value = plan.cabinet_code

    // Get batch to find checklist_id
    const batch = batchesStore.currentBatch
    if (!batch || !batch.checklist_id) {
      alert('Không tìm thấy checklist')
      router.push('/')
      return
    }

    checklistId.value = batch.checklist_id
    checklistName.value = batch.checklist?.name || 'Checklist'

    // Fetch checklist items
    const itemsData = await checklistsStore.fetchChecklistItems(batch.checklist_id)

    // Transform items to include language content
    const lang = authStore.userLanguage
    items.value = itemsData.map(item => ({
      ...item,
      content: item[`content_${lang}`] || item.content_vn
    }))

    // Check if inspection already exists (view mode)
    if (plan.status === 'done') {
      const existingInspection = await inspectionsStore.getInspectionForPlan(planId)
      if (existingInspection) {
        // Load existing answers
        const details = await inspectionsStore.fetchInspectionDetails(existingInspection.id)
        details.forEach(detail => {
          answers.value[detail.item_id] = {
            item_id: detail.item_id,
            is_failed: detail.is_failed,
            score_awarded: detail.score_awarded,
            image: detail.image_url
          }
        })
      }
    }

  } catch (error) {
    console.error('Failed to load inspection:', error)
    alert('Không thể tải dữ liệu')
    router.push('/')
  } finally {
    loading.value = false
  }
})

const getContent = (item) => {
  const lang = userLanguage.value
  return item[`content_${lang}`] || item.content_vn
}

const setAnswer = (itemId, isFailed, score) => {
  answers.value[itemId] = {
    is_failed: isFailed,
    score_awarded: isFailed ? 0 : score,
    item_id: itemId
  }
}

const handleFail = (itemId, item) => {
  setAnswer(itemId, true, item.max_score)
}

const captureImage = async (itemId, event) => {
  const file = event.target.files[0]
  if (!file) return

  try {
    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000
      })
    })

    const watermarkImage = await captureWatermark(file, {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
      cabinetCode: cabinetCode.value,
      timestamp: new Date().toISOString()
    })

    answers.value[itemId].image = watermarkImage
  } catch (error) {
    console.error('Failed to capture image:', error)
    alert('Không thể chụp ảnh. Vui lòng kiểm tra quyền camera.')
  }
}

const removeImage = (itemId) => {
  delete answers.value[itemId].image
}

const goBack = () => {
  router.back()
}

const submitInspection = async () => {
  submitting.value = true

  try {
    const details = Object.values(answers.value).map(a => ({
      item_id: a.item_id,
      is_failed: a.is_failed,
      score_awarded: a.score_awarded,
      image_url: a.image || null
    }))

    const inspectionId = await db.inspections.add({
      plan_detail_id: route.params.planId,
      checklist_id: checklistId.value,
      cabinet_code: cabinetCode.value,
      details,
      sync_status: navigator.onLine ? 'synced' : 'draft',
      created_at: new Date().toISOString()
    })

    if (navigator.onLine) {
      try {
        await inspectionsStore.createInspection({
          plan_detail_id: route.params.planId,
          checklist_id: checklistId.value,
          cabinet_code: cabinetCode.value,
          lat: 0,
          lng: 0,
          details
        })
      } catch (e) {
        console.warn('Sync failed, saved locally')
      }
    }

    router.push('/')
  } catch (error) {
    console.error('Submit failed:', error)
    alert('Có lỗi xảy ra. Vui lòng thử lại.')
  } finally {
    submitting.value = false
  }
}
</script>
