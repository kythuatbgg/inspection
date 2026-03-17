import { defineStore } from 'pinia'
import axios from 'axios'
import { db } from '../db'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// Set axios default header if token exists
const storedToken = localStorage.getItem('token')
if (storedToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
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
        const response = await axios.post(`${API_URL}/login`, { username, password })
        this.token = response.data.token
        this.user = response.data.user
        localStorage.setItem('token', this.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
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
        await axios.post(`${API_URL}/logout`)
      } catch (e) {
        // Ignore
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    },

    async fetchUser() {
      if (!this.token) return
      try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        const response = await axios.get(`${API_URL}/me`)
        this.user = response.data
      } catch (error) {
        this.logout()
      }
    }
  }
})
