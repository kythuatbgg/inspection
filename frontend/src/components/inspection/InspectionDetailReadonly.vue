<template>
  <div class="space-y-4">
    <!-- Header info -->
    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div class="flex items-start sm:items-center justify-between gap-4">
        <div>
           <h3 class="text-lg font-bold">{{ $t('inspection.scoreDisplay') }} <span :class="inspection.final_result?.toUpperCase() === 'PASS' ? 'text-success' : 'text-danger'">{{ inspection.total_score }}</span></h3>
           <p class="text-sm text-slate-500 font-medium mt-1">{{ $t('user.inspector') }}: <span class="text-slate-900">{{ inspection.user?.name || '—' }}</span></p>
           <div class="flex items-center gap-3 mt-2.5">
             <span class="text-xs font-bold px-2 py-1 bg-danger/10 text-danger rounded-md border border-red-100 flex items-center gap-1"><AlertTriangle class="w-3.5 h-3.5" /> {{ inspection.failed_items || 0 }} {{ $t('common.errors') }}</span>
             <span class="text-xs font-bold px-2 py-1 bg-slate-50 text-slate-600 rounded-md border border-slate-200">{{ $t('batch.total') }}: {{ inspection.total_items || 0 }}</span>
           </div>
        </div>
        <span class="px-3 py-1.5 text-sm font-bold rounded-lg shrink-0 mt-1 sm:mt-0" :class="inspection.final_result?.toUpperCase() === 'PASS' ? 'bg-green-100 text-success' : 'bg-red-100 text-danger'">
          {{ inspection.final_result?.toUpperCase() === 'PASS' ? $t('common.resultPass') : $t('common.resultFail') }}
        </span>
      </div>
    </div>
    
    <!-- Photos -->
    <div v-if="inspection.overall_photos?.length" class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div class="mb-3">
        <span class="font-bold text-slate-900 flex items-center gap-2">
          <Camera class="w-5 h-5 text-slate-500" /> 
          {{ $t('inspection.overallPhotos') }}
        </span>
      </div>
      <div class="flex gap-2 overflow-x-auto pb-2">
        <img v-for="photo in inspection.overall_photos" :key="photo" :src="formatImageUrl(photo)" @click="openImage(photo)" class="w-24 h-24 object-cover rounded-[14px] border border-slate-200 shrink-0 cursor-pointer active:scale-95 transition-all" />
      </div>
    </div>
    
    <!-- Checklist items grouped by category -->
    <div class="bg-white rounded-lg border border-slate-200 p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <span class="font-bold text-slate-900 flex items-center gap-2">
          <ListTodo class="w-5 h-5 text-slate-500" />
          {{ $t('batch.itemDetails') }}
        </span>
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg">{{ $t('batch.total') }}: {{ inspection.details?.length || 0 }}</span>
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
      
      <div v-for="(group, category) in groupedDetails" :key="category" class="mb-4 last:mb-0">
        <!-- Category Header (Collapsible) -->
        <button @click="toggleCategory(category)" type="button" class="w-full bg-slate-50 px-4 py-3 rounded-lg mb-3 border border-slate-200 flex items-center justify-between hover:bg-slate-100 active:scale-[0.99] transition-all">
          <div class="flex items-center gap-2">
            <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wide">{{ category || $t('inspection.general') }}</h4>
            <span class="text-xs font-medium text-slate-400">{{ getCategoryFailed(group) }} {{ $t('common.errorsOf', { total: group.length }) }}</span>
          </div>
          <ChevronDown class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': !collapsedCategories[category] }" />
        </button>

        <!-- Items -->
        <div v-show="!collapsedCategories[category]" class="space-y-3">
          <div v-for="detail in group" :key="detail.id" class="p-4 bg-slate-50 rounded-[14px] border border-slate-200 flex gap-4">
             <div class="flex-1 min-w-0">
               <div class="flex items-start gap-2.5">
                 <!-- Pass/Fail icon — only show the correct one -->
                 <CheckCircle2 v-if="!detail.is_failed" class="w-5 h-5 shrink-0 mt-0.5 text-green-500" />
                 <XCircle v-else class="w-5 h-5 shrink-0 mt-0.5 text-red-500" />
                 
                 <div class="min-w-0">
                    <h4 class="font-semibold text-slate-900 text-sm leading-snug">{{ getContent(detail.item || {}) || $t('common.unknownItem') }}</h4>
                    <p v-if="detail.note" class="text-xs text-warning bg-warning/10 px-2 py-1.5 rounded-lg font-medium mt-2 inline-block">📝 {{ $t('common.notePrefix') }} {{ detail.note }}</p>
                 </div>
               </div>
             </div>
             
             <div class="shrink-0 flex flex-col items-end gap-2 text-right">
               <span v-if="detail.item?.is_critical" class="px-2 py-1 rounded bg-red-100 text-danger text-[10px] font-bold">{{ $t('common.criticalError') }}</span>
               <div v-if="detail.image_url" class="mt-1">
                  <img :src="formatImageUrl(detail.image_url)" @click="openImage(detail.image_url)" class="w-16 h-16 object-cover rounded-lg border border-slate-200 cursor-pointer active:scale-95 transition-all" />
               </div>
             </div>
          </div>
        </div>
      </div>

      <div v-if="!inspection.details?.length" class="text-center py-6 text-slate-500 text-sm">
        {{ $t('common.noDetailData') }}
      </div>
    </div>
    
    <!-- Image Viewer Modal -->
    <ImageViewerModal v-model:isOpen="showImageViewer" :src="currentImageSrc" />
  </div>
</template>

<script setup>
import { CheckCircle2, XCircle, ListTodo, Camera, AlertTriangle, ChevronDown } from 'lucide-vue-next'

import { ref, computed } from 'vue'
import api from '@/services/api.js'
import ImageViewerModal from '@/components/common/ImageViewerModal.vue'
import { useInspectionLang } from '@/composables/useInspectionLang.js'

const { currentLang, LANG_OPTIONS, getContent, getCategory } = useInspectionLang()

const props = defineProps({
  inspection: {
    type: Object,
    required: true
  }
})

const showImageViewer = ref(false)
const currentImageSrc = ref('')

// Group details by category
const groupedDetails = computed(() => {
  const groups = {}
  const details = props.inspection.details || []
  details.forEach(detail => {
    const cat = getCategory(detail.item || {}) || 'Chung'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(detail)
  })
  return groups
})

const collapsedCategories = ref({})
const toggleCategory = (category) => {
  collapsedCategories.value = { ...collapsedCategories.value, [category]: !collapsedCategories.value[category] }
}
const getCategoryFailed = (group) => {
  return group.filter(d => d.is_failed).length
}

const openImage = (url) => {
  if (!url) return
  currentImageSrc.value = formatImageUrl(url)
  showImageViewer.value = true
}

const formatImageUrl = (url) => {
  if (!url) return '';
  
  try {
    const urlObj = new URL(url);
    const path = urlObj.pathname + urlObj.search;
    const baseURL = api.defaults.baseURL || 'http://localhost:8000/api';
    const backendHost = baseURL.replace(/\/api\/?$/, '');
    return backendHost + path;
  } catch(e) {
    return url;
  }
};
</script>
