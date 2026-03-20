<template>
  <div ref="mapContainer" class="h-full w-full rounded-lg"></div>
</template>

<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  cabinets: { type: Array, default: () => [] }
})

const router = useRouter()
const mapContainer = ref(null)
let map = null
let markers = []

// Fix Leaflet default icon issue
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
})

const handlePopupClick = (e) => {
  const target = e.target.closest('a[data-cabinet]')
  if (target) {
    e.preventDefault()
    const code = target.getAttribute('data-cabinet')
    router.push(`/cabinets/${code}`)
  }
}

const initMap = () => {
  if (!mapContainer.value) return

  // Attach event listener for router links in popups
  mapContainer.value.addEventListener('click', handlePopupClick)

  // Initialize map centered on Vietnam
  map = L.map(mapContainer.value).setView([10.8231, 106.6297], 13)

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(map)

  updateMarkers()
}

const updateMarkers = () => {
  if (!map) return

  // Clear existing markers
  markers.forEach(marker => map.removeLayer(marker))
  markers = []

  // Prepare groups by bts_site
  const groups = {}
  const validCabinets = props.cabinets.filter(c => c.lat && c.lng)
  
  validCabinets.forEach(cabinet => {
    const site = cabinet.bts_site || 'Không xác định'
    if (!groups[site]) {
      groups[site] = { cabinets: [], lat: cabinet.lat, lng: cabinet.lng }
    }
    groups[site].cabinets.push(cabinet)
  })

  // Add new grouped markers
  Object.keys(groups).forEach(site => {
    const group = groups[site]
    const count = group.cabinets.length
    
    // Create a custom div icon
    const customIcon = L.divIcon({
      html: `
        <div class="relative flex items-center justify-center w-10 h-10 bg-primary-600 text-white rounded-full border-4 border-white shadow-md font-bold text-sm transform -translate-x-1/2 -translate-y-1/2">
          ${count}
        </div>
      `,
      className: '',
      iconSize: [40, 40],
      iconAnchor: [20, 20],
      popupAnchor: [0, -20]
    })

    // Generate popup HTML for listed cabinets
    const cabinetsHtml = group.cabinets.map(cabinet => `
      <div class="flex items-center justify-between py-1 border-b border-gray-50 last:border-0">
        <span class="font-medium text-gray-900">${cabinet.cabinet_code}</span>
        <a href="#" data-cabinet="${cabinet.cabinet_code}" class="text-sm text-primary-600 hover:text-primary-700 font-semibold px-2 py-1 bg-primary-50 rounded-md">Chi tiết</a>
      </div>
    `).join('')

    const popupHtml = `
      <div class="min-w-[200px] max-w-[280px]">
        <div class="font-bold text-gray-900 border-b border-gray-100 pb-2 mb-2">
          ${site === 'Không xác định' ? 'Các tủ không thuộc trạm' : 'Trạm: ' + site}
        </div>
        <div class="max-h-[150px] overflow-y-auto overscroll-contain pr-1 custom-scrollbar">
          ${cabinetsHtml}
        </div>
      </div>
    `

    const marker = L.marker([parseFloat(group.lat), parseFloat(group.lng)], { icon: customIcon })
      .addTo(map)
      .bindPopup(popupHtml, { maxWidth: 320 })
      
    markers.push(marker)
  })

  // Fit bounds if we have markers
  if (markers.length > 0) {
    const featureGroup = L.featureGroup(markers)
    map.fitBounds(featureGroup.getBounds(), { padding: [50, 50], maxZoom: 15 })
  }
}

watch(() => props.cabinets, updateMarkers, { deep: true })

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (mapContainer.value) {
    mapContainer.value.removeEventListener('click', handlePopupClick)
  }
  if (map) {
    map.remove()
    map = null
  }
})
</script>
