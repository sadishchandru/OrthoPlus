import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    { path: '/login',         component: () => import('./pages/LoginPage.vue'),   meta: { public: true } },
    { path: '/unauthorized',  component: () => import('./pages/Unauthorized.vue'), meta: { public: true } },

    { path: '/',             component: () => import('./pages/Dashboard.vue'),        meta: { page: 'dashboard' } },
    { path: '/patients',     component: () => import('./pages/Patients.vue'),         meta: { page: 'patients' } },
    { path: '/patients/:id', component: () => import('./pages/PatientDetail.vue'),    meta: { page: 'patients' } },
    { path: '/appointments', component: () => import('./pages/Appointments.vue'),     meta: { page: 'appointments' } },
    { path: '/inventory',    component: () => import('./pages/Inventory.vue'),        meta: { page: 'inventory' } },
    { path: '/doctor-direct',component: () => import('./pages/DirectDoctorMode.vue'), meta: { page: 'direct-doctor' } },
    { path: '/pharmacy',     component: () => import('./pages/PharmacyBilling.vue'),  meta: { page: 'pharmacy' } },
    { path: '/settings',     component: () => import('./pages/Settings.vue'),         meta: { page: 'settings' } },

    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();

    if (to.meta.public) return true;

    if (!auth.isAuthenticated) {
        return { path: '/login', query: { redirect: to.fullPath } };
    }

    // Page-level access from role page_access ('*' = root, sees all).
    if (to.meta.page && !auth.canAccess(to.meta.page)) {
        return { path: '/unauthorized' };
    }

    return true;
});

export default router;
