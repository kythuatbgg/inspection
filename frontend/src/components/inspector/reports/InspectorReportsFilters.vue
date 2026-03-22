<template>
  <div class="flex flex-col md:flex-row gap-3">
    <!-- Search -->
    <div class="flex-1 relative">
      <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
      <input
        :value="searchQuery"
        type="text"
        :placeholder="$t('inspector.searchCabinet')"
        class="w-full pl-10 pr-4 min-h-[48px] md:min-h-[40px] rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
        @input="$emit('update:searchQuery', $event.target.value)"
      />
    </div>

    <!-- Result filter -->
    <MobileBottomSheet
      :model-value="filterResult"
      :options="resultOptions"
      :label="$t('inspector.filterResult')"
      :placeholder="$t('inspector.filterResult')"
      container-class="md:w-40"
      trigger-class="!min-h-[48px] md:!min-h-[40px]"
      @update:modelValue="$emit('update:filterResult', $event)"
    />

    <!-- Language -->
    <MobileBottomSheet
      :model-value="reportLang"
      :options="langOptions"
      :label="$t('inspector.reportLang')"
      :placeholder="$t('inspector.reportLang')"
      container-class="md:w-36"
      trigger-class="!min-h-[48px] md:!min-h-[40px]"
      @update:modelValue="$emit('update:reportLang', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Search } from 'lucide-vue-next'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'

const { t } = useI18n()

const props = defineProps({
  searchQuery: { type: String, default: '' },
  filterResult: { type: String, default: '' },
  reportLang: { type: String, default: 'en' },
})

defineEmits(['update:searchQuery', 'update:filterResult', 'update:reportLang'])

const langOptions = [
  { value: 'en', label: '🇬🇧 English' },
  { value: 'vn', label: '🇻🇳 Tiếng Việt' },
  { value: 'kh', label: '🇰🇭 ភាសាខ្មែរ' },
]

const resultOptions = computed(() => [
  { value: '', label: t('common.all') },
  { value: 'PASS', label: 'PASS' },
  { value: 'FAIL', label: 'FAIL' },
])
</script>
