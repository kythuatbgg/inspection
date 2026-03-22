import { ref } from 'vue'

/**
 * Keyed download state — replaces the single `downloading` boolean.
 * Only the button whose key is active will be disabled.
 *
 * Usage:
 *   const dl = useDownloadState()
 *   dl.startDownload(`inspection:${id}`)
 *   await downloadFile()
 *   dl.finishDownload(`inspection:${id}`)
 *
 *   :disabled="dl.isDownloading(`inspection:${row.id}`)"
 */
export function useDownloadState() {
  const activeDownloads = ref(new Set())

  function startDownload(key) {
    activeDownloads.value = new Set([...activeDownloads.value, key])
  }

  function finishDownload(key) {
    const next = new Set(activeDownloads.value)
    next.delete(key)
    activeDownloads.value = next
  }

  function isDownloading(key) {
    return activeDownloads.value.has(key)
  }

  /**
   * Returns true if ANY of the given keys is currently downloading.
   * Useful for disabling a group of related actions.
   */
  function isAnyDownloading(...keys) {
    return keys.some(k => activeDownloads.value.has(k))
  }

  return { activeDownloads, startDownload, finishDownload, isDownloading, isAnyDownloading }
}
