<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-0">
    <div class="hidden md:flex items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900">{{ $t('user.manageTitle') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $t('user.manageSubtitle') }}</p>
      </div>
      <button @click="openModal()" class="btn-primary min-h-[52px] px-5 flex items-center gap-2 text-sm font-semibold">
        <UserPlus class="w-5 h-5" />
        {{ $t('user.addNew') }}
      </button>
    </div>

    <div class="md:hidden flex flex-col gap-2">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('user.title') }}</h2>
        <div class="flex shrink-0 flex-col items-center justify-center rounded-[12px] bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm">
          <span>{{ $t('user.totalAccounts', { count: pagination.total }) }}</span>
        </div>
      </div>
      <p class="text-sm leading-relaxed text-slate-500">{{ $t('user.mobileSubtitle') }}</p>
    </div>

    <!-- Desktop Stats -->
    <div class="hidden lg:grid w-full grid-cols-5 gap-4">
      <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <p class="text-[28px] leading-none font-bold text-slate-900">{{ stats.total }}</p>
        <p class="text-sm text-slate-500 mt-2">{{ $t('common.total') }}</p>
      </div>
      <div class="bg-white rounded-lg border border-amber-100 shadow-sm p-5">
        <p class="text-[28px] leading-none font-bold text-warning">{{ stats.admin }}</p>
        <p class="text-sm text-slate-500 mt-2">Admin</p>
      </div>
      <div class="bg-white rounded-lg border border-sky-100 shadow-sm p-5">
        <p class="text-[28px] leading-none font-bold text-sky-600">{{ stats.manager }}</p>
        <p class="text-sm text-slate-500 mt-2">{{ $t('roles.manager') }}</p>
      </div>
      <div class="bg-white rounded-lg border border-emerald-100 shadow-sm p-5">
        <p class="text-[28px] leading-none font-bold text-emerald-600">{{ stats.inspector }}</p>
        <p class="text-sm text-slate-500 mt-2">Inspector</p>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 shadow-sm p-5">
        <p class="text-[28px] leading-none font-bold text-slate-600">{{ stats.staff }}</p>
        <p class="text-sm text-slate-500 mt-2">Staff</p>
      </div>
    </div>

    <!-- Mobile Stats Pro Max Design -->
    <div class="lg:hidden flex flex-col gap-3">
      <div class="bg-white rounded-[20px] border border-slate-200 shadow-sm p-4 relative overflow-hidden">
        <div class="absolute right-0 top-0 bottom-0 w-32 bg-primary-50/50 rounded-l-full -mr-16"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-500">{{ $t('user.totalAccountsLabel') }}</p>
            <p class="text-3xl font-bold tracking-tight text-slate-900 mt-1">{{ stats.total }}</p>
          </div>
          <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 shadow-inner">
            <Users class="w-6 h-6" />
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-warning/10/50 rounded-[16px] border border-amber-100 p-3">
          <p class="text-xs text-warning/70 font-semibold uppercase tracking-wider">Admin</p>
          <p class="text-[22px] leading-none font-bold text-warning mt-1">{{ stats.admin }}</p>
        </div>
        <div class="bg-sky-50/50 rounded-[16px] border border-sky-100 p-3">
          <p class="text-xs text-sky-600/70 font-semibold uppercase tracking-wider">{{ $t('roles.manager') }}</p>
          <p class="text-[22px] leading-none font-bold text-sky-700 mt-1">{{ stats.manager }}</p>
        </div>
        <div class="bg-emerald-50/50 rounded-[16px] border border-emerald-100 p-3">
          <p class="text-xs text-emerald-600/70 font-semibold uppercase tracking-wider">Inspector</p>
          <p class="text-[22px] leading-none font-bold text-emerald-700 mt-1">{{ stats.inspector }}</p>
        </div>
        <div class="bg-slate-50/50 rounded-[16px] border border-slate-200 p-3">
          <p class="text-xs text-slate-500/70 font-semibold uppercase tracking-wider">Staff</p>
          <p class="text-[22px] leading-none font-bold text-slate-600 mt-1">{{ stats.staff }}</p>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-[22px] md:rounded-lg shadow-sm border border-slate-200 md:border-slate-200 p-3 md:p-4">
      <div class="flex flex-col md:flex-row gap-3 md:gap-4">
        <div class="relative min-w-0 flex-1 md:max-w-md">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <Search class="w-5 h-5 text-slate-400" />
          </div>
          <input
            v-model="filters.search"
            type="text"
            :placeholder="$t('user.searchPlaceholder')"
            class="w-full min-w-0 pl-11 pr-4 py-3 min-h-[52px] rounded-[16px] md:rounded-lg border border-transparent md:border-slate-300 bg-slate-50/80 md:bg-white text-slate-900 placeholder:text-slate-400 focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none transition-all"
            @input="handleSearch"
          />
        </div>

        <MobileBottomSheet
          v-model="filters.role"
          :options="roleFilterOptions"
          :label="$t('user.selectRole')"
          :placeholder="$t('user.allRoles')"
          container-class="w-full md:w-[220px]"
          select-class="rounded-[16px] md:rounded-lg border-transparent md:border-slate-300 bg-slate-50/80 md:bg-white focus:bg-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
          trigger-class="bg-slate-50/80"
          @update:model-value="handleSearch"
        />
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-[24px] md:rounded-lg border border-slate-200 shadow-sm p-10 text-center text-slate-500">
      {{ $t('user.loadingUsers') }}
    </div>

    <div v-else-if="error" class="bg-white rounded-[24px] md:rounded-lg border border-red-100 shadow-sm p-10 text-center">
      <p class="text-danger font-medium">{{ error }}</p>
    </div>

    <div v-else class="space-y-4 md:space-y-6">
      <div class="hidden lg:block bg-white rounded-lg overflow-hidden border border-slate-200 shadow-sm">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $t('user.userColumn') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $t('user.roleColumn') }}</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $t('common.createdAt') }}</th>
              <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors">
              <td class="px-4 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                    <span class="text-sm font-semibold text-primary-700">{{ getInitials(user.name) }}</span>
                  </div>
                  <div>
                    <router-link :to="'/users/' + user.id" class="font-medium text-slate-900 hover:text-primary-600 transition-colors block">
                      {{ user.name }}
                    </router-link>
                    <p class="text-sm text-slate-500">{{ user.username }}</p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4">
                <span :class="getRoleClass(user.role)">{{ getRoleLabel(user.role) }}</span>
              </td>
              <td class="px-4 py-4 text-sm text-slate-600">{{ formatDate(user.created_at) }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openModal(user)" class="px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-primary-700 hover:bg-primary-50 transition-colors">
                    {{ $t('common.edit') }}
                  </button>
                  <button @click="confirmDelete(user)" class="px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-danger hover:bg-danger/10 transition-colors">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="users.length === 0" class="px-6 py-10 text-center text-slate-500">
          {{ $t('user.noUsers') }}
        </div>
      </div>

      <div class="lg:hidden flex flex-col gap-4">
        <div
          v-for="user in users"
          :key="user.id"
          class="bg-white rounded-[24px] border border-slate-200 shadow-sm p-5 transition-all duration-200 active:scale-[0.99]"
        >
          <div class="flex items-start gap-4 mb-4 min-w-0">
            <div class="w-12 h-12 rounded-[16px] bg-primary-50 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-bold text-primary-700">{{ getInitials(user.name) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between min-w-0">
                <div class="min-w-0 flex-1 pr-3">
                  <router-link :to="'/users/' + user.id" class="block">
                    <h3 class="text-lg font-bold tracking-tight text-slate-900 hover:text-primary-600 transition-colors truncate">{{ user.name }}</h3>
                  </router-link>
                  <p class="text-sm font-medium text-slate-500 truncate">@{{ user.username }}</p>
                </div>
                <span class="shrink-0" :class="getRoleClass(user.role)">{{ getRoleLabel(user.role) }}</span>
              </div>
            </div>
          </div>

          <div class="bg-slate-50/80 rounded-[16px] p-4 space-y-2.5 mb-4 overflow-hidden">
            <div class="flex items-start justify-between gap-4 text-sm min-w-0">
              <span class="text-slate-500 font-medium shrink-0">Email</span>
              <span class="min-w-0 break-all text-right font-semibold text-slate-900">{{ user.email }}</span>
            </div>
            <div class="flex items-center justify-between gap-4 text-sm min-w-0">
              <span class="text-slate-500 font-medium shrink-0">{{ $t('common.createdAt') }}</span>
              <span class="min-w-0 text-right font-semibold text-slate-900">{{ formatDate(user.created_at) }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3 min-w-0">
            <button @click="openModal(user)" class="flex min-w-0 flex-1 items-center justify-center gap-2 rounded-[14px] border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 min-h-[48px] active:bg-slate-50 transition-colors">
              <FileEdit class="w-5 h-5 shrink-0 text-slate-400" />
              <span class="truncate">{{ $t('common.edit') }}</span>
            </button>
            <button @click="confirmDelete(user)" class="flex min-w-0 flex-1 items-center justify-center gap-2 rounded-[14px] border border-red-100 bg-danger/10/70 px-3 text-sm font-semibold text-danger min-h-[48px] active:bg-red-100 transition-colors">
              <Trash2 class="w-5 h-5 shrink-0" />
              <span class="truncate">{{ $t('common.delete') }}</span>
            </button>
          </div>
        </div>

        <div v-if="users.length === 0" class="flex flex-col items-center justify-center px-4 py-12 bg-white rounded-[24px] border border-slate-200 shadow-sm text-center">
          <UserPlus class="w-16 h-16 text-gray-300 mb-4" />
          <p class="text-slate-600 font-medium">{{ $t('user.noUsers') }}</p>
          <p class="text-sm text-slate-400 mt-1">{{ $t('user.noUsersHint') }}</p>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="hidden md:flex bg-white rounded-lg shadow-sm border border-slate-200 p-6 items-center justify-between">
        <p class="text-sm text-slate-600 font-medium">
          {{ $t('common.showing') }} <span class="font-semibold text-slate-900">{{ pagination.from }}</span> -
          <span class="font-semibold text-slate-900">{{ pagination.to }}</span> {{ $t('common.of') }}
          <span class="font-semibold text-slate-900">{{ pagination.total }}</span> {{ $t('user.userItems') }}
        </p>

        <div class="flex items-center gap-1.5">
          <button
            @click="goToPage(1)"
            :disabled="pagination.current_page === 1"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors items-center justify-center"
            title="Trang đầu"
          >
            <ChevronsLeft class="w-4 h-4" />
          </button>
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors flex items-center justify-center"
            title="Trang trước"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <div class="flex items-center gap-1.5 mx-2">
            <template v-for="page in visiblePages" :key="page">
              <button
                @click="goToPage(page)"
                class="min-h-[40px] min-w-[40px] px-3 rounded-lg text-sm font-semibold transition-all duration-200"
                :class="page === pagination.current_page
                  ? 'bg-primary-600 text-white shadow-sm'
                  : 'border border-slate-200 hover:bg-slate-50 text-slate-700 hover:text-primary-600 hover:border-primary-200'"
              >
                {{ page }}
              </button>
            </template>
          </div>

          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors flex items-center justify-center"
            title="Trang sau"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            @click="goToPage(pagination.last_page)"
            :disabled="pagination.current_page === pagination.last_page"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-slate-200 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 text-slate-600 transition-colors items-center justify-center"
            title="Trang cuối"
          >
            <ChevronsRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="block md:hidden mt-8 mb-24">
        <div class="text-center mb-5">
          <div class="text-sm text-slate-500 font-medium">
            {{ $t('user.title') }} <span class="text-slate-900 font-bold">{{ pagination.from }}</span> - <span class="text-slate-900 font-bold">{{ pagination.to }}</span><br>
            <span class="text-xs text-slate-400 mt-1 inline-block">{{ $t('user.inTotalAccounts', { total: pagination.total }) }}</span>
          </div>
        </div>

        <div class="flex items-center justify-between bg-white p-2 rounded-[24px] shadow-sm border border-slate-200 mx-auto max-w-[320px]">
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="group flex items-center justify-center w-14 h-14 rounded-full bg-slate-50/80 text-slate-600 active:bg-slate-100 active:scale-95 transition-all duration-200 disabled:opacity-40 disabled:active:scale-100 disabled:bg-transparent"
          >
            <ChevronLeft class="w-6 h-6 group-active:-translate-x-1 transition-transform" />
          </button>

          <div class="flex-1 flex flex-col items-center justify-center">
            <div class="flex items-baseline gap-1.5">
              <span class="text-xl font-bold tracking-tight text-slate-900">{{ pagination.current_page }}</span>
              <span class="text-sm font-semibold text-slate-400">/ {{ pagination.last_page }}</span>
            </div>
          </div>

          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="group flex items-center justify-center w-14 h-14 rounded-full text-white bg-primary-600 shadow-sm shadow-primary-500/30 active:bg-primary-700 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:active:scale-100 disabled:shadow-none"
          >
            <ChevronRight class="w-6 h-6 group-active:translate-x-1 transition-transform" />
          </button>
        </div>
      </div>
    </div>

    <button
      @click="openModal()"
      class="md:hidden fixed right-6 bottom-6 w-14 h-14 rounded-[20px] bg-primary-600 text-white shadow-sm shadow-primary-500/40 flex items-center justify-center z-40 active:scale-90 active:bg-primary-700 transition-all duration-200"
      aria-label="Thêm người dùng"
    >
      <UserPlus class="w-7 h-7" />
    </button>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closeModal()"></div>

      <div class="relative bg-white rounded-[24px] md:rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="p-5 md:p-6">
          <div class="flex items-start justify-between gap-4 mb-5">
            <div>
              <h3 class="text-xl font-bold text-slate-900">
                {{ editingUser ? $t('user.editUser') : $t('user.addUser') }}
              </h3>
              <p class="text-sm text-slate-500 mt-1">{{ $t('user.formHint') }}</p>
            </div>
            <button @click="closeModal()" class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center active:bg-slate-200 transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveUser" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">{{ $t('user.fullName') }}</label>
              <input v-model="form.name" type="text" class="input-field min-h-[52px]" required>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">{{ $t('user.usernameField') }}</label>
              <input v-model="form.username" type="text" class="input-field min-h-[52px]" :disabled="editingUser" required>
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
              <input v-model="form.email" type="email" class="input-field min-h-[52px]" required>
            </div>

            <div v-if="!editingUser">
              <label class="block text-sm font-medium text-slate-700 mb-2">{{ $t('user.passwordField') }}</label>
              <input v-model="form.password" type="password" class="input-field min-h-[52px]" :required="!editingUser">
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">{{ $t('user.roleField') }}</label>
              <MobileBottomSheet
                v-model="form.role"
                :options="roleOptions"
                :label="$t('user.selectRole')"
                :placeholder="$t('user.selectRole')"
                select-class="input-field"
              />
            </div>

            <div v-if="formError" class="p-3 bg-danger/10 border border-red-200 rounded-lg">
              <p class="text-sm text-danger">{{ formError }}</p>
            </div>

            <div class="flex gap-3 pt-2 sticky bottom-0 bg-white pb-1">
              <button type="button" @click="closeModal()" class="btn-secondary flex-1 min-h-[52px]">
                {{ $t('common.cancel') }}
              </button>
              <button type="submit" :disabled="saving" class="btn-primary flex-1 min-h-[52px] flex items-center justify-center gap-2">
                <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                <span v-else>{{ $t('common.save') }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteConfirm = false"></div>
      <div class="relative bg-white rounded-[24px] md:rounded-lg shadow-xl w-full max-w-sm p-5 md:p-6">
        <div class="w-12 h-12 rounded-full bg-danger/10 text-danger flex items-center justify-center mb-4">
          <Trash2 class="w-6 h-6" />
        </div>
        <h3 class="text-lg font-bold text-slate-900 mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-slate-600 mb-5">
          {{ $t('user.deleteConfirm') }} <strong>{{ deletingUser?.name }}</strong>?
        </p>
        <div class="flex gap-3">
          <button @click="showDeleteConfirm = false" class="btn-secondary flex-1 min-h-[52px]">
            {{ $t('common.cancel') }}
          </button>
          <button @click="deleteUser" :disabled="deleting" class="flex-1 min-h-[52px] rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
            <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
            <span v-else>Xóa</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Trash2, X, UserPlus, ChevronRight, ChevronLeft, ChevronsRight, ChevronsLeft, FileEdit, Search, Users } from 'lucide-vue-next'

import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getDateLocale } from '@/i18n'
import userService from '@/services/userService.js'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'

const { t } = useI18n()

const roleFilterOptions = computed(() => [
  { value: '', label: t('user.allRoles') },
  { value: 'admin', label: t('roles.admin') },
  { value: 'manager', label: t('roles.manager') },
  { value: 'inspector', label: t('roles.inspector') },
  { value: 'staff', label: t('roles.staff') }
])

const roleOptions = computed(() => [
  { value: 'admin', label: t('roles.admin') },
  { value: 'manager', label: t('roles.manager') },
  { value: 'inspector', label: t('roles.inspector') },
  { value: 'staff', label: t('roles.staff') }
])

const loading = ref(true)
const error = ref(null)
const users = ref([])
const stats = ref({ total: 0, admin: 0, manager: 0, inspector: 0, staff: 0 })
const pagination = ref({
  current_page: 1,
  from: 0,
  last_page: 1,
  per_page: 5,
  to: 0,
  total: 0
})

const filters = ref({ search: '', role: '' })
let searchTimeout = null

const showModal = ref(false)
const editingUser = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref({ name: '', username: '', email: '', password: '', role: 'inspector' })

const showDeleteConfirm = ref(false)
const deletingUser = ref(null)
const deleting = ref(false)

const getRoleClass = (role) => {
  const classes = {
    admin: 'inline-flex items-center rounded-full bg-warning/10 px-3 py-1 text-sm font-semibold text-warning',
    manager: 'inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700',
    inspector: 'inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700',
    staff: 'inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700'
  }
  return classes[role] || classes.staff
}

const getRoleLabel = (role) => {
  const labels = {
    admin: t('roles.admin'),
    manager: t('roles.manager'),
    inspector: t('roles.inspector'),
    staff: t('roles.staff')
  }
  return labels[role] || role
}

const getInitials = (name) => {
  if (!name) return ''
  return name.split(' ').map((part) => part[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString(getDateLocale())
}

const fetchUsers = async () => {
  loading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    }

    if (filters.value.role) params.role = filters.value.role
    if (filters.value.search) params.search = filters.value.search

    const response = await userService.getUsers(params)
    users.value = Array.isArray(response.data) ? response.data : []
    pagination.value = {
      current_page: response.current_page || 1,
      from: response.from || 0,
      last_page: response.last_page || 1,
      per_page: response.per_page || 20,
      to: response.to || 0,
      total: response.total || 0
    }
  } catch (e) {
    error.value = t('common.errorLoadData')
    console.error(e)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
  try {
    const data = await userService.getStats()
    stats.value = data
  } catch (e) {
    console.error(e)
  }
}

const goToPage = async (page) => {
  if (!Number.isInteger(page) || page < 1 || page > pagination.value.last_page) {
    return
  }

  pagination.value.current_page = page
  await fetchUsers()
}

const handleSearch = () => {
  clearTimeout(searchTimeout)
  pagination.value.current_page = 1
  searchTimeout = setTimeout(() => fetchUsers(), 300)
}

const openModal = (user = null) => {
  editingUser.value = user

  if (user) {
    form.value = {
      name: user.name,
      username: user.username,
      email: user.email,
      password: '',
      role: user.role
    }
  } else {
    form.value = {
      name: '',
      username: '',
      email: '',
      password: '',
      role: 'inspector'
    }
  }

  formError.value = ''
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingUser.value = null
}

const saveUser = async () => {
  formError.value = ''
  saving.value = true

  try {
    if (editingUser.value) {
      const data = {
        name: form.value.name,
        email: form.value.email,
        role: form.value.role
      }

      if (form.value.password) data.password = form.value.password
      await userService.updateUser(editingUser.value.id, data)
    } else {
      await userService.createUser(form.value)
    }

    closeModal()
    await Promise.all([fetchUsers(), fetchStats()])
  } catch (e) {
    formError.value = e.response?.data?.message || t('common.errorOccurred')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (user) => {
  deletingUser.value = user
  showDeleteConfirm.value = true
}

const deleteUser = async () => {
  deleting.value = true

  try {
    await userService.deleteUser(deletingUser.value.id)
    showDeleteConfirm.value = false
    deletingUser.value = null

    if (users.value.length === 1 && pagination.value.current_page > 1) {
      pagination.value.current_page -= 1
    }

    await Promise.all([fetchUsers(), fetchStats()])
  } catch (e) {
    alert(e.response?.data?.message || t('common.errorOccurred'))
  } finally {
    deleting.value = false
  }
}

const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page

  if (last <= 5) {
    return Array.from({ length: last }, (_, index) => index + 1)
  }

  if (current <= 3) {
    return [1, 2, 3, 4, 5]
  }

  if (current >= last - 2) {
    return [last - 4, last - 3, last - 2, last - 1, last]
  }

  return [current - 2, current - 1, current, current + 1, current + 2]
})

onMounted(() => {
  fetchUsers()
  fetchStats()
})
</script>

