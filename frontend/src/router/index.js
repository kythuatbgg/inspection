import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue')
  },
  {
    path: '/',
    redirect: (to) => {
      const authStore = useAuthStore()

      if (!authStore.isAuthenticated) {
        return '/login'
      }

      return authStore.isInspector ? '/inspector' : '/admin'
    }
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'manager'] },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('../views/admin/DashboardView.vue')
      },
      {
        path: 'batches',
        name: 'admin-batches',
        component: () => import('../views/admin/BatchesView.vue')
      },
      {
        path: 'batches/:id',
        name: 'admin-batch-detail',
        component: () => import('../views/admin/BatchDetailView.vue')
      },
      {
        path: 'cabinets',
        name: 'admin-cabinets',
        component: () => import('../views/admin/CabinetsView.vue')
      },
      {
        path: 'cabinets/:code',
        name: 'admin-cabinet-detail',
        component: () => import('../views/admin/CabinetDetailView.vue')
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('../views/admin/UsersView.vue')
      },
      {
        path: 'users/:id',
        name: 'admin-user-detail',
        component: () => import('../views/admin/UserDetailView.vue')
      },
      {
        path: 'checklists',
        name: 'admin-checklists',
        component: () => import('../views/admin/ChecklistsView.vue')
      },
      {
        path: 'checklists/:id',
        name: 'admin-checklist-detail',
        component: () => import('../views/admin/ChecklistDetailView.vue')
      },
      {
        path: 'settings',
        name: 'admin-settings',
        component: () => import('../views/admin/SettingsView.vue')
      },
      {
        path: 'profile',
        name: 'admin-profile',
        component: () => import('../views/admin/ProfileView.vue')
      }
    ]
  },
  {
    path: '/batches',
    redirect: '/admin/batches'
  },
  {
    path: '/batches/:id',
    redirect: (to) => `/admin/batches/${to.params.id}`
  },
  {
    path: '/cabinets',
    redirect: '/admin/cabinets'
  },
  {
    path: '/cabinets/:code',
    redirect: (to) => `/admin/cabinets/${to.params.code}`
  },
  {
    path: '/users',
    redirect: '/admin/users'
  },
  {
    path: '/users/:id',
    redirect: (to) => `/admin/users/${to.params.id}`
  },
  {
    path: '/settings',
    redirect: '/admin/settings'
  },
  {
    path: '/inspector',
    component: () => import('../layouts/InspectorLayout.vue'),
    meta: { requiresAuth: true, roles: ['inspector'] },
    children: [
      {
        path: '',
        name: 'inspector-dashboard',
        component: () => import('../views/inspector/DashboardView.vue')
      },
      {
        path: 'tasks',
        name: 'inspector-tasks',
        component: () => import('../views/inspector/TasksView.vue')
      },
      {
        path: 'batch/:id',
        name: 'inspector-batch-detail',
        component: () => import('../views/inspector/BatchDetailView.vue')
      },
      {
        path: 'inspect/:planId',
        name: 'inspector-inspection',
        component: () => import('../views/inspector/InspectionView.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }

  if (to.path === '/login' && authStore.isAuthenticated) {
    next(authStore.isInspector ? '/inspector' : '/admin')
    return
  }

  const allowedRoles = to.meta.roles
  const userRole = authStore.user?.role

  if (allowedRoles?.length && userRole && !allowedRoles.includes(userRole)) {
    next(userRole === 'inspector' ? '/inspector' : '/admin')
    return
  }

  next()
})

export default router
