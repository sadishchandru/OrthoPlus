<template>
  <div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900">Appointments</h1>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <AppointmentCalendar />
    </div>
  </div>
</template>

<script setup>
import { defineAsyncComponent, h } from 'vue';
// Lazy-load FullCalendar-backed component (heavy) with a skeleton fallback
const AppointmentCalendar = defineAsyncComponent({
  loader: () => import('../components/AppointmentCalendar.vue'),
  loadingComponent: { render: () => h('div', { class: 'animate-pulse h-96 bg-gray-100 rounded-lg' }) },
});
</script>
