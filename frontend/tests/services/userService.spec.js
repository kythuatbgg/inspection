import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock api module
vi.mock('@/services/api.js', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

import api from '@/services/api.js'
import { userService } from '@/services/userService.js'

describe('User Service (userService.js) - CRUD Tests', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  // ===== CREATE =====
  describe('createUser', () => {
    it('should create user with valid data', async () => {
      const newUser = { name: 'Test User', username: 'testuser', email: 'test@test.com', password: '123456', role: 'inspector' }
      const mockResponse = { id: 1, ...newUser }
      api.post.mockResolvedValue({ data: { data: mockResponse } })

      const result = await userService.createUser(newUser)

      expect(api.post).toHaveBeenCalledWith('/users', newUser)
      expect(result).toEqual(mockResponse)
    })

    it('should handle creation error', async () => {
      api.post.mockRejectedValue({ response: { data: { message: 'Username đã tồn tại' } } })

      await expect(userService.createUser({})).rejects.toEqual({ response: { data: { message: 'Username đã tồn tại' } } })
    })
  })

  // ===== READ =====
  describe('getUsers', () => {
    it('should fetch all users with pagination', async () => {
      const mockData = { data: [{ id: 1, name: 'User 1' }], current_page: 1, total: 1 }
      api.get.mockResolvedValue({ data: { data: mockData } })

      const result = await userService.getUsers()

      expect(api.get).toHaveBeenCalledWith('/users', { params: {} })
      expect(result).toEqual(mockData)
    })

    it('should filter users by role', async () => {
      api.get.mockResolvedValue({ data: { data: { data: [], current_page: 1, total: 0 } } })

      await userService.getUsers({ role: 'admin' })

      expect(api.get).toHaveBeenCalledWith('/users', { params: { role: 'admin' } })
    })

    it('should search users by name', async () => {
      api.get.mockResolvedValue({ data: { data: { data: [], current_page: 1, total: 0 } } })

      await userService.getUsers({ search: 'Nguyen' })

      expect(api.get).toHaveBeenCalledWith('/users', { params: { search: 'Nguyen' } })
    })
  })

  describe('getUserById', () => {
    it('should fetch single user by ID', async () => {
      const mockData = { id: 1, name: 'User 1' }
      api.get.mockResolvedValue({ data: { data: mockData } })

      const result = await userService.getUserById(1)

      expect(api.get).toHaveBeenCalledWith('/users/1')
      expect(result).toEqual(mockData)
    })
  })

  describe('getStats', () => {
    it('should fetch user statistics', async () => {
      const mockStats = { total: 10, admin: 2, manager: 3, inspector: 4, staff: 1 }
      api.get.mockResolvedValue({ data: { data: mockStats } })

      const result = await userService.getStats()

      expect(api.get).toHaveBeenCalledWith('/users/stats')
      expect(result).toEqual(mockStats)
    })
  })

  // ===== UPDATE =====
  describe('updateUser', () => {
    it('should update user name', async () => {
      const updateData = { name: 'New Name' }
      api.put.mockResolvedValue({ data: { data: updateData } })

      await userService.updateUser(1, updateData)

      expect(api.put).toHaveBeenCalledWith('/users/1', updateData)
    })

    it('should update user role', async () => {
      const updateData = { role: 'manager' }
      api.put.mockResolvedValue({ data: { data: updateData } })

      await userService.updateUser(1, updateData)

      expect(api.put).toHaveBeenCalledWith('/users/1', updateData)
    })

    it('should update user email', async () => {
      const updateData = { email: 'newemail@test.com' }
      api.put.mockResolvedValue({ data: { data: updateData } })

      await userService.updateUser(1, updateData)

      expect(api.put).toHaveBeenCalledWith('/users/1', updateData)
    })

    it('should handle update error', async () => {
      api.put.mockRejectedValue({ response: { data: { message: 'Email đã tồn tại' } } })

      await expect(userService.updateUser(1, { email: 'exists@test.com' })).rejects.toEqual({ response: { data: { message: 'Email đã tồn tại' } } })
    })
  })

  // ===== DELETE =====
  describe('deleteUser', () => {
    it('should delete user successfully', async () => {
      api.delete.mockResolvedValue({ data: { message: 'Xóa thành công' } })

      await userService.deleteUser(1)

      expect(api.delete).toHaveBeenCalledWith('/users/1')
    })

    it('should handle delete self error', async () => {
      api.delete.mockRejectedValue({ response: { data: { message: 'Không thể xóa tài khoản của chính bạn' } } })

      await expect(userService.deleteUser(1)).rejects.toEqual({ response: { data: { message: 'Không thể xóa tài khoản của chính bạn' } } })
    })
  })
})
