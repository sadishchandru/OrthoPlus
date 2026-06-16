import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    { path: '/login', component: () => import('./pages/LoginPage.vue'), meta: { public: true } },

    { path: '/',                component: () => import('./pages/Dashboard.vue') },
    { path: '/patients',        component: () => import('./pages/Patients.vue'),
        meta: { roles: ['doctor', 'front_office', 'billing', 'therapist', 'pharmacy'] } },
    { path: '/patients/:id',    component: () => import('./pages/PatientDetail.vue'),
        meta: { roles: ['doctor', 'front_office', 'billing', 'therapist'] } },
    { path: '/appointments',    component: () => import('./pages/Appointments.vue'),
        meta: { roles: ['doctor', 'front_office', 'therapist'] } },
    { path: '/inventory',       component: () => import('./pages/Inventory.vue'),
        meta: { roles: ['pharmacy', 'billing'] } },
    { path: '/doctor-direct',   component: () => import('./pages/DirectDoctorMode.vue'),
        meta: { roles: ['doctor'] } },
    { path: '/pharmacy',        component: () => import('./pages/PharmacyBilling.vue'),
        meta: { roles: ['pharmacy'] } },
    { path: '/settings',        component: () => import('./pages/Settings.vue'),
        meta: { roles: ['root'] } },

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

    // root passes everything; otherwise check route roles
    if (to.meta.roles && !auth.hasRole(to.meta.roles)) {
        return { path: '/' };
    }

    return true;
});

export default router;
