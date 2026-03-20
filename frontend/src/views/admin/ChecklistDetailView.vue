<template>
  <div class="space-y-6 pb-24 md:pb-0 min-w-0">
    <!-- Back + Title -->
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors shrink-0">
        <ChevronLeft class="w-5 h-5 text-slate-600" />
      </button>
      <div class="min-w-0 flex-1">
        <h2 class="text-lg font-semibold text-slate-900 truncate">{{ checklist.name || 'Chi tiết Checklist' }}</h2>
        <p class="text-sm text-slate-500">#{{ checklist.id }}</p>
      </div>
      <span v-if="checklist.has_active_batch" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 text-warning shrink-0">
        <Lock class="w-3 h-3 inline-block mr-0.5" /> Đang dùng
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-slate-500">Đang tải...</div>

    <template v-else>
      <!-- Locked Warning -->
      <div v-if="checklist.has_active_batch" class="bg-warning/10 border border-amber-200 rounded-lg p-4 flex items-start gap-3">
        <AlertTriangle class="w-5 h-5 text-warning shrink-0 mt-0.5" />
        <div>
          <p class="text-sm font-bold text-amber-800">Checklist đang được lô kiểm tra sử dụng</p>
          <p class="text-sm text-warning mt-0.5">Không thể sửa/xóa. Bạn có thể <button @click="handleClone" class="text-primary-600 font-bold underline">clone ra bản mới</button> để chỉnh sửa.</p>
        </div>
      </div>

      <!-- Checklist Info Card -->
      <div class="card p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">Thông tin checklist</h3>
          <button v-if="!checklist.has_active_batch && !editingInfo" @click="startEditInfo" class="text-sm text-primary-600 font-bold hover:text-primary-700">Sửa</button>
          <div v-if="editingInfo" class="flex gap-2">
            <button @click="editingInfo = false" class="text-sm text-slate-500 font-bold">Hủy</button>
            <button @click="saveInfo" :disabled="savingInfo" class="text-sm text-primary-600 font-bold">{{ savingInfo ? 'Đang lưu...' : 'Lưu' }}</button>
          </div>
        </div>

        <div v-if="!editingInfo" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-slate-50/80 rounded-[14px] p-4">
            <p class="text-sm text-slate-500">Tên</p>
            <p class="font-semibold text-slate-900 mt-1">{{ checklist.name }}</p>
          </div>
          <div class="bg-slate-50/80 rounded-[14px] p-4">
            <p class="text-sm text-slate-500">Điểm đạt tối thiểu</p>
            <p class="font-semibold text-slate-900 mt-1">{{ checklist.min_pass_score ?? '—' }}</p>
          </div>
          <div class="bg-slate-50/80 rounded-[14px] p-4">
            <p class="text-sm text-slate-500">Lỗi nghiêm trọng tối đa</p>
            <p class="font-semibold text-slate-900 mt-1">{{ checklist.max_critical_allowed ?? '—' }}</p>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">Tên checklist</label>
            <input v-model="infoForm.name" type="text" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">Điểm đạt tối thiểu</label>
              <input v-model.number="infoForm.min_pass_score" type="number" min="0" max="100" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">Lỗi nghiêm trọng tối đa</label>
              <input v-model.number="infoForm.max_critical_allowed" type="number" min="0" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm" />
            </div>
          </div>
        </div>
      </div>

      <!-- Items List -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">Hạng mục kiểm tra ({{ items.length }})</h3>
          <button v-if="!checklist.has_active_batch" @click="openAddItem" class="text-sm text-primary-600 font-bold hover:text-primary-700 flex items-center gap-1">
            <Plus class="w-4 h-4" /> Thêm
          </button>
        </div>

        <!-- Items grouped by category -->
        <div v-for="(group, category) in groupedItems" :key="category" class="space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ category }}</span>
            <div class="flex-1 h-px bg-slate-200"></div>
          </div>

          <div v-for="item in group" :key="item.id" class="bg-white rounded-[16px] border border-slate-200 shadow-sm p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-slate-900">{{ item.content_vn }}</p>
                <p v-if="item.content_en" class="text-xs text-slate-500 mt-0.5">🇬🇧 {{ item.content_en }}</p>
                <p v-if="item.content_kh" class="text-xs text-slate-500 mt-0.5">🇰🇭 {{ item.content_kh }}</p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="px-2 py-1 text-xs font-bold rounded-lg" :class="item.is_critical ? 'bg-red-100 text-danger' : 'bg-slate-100 text-slate-600'">
                  {{ item.is_critical ? '⚠ Lỗi nghiêm trọng' : 'Thường' }}
                </span>
                <span class="text-sm font-bold text-primary-600">{{ item.max_score }}đ</span>
              </div>
            </div>

            <div v-if="!checklist.has_active_batch" class="flex justify-end gap-2 mt-3 pt-3 border-t border-slate-200">
              <button @click="openEditItem(item)" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-lg hover:bg-slate-200 flex items-center gap-1">
                <FileEdit class="w-3.5 h-3.5" /> Sửa
              </button>
              <button @click="handleDeleteItem(item)" class="px-3 py-1.5 text-xs font-bold text-danger bg-danger/10 rounded-lg hover:bg-red-100 flex items-center gap-1">
                <Trash2 class="w-3.5 h-3.5" /> Xóa
              </button>
            </div>
          </div>
        </div>

        <div v-if="items.length === 0" class="text-center py-10 text-slate-500">
          <ClipboardList class="w-12 h-12 mx-auto text-gray-300 mb-3" />
          <p class="text-sm">Chưa có hạng mục kiểm tra nào</p>
          <button v-if="!checklist.has_active_batch" @click="openAddItem" class="mt-3 text-sm text-primary-600 font-bold">+ Thêm hạng mục đầu tiên</button>
        </div>
      </div>

      <!-- Delete Checklist Button -->
      <div v-if="!checklist.has_active_batch" class="pt-6 border-t border-slate-200">
        <button @click="handleDeleteChecklist" class="w-full md:w-auto px-6 py-2.5 border border-red-200 text-danger text-sm font-bold rounded-lg hover:bg-danger/10 flex items-center justify-center gap-2 active:scale-95 transition-all">
          <Trash2 class="w-4 h-4" /> Xóa checklist này
        </button>
      </div>
    </template>

    <!-- Item Form Modal -->
    <div v-if="showItemForm" class="fixed inset-0 bg-black/50 z-50 flex items-end md:items-center justify-center" @click.self="showItemForm = false">
      <div class="bg-white w-full md:w-auto md:max-w-lg md:rounded-lg rounded-t-[28px] p-6 max-h-[85vh] overflow-y-auto shadow-2xl">
        <div class="w-10 h-1 rounded-full bg-gray-300 mx-auto mb-4 md:hidden"></div>
        <h3 class="text-lg font-bold text-slate-900 mb-4">{{ editingItem ? 'Sửa hạng mục' : 'Thêm hạng mục' }}</h3>

        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">Danh mục <span class="text-red-500">*</span></label>
            <input v-model="itemForm.category" type="text" placeholder="VD: Vệ sinh, An toàn, Cáp quang..." class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm" />
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">Nội dung (Tiếng Việt) <span class="text-red-500">*</span></label>
            <textarea v-model="itemForm.content_vn" rows="2" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm resize-none" placeholder="Mô tả hạng mục kiểm tra..."></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">🇬🇧 Nội dung (English)</label>
            <textarea v-model="itemForm.content_en" rows="2" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm resize-none" placeholder="Optional"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">🇰🇭 Nội dung (ភាសាខ្មែរ)</label>
            <textarea v-model="itemForm.content_kh" rows="2" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm resize-none" placeholder="Optional"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">Điểm tối đa</label>
              <input v-model.number="itemForm.max_score" type="number" min="0" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm" />
            </div>
            <div>
              <label class="text-sm font-medium text-slate-700 mb-1 block">Lỗi nghiêm trọng?</label>
              <button @click="itemForm.is_critical = !itemForm.is_critical" type="button" class="w-full px-3 py-2.5 border rounded-lg text-sm font-bold flex items-center justify-center gap-2 transition-colors" :class="itemForm.is_critical ? 'border-red-300 bg-danger/10 text-danger' : 'border-slate-300 bg-white text-slate-600'">
                <AlertTriangle v-if="itemForm.is_critical" class="w-4 h-4" />
                {{ itemForm.is_critical ? 'Có — Nghiêm trọng' : 'Không' }}
              </button>
            </div>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="showItemForm = false" class="flex-1 py-2.5 bg-slate-100 text-slate-700 text-sm font-bold rounded-lg hover:bg-slate-200">Hủy</button>
          <button @click="saveItem" :disabled="savingItem || !itemForm.category.trim() || !itemForm.content_vn.trim()" class="flex-1 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center justify-center gap-2">
            <Loader2 v-if="savingItem" class="w-4 h-4 animate-spin" />
            {{ savingItem ? 'Đang lưu...' : 'Lưu' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ChevronLeft, Plus, FileEdit, Trash2, Lock, AlertTriangle, Loader2, ClipboardList } from 'lucide-vue-next'
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import checklistService from '@/services/checklistService.js'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const checklist = ref({})
const items = ref([])

// Edit checklist info
const editingInfo = ref(false)
const savingInfo = ref(false)
const infoForm = ref({ name: '', min_pass_score: 0, max_critical_allowed: 0 })

// Items CRUD
const showItemForm = ref(false)
const editingItem = ref(null)
const savingItem = ref(false)
const itemForm = ref({ category: '', content_vn: '', content_en: '', content_kh: '', max_score: 10, is_critical: false })

const groupedItems = computed(() => {
  const groups = {}
  items.value.forEach(item => {
    const cat = item.category || 'Chưa phân loại'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(item)
  })
  return groups
})

const fetchChecklist = async () => {
  loading.value = true
  try {
    const data = await checklistService.getChecklist(route.params.id)
    checklist.value = data
    items.value = data.items || []
  } catch (e) {
    console.error('Failed to fetch checklist', e)
  } finally {
    loading.value = false
  }
}

const startEditInfo = () => {
  infoForm.value = {
    name: checklist.value.name,
    min_pass_score: checklist.value.min_pass_score,
    max_critical_allowed: checklist.value.max_critical_allowed,
  }
  editingInfo.value = true
}

const saveInfo = async () => {
  savingInfo.value = true
  try {
    await checklistService.updateChecklist(checklist.value.id, infoForm.value)
    editingInfo.value = false
    await fetchChecklist()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể cập nhật')
  } finally {
    savingInfo.value = false
  }
}

const openAddItem = () => {
  editingItem.value = null
  itemForm.value = { category: '', content_vn: '', content_en: '', content_kh: '', max_score: 10, is_critical: false }
  showItemForm.value = true
}

const openEditItem = (item) => {
  editingItem.value = item
  itemForm.value = {
    category: item.category,
    content_vn: item.content_vn,
    content_en: item.content_en || '',
    content_kh: item.content_kh || '',
    max_score: item.max_score,
    is_critical: item.is_critical,
  }
  showItemForm.value = true
}

const saveItem = async () => {
  savingItem.value = true
  try {
    if (editingItem.value) {
      await checklistService.updateItem(checklist.value.id, editingItem.value.id, itemForm.value)
    } else {
      await checklistService.addItem(checklist.value.id, itemForm.value)
    }
    showItemForm.value = false
    await fetchChecklist()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể lưu hạng mục')
  } finally {
    savingItem.value = false
  }
}

const handleDeleteItem = async (item) => {
  if (!confirm(`Xóa hạng mục "${item.content_vn}"?`)) return
  try {
    await checklistService.deleteItem(checklist.value.id, item.id)
    await fetchChecklist()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể xóa hạng mục')
  }
}

const handleClone = async () => {
  if (!confirm(`Clone checklist "${checklist.value.name}" ra bản mới?`)) return
  try {
    const res = await checklistService.cloneChecklist(checklist.value.id)
    alert(res.message || 'Clone thành công!')
    router.push({ name: 'admin-checklist-detail', params: { id: res.data.id } })
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể clone')
  }
}

const handleDeleteChecklist = async () => {
  if (!confirm(`Xóa checklist "${checklist.value.name}" và tất cả hạng mục?\nThao tác không thể hoàn tác.`)) return
  try {
    await checklistService.deleteChecklist(checklist.value.id)
    router.push({ name: 'admin-checklists' })
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể xóa')
  }
}

onMounted(fetchChecklist)
</script>
