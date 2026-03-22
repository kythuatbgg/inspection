import { defineStore } from 'pinia'
import api from '../services/api'
import { db } from '../db'
import { setI18nLocale } from '../i18n'
import { syncCabinets, syncChecklists, syncBatches } from '../db/inspectionDraft'

const storedToken = localStorage.getItem('token')

function syncLocaleFromUser(user) {
  if (!user?.lang_pref) return
  const locale = user.lang_pref === 'en' ? 'en' : 'vi'
  setI18nLocale(locale)
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: storedToken,
    loading: false
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isInspector: (state) => state.user?.role === 'inspector',
    userLanguage: (state) => state.user?.lang_pref || 'vn'
  },

  actions: {
    async login(username, password) {
      this.loading = true
      try {
        const response = await api.post('/login', { username, password })
        const payload = response.data.data || response.data
        this.token = payload.token
        this.user = payload.user
        localStorage.setItem('token', this.token)
        syncLocaleFromUser(this.user)
        // Cache master data in background — non-blocking
        this.cacheMasterData()
        return true
      } catch (error) {
        console.error('Login failed:', error)
        return false
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } catch (e) {
        // Ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },

    async fetchUser() {
      if (!this.token) return
      try {
        const response = await api.get('/me')
        const payload = response.data.data || response.data
        this.user = payload.user || payload
        syncLocaleFromUser(this.user)
      } catch (error) {
        this.logout()
      }
    },

    async setLanguage(lang) {
      const locale = lang === 'en' ? 'en' : 'vi'
      setI18nLocale(locale)

      if (this.user) {
        this.user.lang_pref = locale === 'en' ? 'en' : 'vn'
        try {
          await api.patch('/me/language', { lang_pref: this.user.lang_pref })
        } catch (e) {
          console.error('Failed to update language preference:', e)
        }
      }
    },

    /** Cache master data into Dexie for offline access — fires silently in background. */
    async cacheMasterData() {
      if (!this.isAuthenticated) return
      try {
        await Promise.allSettled([
          syncCabinets(),
          syncChecklists(),
          syncBatches(this.user?.id),
        ])
      } catch {
        // Silent — offline access should not break the session
      }
    }
  }
})
