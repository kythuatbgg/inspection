<template>
  <table class="data-table">
    <thead>
      <tr>
        <th>#</th>
        <th>{{ $t('reports.cabinetCode') }}</th>
        <th>{{ $t('reports.btsSite') }}</th>
        <th>{{ $t('reports.result') }}</th>
        <th>{{ $t('reports.score') }}</th>
        <th>{{ $t('reports.inspectorName') }}</th>
        <th>{{ $t('reports.selectBatch') }}</th>
        <th>{{ $t('reports.actions') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(row, i) in items" :key="row.id">
        <td>{{ i + 1 }}</td>
        <td class="font-mono">{{ row.cabinet_code }}</td>
        <td class="font-mono text-muted">{{ row.bts_site }}</td>
        <td>
          <span class="badge" :class="row.final_result === 'PASS' ? 'badge-pass' : 'badge-fail'">
            {{ row.final_result === 'PASS' ? $t('common.resultPass') : $t('common.resultFail') }}
          </span>
        </td>
        <td><strong>{{ row.total_score }}</strong></td>
        <td>{{ row.inspector_name }}</td>
        <td class="text-muted text-sm">{{ row.batch_name }}</td>
        <td>
          <button
            class="btn btn-sm btn-outline"
            :disabled="isDownloading(`inspection:${row.id}`)"
            @click="$emit('download', row.id, row.cabinet_code)"
          >
            <Download :size="12" />
            {{ $t('reports.exportPdf') }}
          </button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
import { Download } from 'lucide-vue-next'

defineProps({
  items: { type: Array, default: () => [] },
  isDownloading: { type: Function, required: true },
})
defineEmits(['download'])
</script>
