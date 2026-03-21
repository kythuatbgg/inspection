<template>
  <div class="relative w-full aspect-square bg-slate-50 border-2 border-dashed rounded-lg overflow-hidden group flex items-center justify-center transition-all shadow-sm"
       :class="borderClass">
    
    <!-- Image Preview -->
    <template v-if="modelValue">
      <img 
        :src="modelValue" 
        class="w-full h-full object-cover" 
        style="image-orientation: from-image;"
        alt="Uploaded photo" 
        @error="onImageError"
      />
      <!-- Image load error overlay -->
      <div v-if="imageLoadError" class="absolute inset-0 bg-danger/10 flex flex-col items-center justify-center">
        <AlertCircle class="w-6 h-6 text-red-400 mb-1" />
        <span class="text-[10px] text-red-500 font-medium">{{ $t('common.imageLoadError') }}</span>
        <button @click.prevent="retryPreview" class="mt-1 text-[10px] text-primary-600 font-bold underline">{{ $t('common.reload') }}</button>
      </div>
      <!-- Remove button -->
      <button v-if="!imageLoadError" @click.prevent="removeImage" type="button" 
              class="absolute top-1.5 right-1.5 p-1.5 bg-black/50 hover:bg-black/70 rounded-full text-white transition-colors z-10">
        <X class="w-3.5 h-3.5" />
      </button>
    </template>

    <!-- Upload Placeholder -->
    <template v-else-if="!isUploading && !uploadError">
      <div class="flex flex-col items-center justify-center w-full h-full p-4 gap-2.5">
        <label class="cursor-pointer flex items-center justify-center gap-2 w-full py-2.5 bg-white border border-slate-200 shadow-sm rounded-[10px] text-primary-600 hover:bg-slate-50 active:scale-95 transition-all">
          <input type="file" accept="image/*" capture="environment" class="hidden" @change="onFileChange" :disabled="disabled" />
          <Camera class="w-5 h-5" />
          <span class="text-xs font-bold">{{ $t('common.takePhoto') }}</span>
        </label>

        <label class="cursor-pointer flex items-center justify-center gap-2 w-full py-2.5 bg-slate-100 rounded-[10px] text-slate-700 hover:bg-slate-200 active:scale-95 transition-all">
          <input type="file" accept="image/png, image/jpeg, image/jpg, image/webp" class="hidden" @change="onFileChange" :disabled="disabled" />
          <Image class="w-5 h-5 text-slate-500" />
          <span class="text-xs font-bold">{{ $t('common.gallery') }}</span>
        </label>
      </div>
    </template>

    <!-- Loading State -->
    <template v-else-if="isUploading">
      <div class="flex flex-col items-center justify-center text-primary-600">
        <Loader2 class="w-6 h-6 animate-spin mb-2" />
        <span class="text-[10px] font-medium">{{ retryCount > 0 ? $t('common.retrying', { count: retryCount }) : $t('common.uploading') }}</span>
      </div>
    </template>

    <!-- Error State with Retry -->
    <template v-else-if="uploadError">
      <div class="flex flex-col items-center justify-center p-3 text-center">
        <AlertCircle class="w-6 h-6 text-red-400 mb-1" />
        <span class="text-[10px] text-red-500 font-medium mb-2 leading-tight">{{ uploadError }}</span>
        <div class="flex gap-2">
          <button @click.prevent="retryUpload" class="text-[10px] px-2.5 py-1 bg-primary-600 text-white rounded-lg font-bold">{{ $t('inspector.retry') }}</button>
          <label class="text-[10px] px-2.5 py-1 bg-slate-200 text-slate-700 rounded-lg font-bold cursor-pointer">
            {{ $t('common.chooseOther') }}
            <input type="file" accept="image/*" class="hidden" @change="onFileChange" />
          </label>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { AlertCircle, Loader2, Image, Camera, X } from 'lucide-vue-next'
import imageCompression from 'browser-image-compression'

import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()

const MAX_RETRIES = 3
const MAX_FILE_SIZE = 10 * 1024 * 1024  // 10MB

