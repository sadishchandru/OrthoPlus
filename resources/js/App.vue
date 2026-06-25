<template>
  <div class="min-h-screen bg-gray-50">
    <header v-if="showNav" class="bg-white shadow-sm sticky top-0 z-40 print:hidden">
      <div class="flex items-center gap-2 min-h-[3.5rem] px-3 md:px-4 max-w-7xl mx-auto">
        <div class="flex items-center gap-2 flex-shrink-0">
          <button @click="drawerOpen = true" class="sm:hidden p-2 rounded-lg hover:bg-gray-100" aria-label="Menu">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <router-link to="/" class="flex items-center" aria-label="OrthoPlus">
            <Logo variant="full" class="h-8 hidden sm:block" />
            <Logo variant="mark" class="h-8 sm:hidden" />
          </router-link>
        </div>

        <nav class="hidden sm:flex flex-nowrap items-center justify-center gap-1 flex-1 min-w-0 overflow-x-auto scrollbar-hide">
          <router-link
            v-for="link in desktopLinks"
            :key="link.to"
            :to="link.to"
            class="nav-pill"
            active-class="nav-pill-active"
          >{{ link.label }}</router-link>
        </nav>

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
          <button @click="logout" :title="`${auth.user?.name || 'User'} - logout`"
                  class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center hover:bg-blue-200">
            {{ userInitials }}
          </button>
        </div>
      </div>
    </header>

    <main :class="showNav ? 'max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-6 pb-20 sm:pb-6' : ''">
      <router-view />
    </main>

    <nav v-if="showNav" class="sm:hidden fixed bottom-0 inset-x-0 z-40 flex overflow-x-auto border-t bg-white print:hidden">
      <router-link
        v-for="link in bottomLinks"
        :key="link.to"
        :to="link.to"
        class="mobile-nav-item"
        active-class="text-blue-600"
      >
        <span class="font-semibold">{{ link.mark }}</span>
        <span>{{ link.short }}</span>
      </router-link>
      <button @click="drawerOpen = true" class="mobile-nav-item">
        <span class="font-semibold">+</span>
        <span>More</span>
      </button>
    </nav>

    <teleport to="body">
      <div v-if="showNav && drawerOpen" @click="drawerOpen = false"
           class="fixed inset-0 bg-black/40 z-50 sm:hidden print:hidden"></div>
      <transition name="drawer">
        <aside v-if="showNav && drawerOpen"
               class="fixed left-0 top-0 bottom-0 w-72 max-w-[80vw] bg-white shadow-xl z-50 sm:hidden print:hidden overflow-y-auto flex flex-col">
          <div class="p-4 border-b flex justify-between items-center">
            <span class="font-bold text-blue-700">OrthoPlus</span>
            <button @click="drawerOpen = false" class="w-10 h-10 flex items-center justify-center rounded hover:bg-gray-100" aria-label="Close">X</button>
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
            <div class="text-gray-700 mb-2">{{ auth.user.name }} <span class="uppercase text-gray-400">{{ auth.role }}</span></div>
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
import Logo from './components/Logo.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const drawerOpen = ref(false);

onMounted(() => { if (auth.isAuthenticated) auth.fetchMe(); });

watch(() => route.path, () => { drawerOpen.value = false; });

const links = [
  { label: 'Dashboard',     to: '/dashboard',      page: 'dashboard',     mark: 'DB', short: 'Home',     bottom: true },
  { label: 'Patients',      to: '/patients',       page: 'patients',      mark: 'PT', short: 'Patients', bottom: true },
  { label: 'Appointments',  to: '/appointments',   page: 'appointments',  mark: 'AP', short: 'Appts',    bottom: true },
  { label: 'Inventory',     to: '/inventory',      page: 'inventory',     mark: 'IN', short: 'Stock' },
  { label: 'Pharmacy',      to: '/pharmacy',       page: 'pharmacy',      mark: 'RX', short: 'Pharma',   bottom: true },
  { label: 'Settings',      to: '/settings',       page: 'settings',      mark: 'SE', short: 'Settings' },
  { label: 'Direct Doctor', to: '/doctor-direct',  page: 'direct-doctor', mark: 'DD', short: 'Doctor', highlight: true },
];

const publicPaths = ['/', '/login', '/clinic/login', '/hospital/login', '/unauthorized'];
const showNav = computed(() => (
  auth.isAuthenticated &&
  !publicPaths.includes(route.path) &&
  !route.path.startsWith('/hospital')
));
const allowed = (link) => auth.canAccess(link.page);

const visibleLinks = computed(() => links.filter(allowed));
const desktopLinks = computed(() => visibleLinks.value.filter((link) => !link.highlight));
const directDoctor = computed(() => visibleLinks.value.find((link) => link.to === '/doctor-direct'));
const bottomLinks = computed(() => visibleLinks.value.filter((link) => link.bottom).slice(0, 4));

const userInitials = computed(() => {
  const name = auth.user?.name?.trim() || '';
  return name ? name.split(/\s+/).map((word) => word[0]).slice(0, 2).join('').toUpperCase() : '?';
});

async function logout() {
  await auth.logout();
  router.replace('/clinic/login');
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
