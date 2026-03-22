import { ref } from 'vue'

/**
 * Composable quản lý PWA Install Prompt (Add to Home Screen).
 *
 * Browser sẽ fire event `beforeinstallprompt` khi app thỏa điều kiện cài đặt.
 * Composablenày bắt event đó và expose ra cho UI.
 */
export function useInstallPrompt() {
  const canInstall = ref(false)
  const showBanner = ref(false)

  /** @type {BeforeInstallPromptEvent|null} */
  let deferredPrompt = null

  function handleBeforeInstallPrompt(e) {
    e.preventDefault()
    deferredPrompt = e
    canInstall.value = true
    showBanner.value = true
  }

  function handleAppInstalled() {
    deferredPrompt = null
    canInstall.value = false
    showBanner.value = false
  }

  async function install() {
    if (!deferredPrompt) return

    deferredPrompt.prompt()
    const { outcome } = await deferredPrompt.userChoice

    if (outcome === 'accepted') {
      deferredPrompt = null
      canInstall.value = false
      showBanner.value = false
    }
    // If dismissed, keep canInstall=true but hide banner
    if (outcome === 'dismissed') {
      showBanner.value = false
    }
  }

  function dismiss() {
    showBanner.value = false
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
    window.addEventListener('appinstalled', handleAppInstalled)
  }

  return {
    canInstall,
    showBanner,
    install,
    dismiss,
  }
}
