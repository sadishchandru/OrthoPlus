<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
      <span class="text-sm text-gray-500">{{ today }}</span>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div v-for="card in kpiCards" :key="card.label" class="bg-white rounded-xl p-4 border border-gray-200 shadow-sm">
        <div class="text-2xl mb-1">{{ card.icon }}</div>
        <div class="text-2xl font-bold text-gray-900">{{ card.value }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ card.label }}</div>
      </div>
    </div>

    <!-- Today's appointments -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Today's Appointments</h2>
      </div>
      <div class="divide-y divide-gray-100">
        <div v-if="!stats.today_appointments?.length" class="px-5 py-6 text-center text-gray-400 text-sm">No appointments today</div>
        <div
          v-for="apt in stats.today_appointments"
          :key="apt.id"
          class="px-5 py-3 flex items-center justify-between hover:bg-gray-50"
        >
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm">
              {{ apt.patient?.name?.charAt(0) }}
            </div>
            <div>
              <div class="font-medium text-sm text-gray-800">{{ apt.patient?.name }}</div>
              <div class="text-xs text-gray-400">{{ apt.patient?.op_number }} · {{ apt.scheduled_time }}</div>
            </div>
          </div>
          <span :class="statusClass(apt.status)" class="text-xs px-2 py-0.5 rounded-full font-medium">{{ apt.status }}</span>
        </div>
      </div>
    </div>

    <!-- Low stock alert -->
    <div v-if="stats.low_stock_medicines?.length" class="bg-red-50 border border-red-200 rounded-xl p-4">
      <h3 class="font-semibold text-red-800 mb-2">Low Stock Alert</h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="med in stats.low_stock_medicines"
          :key="med.id"
          class="bg-red-100 text-red-700 text-xs px-3 py-1 rounded-full"
        >
          {{ med.name }} — {{ med.quantity_in_stock }} left
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({});
const loading = ref(true);

const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/reports/dashboard');
    stats.value = data;
  } finally {
    loading.value = false;
  }
});

const kpiCards = computed(() => [
  { icon: '👥', label: 'Patients Today', value: stats.value.today_patients ?? '—' },
  { icon: '💰', label: 'Revenue Today', value: stats.value.today_revenue ? '₹' + Number(stats.value.today_revenue).toLocaleString('en-IN') : '—' },
  { icon: '📋', label: 'Pending Invoices', value: stats.value.pending_invoices ?? '—' },
  { icon: '🏥', label: 'Total Patients', value: stats.value.total_patients ?? '—' },
]);

function statusClass(status) {
  return {
    scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-gray-100 text-gray-500',
    no_show: 'bg-red-100 text-red-600',
  }[status] || 'bg-gray-100 text-gray-500';
}
</script>
