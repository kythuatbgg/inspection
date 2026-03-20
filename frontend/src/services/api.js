import axios from 'axios'

let API_URL = import.meta.env.VITE_API_URL

// In development, force relative URL to use Vite's proxy for zero-config Tunnel & LAN access
if (import.meta.env.DEV) {
  API_URL = '/api'
} else {
  // Auto-correct localhost in VITE_API_URL to the actual LAN IP for production builds if needed
  if (API_URL && API_URL.includes('localhost') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    API_URL = API_URL.replace('localhost', window.location.hostname)
  }
}

if (!API_URL) {
  API_URL = '/api'
}

// Create axios instance
const api = axios.create({
  baseURL: API_URL,
  timeout: 30000,
})

// Set default headers
api.defaults.headers.common['Content-Type'] = 'application/json'
api.defaults.headers.common['Accept'] = 'application/json'

import { showGlobalLoading, hideGlobalLoading } from '@/composables/useLoading.js'

// Request interceptor - Add auth token
api.interceptors.request.use(
  (config) => {
    // Only show loading for requests that don't silently happen in background
    if (!config.silent) {
      showGlobalLoading()
    }
    
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    hideGlobalLoading()
    return Promise.reject(error)
  }
)

// Response interceptor - Handle errors
api.interceptors.response.use(
  (response) => {
    if (!response.config.silent) {
      hideGlobalLoading()
    }
    return response
  },
  (error) => {
    if (!error.config?.silent) {
      hideGlobalLoading()
    }
    
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('userRole')
      // Could redirect to login here
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
export { API_URL }
