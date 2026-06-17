<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header v-if="showNav" class="bg-white shadow-sm sticky top-0 z-40 print:hidden">
      <div class="flex items-center gap-2 min-h-[3.5rem] px-3 md:px-4 max-w-7xl mx-auto">
        <!-- Left: hamburger (<sm) + brand -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <button @click="drawerOpen = true" class="sm:hidden p-2 rounded-lg hover:bg-gray-100" aria-label="Menu">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <span class="font-bold text-blue-700 text-sm md:text-base whitespace-nowrap">
            <span class="hidden sm:inline">OrthoPlus</span>
            <span class="sm:hidden">OP</span>
          </span>
          <span class="text-xs text-gray-400 hidden lg:inline">Clinic Management</span>
        </div>

        <!-- Center: nav from sm up, role-filtered. Single row — scrolls if too wide (never wraps). -->
        <nav class="hidden sm:flex flex-nowrap items-center justify-center gap-1 flex-1 min-w-0 overflow-x-auto scrollbar-hide">
          <router-link
            v-for="link in desktopLinks"
            :key="link.to"
            :to="link.to"
            class="nav-pill"
            active-class="nav-pill-active"
          >{{ link.label }}</router-link>
        </nav>

        <!-- Right: Direct Doctor + role + avatar -->
        <div class="flex items-center gap-1 md:gap-2 flex-shrink-0 ml-auto sm:ml-0">
          <router-link
            v-if="directDoctor"
            :to="directDoctor.to"
            class="bg-blue-600 hover:bg-blue-700 text-white text-xs lg:text-sm px-2 lg:px-3 py-1.5 rounded-lg font-medium whitespace-nowrap flex-shrink-0"
          >
            <span class="hidden lg:inline">Direct Doctor</span>
            <span class="lg:hidden">DD</span>
          </router-link>
          <span v-if="auth.role" class="hidden lg:inline text-xs bg-gray-100 px-2 py-1 rounded-full uppercase flex-shrink-0">{{ auth.role }}</span>
          <button @click="logout" :title="auth.user?.name + ' — logout'"
                  class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center hover:bg-blue-200">
            {{ userInitials }}
          </button>
        </div>
      </div>
    </header>

    <!-- Page content (extra bottom padding on mobile to clear bottom nav) -->
    <main :class="showNav ? 'max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6 pb-20 sm:pb-6' : ''">
      <router-view />
    </main>

    <!-- Mobile bottom nav -->
    <nav v-if="showNav" class="sm:hidden fixed bottom-0 inset-x-0 z-40 flex overflow-x-auto border-t bg-white print:hidden">
      <router-link
        v-for="link in bottomLinks"
        :key="link.to"
        :to="link.to"
        class="mobile-nav-item"
        active-class="text-blue-600"
      >
        <span class="text-lg leading-none">{{ link.icon }}</span>
        <span>{{ link.short }}</span>
      </router-link>
      <button @click="drawerOpen = true" class="mobile-nav-item">
        <span class="text-lg leading-none">☰</span>
        <span>More</span>
      </button>
    </nav>

    <!-- Mobile drawer (teleported) -->
    <teleport to="body">
      <div v-if="showNav && drawerOpen" @click="drawerOpen = false"
           class="fixed inset-0 bg-black/40 z-50 sm:hidden print:hidden"></div>
      <transition name="drawer">
        <aside v-if="showNav && drawerOpen"
               class="fixed left-0 top-0 bottom-0 w-72 max-w-[80vw] bg-white shadow-xl z-50 sm:hidden print:hidden overflow-y-auto flex flex-col">
          <div class="p-4 border-b flex justify-between items-center">
            <span class="font-bold text-blue-700">OrthoPlus</span>
            <button @click="drawerOpen = false" class="w-10 h-10 flex items-center justify-center rounded hover:bg-gray-100" aria-label="Close">✕</button>
          </div>
          <nav class="p-3 space-y-1 flex-1">
            <router-link
              v-for="link in visibleLinks"
              :key="link.to"
              :to="link.to"
              @click="drawerOpen = false"
              class="drawer-link"
              :class="link.highlight ? 'font-bold text-blue-600' : ''"
            >{{ link.label }}</router-link>
          </nav>
          <div v-if="auth.user" class="p-4 border-t text-sm">
            <div class="text-gray-700 mb-2">{{ auth.user.name }} · <span class="uppercase text-gray-400">{{ auth.role }}</span></div>
            <button @click="logout" class="w-full text-left text-blue-600 hover:underline py-2">Logout</button>
          </div>
        </aside>
      </transition>
    </teleport>
  </div>
</template>

<script setup>
import { computed, ref, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const drawerOpen = ref(false);

// Refresh user on load so page_access is present for older cached sessions.
onMounted(() => { if (auth.isAuthenticated) auth.fetchMe(); });

watch(() => route.path, () => { drawerOpen.value = false; });

// page = page_access key (drives visibility via role page_access).
// bottom + icon + short = shown in the mobile bottom bar.
const links = [
  { label: 'Dashboard',     to: '/',              page: 'dashboard',     icon: '🏠', short: 'Home',     bottom: true },
  { label: 'Patients',      to: '/patients',      page: 'patients',      icon: '👤', short: 'Patients', bottom: true },
  { label: 'Appointments',  to: '/appointments',  page: 'appointments',  icon: '📅', short: 'Appts',    bottom: true },
  { label: 'Inventory',     to: '/inventory',     page: 'inventory',     bp: 'lg' },
  { label: 'Pharmacy',      to: '/pharmacy',      page: 'pharmacy',      bp: 'lg', icon: '💊', short: 'Pharma', bottom: true },
  { label: 'Settings',      to: '/settings',      page: 'settings',      bp: 'xl' },
  { label: 'Direct Doctor', to: '/doctor-direct', page: 'direct-doctor', highlight: true },
];

const showNav = computed(() => route.path !== '/login' && route.path !== '/unauthorized' && auth.isAuthenticated);
const allowed = (l) => auth.canAccess(l.page);

const visibleLinks = computed(() => links.filter(allowed));
const desktopLinks = computed(() => visibleLinks.value.filter((l) => !l.highlight));
const directDoctor = computed(() => visibleLinks.value.find((l) => l.to === '/doctor-direct'));
const bottomLinks = computed(() => visibleLinks.value.filter((l) => l.bottom).slice(0, 4));

const userInitials = computed(() => {
  const n = auth.user?.name?.trim() || '';
  return n ? n.split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase() : '?';
});

function bpClass(bp) {
  if (bp === 'lg') return 'hidden lg:inline-flex';
  if (bp === 'xl') return 'hidden xl:inline-flex';
  return 'inline-flex';
}

async function logout() {
  await auth.logout();
  router.replace('/login');
}
</script>

<style scoped>
@reference "tailwindcss";
.nav-pill { @apply px-2 lg:px-3 py-1.5 rounded-lg text-xs lg:text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 whitespace-nowrap transition-colors flex-shrink-0; }
.nav-pill-active, .nav-pill.router-link-active { @apply bg-blue-50 text-blue-700 font-medium; }
.drawer-link { @apply block px-4 py-3 rounded-lg text-gray-700 hover:bg-blue-50 hover:text-blue-700; }
.drawer-link.router-link-active { @apply bg-blue-50 text-blue-700; }
.mobile-nav-item { @apply flex-1 flex flex-col items-center justify-center py-2 text-[11px] text-gray-600 gap-0.5 min-h-[3.5rem]; }
.drawer-enter-active, .drawer-leave-active { transition: transform 0.2s ease; }
.drawer-enter-from, .drawer-leave-to { transform: translateX(-100%); }
</style>
