<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-foreground">Dashboard</h1>
      <span class="text-sm text-muted-foreground">{{ today }}</span>
    </div>

    <!-- KPI Cards (skeleton while loading) -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
      <Card v-for="i in 4" :key="i" class="p-4 space-y-2">
        <Skeleton class="h-6 w-6" />
        <Skeleton class="h-6 w-16" />
        <Skeleton class="h-3 w-20" />
      </Card>
    </div>
    <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
      <Card v-for="card in kpiCards" :key="card.label" class="p-4">
        <div class="text-2xl mb-1">{{ card.icon }}</div>
        <div class="text-2xl font-bold text-foreground">{{ card.value }}</div>
        <div class="text-xs text-muted-foreground mt-1">{{ card.label }}</div>
      </Card>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <router-link v-for="a in quickActions" :key="a.to" :to="a.to"
        class="flex items-center gap-2 p-3 rounded-xl bg-accent text-accent-foreground hover:bg-accent/80 text-sm font-medium transition-colors">
        {{ a.label }}
      </router-link>
    </div>

    <!-- Today's appointments -->
    <Card>
      <CardHeader class="py-4">
        <CardTitle class="text-base">Today's Appointments</CardTitle>
      </CardHeader>
      <div class="divide-y divide-border border-t border-border">
        <div v-if="!stats.today_appointments?.length" class="px-5 py-6 text-center text-muted-foreground text-sm">No appointments today</div>
        <div
          v-for="apt in stats.today_appointments"
          :key="apt.id"
          class="px-5 py-3 flex items-center justify-between hover:bg-muted/50"
        >
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-accent flex items-center justify-center text-accent-foreground font-semibold text-sm">
              {{ apt.patient?.name?.charAt(0) }}
            </div>
            <div>
              <div class="font-medium text-sm text-foreground">{{ apt.patient?.name }}</div>
              <div class="text-xs text-muted-foreground">{{ apt.patient?.op_number }} · {{ apt.scheduled_time }}</div>
            </div>
          </div>
          <Badge :variant="statusVariant(apt.status)" class="capitalize">{{ apt.status }}</Badge>
        </div>
      </div>
    </Card>

    <!-- Low stock alert -->
    <Card v-if="stats.low_stock_medicines?.length" class="border-destructive/30 bg-destructive/5 p-4">
      <h3 class="font-semibold text-destructive mb-2">Low Stock Alert</h3>
      <div class="flex flex-wrap gap-2">
        <Badge v-for="med in stats.low_stock_medicines" :key="med.id" variant="destructive">
          {{ med.name }} — {{ med.quantity_in_stock }} left
        </Badge>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

const stats = ref({});
const loading = ref(true);

const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const quickActions = [
  { to: '/patients', label: '➕ New Patient' },
  { to: '/appointments', label: '📅 Appointments' },
  { to: '/doctor-direct', label: '👨‍⚕️ Direct Doctor' },
  { to: '/pharmacy', label: '💊 Pharmacy' },
];

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

function statusVariant(status) {
  return {
    scheduled: 'default',
    completed: 'success',
    cancelled: 'secondary',
    no_show: 'destructive',
  }[status] || 'secondary';
}
</script>
