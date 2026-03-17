/**
 * Capture image with GPS watermark
 * Adds lat/lng, cabinet code, and timestamp to image
 */

export async function captureWatermark(file, metadata) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    const reader = new FileReader()

    reader.onload = (e) => {
      img.onload = () => {
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')

        // Set canvas size to match image
        canvas.width = img.width
        canvas.height = img.height

        // Draw original image
        ctx.drawImage(img, 0, 0)

        // Watermark settings
        const fontSize = Math.max(16, Math.floor(img.width / 40))
        ctx.font = `${fontSize}px Arial`
        ctx.textAlign = 'right'

        // Semi-transparent background for text
        const textLines = [
          metadata.cabinetCode || 'N/A',
          `Lat: ${metadata.lat?.toFixed(6) || '0.000000'}`,
          `Lng: ${metadata.lng?.toFixed(6) || '0.000000'}`,
          new Date(metadata.timestamp).toLocaleString('vi-VN')
        ]

        const lineHeight = fontSize * 1.4
        const padding = fontSize
        const boxWidth = Math.max(...textLines.map(t => ctx.measureText(t).width)) + padding * 2
        const boxHeight = lineHeight * textLines.length + padding * 2

        // Draw watermark box (bottom right)
        ctx.fillStyle = 'rgba(0, 0, 0, 0.6)'
        ctx.fillRect(
          canvas.width - boxWidth - 20,
          canvas.height - boxHeight - 20,
          boxWidth,
          boxHeight
        )

        // Draw text
        ctx.fillStyle = '#ffffff'
        textLines.forEach((line, index) => {
          const y = canvas.height - 20 - boxHeight + padding + (index + 1) * lineHeight - fontSize / 2
          ctx.fillText(line, canvas.width - 20 - padding, y)
        })

        // Compress and return as base64
        const base64 = canvas.toDataURL('image/jpeg', 0.6)
        resolve(base64)
      }

      img.onerror = reject
      img.src = e.target.result
    }

    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

/**
 * Get current GPS position
 */
export function getCurrentPosition() {
  return new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject(new Error('Geolocation not supported'))
      return
    }

    navigator.geolocation.getCurrentPosition(resolve, reject, {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    })
  })
}
