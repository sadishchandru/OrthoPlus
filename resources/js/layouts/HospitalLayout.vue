<template>
  <div class="min-h-screen bg-slate-100 lg:flex">
    <aside class="hidden lg:flex lg:w-72 lg:flex-col text-white print:hidden" :style="{ background: 'var(--brand-sidebar)' }">
      <div class="px-5 py-5 border-b border-white/10">
        <router-link to="/hospital/dashboard" aria-label="Dashboard"><Logo variant="full" mono class="h-9" /></router-link>
        <div class="text-[10px] text-white/55 mt-1 tracking-wide">HOSPITAL MANAGEMENT</div>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
        <div v-for="group in visibleGroups" :key="group.label">
          <div class="px-3 text-[11px] uppercase tracking-wider text-white/45 font-semibold">{{ group.label }}</div>
          <div class="mt-2 space-y-1">
            <router-link
              v-for="item in group.items"
              :key="item.to"
              :to="item.to"
              class="nav-link"
              active-class="nav-link-active"
            >
              <span class="nav-ic"><HmsIcon :name="item.icon" /></span>
              <span>{{ item.label }}</span>
            </router-link>
          </div>
        </div>
      </nav>
    </aside>

    <div class="min-w-0 flex-1">
      <header class="sticky top-0 z-40 text-white shadow-sm print:hidden" :style="{ background: 'var(--brand-header)' }">
        <div class="min-h-[3.5rem] px-3 sm:px-4 flex items-center gap-3">
          <button @click="drawerOpen = true" class="lg:hidden w-10 h-10 rounded-lg hover:bg-white/10" aria-label="Menu">
            <span class="block w-5 h-0.5 bg-white mx-auto mb-1"></span>
            <span class="block w-5 h-0.5 bg-white mx-auto mb-1"></span>
            <span class="block w-5 h-0.5 bg-white mx-auto"></span>
          </button>

          <router-link to="/hospital/dashboard" class="min-w-0 lg:hidden" aria-label="Dashboard">
            <Logo variant="full" mono class="h-7" />
          </router-link>

          <div class="ml-auto flex items-center gap-2">
            <span v-if="auth.role" class="hidden sm:inline-flex text-xs bg-white/10 px-2 py-1 rounded uppercase">{{ auth.role }}</span>
            <button @click="logout" class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 text-xs font-bold">
              {{ initials }}
            </button>
          </div>
        </div>
      </header>

      <main class="max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6 pb-20 lg:pb-6">
        <router-view />
      </main>
    </div>

    <teleport to="body">
      <div v-if="drawerOpen" @click="drawerOpen = false" class="fixed inset-0 bg-black/40 z-50 lg:hidden print:hidden"></div>
      <transition name="drawer">
        <aside
          v-if="drawerOpen"
          class="fixed left-0 top-0 bottom-0 w-80 max-w-[84vw] text-white z-50 lg:hidden print:hidden overflow-y-auto"
          :style="{ background: 'var(--brand-sidebar)' }"
        >
          <div class="p-4 border-b border-white/10 flex items-center justify-between">
            <router-link to="/hospital/dashboard" aria-label="Dashboard">
              <Logo variant="full" mono class="h-7" />
            </router-link>
            <button @click="drawerOpen = false" class="w-10 h-10 rounded hover:bg-white/10" aria-label="Close">✕</button>
          </div>

          <nav class="px-3 py-4 space-y-5">
            <div v-for="group in visibleGroups" :key="group.label">
              <div class="px-3 text-[11px] uppercase tracking-wider text-white/45 font-semibold">{{ group.label }}</div>
              <div class="mt-2 space-y-1">
                <router-link
                  v-for="item in group.items"
                  :key="item.to"
                  :to="item.to"
                  @click="drawerOpen = false"
                  class="nav-link"
                  active-class="nav-link-active"
                >
                  <span class="nav-ic"><HmsIcon :name="item.icon" /></span>
                  <span>{{ item.label }}</span>
                </router-link>
              </div>
            </div>
          </nav>
        </aside>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Logo from '../components/Logo.vue';
import HmsIcon from '../components/HmsIcon.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const drawerOpen = ref(false);

watch(() => route.path, () => { drawerOpen.value = false; });

const groups = [
  {
    label: 'Overview',
    items: [
      { label: 'Dashboard', to: '/hospital/dashboard', page: 'dashboard', icon: 'dashboard' },
    ],
  },
  {
    label: 'Out-Patients',
    items: [
      { label: 'OPD Registration', to: '/hospital/opd-registration', page: 'opd', icon: 'user-plus' },
      { label: 'OPD Consulting', to: '/hospital/opd-consulting', page: 'opd', icon: 'stethoscope' },
    ],
  },
  {
    label: 'In-Patients',
    items: [
      { label: 'Admissions', to: '/hospital/admissions', page: 'inpatients', icon: 'bed' },
      { label: 'Bed Management', to: '/hospital/beds', page: 'beds', icon: 'layout' },
      { label: 'Discharge', to: '/hospital/discharge', page: 'inpatients', icon: 'exit' },
    ],
  },
  {
    label: 'Surgery',
    items: [
      { label: 'Schedule', to: '/hospital/surgery', page: 'surgery', icon: 'tool' },
      { label: 'Pre-Op Planning', to: '/hospital/pre-op', page: 'surgery', icon: 'clipboard' },
      { label: 'Implant Stock', to: '/hospital/implants', page: 'surgery', icon: 'box' },
    ],
  },
  {
    label: 'Operations',
    items: [
      { label: 'Staff', to: '/hospital/staff', page: 'staff', icon: 'users' },
      { label: 'Pharmacy', to: '/hospital/pharmacy', page: 'pharmacy', icon: 'pill' },
      { label: 'Billing', to: '/hospital/billing', page: 'ip-billing', icon: 'receipt' },
      { label: 'Reports', to: '/hospital/reports', page: 'hospital-reports', icon: 'chart' },
      { label: 'Settings', to: '/hospital/settings', page: 'settings', icon: 'settings' },
    ],
  },
];

const visibleGroups = computed(() => groups
  .map((group) => ({ ...group, items: group.items.filter((item) => auth.canAccess(item.page)) }))
  .filter((group) => group.items.length));

const initials = computed(() => {
  const name = auth.user?.name?.trim() || '';
  return name ? name.split(/\s+/).map((part) => part[0]).slice(0, 2).join('').toUpperCase() : '?';
});

async function logout() {
  await auth.logout();
  router.replace('/hospital/login');
}
</script>

<style scoped>
@reference "tailwindcss";
.nav-link { @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-white/75 hover:bg-white/10 hover:text-white transition; }
.nav-link-active, .nav-link.router-link-active { @apply bg-white font-semibold shadow-sm; color: var(--brand-sidebar, #1F5523); }
.nav-ic { @apply shrink-0 opacity-90; }
.nav-ic svg { @apply w-5 h-5; }
.nav-link-active .nav-ic, .nav-link.router-link-active .nav-ic { @apply opacity-100; }
.drawer-enter-active, .drawer-leave-active { transition: transform 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(-100%); }
</style>
