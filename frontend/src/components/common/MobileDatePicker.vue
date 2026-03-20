<template>
  <div class="relative w-full" :class="containerClass" ref="containerRef">
    <!-- Trigger Button -->
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
        isOpen && !isMobile ? 'border-primary-500 ring-2 ring-primary-500/20' : '',
        error ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : ''
      ]"
    >
      <span :class="modelValue ? 'text-gray-100 font-medium' : 'text-gray-500'" class="truncate">
        {{ formattedDate || placeholder }}
      </span>
      <Calendar class="w-5 h-5 text-gray-500 shrink-0 ml-2" />
    </button>

    <!-- Desktop Dropdown -->
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-if="isOpen && !isMobile"
          class="fixed z-[100] bg-dark-surface rounded-xl border border-gray-700/50 shadow-lg shadow-gray-200/50 p-4"
          :style="dropdownStyle"
          ref="dropdownRef"
        >
          <div class="flex items-center justify-between mb-4">
            <button type="button" @click="changeMonth(-1)" class="p-1 hover:bg-dark-elevated rounded-lg transition-colors">
              <ChevronLeft class="w-5 h-5 text-gray-500" />
            </button>
            <div class="font-semibold text-gray-100">{{ monthNames[currentMonth] }} {{ currentYear }}</div>
            <button type="button" @click="changeMonth(1)" class="p-1 hover:bg-dark-elevated rounded-lg transition-colors">
              <ChevronRight class="w-5 h-5 text-gray-500" />
            </button>
          </div>
          
          <div class="grid grid-cols-7 gap-1 mb-2">
            <div v-for="day in ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']" :key="day" class="text-center text-xs font-medium text-gray-500 py-1">
              {{ day }}
            </div>
          </div>
          
          <div class="grid grid-cols-7 gap-1">
            <button
              v-for="{ date, isCurrentMonth, isSelected, isToday, isDisabled } in calendarDays"
              :key="date.toISOString()"
              type="button"
              @click="!isDisabled && selectDate(date)"
              :disabled="isDisabled"
              class="w-8 h-8 rounded-full flex items-center justify-center text-sm transition-colors"
              :class="[
                !isCurrentMonth ? 'text-gray-300' : 'text-gray-300',
                isSelected ? 'bg-primary-500 text-white font-semibold hover:bg-primary-400' : '',
                !isSelected && isToday ? 'bg-primary-500/10 text-primary-700 font-semibold' : '',
                !isSelected && !isDisabled ? 'hover:bg-dark-elevated' : '',
                isDisabled ? 'opacity-50 cursor-not-allowed text-gray-300' : ''
              ]"
            >
              {{ date.getDate() }}
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Mobile Bottom Sheet -->
    <Teleport to="body">
      <Transition name="sheet-backdrop">
        <div v-if="isOpen && isMobile" class="fixed inset-0 z-[60] bg-black/50" @click="close"></div>
      </Transition>

      <Transition name="sheet-slide">
        <div v-if="isOpen && isMobile" class="fixed inset-x-0 bottom-0 z-[61] flex flex-col">
          <div class="bg-dark-surface rounded-t-[28px] shadow-2xl flex flex-col safe-bottom">
            <!-- Drag Handle -->
            <div class="flex justify-center pt-3 pb-1">
              <div class="w-10 h-1 rounded-full bg-gray-300"></div>
            </div>

            <!-- Title -->
            <div class="px-5 pb-3 border-b border-gray-700/30 flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-100">{{ label || 'Chọn ngày' }}</h3>
              <button @click="close" class="p-2 -mr-2 text-primary-400 font-semibold text-sm">Xong</button>
            </div>

            <!-- Calendar -->
            <div class="p-5">
              <div class="flex items-center justify-between mb-4">
                <button type="button" @click="changeMonth(-1)" class="w-10 h-10 flex items-center justify-center hover:bg-dark-elevated rounded-xl transition-colors active:bg-gray-700">
                  <ChevronLeft class="w-5 h-5 text-gray-500" />
                </button>
                <div class="font-bold text-gray-100 text-base">{{ monthNames[currentMonth] }} {{ currentYear }}</div>
                <button type="button" @click="changeMonth(1)" class="w-10 h-10 flex items-center justify-center hover:bg-dark-elevated rounded-xl transition-colors active:bg-gray-700">
                  <ChevronRight class="w-5 h-5 text-gray-500" />
                </button>
              </div>
              
              <div class="grid grid-cols-7 gap-x-1 gap-y-2 mb-2">
                <div v-for="day in ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']" :key="day" class="text-center text-[13px] font-bold text-gray-500 py-1">
                  {{ day }}
                </div>
              </div>
              
              <div class="grid grid-cols-7 gap-x-1 gap-y-2">
                <button
                  v-for="{ date, isCurrentMonth, isSelected, isToday, isDisabled } in calendarDays"
                  :key="date.toISOString()"
                  type="button"
                  @click="!isDisabled && selectDate(date)"
                  :disabled="isDisabled"
                  class="aspect-square w-full max-w-[40px] mx-auto rounded-full flex items-center justify-center text-[15px] transition-colors active:scale-95"
                  :class="[
                    !isCurrentMonth ? 'text-gray-300' : 'text-gray-100 font-medium',
                    isSelected ? 'bg-primary-500 text-white font-bold shadow-md shadow-primary-500/20' : '',
                    !isSelected && isToday ? 'bg-primary-500/10 text-primary-700 font-bold border border-primary-200' : '',
                    !isSelected && !isDisabled && !isToday ? 'active:bg-dark-elevated' : '',
                    isDisabled ? 'opacity-40 cursor-not-allowed text-gray-300' : ''
                  ]"
                >
                  {{ date.getDate() }}
                </button>
              </div>
            </div>
            
            <div class="px-5 pb-5 pt-2">
              <button 
                type="button" 
                @click="selectToday" 
                class="w-full py-3.5 rounded-xl bg-dark-bg text-gray-300 font-semibold active:bg-dark-elevated transition-colors"
              >
                Hôm nay
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ChevronRight, ChevronLeft, Calendar } from 'lucide-vue-next'

