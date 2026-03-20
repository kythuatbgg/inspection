<template>
  <div class="space-y-6 pb-24 md:pb-0">
    <!-- Back + Title + Actions -->
    <div class="flex items-center gap-3">
      <button @click="$router.back()" class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors shrink-0">
        <ChevronLeft class="w-5 h-5 text-gray-600" />
      </button>
      <div class="min-w-0 flex-1">
        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ batch.name || 'Chi tiết lô' }}</h2>
        <p class="text-sm text-gray-500">Mã lô: #{{ batch.id }}</p>
      </div>
      <span v-if="batch.status" :class="getStatusClass(batch.status)" class="shrink-0">{{ getStatusLabel(batch.status) }}</span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12 text-gray-500">Đang tải...</div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12 text-red-500">{{ error }}</div>

    <template v-else>
      <!-- Action Buttons Row -->
      <div v-if="batch.status !== 'completed'" class="flex flex-wrap gap-3 md:gap-2">
        <button @click="showEditModal = true" class="flex-1 md:flex-none min-w-[120px] md:min-w-0 min-h-[48px] md:min-h-[40px] px-4 py-3 md:py-2 bg-white border border-gray-200 text-gray-700 text-sm font-bold md:font-medium rounded-[14px] md:rounded-xl hover:bg-gray-50 flex items-center justify-center gap-2 shadow-sm md:shadow-none active:scale-[0.98] transition-all">
          <FileEdit class="w-5 h-5 md:w-4 md:h-4 text-gray-500" />
          Sửa lô
        </button>
        <button @click="handleDelete" class="flex-1 md:flex-none min-w-[120px] md:min-w-0 min-h-[48px] md:min-h-[40px] px-4 py-3 md:py-2 bg-white border border-red-200 text-red-600 text-sm font-bold md:font-medium rounded-[14px] md:rounded-xl hover:bg-red-50 flex items-center justify-center gap-2 shadow-sm md:shadow-none active:scale-[0.98] transition-all">
          <Trash2 class="w-5 h-5 md:w-4 md:h-4 text-red-500" />
          Xóa lô
        </button>
      </div>

      <!-- Batch Info Card -->
      <div class="card p-5 space-y-4">
        <h3 class="font-semibold text-gray-900">Thông tin lô</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-gray-50/80 rounded-[14px] p-4">
            <p class="text-sm text-gray-500">Người kiểm tra</p>
            <p class="font-semibold text-gray-900 mt-1">{{ batch.user?.name || '—' }}</p>
          </div>
          <div class="bg-gray-50/80 rounded-[14px] p-4">
            <p class="text-sm text-gray-500">Checklist</p>
            <p class="font-semibold text-gray-900 mt-1">{{ batch.checklist?.name || '—' }}</p>
          </div>
          <div class="bg-gray-50/80 rounded-[14px] p-4">
            <p class="text-sm text-gray-500">Ngày bắt đầu</p>
            <p class="font-semibold text-gray-900 mt-1">{{ formatDate(batch.start_date) || '—' }}</p>
          </div>
          <div class="bg-gray-50/80 rounded-[14px] p-4">
            <p class="text-sm text-gray-500">Ngày kết thúc</p>
            <p class="font-semibold text-gray-900 mt-1">{{ formatDate(batch.end_date) || '—' }}</p>
          </div>
        </div>
      </div>



      <!-- Results & Review Section -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Kết quả kiểm tra ({{ results.length }})</h3>
          <div class="flex items-center gap-3">
            <button v-if="batch.status !== 'completed'" @click="showAddCabinetModal = true" class="text-xs md:text-sm border border-primary-200 bg-primary-50 text-primary-700 font-bold px-3 py-1.5 rounded-lg hover:bg-primary-100 flex items-center gap-1.5 active:scale-95 transition-all hidden md:flex">
              <Plus class="w-4 h-4" />
              Thêm tủ
            </button>
            <button v-if="batch.status !== 'completed'" @click="showAddCabinetModal = true" class="p-1.5 text-primary-600 border border-primary-200 bg-primary-50 rounded-lg hover:bg-primary-100 md:hidden active:scale-95">
              <Plus class="w-4 h-4" />
            </button>

            <button v-if="batch.status !== 'completed' && results.some(r => r.inspection && r.review_status === 'pending')" @click="approveAllPending" class="text-xs md:text-sm border border-green-200 bg-green-50 text-green-700 font-bold px-3 py-1.5 rounded-lg hover:bg-green-100 flex items-center gap-1.5 active:scale-95 transition-all">
              <Check class="w-4 h-4" />
              Duyệt tất cả
            </button>
            <button @click="fetchResults" class="text-sm text-primary-600 font-medium hover:text-primary-700">
              Làm mới
            </button>
          </div>
        </div>

        <!-- Summary cards (Responsive Grid) -->
        <div v-if="summary" class="space-y-3 md:space-y-0 md:grid md:grid-cols-5 md:gap-4">
          <!-- Primary Metric -->
          <div class="bg-primary-50 rounded-[20px] md:rounded-2xl p-5 md:p-4 text-center border border-primary-100 flex md:flex-col items-center justify-between md:justify-center md:gap-3 md:col-span-1">
            <div class="w-16 h-16 md:w-14 md:h-14 rounded-full bg-white border border-primary-100 flex items-center justify-center shrink-0 md:order-1">
               <span class="text-lg md:text-base font-black" :class="progressColor">{{ progress.percentage }}%</span>
            </div>
            <div class="text-left md:text-center md:order-2">
              <p class="text-3xl md:text-2xl font-black text-primary-700 leading-none mb-1">{{ progress.completed }}<span class="text-lg md:text-base text-primary-500 font-bold">/{{ progress.total }}</span></p>
              <p class="text-sm md:text-xs text-primary-600 font-bold">Tủ đã kiểm</p>
            </div>
          </div>

          <!-- Secondary Metrics Grid -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 md:col-span-4">
            <div class="bg-gray-50/80 rounded-[16px] md:rounded-2xl p-4 text-center border border-gray-100 md:flex md:flex-col md:justify-center">
              <p class="text-2xl md:text-3xl font-black text-green-600 mb-0.5 md:mb-1">{{ summary.passed }}</p>
              <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Đạt</p>
            </div>
            <div class="bg-gray-50/80 rounded-[16px] md:rounded-2xl p-4 text-center border border-gray-100 md:flex md:flex-col md:justify-center">
              <p class="text-2xl md:text-3xl font-black text-red-600 mb-0.5 md:mb-1">{{ summary.failed }}</p>
              <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Không đạt</p>
            </div>
            <div class="bg-gray-50/80 rounded-[16px] md:rounded-2xl p-4 text-center border border-gray-100 md:flex md:flex-col md:justify-center">
              <p class="text-2xl md:text-3xl font-black text-blue-600 mb-0.5 md:mb-1">{{ summary.reviewed }}</p>
              <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Đã duyệt</p>
            </div>
            <div class="bg-gray-50/80 rounded-[16px] md:rounded-2xl p-4 text-center border border-gray-100 md:flex md:flex-col md:justify-center">
              <p class="text-2xl md:text-3xl font-black text-amber-600 mb-0.5 md:mb-1">{{ summary.pending_review }}</p>
              <p class="text-xs text-gray-500 font-bold uppercase tracking-wide">Chờ duyệt</p>
            </div>
          </div>
        </div>

        <!-- Results Desktop Table -->
        <div class="hidden md:block bg-white rounded-[16px] overflow-hidden border border-gray-200 shadow-sm">
          <table class="w-full">
            <thead class="bg-gray-50/80 border-b border-gray-100">
              <tr>
                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tủ thiết bị</th>
                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kết quả</th>
                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Chi tiết kiểm tra</th>
                <th class="px-5 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-40">Ghi chú duyệt</th>
                <th class="px-5 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider min-w-[180px]">Duyệt kết quả</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in results" :key="'desk-'+item.plan_id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4">
                  <h4 class="font-bold text-gray-900 text-sm mb-1">{{ item.cabinet_code }}</h4>
                  <div class="flex items-center gap-2">
                    <span v-if="item.cabinet?.area" class="text-xs text-gray-500">{{ item.cabinet.area }}</span>
                    <a v-if="item.cabinet?.lat && item.cabinet?.lng" :href="`https://www.google.com/maps?q=${item.cabinet.lat},${item.cabinet.lng}`" target="_blank" class="inline-flex items-center text-blue-500 hover:text-blue-600 transition-colors" title="Xem trên bản đồ">
                      <MapPin class="w-4 h-4" />
                    </a>
                    <span v-if="!item.cabinet?.area && (!item.cabinet?.lat || !item.cabinet?.lng)" class="text-xs text-gray-400">—</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span v-if="!item.inspection" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-500">Chưa kiểm</span>
                  <span v-else-if="item.inspection.final_result === 'PASS'" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-700">ĐẠT</span>
                  <span v-else class="px-2.5 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-700">KHÔNG ĐẠT</span>
                </td>
                <td class="px-5 py-4">
                  <div v-if="item.inspection" class="flex flex-col gap-2">
                    <div class="flex items-center gap-3 text-xs">
                      <span class="text-green-600 font-bold"><span class="text-gray-500 font-medium">Đạt:</span> {{ item.inspection.passed_items }}</span>
                      <span class="text-red-600 font-bold"><span class="text-gray-500 font-medium">Rớt:</span> {{ item.inspection.failed_items }}</span>
                      <span class="text-gray-900 font-bold"><span class="text-gray-500 font-medium">Tổng:</span> {{ item.inspection.total_items }}</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs mt-1 border-t border-gray-100 pt-2 w-fit">
                      <span class="text-primary-700 font-bold bg-primary-50 px-2 py-0.5 rounded"><span class="text-primary-600/70 font-medium mr-1">Điểm gốc:</span>{{ item.inspection.total_score }}</span>
                      <span v-if="item.inspection.critical_errors_count > 0" class="text-red-700 font-bold bg-red-50 px-2 py-0.5 rounded flex items-center gap-1">
                        <AlertTriangle class="w-3 h-3" />
                         {{ item.inspection.critical_errors_count }} lỗi nghiêm trọng
                      </span>
                      <span v-else class="text-gray-500 font-medium border border-gray-200 bg-gray-50 px-2 py-0.5 rounded">0 lỗi nghiêm trọng</span>
                    </div>
                    <button @click="viewInspection(item)" class="text-primary-600 hover:text-primary-700 font-bold text-xs flex items-center gap-1 w-fit mt-1 bg-primary-50 px-2.5 py-1.5 rounded-lg transition-colors">
                      <Eye class="w-3.5 h-3.5" /> Xem chi tiết
                    </button>
                  </div>
                  <span v-else class="text-gray-400 text-sm">—</span>
                </td>
                <td class="px-5 py-4 text-sm">
                  <p v-if="item.review_note" class="text-amber-700 bg-amber-50 px-2 py-1.5 rounded-lg text-xs font-medium">{{ item.review_note }}</p>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <div v-if="batch.status !== 'completed' && item.review_status === 'pending' && item.inspection" class="flex justify-end gap-2">
                    <button @click="reviewPlan(item.plan_id, 'approved')" class="px-3 py-1.5 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 active:scale-95 transition-all">
                      Duyệt
                    </button>
                    <button @click="openRejectModal(item)" class="px-3 py-1.5 bg-white border border-red-200 text-red-600 text-xs font-bold rounded-lg hover:bg-red-50 active:scale-95 transition-all">
                      Từ chối
                    </button>
                  </div>
                  <div v-else class="flex items-center justify-end gap-2">
                    <span v-if="item.review_status === 'approved'" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-700 flex items-center gap-1"><Check class="w-3 h-3" /> Đã duyệt</span>
                    <span v-else-if="item.review_status === 'rejected'" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-orange-100 text-orange-700 flex items-center gap-1"><X class="w-3 h-3" /> Đã từ chối</span>
                    <span v-else class="text-gray-400 text-sm">—</span>

                    <button v-if="batch.status !== 'completed' && item.review_status !== 'pending' && item.inspection" @click="reviewPlan(item.plan_id, 'pending')" class="p-1.5 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-lg shrink-0 transition-colors" title="Bỏ duyệt (Hoàn tác)">
                      <Undo2 class="w-4 h-4" />
                    </button>
                    <!-- Remove Cabinet from Batch -->
                    <button v-if="batch.status !== 'completed' && !item.inspection && item.status !== 'done'" @click="removeCabinet(item)" class="p-1.5 text-red-400 hover:text-red-700 hover:bg-red-50 rounded-lg shrink-0 transition-colors" title="Xóa khỏi lô">
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="results.length === 0" class="text-center py-10 text-gray-500">
            Chưa có dữ liệu kiểm tra
          </div>
        </div>

        <!-- Results Mobile Cards -->
        <div class="md:hidden space-y-3">
          <div v-for="item in results" :key="'mob-'+item.plan_id" class="bg-white rounded-[18px] p-4 shadow-sm border border-gray-100">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <h4 class="font-bold text-gray-900">{{ item.cabinet_code }}</h4>
                <div class="flex items-center gap-2 mt-0.5">
                  <span v-if="item.cabinet?.area" class="text-sm text-gray-500">{{ item.cabinet.area }}</span>
                  <a v-if="item.cabinet?.lat && item.cabinet?.lng" :href="`https://www.google.com/maps?q=${item.cabinet.lat},${item.cabinet.lng}`" target="_blank" class="inline-flex items-center text-blue-500 hover:text-blue-600 transition-colors p-1 -m-1" title="Xem trên bản đồ">
                    <MapPin class="w-4.5 h-4.5" />
                  </a>
                  <span v-if="!item.cabinet?.area && (!item.cabinet?.lat || !item.cabinet?.lng)" class="text-sm text-gray-400">—</span>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <!-- Inspection result badge -->
                <span v-if="!item.inspection" class="px-2 py-1 text-xs font-bold rounded-lg bg-gray-100 text-gray-500">Chưa kiểm tra</span>
                <span v-else-if="item.inspection.final_result === 'PASS'" class="px-2 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-700">ĐẠT</span>
                <span v-else class="px-2 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-700">KHÔNG ĐẠT</span>
                <!-- Review badge -->
                <span v-if="item.review_status === 'approved'" class="px-2 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-700">✓ Duyệt</span>
                <span v-else-if="item.review_status === 'rejected'" class="px-2 py-1 text-xs font-bold rounded-lg bg-orange-100 text-orange-700">✗ Từ chối</span>
              </div>
            </div>

            <!-- Inspection details (if inspected) -->
            <div v-if="item.inspection" class="mt-3 pt-3 border-t border-gray-100">
              <div class="grid grid-cols-3 gap-3 text-center mb-3">
                <div class="bg-gray-50/50 rounded-xl p-2">
                  <p class="text-lg font-bold text-gray-900">{{ item.inspection.passed_items }}</p>
                  <p class="text-[10px] text-gray-500 font-medium">Đạt</p>
                </div>
                <div class="bg-gray-50/50 rounded-xl p-2">
                  <p class="text-lg font-bold text-red-600">{{ item.inspection.failed_items }}</p>
                  <p class="text-[10px] text-gray-500 font-medium">Không đạt</p>
                </div>
                <div class="bg-gray-50/50 rounded-xl p-2">
                  <p class="text-lg font-bold text-gray-600">{{ item.inspection.total_items }}</p>
                  <p class="text-[10px] text-gray-500 font-medium">Tổng</p>
                </div>
              </div>
              
              <div class="flex items-center gap-2 mb-3">
                <div class="flex-1 bg-primary-50 rounded-xl p-2 flex flex-col items-center justify-center">
                  <p class="text-xs text-primary-600/70 font-medium">Tổng điểm</p>
                  <p class="text-sm font-bold text-primary-700">{{ item.inspection.total_score }}</p>
                </div>
                <div class="flex-[1.5] rounded-xl p-2 flex flex-col items-center justify-center border" :class="item.inspection.critical_errors_count > 0 ? 'bg-red-50 border-red-100' : 'bg-gray-50/50 border-gray-100'">
                  <p class="text-xs font-medium" :class="item.inspection.critical_errors_count > 0 ? 'text-red-500/80' : 'text-gray-500'">Lỗi nghiêm trọng</p>
                  <p class="text-sm font-bold" :class="item.inspection.critical_errors_count > 0 ? 'text-red-700 flex items-center gap-1' : 'text-gray-500 flex items-center gap-1'">
                    <AlertTriangle v-if="item.inspection.critical_errors_count > 0" class="w-3.5 h-3.5 inline-block" />
                    {{ item.inspection.critical_errors_count }} lỗi
                  </p>
                </div>
              </div>

              <button @click="viewInspection(item)" class="w-full flex items-center justify-center gap-1.5 py-2 mb-3 bg-primary-50 text-primary-700 hover:bg-primary-100 rounded-xl font-bold text-sm transition-colors">
                <Eye class="w-4 h-4" />
                Xem chi tiết kiểm tra
              </button>

              <!-- Removed Photos as requested -->

              <!-- Review note if rejected -->
              <div v-if="item.review_note" class="bg-amber-50 text-amber-800 text-xs p-3 rounded-xl mb-3">
                💬 {{ item.review_note }}
              </div>

              <!-- Review actions (only if not completed & inspection exists & not yet reviewed) -->
              <div v-if="batch.status !== 'completed' && item.review_status === 'pending'" class="flex gap-3">
                <button @click="reviewPlan(item.plan_id, 'approved')" class="flex-1 min-h-[48px] bg-green-600 text-white text-sm font-bold rounded-[14px] hover:bg-green-700 active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 shadow-sm">
                  <Check class="w-5 h-5" />
                  Duyệt
                </button>
                <button @click="openRejectModal(item)" class="flex-1 min-h-[48px] bg-white border-2 border-red-100 text-red-600 text-sm font-bold rounded-[14px] hover:bg-red-50 active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 shadow-sm">
                  <X class="w-5 h-5" />
                  Từ chối
                </button>
              </div>

              <!-- Undo Review & Remove -->
              <div v-if="batch.status !== 'completed'" class="flex justify-end gap-2 mt-3 border-t border-gray-100 pt-3">
                <button v-if="item.review_status !== 'pending' && item.inspection" @click="reviewPlan(item.plan_id, 'pending')" class="px-3 py-1.5 border border-gray-200 bg-white text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50 active:scale-95 transition-all flex items-center gap-1.5 shadow-sm">
                  <Undo2 class="w-4 h-4" />
                  Hoàn tác
                </button>
                <button v-if="!item.inspection && item.status !== 'done'" @click="removeCabinet(item)" class="px-3 py-1.5 border border-red-200 bg-white text-red-600 text-sm font-semibold rounded-xl hover:bg-red-50 active:scale-95 transition-all flex items-center gap-1.5 shadow-sm">
                  <Trash2 class="w-4 h-4" />
                  Xóa
                </button>
              </div>
            </div>
          </div>
          <div v-if="results.length === 0" class="text-center py-8 text-gray-500">
            Chưa có dữ liệu kiểm tra
          </div>
        </div>

        <!-- Close Batch Button -->
        <div v-if="batch.status !== 'completed' && canClose" class="pt-6 md:pt-8 md:flex md:flex-col md:items-end">
          <button @click="handleClose" :disabled="closing" class="w-full md:w-auto md:px-10 min-h-[56px] md:min-h-[48px] bg-primary-600 text-white text-base md:text-sm font-bold rounded-2xl md:rounded-xl hover:bg-primary-700 active:scale-[0.98] transition-all shadow-lg shadow-primary-600/20 flex items-center justify-center gap-2">
            <Loader2 v-if="closing" class="w-5 h-5 md:w-4 md:h-4 animate-spin" />
            <Lock v-else class="w-5 h-5 md:w-4 md:h-4" />
            {{ closing ? 'Đang kết thúc...' : 'Kết thúc lô' }}
          </button>
          <p class="text-center md:text-right text-xs text-gray-500 mt-3 md:mt-2 flex items-center justify-center md:justify-end gap-1.5">
            <Info class="w-4 h-4 md:w-3 md:h-3 text-primary-500" />
            Sau khi kết thúc sẽ không thể thay đổi dữ liệu
          </p>
        </div>

        <!-- Closed summary -->
        <div v-if="batch.status === 'completed' && batch.closed_at" class="bg-green-50/80 border border-green-100 rounded-2xl p-5 md:flex md:items-center md:justify-between mt-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
              <ShieldCheck class="w-5 h-5 text-green-600" />
            </div>
            <div>
              <p class="text-base font-bold text-green-900 mb-0.5">Lô đã kết thúc thành công</p>
              <p class="text-sm text-green-700">Đã chốt ngày {{ formatDate(batch.closed_at) }}</p>
            </div>
          </div>
          <!-- Rollback (Reopen) Button -->
          <div class="mt-4 md:mt-0 flex shrink-0 border-t border-green-200/60 pt-4 md:border-t-0 md:pt-0">
            <button @click="handleReopen" :disabled="reopening" class="w-full md:w-auto px-6 py-2.5 md:py-2 border border-green-200 bg-white text-green-700 md:text-gray-700 md:border-gray-200 text-sm font-bold rounded-xl hover:bg-green-50 md:hover:bg-gray-50 active:scale-95 transition-all flex items-center justify-center gap-2 shadow-sm">
              <Loader2 v-if="reopening" class="w-4 h-4 animate-spin outline-none" />
              <Undo2 v-else class="w-4 h-4" />
              {{ reopening ? 'Đang thao tác...' : 'Mở lại lô (Hoàn tác)' }}
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showEditModal = false">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Sửa lô kiểm tra</h3>

        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Tên lô</label>
            <input v-model="editForm.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Ngày bắt đầu</label>
            <input v-model="editForm.start_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Ngày kết thúc</label>
            <input v-model="editForm.end_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm" />
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="showEditModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl">Hủy</button>
          <button @click="handleEdit" :disabled="saving" class="flex-1 py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700">
            {{ saving ? 'Đang lưu...' : 'Lưu' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="showRejectModal = false">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Từ chối duyệt</h3>
        <p class="text-sm text-gray-500 mb-4">Tủ: {{ rejectTarget?.cabinet_code }}</p>

        <textarea v-model="rejectNote" rows="3" placeholder="Lý do từ chối (tùy chọn)..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm resize-none"></textarea>

        <div class="flex gap-3 mt-4">
          <button @click="showRejectModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200">Hủy</button>
          <button @click="confirmReject" class="flex-1 py-2.5 bg-red-600 text-white text-sm font-bold rounded-xl hover:bg-red-700 active:scale-95 transition-all shadow-sm">Xác nhận từ chối</button>
        </div>
      </div>
    </div>

    <!-- Add Cabinet Modal (Search & Select) -->
    <div v-if="showAddCabinetModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeAddCabinetModal">
      <div class="bg-white rounded-2xl p-6 w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Thêm tủ vào lô kiểm tra</h3>
        <p class="text-sm text-gray-500 mb-4">Tìm kiếm và chọn các tủ muốn thêm. (Lưu ý: Tủ đã có trong lô sẽ bị ẩn)</p>
        
        <!-- Search Input -->
        <div class="relative mb-4 shrink-0">
          <input v-model="cabinetSearchQuery" @input="debouncedSearchCabinets" type="text" placeholder="Nhập mã tủ để tìm kiếm..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:bg-white transition-all"/>
          <Search class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
        </div>

        <!-- Selected Pills -->
        <div v-if="selectedCabinetCodes.length > 0" class="flex flex-wrap gap-2 mb-4 shrink-0 max-h-24 overflow-y-auto p-2.5 bg-gray-50 rounded-xl border border-gray-200 shadow-inner">
          <span v-for="code in selectedCabinetCodes" :key="code" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold border border-blue-200">
            {{ code }}
            <button @click="toggleSelectCabinet(code)" class="text-blue-500 hover:text-blue-900 hover:bg-blue-200 rounded p-0.5"><X class="w-3.5 h-3.5" /></button>
          </span>
        </div>

        <!-- Search Results List -->
        <div class="flex-1 overflow-y-auto mb-5 border border-gray-200 rounded-xl divide-y divide-gray-100 bg-white">
          <div v-if="searchingCabinets" class="p-6 flex flex-col items-center justify-center text-gray-500 gap-3">
            <Loader2 class="w-6 h-6 animate-spin text-primary-500" />
            <span class="text-sm font-medium">Đang tìm kiếm...</span>
          </div>
          <div v-else-if="availableCabinets.length === 0" class="p-6 text-center text-sm text-gray-500">
            {{ cabinetSearchQuery ? 'Không tìm thấy tủ phù hợp hoặc tủ đã có trong lô này.' : 'Gõ mã tủ vào ô tìm kiếm ở trên kết quả sẽ hiện ở đây.' }}
          </div>
          
          <label v-else v-for="cab in availableCabinets" :key="cab.cabinet_code" class="flex items-center p-3 hover:bg-blue-50/50 cursor-pointer transition-colors" :class="{'bg-blue-50/80': selectedCabinetCodes.includes(cab.cabinet_code)}">
            <input type="checkbox" :value="cab.cabinet_code" v-model="selectedCabinetCodes" class="w-4.5 h-4.5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 mt-0.5 shadow-sm cursor-pointer">
            <div class="ml-3 flex-1">
              <p class="text-sm font-bold text-gray-900">{{ cab.cabinet_code }}</p>
              <p v-if="cab.bts_site" class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                <Building2 class="w-3 h-3" />
                Trạm: {{ cab.bts_site }}
              </p>
            </div>
          </label>
        </div>

        <div class="flex gap-3 mt-auto shrink-0 border-t border-gray-100 pt-5">
          <button @click="closeAddCabinetModal" class="flex-[1] py-2.5 bg-gray-100 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-200 transition-colors shadow-sm">Hủy</button>
          <button @click="submitAddCabinets" :disabled="addingCabinets || selectedCabinetCodes.length === 0" class="flex-[2] py-2.5 bg-primary-600 text-white text-sm font-bold rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:bg-gray-300 disabled:text-gray-500 cursor-pointer disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 shadow-sm">
            <Loader2 v-if="addingCabinets" class="w-4 h-4 animate-spin outline-none" />
            <span v-else>Xác nhận Thêm ({{ selectedCabinetCodes.length }}) tủ</span>
          </button>
        </div>
      </div>
    </div>
    <!-- View Inspection Detail Modal -->
    <div v-if="showInspectionDetail" class="fixed inset-0 bg-white md:bg-black/50 z-50 flex flex-col md:items-center md:justify-center p-0 md:p-6" @click.self="showInspectionDetail = false">
      <div class="bg-gray-50 flex flex-col w-full h-full md:rounded-2xl md:max-w-4xl md:h-auto md:max-h-[90vh] overflow-hidden shadow-none md:shadow-2xl">
        <div class="flex items-center justify-between p-4 bg-white border-b border-gray-200 shrink-0 sticky top-0 z-10">
          <h3 class="text-lg font-bold text-gray-900 truncate pr-2">Chi tiết tủ {{ currentCabinetCode }}</h3>
          <div class="flex items-center gap-2 md:gap-3 shrink-0">
            <span v-if="currentInspectionData?.final_result" class="px-2 md:px-2.5 py-1 text-xs font-bold rounded-lg" :class="currentInspectionData.final_result.toUpperCase() === 'PASS' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
              {{ currentInspectionData.final_result.toUpperCase() === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
            </span>
            <button @click="showInspectionDetail = false" class="text-gray-400 hover:text-gray-700 bg-gray-100 p-1.5 rounded-xl transition-colors active:scale-95">
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 md:p-6 overscroll-contain">
          <div v-if="loadingInspection" class="flex flex-col items-center justify-center h-40 text-gray-500 gap-3">
            <Loader2 class="w-8 h-8 animate-spin text-primary-500" />
            <span class="text-sm font-medium">Đang tải chi tiết...</span>
          </div>
          
          <InspectionDetailReadonly v-else-if="currentInspectionData" :inspection="currentInspectionData" />
          
          <div v-else class="text-center py-10 text-gray-500 text-sm">
            Không tải được dữ liệu chi tiết
          </div>
        </div>
        
        <div v-if="batch.status !== 'completed' && currentPlanData?.review_status === 'pending'" class="p-4 bg-white border-t border-gray-100 shrink-0 flex gap-3 pb-safe">
          <button @click="showRejectModal = true; rejectTarget = currentPlanData; showInspectionDetail = false" class="flex-1 min-h-[52px] bg-white border border-red-200 text-red-600 font-bold rounded-xl hover:bg-red-50 active:bg-red-100 flex items-center justify-center gap-2 transition-all text-sm shadow-sm md:shadow-none md:flex-none md:px-6">
            <X class="w-5 h-5" />
            Từ chối
          </button>
          <button @click="reviewPlan(currentPlanData.plan_id, 'approved'); showInspectionDetail = false" class="flex-1 min-h-[52px] bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 active:bg-green-800 flex items-center justify-center gap-2 shadow-md transition-all text-sm md:flex-none md:px-8">
            <Check class="w-5 h-5" />
            Duyệt kết quả
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { AlertTriangle, Check, X, Loader2, Building2, Search, Undo2, ShieldCheck, Info, Lock, Trash2, Eye, MapPin, Plus, FileEdit, ChevronLeft } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api.js'
import batchService from '@/services/batchService.js'
import cabinetService from '@/services/cabinetService.js'
import InspectionDetailReadonly from '@/components/inspection/InspectionDetailReadonly.vue'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const error = ref(null)
const batch = ref({})
const progress = ref({ total: 0, completed: 0, percentage: 0 })
const results = ref([])
const summary = ref(null)

// Modals
const showEditModal = ref(false)
const showRejectModal = ref(false)
const rejectTarget = ref(null)
const rejectNote = ref('')
const saving = ref(false)
const closing = ref(false)
const reopening = ref(false)

const editForm = ref({ name: '', start_date: '', end_date: '' })

// View Inspection State
const showInspectionDetail = ref(false)
const currentInspectionData = ref(null)
const loadingInspection = ref(false)
const currentCabinetCode = ref('')
const currentPlanData = ref(null)

const viewInspection = async (item) => {
  currentCabinetCode.value = item.cabinet_code
  currentPlanData.value = item
  showInspectionDetail.value = true
  loadingInspection.value = true
  try {
    const res = await api.get(`/plans/${item.plan_id}/inspection`)
    currentInspectionData.value = res.data?.data || null
  } catch (e) {
    console.error('Failed to load inspection details', e)
    currentInspectionData.value = null
  } finally {
    loadingInspection.value = false
  }
}

// Add Cabinet Modal State
const showAddCabinetModal = ref(false)
const cabinetSearchQuery = ref('')
const searchingCabinets = ref(false)
const addingCabinets = ref(false)
const availableCabinets = ref([])
const selectedCabinetCodes = ref([])
let searchTimeout = null

const closeAddCabinetModal = () => {
  showAddCabinetModal.value = false
  cabinetSearchQuery.value = ''
  selectedCabinetCodes.value = []
  availableCabinets.value = []
}

const toggleSelectCabinet = (code) => {
  selectedCabinetCodes.value = selectedCabinetCodes.value.filter(c => c !== code)
}

const debouncedSearchCabinets = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(searchCabinets, 500)
}

const searchCabinets = async () => {
  if (!cabinetSearchQuery.value.trim()) {
    availableCabinets.value = []
    return
  }
  searchingCabinets.value = true
  try {
    const res = await cabinetService.getCabinets({ page: 1, per_page: 20, search: cabinetSearchQuery.value })
    // Filter out cabinets already in the batch
    const existingCodes = results.value.map(r => r.cabinet_code)
    availableCabinets.value = res.data.filter(c => !existingCodes.includes(c.cabinet_code))
  } catch (error) {
    console.error('Error searching cabinets', error)
  } finally {
    searchingCabinets.value = false
  }
}

const submitAddCabinets = async () => {
  if (!selectedCabinetCodes.value.length) return
  addingCabinets.value = true
  try {
    const res = await batchService.addCabinetsToBatch(batch.value.id, selectedCabinetCodes.value)
    alert(res.message || 'Đã thêm tủ thành công!')
    closeAddCabinetModal()
    await fetchBatch()
    await fetchResults()
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi khi thêm tủ')
  } finally {
    addingCabinets.value = false
  }
}

const removeCabinet = async (item) => {
  if (item.inspection || item.status === 'done') {
    alert('⚠️ Không thể xóa tủ đã được kiểm tra khỏi lô!')
    return
  }
  if (!confirm(`Bạn có chắc chắn muốn XÓA TỦ [ ${item.cabinet_code} ] khỏi lô ${batch.value.name} không?`)) return
  try {
    const res = await batchService.removeCabinetFromBatch(batch.value.id, item.plan_id)
    alert(res.message || 'Đã xóa tủ thành công')
    await fetchBatch()
    await fetchResults()
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi khi xóa tủ')
  }
}

const getStatusClass = (status) => {
  const classes = { completed: 'badge-success', in_progress: 'badge-warning', pending: 'badge-info', active: 'badge-warning' }
  return classes[status] || 'badge-info'
}

const getStatusLabel = (status) => {
  const labels = { completed: 'Hoàn thành', in_progress: 'Đang kiểm tra', pending: 'Chờ xử lý', active: 'Đang hoạt động' }
  return labels[status] || 'Chờ xử lý'
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString('vi-VN')
}

const formatDateInput = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toISOString().split('T')[0]
}

const progressColor = computed(() => {
  const p = progress.value.percentage
  if (p >= 100) return 'text-green-600'
  if (p >= 50) return 'text-amber-600'
  return 'text-gray-600'
})

const progressBarColor = computed(() => {
  const p = progress.value.percentage
  if (p >= 100) return 'bg-green-500'
  if (p >= 50) return 'bg-amber-500'
  return 'bg-primary-500'
})

// Can close: all inspected + all reviewed
const canClose = computed(() => {
  if (!summary.value) return false
  return summary.value.not_inspected === 0 && summary.value.pending_review === 0
})

// Fetch batch info
const fetchBatch = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await batchService.getBatchById(route.params.id)
    batch.value = data
    progress.value = data.progress || { total: 0, completed: 0, percentage: 0 }
    editForm.value = {
      name: data.name,
      start_date: formatDateInput(data.start_date),
      end_date: formatDateInput(data.end_date),
    }
  } catch (e) {
    error.value = 'Không thể tải thông tin lô kiểm tra'
  } finally {
    loading.value = false
  }
}

