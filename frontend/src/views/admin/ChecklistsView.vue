<template>
  <div class="space-y-5 pb-24 md:pb-0 min-w-0">
    <!-- Header -->
    <div class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <h2 class="text-lg font-semibold text-gray-900 truncate">Quản lý Checklist</h2>
        <p class="text-sm text-gray-500">{{ checklists.length }} checklist</p>
      </div>
      <button @click="showCreateModal = true" class="hidden md:flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 active:scale-95 transition-all shadow-sm">
        <Plus class="w-4 h-4" />
        Tạo mới
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-gray-500">Đang tải...</div>

    <template v-else>
      <!-- Desktop Table -->
      <div class="hidden md:block bg-white rounded-[16px] border border-gray-200 shadow-sm overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50/80 border-b border-gray-100">
            <tr>
              <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tên checklist</th>
              <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Hạng mục</th>
              <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Điểm đạt</th>
              <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Lỗi tối đa</th>
              <th class="px-5 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Trạng thái</th>
              <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="c in checklists" :key="c.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-4">
                <router-link :to="{ name: 'admin-checklist-detail', params: { id: c.id } }" class="font-bold text-gray-900 text-sm hover:text-primary-600 transition-colors">
                  {{ c.name }}
                </router-link>
              </td>
              <td class="px-5 py-4 text-center text-sm font-semibold text-gray-700">{{ c.items_count }}</td>
              <td class="px-5 py-4 text-center text-sm text-gray-600">{{ c.min_pass_score ?? '—' }}</td>
              <td class="px-5 py-4 text-center text-sm text-gray-600">{{ c.max_critical_allowed ?? '—' }}</td>
              <td class="px-5 py-4 text-center">
                <span v-if="c.has_active_batch" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 text-amber-700">Đang dùng</span>
                <span v-else class="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-700">Rảnh</span>
              </td>
              <td class="px-5 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <router-link :to="{ name: 'admin-checklist-detail', params: { id: c.id } }" class="px-3 py-1.5 text-xs font-bold text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors">
                    Chi tiết
                  </router-link>
                  <button v-if="c.has_active_batch" @click="cloneChecklist(c)" class="px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    Clone
                  </button>
                  <button v-if="!c.has_active_batch" @click="confirmDelete(c)" class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
                    Xóa
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="checklists.length === 0" class="text-center py-10 text-gray-500">Chưa có checklist nào</div>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-3">
        <router-link v-for="c in checklists" :key="'m-'+c.id" :to="{ name: 'admin-checklist-detail', params: { id: c.id } }" class="block bg-white rounded-[18px] p-4 shadow-sm border border-gray-100 active:scale-[0.99] transition-transform">
          <div class="flex items-start justify-between gap-3 mb-3">
            <h4 class="font-bold text-gray-900 flex-1 min-w-0 truncate">{{ c.name }}</h4>
            <span v-if="c.has_active_batch" class="px-2 py-1 text-[10px] font-bold rounded-lg bg-amber-100 text-amber-700 shrink-0">Đang dùng</span>
            <span v-else class="px-2 py-1 text-[10px] font-bold rounded-lg bg-green-100 text-green-700 shrink-0">Rảnh</span>
          </div>
          <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-gray-50/80 rounded-xl p-2.5">
              <p class="text-lg font-black text-gray-900">{{ c.items_count }}</p>
              <p class="text-[10px] text-gray-500 font-medium">Hạng mục</p>
            </div>
            <div class="bg-gray-50/80 rounded-xl p-2.5">
              <p class="text-lg font-black text-primary-600">{{ c.min_pass_score ?? '—' }}</p>
              <p class="text-[10px] text-gray-500 font-medium">Điểm đạt</p>
            </div>
            <div class="bg-gray-50/80 rounded-xl p-2.5">
              <p class="text-lg font-black text-red-600">{{ c.max_critical_allowed ?? '—' }}</p>
              <p class="text-[10px] text-gray-500 font-medium">Lỗi tối đa</p>
            </div>
          </div>
          <div v-if="c.has_active_batch" class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
            <button @click.prevent="cloneChecklist(c)" class="px-3 py-1.5 text-xs font-bold text-blue-600 bg-blue-50 rounded-lg">Clone bản mới</button>
          </div>
        </router-link>
        <div v-if="checklists.length === 0" class="text-center py-8 text-gray-500">Chưa có checklist nào</div>
      </div>
    </template>

    <!-- FAB Mobile -->
    <button @click="showCreateModal = true" class="md:hidden fixed bottom-6 right-6 w-14 h-14 bg-primary-600 text-white rounded-full shadow-lg shadow-primary-600/30 flex items-center justify-center hover:bg-primary-700 active:scale-90 transition-all z-20">
      <Plus class="w-6 h-6" />
    </button>

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showCreateModal = false">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Tạo Checklist mới</h3>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Tên checklist <span class="text-red-500">*</span></label>
            <input v-model="createForm.name" type="text" placeholder="VD: Checklist kiểm tra tủ cáp quang" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-gray-700 mb-1 block">Điểm đạt tối thiểu</label>
              <input v-model.number="createForm.min_pass_score" type="number" min="0" max="100" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm" />
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 mb-1 block">Lỗi nghiêm trọng tối đa</label>
              <input v-model.number="createForm.max_critical_allowed" type="number" min="0" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm" />
            </div>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="showCreateModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200">Hủy</button>
          <button @click="handleCreate" :disabled="creating || !createForm.name.trim()" class="flex-1 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 disabled:opacity-50 flex items-center justify-center gap-2">
            <Loader2 v-if="creating" class="w-4 h-4 animate-spin" />
            {{ creating ? 'Đang tạo...' : 'Tạo' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showDeleteConfirm = false">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl">
        <div class="flex items-start gap-4 mb-4">
          <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center shrink-0">
            <AlertTriangle class="w-6 h-6 text-red-600" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900">Xóa checklist?</h3>
            <p class="text-sm text-gray-500 mt-1">{{ deleteTarget?.name }}</p>
          </div>
        </div>
        <p class="text-sm text-gray-600 mb-4">Xóa checklist sẽ xóa luôn tất cả hạng mục bên trong. Thao tác không thể hoàn tác.</p>
        <div class="flex gap-3">
          <button @click="showDeleteConfirm = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200">Hủy</button>
          <button @click="handleDelete" :disabled="deleting" class="flex-1 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl hover:bg-red-700 flex items-center justify-center gap-2">
            <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
            {{ deleting ? 'Đang xóa...' : 'Xác nhận xóa' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Plus, Loader2, AlertTriangle } from 'lucide-vue-next'
import { ref, onMounted } from 'vue'
import checklistService from '@/services/checklistService.js'

const loading = ref(true)
const checklists = ref([])

const showCreateModal = ref(false)
const creating = ref(false)
const createForm = ref({ name: '', min_pass_score: 70, max_critical_allowed: 0 })

const showDeleteConfirm = ref(false)
const deleteTarget = ref(null)
const deleting = ref(false)

const fetchChecklists = async () => {
  loading.value = true
  try {
    const res = await checklistService.getChecklists()
    checklists.value = res.data || []
  } catch (e) {
    console.error('Failed to fetch checklists', e)
  } finally {
    loading.value = false
  }
}

const handleCreate = async () => {
  if (!createForm.value.name.trim()) return
  creating.value = true
  try {
    await checklistService.createChecklist(createForm.value)
    showCreateModal.value = false
    createForm.value = { name: '', min_pass_score: 70, max_critical_allowed: 0 }
    await fetchChecklists()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể tạo checklist')
  } finally {
    creating.value = false
  }
}

const cloneChecklist = async (c) => {
  if (!confirm(`Clone checklist "${c.name}" ra bản mới?`)) return
  try {
    const res = await checklistService.cloneChecklist(c.id)
    alert(res.message || 'Clone thành công!')
    await fetchChecklists()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể clone')
  }
}

const confirmDelete = (c) => {
  deleteTarget.value = c
  showDeleteConfirm.value = true
}

const handleDelete = async () => {
  if (!deleteTarget.value) return
  deleting.value = true
  try {
    await checklistService.deleteChecklist(deleteTarget.value.id)
    showDeleteConfirm.value = false
    await fetchChecklists()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể xóa')
  } finally {
    deleting.value = false
  }
}

onMounted(fetchChecklists)
</script>