const COMPRESSION_OPTIONS = {
  maxSizeMB: 0.8,           // Target max 800KB
  maxWidthOrHeight: 1920,   // Max dimension 1920px
  useWebWorker: true,       // Không block UI thread
  fileType: 'image/jpeg',   // Giữ JPEG
  initialQuality: 0.82,     // Chất lượng cao, mắt thường không phân biệt
}

const props = defineProps({
  modelValue: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  existingHashes: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:modelValue', 'uploading', 'hash'])

const isUploading = ref(false)
const uploadError = ref('')
const retryCount = ref(0)
const imageLoadError = ref(false)
let lastFile = null

const borderClass = computed(() => {
  if (uploadError.value) return 'border-red-300 bg-danger/10'
  if (isUploading.value) return 'border-primary-300 bg-primary-50'
  if (props.modelValue) return 'border-transparent'
  return 'border-slate-200 hover:bg-slate-100'
})

// ---- Client-side image compression via browser-image-compression ----
const compressImage = async (file) => {
  try {
    const compressed = await imageCompression(file, COMPRESSION_OPTIONS)
    return compressed
  } catch (e) {
    console.warn('Compression failed, using original:', e)
    return file
  }
}

// ---- Simple file hash for duplicate detection ----
const hashFile = async (file) => {
  const buffer = await file.slice(0, 8192).arrayBuffer()
  const hashBuffer = await crypto.subtle.digest('SHA-256', buffer)
  const hashArray = Array.from(new Uint8Array(hashBuffer))
  return hashArray.map(b => b.toString(16).padStart(2, '0')).join('').slice(0, 16)
}

// ---- File change handler ----
const onFileChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  event.target.value = ''

  // Validate size
  if (file.size > MAX_FILE_SIZE) {
    uploadError.value = t('common.imageTooLarge')
    return
  }

  // Duplicate check
  try {
    const hash = await hashFile(file)
    if (props.existingHashes.includes(hash)) {
      uploadError.value = t('common.imageAlreadySelected')
      return
    }
    emit('hash', hash)
  } catch (e) {
    // hash fail is not fatal
  }

  uploadError.value = ''
  retryCount.value = 0
  lastFile = file
  await uploadFile(file)
}

// ---- Upload with auto-retry ----
const uploadFile = async (file) => {
  try {
    isUploading.value = true
    uploadError.value = ''
    emit('uploading', true)

    // Compress
    const processedFile = await compressImage(file)

    const formData = new FormData()
    formData.append('image', processedFile)

    const response = await api.post('/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      silent: true,
      timeout: 30000
    })

    if (response.data?.url) {
      imageLoadError.value = false
      emit('update:modelValue', response.data.url)
    } else {
      throw new Error(t('common.serverNoUrl'))
    }
  } catch (error) {
    console.error('Upload failed:', error)

    // Auto-retry
    if (retryCount.value < MAX_RETRIES) {
      retryCount.value++
      await new Promise(r => setTimeout(r, 1000 * retryCount.value))
      return uploadFile(file)
    }

    // Final failure
    const status = error.response?.status
    if (status === 413) {
      uploadError.value = t('common.fileTooLargeServer')
    } else if (status === 422) {
      uploadError.value = error.response?.data?.message || t('common.fileInvalid')
    } else if (status >= 500) {
      uploadError.value = t('common.serverError')
    } else if (!navigator.onLine || error.code === 'ERR_NETWORK') {
      uploadError.value = t('common.networkError')
    } else {
      uploadError.value = t('common.uploadFailed')
    }
  } finally {
    isUploading.value = false
    emit('uploading', false)
  }
}

// ---- Retry handlers ----
const retryUpload = () => {
  if (lastFile) {
    retryCount.value = 0
    uploadFile(lastFile)
  }
}

const retryPreview = () => {
  imageLoadError.value = false
}

const onImageError = () => {
  imageLoadError.value = true
}

const removeImage = () => {
  imageLoadError.value = false
  uploadError.value = ''
  emit('update:modelValue', null)
}
</script>