// Fetch results
const fetchResults = async () => {
  try {
    const res = await batchService.getBatchResults(route.params.id)
    results.value = res.data || []
    summary.value = res.summary || null
  } catch (e) {
    console.error('Failed to fetch results:', e)
  }
}

// Edit batch
const handleEdit = async () => {
  saving.value = true
  try {
    await batchService.updateBatch(batch.value.id, editForm.value)
    showEditModal.value = false
    await fetchBatch()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể cập nhật')
  } finally {
    saving.value = false
  }
}

// Delete batch
const handleDelete = async () => {
  if (!confirm('Bạn có chắc muốn xóa lô kiểm tra này?')) return
  try {
    await batchService.deleteBatch(batch.value.id, true)
    router.push({ name: 'admin-batches' })
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể xóa')
  }
}

// Review: approve
const reviewPlan = async (planId, status) => {
  try {
    await batchService.reviewPlan(planId, { review_status: status })
    await fetchResults()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể thao tác')
  }
}

// Review: approve all pending
const approveAllPending = async () => {
  const pendingItems = results.value.filter(item => item.inspection && item.review_status === 'pending')
  if (!pendingItems.length) return

  if (confirm(`Bạn có chắc chắn muốn Duyệt ĐẠT cho ${pendingItems.length} tủ thiết bị đang chờ duyệt?`)) {
    try {
      loading.value = true
      let successCount = 0
      for (const item of pendingItems) {
        await batchService.reviewPlan(item.plan_id, { review_status: 'approved' })
        successCount++
      }
      alert(`Đã duyệt thành công ${successCount} tủ thiết bị.`)
      await fetchResults()
    } catch (e) {
      console.error(e)
      alert(e.response?.data?.message || 'Có lỗi xảy ra khi duyệt hàng loạt.')
      await fetchResults()
    } finally {
      loading.value = false
    }
  }
}

