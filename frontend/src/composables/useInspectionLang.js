import { ref, watch } from 'vue'

const STORAGE_KEY = 'inspection_lang'

export function useInspectionLang() {
  const currentLang = ref(localStorage.getItem(STORAGE_KEY) || 'en')

  watch(currentLang, (lang) => {
    localStorage.setItem(STORAGE_KEY, lang)
  })

  const LANG_OPTIONS = [
    { value: 'vn', label: 'Tiếng Việt', flag: 'VN' },
    { value: 'en', label: 'English',     flag: 'EN' },
    { value: 'kh', label: 'Khmer',       flag: 'KH' },
  ]

  const getContent = (item) => {
    return item[`content_${currentLang.value}`] || item.content_en || ''
  }

  const getCategory = (item) => {
    return item[`category_${currentLang.value}`] || item.category || ''
  }

  return { currentLang, LANG_OPTIONS, getContent, getCategory }
}
