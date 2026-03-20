<template>
  <div ref="mapContainer" class="h-full w-full rounded-lg"></div>
</template>

<script setup>
import { onMounted, ref, watch, onUnmounted } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
  cabinets: { type: Array, default: () => [] }
})

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

const initMap = () => {
  if (!mapContainer.value) return

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

  // Add new markers
  const validCabinets = props.cabinets.filter(c => c.lat && c.lng)

  validCabinets.forEach(cabinet => {
    const marker = L.marker([parseFloat(cabinet.lat), parseFloat(cabinet.lng)])
      .addTo(map)
      .bindPopup(`
        <div class="text-sm">
          <strong class="text-base">${cabinet.cabinet_code}</strong><br>
          <span>${cabinet.name || ''}</span><br>
          <span class="text-gray-500">${cabinet.bts_site || ''}</span><br>
          <span class="text-xs text-gray-400">${cabinet.address || ''}</span>
        </div>
      `)
    markers.push(marker)
  })

  // Fit bounds if we have markers
  if (markers.length > 0) {
    const group = L.featureGroup(markers)
    map.fitBounds(group.getBounds(), { padding: [50, 50] })
  }
}

watch(() => props.cabinets, updateMarkers, { deep: true })

onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>
