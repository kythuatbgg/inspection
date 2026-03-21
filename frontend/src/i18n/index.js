import { createI18n } from 'vue-i18n'
import vi from './vi.json'
import en from './en.json'

const savedLocale = localStorage.getItem('locale') || 'vi'

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'vi',
  messages: { vi, en }
})

export function setI18nLocale(locale) {
  i18n.global.locale.value = locale
  localStorage.setItem('locale', locale)
  document.documentElement.setAttribute('lang', locale === 'vi' ? 'vi' : 'en')
}

export function getI18nLocale() {
  return i18n.global.locale.value
}

export function getDateLocale() {
  return i18n.global.locale.value === 'vi' ? 'vi-VN' : 'en-US'
}

export default i18n
