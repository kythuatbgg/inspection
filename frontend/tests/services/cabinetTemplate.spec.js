import { describe, expect, test, vi } from 'vitest'
import axios from 'axios'

vi.mock('axios')

describe('CabinetController - downloadTemplate', () => {
  test('downloadTemplate requests an Excel file', async () => {
    axios.get.mockResolvedValue({
      status: 200,
      headers: {
        'content-type': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    })

    const response = await axios.get('/api/cabinets/template', {
      responseType: 'blob'
    })

    expect(axios.get).toHaveBeenCalledWith('/api/cabinets/template', {
      responseType: 'blob'
    })
    expect(response.status).toBe(200)
    expect(response.headers['content-type']).toContain('spreadsheetml.sheet')
  })
})