// Review: reject modal
const openRejectModal = (item) => {
  rejectTarget.value = item
  rejectNote.value = ''
  showRejectModal.value = true
}

const confirmReject = async () => {
  if (!rejectTarget.value) return
  try {
    await batchService.reviewPlan(rejectTarget.value.plan_id, {
      review_status: 'rejected',
      review_note: rejectNote.value || null,
    })
    showRejectModal.value = false
    await fetchResults()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể từ chối')
  }
}

// Close batch
const handleClose = async () => {
  if (!confirm('Kết thúc lô kiểm tra? Sau khi kết thúc sẽ không thể sửa đổi.')) return
  closing.value = true
  try {
    const res = await batchService.closeBatch(batch.value.id)
    alert(`✅ ${res.message}\n\nĐạt: ${res.summary.approved}\nTừ chối: ${res.summary.rejected}`)
    await fetchBatch()
    await fetchResults()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể kết thúc lô')
  } finally {
    closing.value = false
  }
}

// Reopen batch (Rollback)
const handleReopen = async () => {
  if (!confirm('Bạn có chắc chắn muốn HOÀN TÁC Trạng Thái Kết Thúc?\nLập tức mở lại lô kiểm tra này để tiếp tục làm việc!')) return
  reopening.value = true
  try {
    await batchService.reopenBatch(batch.value.id)
    alert('✅ Đã mở lại lô thành công')
    await fetchBatch()
    await fetchResults()
  } catch (e) {
    alert(e.response?.data?.message || 'Không thể mở lại lô này')
  } finally {
    reopening.value = false
  }
}

onMounted(async () => {
  await fetchBatch()
  await fetchResults()
})
</script>
