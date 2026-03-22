<template>
  <div class="rounded-xl bg-white border border-slate-200 shadow-sm">

    <!-- Filters header -->
    <div class="p-5 border-b border-slate-100">
      <h3 class="text-sm font-bold text-slate-900 mb-3 font-heading">{{ $t('inspector.allInspections') }}</h3>
      <slot name="filters" />
    </div>

    <!-- Loading: skeleton rows -->
    <div v-if="isLoading" class="p-5 space-y-3">
      <div v-for="i in 4" :key="i" class="min-h-12 bg-slate-100 rounded-lg animate-pulse"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="flex flex-col items-center justify-center py-10 text-center">
      <AlertCircle class="w-8 h-8 text-red-400 mb-3" />
      <p class="text-sm font-semibold text-red-600 mb-1">{{ $t('common.errorLoadData') }}</p>
      <button class="text-xs text-primary-600 font-semibold hover:underline" @click="$emit('retry')">
        {{ $t('common.retry') }}
      </button>
    </div>

    <!-- Data: table + mobile + pagination -->
    <template v-else-if="items.length">
      <div class="hidden md:block overflow-x-auto">
        <InspectorInspectionsTable
          :items="items"
          :is-downloading="isDownloading"
          @download="(id, cabinet) => $emit('download', id, cabinet)"
        />
      </div>
      <div class="md:hidden">
        <InspectorInspectionsMobileList
          :items="items"
          :is-downloading="isDownloading"
          @download="(id, cabinet) => $emit('download', id, cabinet)"
        />
      </div>

      <!-- Pagination -->
      <div v-if="hasPagination && lastPage > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
        <p class="text-xs text-slate-500">
          {{ $t('common.showing') }} {{ (page - 1) * perPage + 1 }}–{{ Math.min(page * perPage, total) }} / {{ total }}
        </p>
        <div class="flex gap-1">
          <button
            class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            :disabled="page <= 1"
            @click="$emit('go-to-page', page - 1)"
          >
            <ChevronLeft class="w-3.5 h-3.5" />
          </button>
          <button
            v-for="p in visiblePages"
            :key="p"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors"
            :class="p === page ? 'bg-primary-600 text-white border-primary-600' : 'border-slate-200 hover:bg-slate-50'"
            @click="$emit('go-to-page', p)"
          >
            {{ p }}
          </button>
          <button
            class="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition-colors"
            :disabled="page >= lastPage"
            @click="$emit('go-to-page', page + 1)"
          >
            <ChevronRight class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>
    </template>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-14 text-center">
      <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
        <FileSearch class="w-7 h-7 text-slate-400" />
      </div>
      <p class="font-semibold text-slate-800">
        {{ emptyReason === 'filtered' ? $t('inspector.noFilteredResults') : $t('inspector.noInspections') }}
      </p>
      <button
        v-if="emptyReason === 'filtered'"
        class="mt-2 text-xs text-primary-600 font-semibold hover:underline"
        @click="$emit('clear-filters')"
      >
        {{ $t('inspector.clearFilters') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { FileSearch, AlertCircle, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import InspectorInspectionsTable from './InspectorInspectionsTable.vue'
import InspectorInspectionsMobileList from './InspectorInspectionsMobileList.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
  error: { type: String, default: null },
  isDownloading: { type: Function, required: true },
  page: { type: Number, default: 1 },
  perPage: { type: Number, default: 20 },
  total: { type: Number, default: 0 },
  lastPage: { type: Number, default: 1 },
  hasPagination: { type: Boolean, default: false },
  emptyReason: { type: String, default: 'empty' }, // 'empty' | 'filtered' | 'error'
})

defineEmits(['download', 'retry', 'go-to-page', 'clear-filters'])

// Build visible page numbers around current page
const visiblePages = computed(() => {
  const pages = []
  const total = props.lastPage
  const current = props.page
  const delta = 2
  for (let i = Math.max(1, current - delta); i <= Math.min(total, current + delta); i++) {
    pages.push(i)
  }
  return pages
})
</script>
