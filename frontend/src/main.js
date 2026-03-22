import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import i18n from './i18n'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n)

app.mount('#app')

// ── PWA: Register Service Worker ───────────────────────────────────────────
if ('serviceWorker' in navigator) {
  import('virtual:pwa-register').then(({ registerSW }) => {
    registerSW({
      onNeedRefresh() {
        // Optional: show "Update available" prompt to user
        console.info('[PWA] New version available. Refresh to update.')
      },
      onOfflineReady() {
        console.info('[PWA] App ready to work offline.')
      },
      onRegistered(registration) {
        console.info('[PWA] Service Worker registered:', registration?.scope)
      },
      onRegisterError(error) {
        console.warn('[PWA] Service Worker registration error:', error)
      },
    })
  }).catch(() => {
    // virtual:pwa-register not available in dev without build — silent fail
  })
}
