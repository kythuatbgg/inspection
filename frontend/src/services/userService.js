import api from './api.js'

export const userService = {
  /**
   * Get all users with pagination
   * @param {object} params - Query parameters (search, role, page)
   * @returns {Promise}
   */
  async getUsers(params = {}) {
    const response = await api.get('/users', { params })
    return response.data.data || response.data
  },

  /**
   * Get user by ID
   * @param {number} id - User ID
   * @returns {Promise}
   */
  async getUserById(id) {
    const response = await api.get(`/users/${id}`)
    return response.data.data || response.data
  },

  /**
   * Create new user
   * @param {object} data - User data
   * @returns {Promise}
   */
  async createUser(data) {
    const response = await api.post('/users', data)
    return response.data.data || response.data
  },

  /**
   * Update user
   * @param {number} id - User ID
   * @param {object} data - User data
   * @returns {Promise}
   */
  async updateUser(id, data) {
    const response = await api.put(`/users/${id}`, data)
    return response.data.data || response.data
  },

  /**
   * Delete user
   * @param {number} id - User ID
   * @returns {Promise}
   */
  async deleteUser(id) {
    const response = await api.delete(`/users/${id}`)
    return response.data
  },

  /**
   * Get user statistics
   * @returns {Promise}
   */
  async getStats() {
    const response = await api.get('/users/stats')
    return response.data.data || response.data
  }
}

export default userService
