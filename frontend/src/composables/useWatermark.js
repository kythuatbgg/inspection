/**
 * Watermark Camera composable.
 * Dùng Canvas API để vẽ watermark lên ảnh: GPS tọa độ + mã tủ + timestamp.
 * Kết hợp nén ảnh qua browser-image-compression.
 */

import imageCompression from 'browser-image-compression'

/**
 * Lấy vị trí GPS hiện tại.
 * Fallback về null nếu không lấy được.
 */
export function getCurrentPosition() {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve(null)
      return
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve(pos),
      () => resolve(null),
      { timeout: 5000, maximumAge: 30000 }
    )
  })
}

/**
 * Convert File/Blob to base64 data URL.
 */
function fileToBase64(blob) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onloadend = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(blob)
  })
}

/**
 * Lấy text watermark hiển thị tọa độ.
 */
function getCoordText(lat, lng) {
  if (lat != null && lng != null) {
    return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
  }
  return '—'
}

/**
 * Lấy text watermark hiển thị thông tin tủ và thời gian.
 */
function getMetaText(cabinetCode) {
  const ts = new Date().toLocaleString('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
  return `${cabinetCode || '—'} | ${ts}`
}

/**
 * Vẽ watermark lên canvas.
 * @param {CanvasRenderingContext2D} ctx
 * @param {number} w - canvas width
 * @param {number} h - canvas height
 * @param {string} coordText
 * @param {string} metaText
 */
function drawWatermark(ctx, w, h, coordText, metaText) {
  const fontSize = Math.max(12, Math.round(w * 0.022))
  const smallFontSize = Math.max(10, Math.round(w * 0.016))
  const boxHeight = Math.round(h * 0.08) + 4

  // Semi-transparent dark box
  ctx.fillStyle = 'rgba(0,0,0,0.55)'
  ctx.fillRect(0, h - boxHeight, w, boxHeight)

  // GPS icon line
  ctx.font = `${fontSize}px "Courier New", monospace`
  ctx.fillStyle = '#00ff88'
  ctx.fillText(`📍 ${coordText}`, 10, h - boxHeight + fontSize + 4)

  // Cabinet + timestamp line
  ctx.font = `${smallFontSize}px "Courier New", monospace`
  ctx.fillStyle = '#ffffff'
  ctx.fillText(metaText, 10, h - 6)
}

/**
 * Chụp ảnh với watermark.
 * Nén ảnh trước khi vẽ watermark để giảm kích thước.
 *
 * @param {File} file - File ảnh nguồn
 * @param {{ lat: number|null, lng: number|null, cabinetCode: string }} options
 * @returns {Promise<string>} base64 data URL
 */
export async function captureWithWatermark(file, options = {}) {
  const { lat, lng, cabinetCode = '—' } = options

  // Compress first (reduces canvas processing size)
  let processedFile = file
  try {
    processedFile = await imageCompression(file, {
      maxSizeMB: 0.5,
      maxWidthOrHeight: 1920,
      useWebWorker: true,
    })
  } catch {
    // Compression failed — proceed with original file
  }

  // Convert to data URL for canvas drawing
  const dataUrl = await fileToBase64(processedFile)

  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = async () => {
      try {
        const canvas = document.createElement('canvas')
        canvas.width = img.width
        canvas.height = img.height
        const ctx = canvas.getContext('2d')

        // Draw original image
        ctx.drawImage(img, 0, 0)

        // Draw watermark
        const coordText = getCoordText(lat, lng)
        const metaText = getMetaText(cabinetCode)
        drawWatermark(ctx, img.width, img.height, coordText, metaText)

        // Export as JPEG at 0.75 quality
        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error('Canvas toBlob failed'))
              return
            }
            fileToBase64(blob).then(resolve).catch(reject)
          },
          'image/jpeg',
          0.75
        )
      } catch (e) {
        reject(e)
      }
    }
    img.onerror = () => reject(new Error('Image load failed'))
    img.src = dataUrl
  })
}
