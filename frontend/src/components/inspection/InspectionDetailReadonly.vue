<template>
  <div class="space-y-4">
    <!-- Header info -->
    <div class="bg-dark-surface rounded-2xl border border-gray-700/50 p-5 shadow-lg shadow-black/10">
      <div class="flex items-start sm:items-center justify-between gap-4">
        <div>
           <h3 class="text-lg font-bold">Điểm số: <span :class="inspection.final_result?.toUpperCase() === 'PASS' ? 'text-green-600' : 'text-red-600'">{{ inspection.total_score }}</span></h3>
           <p class="text-sm text-gray-500 font-medium mt-1">Người kiểm tra: <span class="text-gray-100">{{ inspection.user?.name || '—' }}</span></p>
           <div class="flex items-center gap-3 mt-2.5">
             <span class="text-xs font-bold px-2 py-1 bg-red-500/10 text-red-600 rounded-md border border-red-500/20 flex items-center gap-1"><AlertTriangle class="w-3.5 h-3.5" /> {{ inspection.failed_items || 0 }} lỗi</span>
             <span class="text-xs font-bold px-2 py-1 bg-dark-bg text-gray-500 rounded-md border border-gray-700/50">Tổng: {{ inspection.total_items || 0 }}</span>
           </div>
        </div>
        <span class="px-3 py-1.5 text-sm font-bold rounded-lg shrink-0 mt-1 sm:mt-0" :class="inspection.final_result?.toUpperCase() === 'PASS' ? 'bg-green-500/15 text-green-400' : 'bg-red-500/15 text-red-400'">
          {{ inspection.final_result?.toUpperCase() === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
        </span>
      </div>
    </div>
    
    <!-- Photos -->
    <div v-if="inspection.overall_photos?.length" class="bg-dark-surface rounded-2xl border border-gray-700/50 p-5 shadow-lg shadow-black/10">
      <div class="mb-3">
        <span class="font-bold text-gray-100 flex items-center gap-2">
          <Camera class="w-5 h-5 text-gray-500" /> 
          Ảnh tổng quan
        </span>
      </div>
      <div class="flex gap-2 overflow-x-auto pb-2">
        <img v-for="photo in inspection.overall_photos" :key="photo" :src="formatImageUrl(photo)" @click="openImage(photo)" class="w-24 h-24 object-cover rounded-[14px] border border-gray-700/50 shrink-0 cursor-pointer active:scale-95 transition-all" />
      </div>
    </div>
    
    <!-- Checklist items grouped by category -->
    <div class="bg-dark-surface rounded-2xl border border-gray-700/50 p-5 shadow-lg shadow-black/10">
      <div class="flex items-center justify-between mb-4">
        <span class="font-bold text-gray-100 flex items-center gap-2">
          <ListTodo class="w-5 h-5 text-gray-500" />
          Chi tiết từng hạng mục
        </span>
        <span class="text-xs font-medium px-2.5 py-1 bg-dark-elevated text-gray-500 rounded-lg">Tổng: {{ inspection.details?.length || 0 }}</span>
      </div>
      
      <div v-for="(group, category) in groupedDetails" :key="category" class="mb-4 last:mb-0">
        <!-- Category Header (Collapsible) -->
        <button @click="toggleCategory(category)" type="button" class="w-full bg-dark-bg px-4 py-3 rounded-xl mb-3 border border-gray-700/30 flex items-center justify-between hover:bg-dark-elevated active:scale-[0.99] transition-all">
          <div class="flex items-center gap-2">
            <h4 class="text-sm font-bold text-gray-300 uppercase tracking-wide">{{ category || 'Chung' }}</h4>
            <span class="text-xs font-medium text-gray-500">{{ getCategoryFailed(group) }} lỗi / {{ group.length }}</span>
          </div>
          <ChevronDown class="w-4 h-4 text-gray-500 transition-transform" :class="{ 'rotate-180': !collapsedCategories[category] }" />
        </button>

        <!-- Items -->
        <div v-show="!collapsedCategories[category]" class="space-y-3">
          <div v-for="detail in group" :key="detail.id" class="p-4 bg-dark-bg rounded-[14px] border border-gray-700/30 flex gap-4">
             <div class="flex-1 min-w-0">
               <div class="flex items-start gap-2.5">
                 <!-- Pass/Fail icon — only show the correct one -->
                 <CheckCircle2 v-if="!detail.is_failed" class="w-5 h-5 shrink-0 mt-0.5 text-green-500" />
                 <XCircle v-else class="w-5 h-5 shrink-0 mt-0.5 text-red-500" />
                 
                 <div class="min-w-0">
                    <h4 class="font-semibold text-gray-100 text-sm leading-snug">{{ detail.item?.content_vn || 'Hạng mục không xác định' }}</h4>
                    <p v-if="detail.note" class="text-xs text-amber-400 bg-amber-500/10 px-2 py-1.5 rounded-lg font-medium mt-2 inline-block">📝 Ghi chú: {{ detail.note }}</p>
                 </div>
               </div>
             </div>
             
             <div class="shrink-0 flex flex-col items-end gap-2 text-right">
               <span v-if="detail.item?.is_critical" class="px-2 py-1 rounded bg-red-500/15 text-red-400 text-[10px] font-bold">Lỗi nghiêm trọng</span>
               <div v-if="detail.image_url" class="mt-1">
                  <img :src="formatImageUrl(detail.image_url)" @click="openImage(detail.image_url)" class="w-16 h-16 object-cover rounded-xl border border-gray-700/50 cursor-pointer active:scale-95 transition-all" />
               </div>
             </div>
          </div>
        </div>
      </div>

      <div v-if="!inspection.details?.length" class="text-center py-6 text-gray-500 text-sm">
        Không có dữ liệu chi tiết
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
    const cat = detail.item?.category || 'Chung'
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