import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' }, // YYYY-MM-DD format
  minDate: { type: String, default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Chọn ngày...' },
  containerClass: { type: String, default: '' },
  triggerClass: { type: String, default: '' },
  error: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const monthNames = [
  'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
  'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
]

const isOpen = ref(false)
const isMobile = ref(false)
const containerRef = ref(null)
const triggerRef = ref(null)
const dropdownRef = ref(null)
const dropdownStyle = ref({})

// Calendar State
const currentDate = new Date()
const currentMonth = ref(currentDate.getMonth())
const currentYear = ref(currentDate.getFullYear())

// Initialize calendar to selected date if exists
watch(() => props.modelValue, (val) => {
  if (val) {
    const d = new Date(val)
    if (!isNaN(d)) {
      currentMonth.value = d.getMonth()
      currentYear.value = d.getFullYear()
    }
  }
}, { immediate: true })

const formattedDate = computed(() => {
  if (!props.modelValue) return ''
  const parts = props.modelValue.split('-')
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`
  }
  return props.modelValue
})

const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDayOfMonth = new Date(year, month, 1)
  const lastDayOfMonth = new Date(year, month + 1, 0)
  
  const days = []
  
  // Padding for previous month
  const firstDayOfWeek = firstDayOfMonth.getDay() // 0 = Sunday
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    days.push({
      date: new Date(year, month - 1, prevMonthLastDay - i),
      isCurrentMonth: false,
      isSelected: false,
      isToday: false,
      isDisabled: true
    })
  }
  
  // Current month days
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  const selectedDate = props.modelValue ? new Date(props.modelValue) : null
  if (selectedDate) selectedDate.setHours(0, 0, 0, 0)
    
  const minDateObj = props.minDate ? new Date(props.minDate) : null
  if (minDateObj) minDateObj.setHours(0, 0, 0, 0)

  for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
    const date = new Date(year, month, i)
    date.setHours(0, 0, 0, 0)
    
    let isDisabled = false
    if (minDateObj && date < minDateObj) {
      isDisabled = true
    }
    
    days.push({
      date,
      isCurrentMonth: true,
      isSelected: selectedDate && date.getTime() === selectedDate.getTime(),
      isToday: date.getTime() === today.getTime(),
      isDisabled
    })
  }
  
  // Padding for next month
  const totalDaysSoFar = days.length
  const daysNeeded = Math.ceil(totalDaysSoFar / 7) * 7 - totalDaysSoFar
  
  for (let i = 1; i <= daysNeeded; i++) {
    days.push({
      date: new Date(year, month + 1, i),
      isCurrentMonth: false,
      isSelected: false,
      isToday: false,
      isDisabled: true
    })
  }
  
  return days
})

const changeMonth = (delta) => {
  let newMonth = currentMonth.value + delta
  let newYear = currentYear.value
  
  if (newMonth > 11) {
    newMonth = 0
    newYear++
  } else if (newMonth < 0) {
    newMonth = 11
    newYear--
  }
  
  currentMonth.value = newMonth
  currentYear.value = newYear
}

const selectDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  emit('update:modelValue', `${year}-${month}-${day}`)
  close()
}

const selectToday = () => {
  const today = new Date()
  // Check if today is disabled due to minDate
  const minDateObj = props.minDate ? new Date(props.minDate) : null
  if (minDateObj) minDateObj.setHours(0, 0, 0, 0)
  
  today.setHours(0, 0, 0, 0)
  
  if (!minDateObj || today >= minDateObj) {
    selectDate(today)
  }
}

// Layout & Window Logic
const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

const updateDropdownPosition = () => {
  if (!triggerRef.value) return
  const rect = triggerRef.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const dropdownHeight = 350

  if (spaceBelow >= dropdownHeight || spaceBelow >= rect.top) {
    dropdownStyle.value = {
      top: rect.bottom + 4 + 'px',
      left: rect.left + 'px',
      width: '320px' // Fixed width for desktop calendar
    }
  } else {
    dropdownStyle.value = {
      bottom: (window.innerHeight - rect.top + 4) + 'px',
      left: rect.left + 'px',
      width: '320px'
    }
  }
}

const handleClickOutside = (e) => {
  if (!isMobile.value && isOpen.value) {
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

const toggle = () => {
  if (isOpen.value) close()
  else open()
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
</script>

<style scoped>
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
