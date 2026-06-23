<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Hospital Dashboard</h1>
        <p class="text-xs text-slate-500">Today at a glance</p>
      </div>
      <button @click="load" class="btn-secondary" :disabled="loading">{{ loading ? 'Refreshing...' : 'Refresh' }}</button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="kpi">
        <div class="label">Total Beds</div>
        <div class="value text-slate-800">{{ occupancy.total_beds ?? 0 }}</div>
      </div>
      <div class="kpi">
        <div class="label">Available Beds</div>
        <div class="value text-emerald-600">{{ occupancy.available ?? 0 }}</div>
      </div>
      <div class="kpi">
        <div class="label">Admissions</div>
        <div class="value text-blue-700">{{ census.total ?? 0 }}</div>
      </div>
      <div class="kpi">
        <div class="label">Surgeries Today</div>
        <div class="value text-[#1e3a5f]">{{ surgeries.count ?? 0 }}</div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
      <section class="panel lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-slate-800">Bed Occupancy</h2>
          <span class="text-xs text-slate-500">{{ occupancy.occupancy_pct ?? 0 }}%</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full bg-[#1e3a5f]" :style="{ width: `${occupancy.occupancy_pct ?? 0}%` }"></div>
        </div>
        <div class="mt-4 grid sm:grid-cols-2 gap-2 text-sm">
          <div v-for="(ward, name) in occupancy.by_ward" :key="name" class="flex justify-between rounded-lg bg-slate-50 px-3 py-2">
            <span class="text-slate-600">{{ name }}</span>
            <span class="font-medium text-slate-900">{{ ward.occupied }}/{{ ward.total }}</span>
          </div>
          <p v-if="!Object.keys(occupancy.by_ward || {}).length" class="text-sm text-slate-400">No ward data yet.</p>
        </div>
      </section>

      <section class="panel">
        <h2 class="font-semibold text-slate-800 mb-3">Surgery Status</h2>
        <div class="space-y-2">
          <div v-for="(count, status) in surgeries.by_status" :key="status" class="flex justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
            <span class="capitalize text-slate-600">{{ status }}</span>
            <span class="font-medium text-slate-900">{{ count }}</span>
          </div>
          <p v-if="!Object.keys(surgeries.by_status || {}).length" class="text-sm text-slate-400">No surgeries scheduled today.</p>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const loading = ref(false);
const occupancy = ref({});
const census = ref({});
const surgeries = ref({});

onMounted(load);

async function load() {
  loading.value = true;
  const today = new Date().toISOString().slice(0, 10);
  try {
    const [occ, cen, surg] = await Promise.all([
      axios.get('/api/reports/bed-occupancy'),
      axios.get('/api/reports/ip-census'),
      axios.get('/api/reports/surgery-list', { params: { from: today, to: today } }),
    ]);
    occupancy.value = occ.data;
    census.value = cen.data;
    surgeries.value = surg.data;
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.kpi { @apply bg-white border border-slate-200 rounded-lg p-4; }
.label { @apply text-xs text-slate-500; }
.value { @apply mt-1 text-3xl font-bold; }
.panel { @apply bg-white border border-slate-200 rounded-lg p-4; }
.btn-secondary { @apply bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50; }
</style>
