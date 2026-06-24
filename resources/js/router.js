import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const routes = [
    { path: '/',              component: () => import('./pages/PortalSelect.vue'),    meta: { public: true, guest: true } },
    { path: '/login',         redirect: '/clinic/login' },
    { path: '/clinic/login',  component: () => import('./pages/LoginPage.vue'),       props: { module: 'clinic' },   meta: { public: true, guest: true } },
    { path: '/hospital/login',component: () => import('./pages/HospitalLoginPage.vue'), props: { module: 'hospital' }, meta: { public: true, guest: true } },
    { path: '/unauthorized',  component: () => import('./pages/Unauthorized.vue'),    meta: { public: true } },

    { path: '/dashboard',    component: () => import('./pages/Dashboard.vue'),        meta: { page: 'dashboard', module: 'clinic' } },
    { path: '/patients',     component: () => import('./pages/Patients.vue'),         meta: { page: 'patients', module: 'clinic' } },
    { path: '/patients/:id', component: () => import('./pages/PatientDetail.vue'),    meta: { page: 'patients', module: 'clinic' } },
    { path: '/appointments', component: () => import('./pages/Appointments.vue'),     meta: { page: 'appointments', module: 'clinic' } },
    { path: '/inventory',    component: () => import('./pages/Inventory.vue'),        meta: { page: 'inventory', module: 'clinic' } },
    { path: '/doctor-direct',component: () => import('./pages/DirectDoctorMode.vue'), meta: { page: 'direct-doctor', module: 'clinic' } },
    { path: '/pharmacy',     component: () => import('./pages/PharmacyBilling.vue'),  meta: { page: 'pharmacy', module: 'clinic' } },
    { path: '/settings',     component: () => import('./pages/Settings.vue'),         meta: { page: 'settings', module: 'clinic' } },

    { path: '/admissions',       redirect: '/hospital/admissions' },
    { path: '/beds',             redirect: '/hospital/beds' },
    { path: '/opd',              redirect: '/hospital/opd' },
    { path: '/surgeries',        redirect: '/hospital/surgery' },
    { path: '/staff',            redirect: '/hospital/staff' },
    { path: '/imaging',          redirect: '/hospital/imaging' },
    { path: '/ip-billing',       redirect: '/hospital/billing' },
    { path: '/op-orders',        redirect: '/hospital/op-orders' },
    { path: '/hospital-reports', redirect: '/hospital/reports' },

    {
        path: '/hospital',
        component: () => import('./layouts/HospitalLayout.vue'),
        meta: { module: 'hospital' },
        children: [
            { path: '',               redirect: '/hospital/dashboard' },
            { path: 'dashboard',      component: () => import('./pages/Hospital/Dashboard.vue'),                         meta: { page: 'dashboard' } },
            { path: 'admissions',     component: () => import('./pages/Hospital/InPatients/AdmissionList.vue'),           meta: { page: 'inpatients' } },
            { path: 'admissions/new', component: () => import('./pages/Hospital/InPatients/AdmissionForm.vue'),           meta: { page: 'inpatients' } },
            { path: 'beds',           component: () => import('./pages/Hospital/InPatients/BedManagement.vue'),           meta: { page: 'beds' } },
            { path: 'opd',                component: () => import('./pages/Hospital/OPD/OpdConsulting.vue'),              meta: { page: 'opd' } },
            { path: 'opd-registration',   component: () => import('./pages/Hospital/OPD/OpdRegistration.vue'),            meta: { page: 'opd' } },
            { path: 'opd-consulting',     component: () => import('./pages/Hospital/OPD/OpdConsulting.vue'),              meta: { page: 'opd' } },
            { path: 'opd-visits',     redirect: '/hospital/opd-consulting' },
            { path: 'surgery',        component: () => import('./pages/Hospital/Surgery/SurgerySchedule.vue'),            meta: { page: 'surgery' } },
            { path: 'pre-op',         redirect: '/hospital/surgery' },
            { path: 'implants',       redirect: '/hospital/surgery' },
            { path: 'staff',          component: () => import('./pages/Hospital/Staff/StaffList.vue'),                    meta: { page: 'staff' } },
            { path: 'pharmacy',       component: () => import('./pages/PharmacyBilling.vue'),                             meta: { page: 'pharmacy' } },
            { path: 'billing',        component: () => import('./pages/IpBilling.vue'),                                   meta: { page: 'ip-billing' } },
            { path: 'reports',        component: () => import('./pages/Hospital/Reports/Dashboard.vue'),                  meta: { page: 'hospital-reports' } },
            { path: 'settings',       component: () => import('./pages/Settings.vue'),                                    meta: { page: 'settings' } },
            { path: 'imaging',        component: () => import('./pages/Imaging.vue'),                                     meta: { page: 'imaging' } },
            { path: 'op-orders',      component: () => import('./pages/OpOrders.vue'),                                    meta: { page: 'op-devices' } },
            { path: 'discharge',      redirect: '/hospital/admissions' },
        ],
    },

    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore();

    if (to.meta.public) return true;

    const targetModule = to.meta.module || 'clinic';

    if (!auth.isAuthenticated) {
        return {
            path: targetModule === 'hospital' ? '/hospital/login' : '/clinic/login',
            query: { redirect: to.fullPath },
        };
    }

    const userModule = auth.user?.module || 'clinic';
    if (targetModule && userModule !== 'both' && userModule !== targetModule) {
        return { path: targetModule === 'hospital' ? '/clinic/login' : '/hospital/login' };
    }

    if (to.meta.page && !auth.canAccess(to.meta.page)) {
        return { path: '/unauthorized' };
    }

    return true;
});

export default router;
