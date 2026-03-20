<template>
  <div class="relative w-full" :class="containerClass" ref="containerRef">
    <!-- Trigger Button (both desktop and mobile) -->
    <button
      type="button"
      @click="toggle"
      ref="triggerRef"
      class="w-full flex items-center justify-between px-4 py-3 min-h-[52px] text-left transition-all duration-200"
      :class="[
        isMobile
          ? 'rounded-[16px] border border-transparent bg-dark-elevated/80 active:bg-dark-elevated'
          : 'rounded-xl border border-gray-600 bg-dark-surface hover:border-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20',
        triggerClass,
        isOpen && !isMobile ? 'border-primary-500 ring-2 ring-primary-500/20' : ''
      ]"
    >
      <span :class="selectedLabel ? 'text-gray-100 font-medium' : 'text-gray-500'" class="truncate">
        {{ selectedLabel || placeholder }}
      </span>
      <ChevronDown class="w-5 h-5 text-gray-500 shrink-0 ml-2 transition-transform duration-200" />
    </button>

    <!-- Desktop: Custom Dropdown (Teleported to body to avoid overflow clipping) -->
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-if="isOpen && !isMobile"
          class="fixed z-[100] bg-dark-surface rounded-xl border border-gray-700/50 shadow-lg shadow-gray-200/50 overflow-hidden"
          :style="dropdownStyle"
          ref="dropdownRef"
        >
          <div class="py-1.5 max-h-[280px] overflow-y-auto overscroll-contain">
            <button
              v-for="opt in normalizedOptions"
              :key="opt.value"
              type="button"
              @click.stop="selectOption(opt.value)"
              class="w-full flex items-center justify-between px-4 min-h-[40px] text-sm transition-colors duration-150"
              :class="String(opt.value) === String(modelValue)
                ? 'bg-primary-500/10 text-primary-700 font-semibold'
                : 'text-gray-300 hover:bg-dark-bg font-medium'"
            >
              <span>{{ opt.label }}</span>
              <Check class="w-4 h-4 text-primary-400 shrink-0 ml-2" />
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Mobile: Bottom Sheet -->
    <Teleport to="body">
      <Transition name="sheet-backdrop">
        <div v-if="isOpen && isMobile" class="fixed inset-0 z-[60] bg-black/50" @click="close"></div>
      </Transition>

      <Transition name="sheet-slide">
        <div v-if="isOpen && isMobile" class="fixed inset-x-0 bottom-0 z-[61] flex flex-col">
          <div class="bg-dark-surface rounded-t-[28px] shadow-2xl max-h-[70vh] flex flex-col overflow-hidden safe-bottom">
            <!-- Drag Handle -->
            <div class="flex justify-center pt-3 pb-1">
              <div class="w-10 h-1 rounded-full bg-gray-300"></div>
            </div>

            <!-- Title -->
            <div class="px-5 pb-3 border-b border-gray-700/30">
              <h3 class="text-lg font-bold text-gray-100">{{ label }}</h3>
            </div>

            <!-- Options -->
            <div class="overflow-y-auto overscroll-contain py-2">
              <button
                v-for="opt in normalizedOptions"
                :key="opt.value"
                type="button"
                @click="selectOption(opt.value)"
                class="w-full flex items-center justify-between px-5 min-h-[56px] transition-colors active:bg-dark-bg"
                :class="String(opt.value) === String(modelValue) ? 'bg-primary-500/10/60' : ''"
              >
                <span class="text-[15px] font-medium" :class="String(opt.value) === String(modelValue) ? 'text-primary-700' : 'text-gray-100'">
                  {{ opt.label }}
                </span>
                <Check class="w-5 h-5 text-primary-400 shrink-0" />
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { Check, ChevronDown } from 'lucide-vue-next'

import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, required: true },
  label: { type: String, default: 'Chọn một mục' },
  placeholder: { type: String, default: 'Chọn...' },
  containerClass: { type: String, default: '' },
  selectClass: { type: String, default: '' },
  triggerClass: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)
const isMobile = ref(false)
const containerRef = ref(null)
const triggerRef = ref(null)
const dropdownRef = ref(null)
const dropdownStyle = ref({})

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

const updateDropdownPosition = () => {
  if (!triggerRef.value) return
  const rect = triggerRef.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const dropdownHeight = 300

  // Position below trigger, or above if not enough space
  if (spaceBelow >= dropdownHeight || spaceBelow >= rect.top) {
    dropdownStyle.value = {
      top: rect.bottom + 4 + 'px',
      left: rect.left + 'px',
      width: rect.width + 'px',
      minWidth: '180px'
    }
  } else {
    dropdownStyle.value = {
      bottom: (window.innerHeight - rect.top + 4) + 'px',
      left: rect.left + 'px',
      width: rect.width + 'px',
      minWidth: '180px'
    }
  }
}

const handleClickOutside = (e) => {
  if (!isMobile.value && isOpen.value) {
    // Check if click is inside the trigger or the dropdown
    const inTrigger = containerRef.value && containerRef.value.contains(e.target)
    const inDropdown = dropdownRef.value && dropdownRef.value.contains(e.target)
    if (!inTrigger && !inDropdown) {
      close()
    }
  }
}

const handleEscape = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    close()
  }
}

const handleScroll = () => {
  if (isOpen.value && !isMobile.value) {
    updateDropdownPosition()
  }
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  document.addEventListener('mousedown', handleClickOutside)
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleScroll, true)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
  document.removeEventListener('mousedown', handleClickOutside)
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScroll, true)
})

const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    if (typeof opt === 'object' && opt !== null) {
      return { value: opt.value ?? opt.id ?? '', label: opt.label ?? opt.name ?? '' }
    }
    return { value: opt, label: String(opt) }
  })
})

const selectedLabel = computed(() => {
  const found = normalizedOptions.value.find((o) => String(o.value) === String(props.modelValue))
  return found?.label || ''
})

const toggle = () => {
  if (isOpen.value) {
    close()
  } else {
    open()
  }
}

const open = () => {
  isOpen.value = true
  if (isMobile.value) {
    document.body.style.overflow = 'hidden'
  } else {
    nextTick(() => {
      updateDropdownPosition()
    })
  }
}

const close = () => {
  isOpen.value = false
  if (isMobile.value) {
    document.body.style.overflow = ''
  }
}

const selectOption = (value) => {
  emit('update:modelValue', value)
  close()
}
</script>

<style scoped>
/* Desktop dropdown animation */
.dropdown-enter-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-leave-active {
  transition: opacity 0.1s ease, transform 0.1s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}

/* Mobile bottom sheet animations */
.sheet-backdrop-enter-active,
.sheet-backdrop-leave-active {
  transition: opacity 0.25s ease;
}
.sheet-backdrop-enter-from,
.sheet-backdrop-leave-to {
  opacity: 0;
}

.sheet-slide-enter-active {
  transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-slide-leave-active {
  transition: transform 0.2s cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-slide-enter-from,
.sheet-slide-leave-to {
  transform: translateY(100%);
}
</style>
