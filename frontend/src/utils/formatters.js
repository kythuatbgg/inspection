/**
 * Lightweight shared formatters — no external dependencies.
 */

/**
 * Format ISO date string to DD/MM/YYYY
 */
export function formatDate(dateStr) {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`
}

/**
 * Format "YYYY-MM" to "T{M}" for timeline labels.
 * Uses current locale month name if available, falls back to T-format.
 */
export function formatTimelineLabel(monthStr, locale = 'en') {
  if (!monthStr) return ''
  const [year, month] = monthStr.split('-')
  const monthNum = parseInt(month, 10)
  try {
    const d = new Date(parseInt(year, 10), monthNum - 1, 1)
    return d.toLocaleString(locale, { month: 'short' }).replace('.', '')
  } catch {
    return `T${monthNum}`
  }
}
