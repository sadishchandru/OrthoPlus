<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Hospital Reports</h1>
      <div class="flex gap-2 items-center">
        <input v-model="from" type="date" class="input" />
        <span class="text-gray-400">→</span>
        <input v-model="to" type="date" class="input" />
        <button @click="loadAll" class="btn-primary">Refresh</button>
      </div>
    </div>

    <!-- Occupancy + census -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Bed Occupancy</h3>
        <div class="grid grid-cols-3 gap-2 text-center mb-3">
          <div><div class="text-2xl font-bold text-gray-700">{{ occ.total_beds ?? 0 }}</div><div class="text-xs text-gray-500">Total</div></div>
          <div><div class="text-2xl font-bold text-red-600">{{ occ.occupied ?? 0 }}</div><div class="text-xs text-gray-500">Occupied</div></div>
          <div><div class="text-2xl font-bold text-green-600">{{ occ.available ?? 0 }}</div><div class="text-xs text-gray-500">Available</div></div>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2">
          <div class="bg-blue-600 h-2 rounded-full" :style="{ width: (occ.occupancy_pct ?? 0) + '%' }"></div>
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ occ.occupancy_pct ?? 0 }}% occupancy</div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">In-Patient Census <span class="text-xs text-gray-400 font-normal">({{ census.total ?? 0 }} admitted)</span></h3>
        <ul class="space-y-1 text-sm">
          <li v-for="(count, ward) in census.by_ward" :key="ward" class="flex justify-between">
            <span class="text-gray-600">{{ ward }}</span><span class="font-medium text-gray-800">{{ count }}</span>
          </li>
          <li v-if="!census.total" class="text-gray-400">No patients admitted.</li>
        </ul>
      </div>
    </div>

    <!-- Revenue -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <h3 class="font-semibold text-gray-800 mb-3">In-Patient Revenue</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
        <div><div class="text-xl font-bold text-gray-800">₹{{ fmt(rev.total_billed) }}</div><div class="text-xs text-gray-500">Billed</div></div>
        <div><div class="text-xl font-bold text-green-600">₹{{ fmt(rev.total_paid) }}</div><div class="text-xs text-gray-500">Collected</div></div>
        <div><div class="text-xl font-bold text-red-600">₹{{ fmt(rev.total_balance) }}</div><div class="text-xs text-gray-500">Outstanding</div></div>
        <div><div class="text-xl font-bold text-gray-700">{{ rev.bill_count ?? 0 }}</div><div class="text-xs text-gray-500">Bills</div></div>
      </div>
      <div v-if="rev.breakdown" class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
        <span>Room: ₹{{ fmt(rev.breakdown.room) }}</span>
        <span>Pharmacy: ₹{{ fmt(rev.breakdown.pharmacy) }}</span>
        <span>Surgery: ₹{{ fmt(rev.breakdown.surgery) }}</span>
        <span>Implant: ₹{{ fmt(rev.breakdown.implant) }}</span>
        <span>Misc: ₹{{ fmt(rev.breakdown.misc) }}</span>
      </div>
    </div>

    <!-- Surgery + implants -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Surgeries <span class="text-xs text-gray-400 font-normal">({{ surgery.count ?? 0 }})</span></h3>
        <div class="text-sm text-gray-600 mb-2">By status:</div>
        <div class="flex flex-wrap gap-2">
          <span v-for="(c, st) in surgery.by_status" :key="st" class="text-xs bg-gray-100 px-2 py-1 rounded-full capitalize">{{ st }}: {{ c }}</span>
          <span v-if="!surgery.count" class="text-gray-400 text-sm">None in range.</span>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Implant Usage</h3>
        <div class="text-sm text-gray-600 mb-2">{{ implant.total_qty ?? 0 }} units · ₹{{ fmt(implant.total_cost) }}</div>
        <ul class="space-y-1 text-sm max-h-32 overflow-y-auto">
          <li v-for="im in implant.by_implant" :key="im.implant" class="flex justify-between">
            <span class="text-gray-600">{{ im.implant }}</span><span class="text-gray-800">{{ im.qty_used }} · ₹{{ fmt(im.total_cost) }}</span>
          </li>
          <li v-if="!implant.by_implant?.length" class="text-gray-400">No implants used.</li>
        </ul>
      </div>
    </div>

    <!-- Discharges + global periods -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Discharges <span class="text-xs text-gray-400 font-normal">({{ discharge.count ?? 0 }})</span></h3>
        <div class="flex flex-wrap gap-2">
          <span v-for="(c, cond) in discharge.by_condition" :key="cond" class="text-xs bg-gray-100 px-2 py-1 rounded-full capitalize">{{ cond }}: {{ c }}</span>
          <span v-if="!discharge.count" class="text-gray-400 text-sm">None in range.</span>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="font-semibold text-gray-800 mb-3">Active Global Periods <span class="text-xs text-gray-400 font-normal">({{ global.count ?? 0 }})</span></h3>
        <ul class="space-y-1 text-sm max-h-32 overflow-y-auto">
          <li v-for="p in global.periods" :key="p.id" class="flex justify-between">
            <span class="text-gray-600">{{ p.patient?.name }}</span><span class="text-gray-500 text-xs">ends {{ fmtDate(p.global_end_90days) }}</span>
          </li>
          <li v-if="!global.count" class="text-gray-400">No active periods.</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const today = new Date();
const monthAgo = new Date(Date.now() - 30 * 864e5);
const from = ref(monthAgo.toISOString().slice(0, 10));
const to = ref(today.toISOString().slice(0, 10));

const occ = ref({});
const census = ref({});
const rev = ref({});
const surgery = ref({});
const implant = ref({});
const discharge = ref({});
const global = ref({});

onMounted(loadAll);

async function loadAll() {
  const params = { from: from.value, to: to.value };
  const [a, b, c, d, e, f, g] = await Promise.all([
    axios.get('/api/reports/bed-occupancy'),
    axios.get('/api/reports/ip-census'),
    axios.get('/api/reports/revenue-ip', { params }),
    axios.get('/api/reports/surgery-list', { params }),
    axios.get('/api/reports/implant-usage', { params }),
    axios.get('/api/reports/discharge-summary', { params }),
    axios.get('/api/reports/global-periods'),
  ]);
  occ.value = a.data; census.value = b.data; rev.value = c.data;
  surgery.value = d.data; implant.value = e.data; discharge.value = f.data; global.value = g.data;
}

function fmt(n) { return Number(n || 0).toLocaleString('en-IN'); }
function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
</style>
