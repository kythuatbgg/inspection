<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[100] bg-black/95 flex max-w-[100vw] max-h-[100dvh] overflow-hidden select-none" @click.self="close">
      <!-- Close button -->
      <button @click="close" class="absolute top-4 right-4 z-[110] p-2 bg-black/50 text-white rounded-full hover:bg-black/80 active:scale-95 transition-all outline-none">
        <X class="w-6 h-6" />
      </button>
      
      <!-- Controls -->
      <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-[110] flex items-center justify-between gap-1 sm:gap-3 bg-gray-900/80 px-4 py-2.5 rounded-2xl backdrop-blur-xl border border-white/10 shadow-2xl w-[90%] sm:w-auto max-w-sm">
        <button @click="zoomOut" class="p-2.5 sm:p-2 text-white hover:text-primary-400 active:scale-95 transition-transform outline-none"><ZoomIn class="w-6 h-6" /></button>
        <div class="text-white/80 text-xs font-mono font-bold w-12 text-center pointer-events-none">{{ Math.round(scale * 100) }}%</div>
        <button @click="zoomIn" class="p-2.5 sm:p-2 text-white hover:text-primary-400 active:scale-95 transition-transform outline-none"><ZoomOut class="w-6 h-6" /></button>
        <div class="w-px h-8 bg-white/20 mx-1"></div>
        <button @click="rotateLeft" class="p-2.5 sm:p-2 text-white hover:text-primary-400 active:scale-95 transition-transform outline-none"><Undo2 class="w-5 h-5" /></button>
        <button @click="rotateRight" class="p-2.5 sm:p-2 text-white hover:text-primary-400 active:scale-95 transition-transform outline-none" style="transform: scaleX(-1)"><Undo2 class="w-5 h-5" /></button>
        <button @click="reset" class="p-2.5 sm:p-2 text-white hover:text-primary-400 active:scale-95 transition-transform outline-none ml-1"><RefreshCw class="w-5 h-5" /></button>
      </div>

      <!-- Image container with panning -->
      <div 
        class="flex-1 w-full h-full flex items-center justify-center cursor-move touch-none"
        @mousedown="startPan" 
        @mousemove="pan" 
        @mouseup="endPan" 
        @mouseleave="endPan"
        @touchstart="startPanTouch" 
        @touchmove="panTouch" 
        @touchend="endPan"
        @wheel.prevent="handleWheel"
        style="will-change: transform"
      >
        <img 
          v-if="src"
          :src="src" 
          class="max-w-full max-h-full object-contain origin-center transition-transform duration-75 ease-out"
          :style="{ transform: `translate(${translateX}px, ${translateY}px) scale(${scale}) rotate(${rotation}deg)` }"
          @dragstart.prevent
          @click.stop
        />
        <div v-else class="text-white/50 text-sm">Cannot load image</div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { RefreshCw, Undo2, ZoomOut, ZoomIn, X } from 'lucide-vue-next'

import { ref, watch, onUnmounted } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  src: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:isOpen'])

const scale = ref(1)
const rotation = ref(0)
const translateX = ref(0)
const translateY = ref(0)

const isDragging = ref(false)
const startX = ref(0)
const startY = ref(0)

const close = () => {
  emit('update:isOpen', false)
  // small delay before reset to let any unmount animations play nicely if added later
  setTimeout(reset, 200)
}

const reset = () => {
  scale.value = 1
  rotation.value = 0
  translateX.value = 0
  translateY.value = 0
}

const zoomIn = () => { scale.value = Math.min(scale.value + 0.5, 6) }
const zoomOut = () => { scale.value = Math.max(scale.value - 0.5, 0.5) }
const rotateLeft = () => { rotation.value -= 90 }
const rotateRight = () => { rotation.value += 90 }

const handleWheel = (e) => {
  // Smooth zooming with wheel
  if (e.deltaY < 0) {
    scale.value = Math.min(scale.value + 0.15, 6)
  } else {
    scale.value = Math.max(scale.value - 0.15, 0.5)
  }
}

// Mouse pan
const startPan = (e) => {
  if (e.target.tagName !== 'BUTTON' && e.target.closest('button') === null) {
    isDragging.value = true
    startX.value = e.clientX - translateX.value
    startY.value = e.clientY - translateY.value
  }
}
const pan = (e) => {
  if (!isDragging.value) return
  translateX.value = e.clientX - startX.value
  translateY.value = e.clientY - startY.value
}
const endPan = () => {
  isDragging.value = false
}

let lastTouchDistance = 0
// Touch pan & pinch to zoom basics
const startPanTouch = (e) => {
  if (e.touches.length === 1) {
    isDragging.value = true
    startX.value = e.touches[0].clientX - translateX.value
    startY.value = e.touches[0].clientY - translateY.value
  } else if (e.touches.length === 2) {
    // pinch start
    isDragging.value = false
    lastTouchDistance = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    )
  }
}
const panTouch = (e) => {
  if (e.touches.length === 1 && isDragging.value) {
    translateX.value = e.touches[0].clientX - startX.value
    translateY.value = e.touches[0].clientY - startY.value
  } else if (e.touches.length === 2) {
    // pinch move
    const dist = Math.hypot(
      e.touches[0].clientX - e.touches[1].clientX,
      e.touches[0].clientY - e.touches[1].clientY
    )
    if (lastTouchDistance > 0) {
      const delta = (dist - lastTouchDistance) * 0.01
      scale.value = Math.min(Math.max(scale.value + delta, 0.5), 6)
    }
    lastTouchDistance = dist
  }
}

// Lock body scroll
watch(() => props.isOpen, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden'
    reset()
  } else {
    document.body.style.overflow = ''
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>
