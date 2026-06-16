<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation (hidden on login screen) -->
    <nav v-if="showNav" class="bg-blue-800 text-white shadow-lg print:hidden">
      <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-xl font-bold">OrthoPlus</span>
          <span class="text-blue-300 text-sm hidden sm:inline">Clinic Management</span>
        </div>

        <div class="flex items-center gap-4 text-sm">
          <router-link
            v-for="link in visibleLinks"
            :key="link.to"
            :to="link.to"
            :class="link.highlight
              ? 'bg-green-600 hover:bg-green-500 px-3 py-1 rounded font-medium transition-colors'
              : 'hover:text-blue-200 transition-colors'"
          >
            {{ link.label }}
          </router-link>

          <!-- User + role badge -->
          <div v-if="auth.user" class="flex items-center gap-2 pl-3 border-l border-blue-600">
            <span class="text-blue-100 hidden md:inline">{{ auth.user.name }}</span>
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-500 uppercase">{{ auth.role }}</span>
            <button @click="logout" class="text-blue-200 hover:text-white text-xs underline">Logout</button>
          </div>
        </div>
      </div>
    </nav>

    <main :class="showNav ? 'max-w-7xl mx-auto px-4 py-6' : ''">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

// label, to, roles (empty = everyone authenticated), highlight
const links = [
  { label: 'Dashboard',     to: '/',            roles: [] },
  { label: 'Patients',      to: '/patients',    roles: ['doctor', 'front_office', 'billing', 'therapist', 'pharmacy'] },
  { label: 'Appointments',  to: '/appointments',roles: ['doctor', 'front_office', 'therapist'] },
  { label: 'Inventory',     to: '/inventory',   roles: ['pharmacy', 'billing'] },
  { label: 'Pharmacy',      to: '/pharmacy',    roles: ['pharmacy'] },
  { label: 'Settings',      to: '/settings',    roles: ['root'] },
  { label: 'Direct Doctor', to: '/doctor-direct', roles: ['doctor'], highlight: true },
];

const showNav = computed(() => route.path !== '/login' && auth.isAuthenticated);

const visibleLinks = computed(() =>
  links.filter((l) => l.roles.length === 0 || auth.hasRole(l.roles))
);

async function logout() {
  await auth.logout();
  router.replace('/login');
}
</script>
