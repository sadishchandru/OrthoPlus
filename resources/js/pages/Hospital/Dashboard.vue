<template>
  <div class="space-y-5">
    <div class="flex items-end justify-between flex-wrap gap-2">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Hospital Dashboard</h1>
        <p class="text-xs text-slate-500">{{ today }}</p>
      </div>
      <button @click="load" class="text-xs px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium" :disabled="loading">
        {{ loading ? 'Refreshing…' : 'Refresh' }}
      </button>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div v-for="c in cards" :key="c.key" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
        <span :class="tone(c.tone)" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0">
          <HmsIcon :name="c.icon" class="w-5 h-5" />
        </span>
        <div class="min-w-0">
          <div class="text-2xl font-bold text-slate-800 leading-none">
            <span v-if="loading" class="inline-block w-10 h-6 bg-slate-100 rounded animate-pulse"></span>
            <template v-else>{{ c.money ? '₹' + fmt(kpi[c.key]) : (kpi[c.key] ?? 0) }}</template>
          </div>
          <div class="text-xs text-slate-500 mt-1 truncate">{{ c.label }}</div>
        </div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
      <!-- Registration trend chart -->
      <section class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h2 class="font-semibold text-slate-800">Patient Registration Trend</h2>
          <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs">
            <button v-for="p in periods" :key="p.key" @click="period = p.key"
                    :class="period === p.key ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'"
                    class="px-3 py-1 rounded-md font-medium transition">{{ p.label }}</button>
          </div>
        </div>

        <div v-if="loading" class="h-52 bg-slate-50 rounded-lg animate-pulse"></div>
        <div v-else class="relative">
          <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" :style="{ height: '13rem' }" @mouseleave="hover = null">
            <!-- y gridlines + labels -->
            <g v-for="(yv, gi) in yTicks" :key="gi">
              <line :x1="padL" :x2="W - 6" :y1="yPos(yv)" :y2="yPos(yv)" stroke="#eef2f0" stroke-width="1" />
              <text :x="padL - 6" :y="yPos(yv) + 3" text-anchor="end" font-size="9" fill="#94a3b8">{{ yv }}</text>
            </g>
            <!-- bars (equal spacing, animated height) -->
            <g v-for="(d, i) in series" :key="i" @mouseenter="hover = { ...d, i }">
              <rect :x="barX(i)" :width="barW" y="0" :height="H - padB" fill="transparent" />
              <rect class="bar" :x="barX(i)" :y="yPos(d.count)" :width="barW" :height="(H - padB) - yPos(d.count)"
                    rx="4" fill="var(--brand-primary, #2E7D32)" :opacity="hover && hover.i !== i ? 0.55 : 1" />
              <text v-if="d.count" :x="barX(i) + barW / 2" :y="yPos(d.count) - 4" text-anchor="middle" font-size="9" fill="#475569" font-weight="600">{{ d.count }}</text>
              <text :x="barX(i) + barW / 2" :y="H - 6" text-anchor="middle" font-size="9" fill="#94a3b8">{{ d.date }}</text>
            </g>
          </svg>
          <!-- hover tooltip -->
          <div v-if="hover" class="absolute -top-1 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-xs rounded-lg px-2.5 py-1 shadow-lg pointer-events-none">
            {{ hover.date }} · <b>{{ hover.count }}</b> registered
          </div>
        </div>
      </section>

      <!-- Quick actions -->
      <section class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <h2 class="font-semibold text-slate-800 mb-3">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-2">
          <router-link v-for="a in actions" :key="a.to" :to="a.to"
                       class="flex flex-col items-center gap-1.5 p-3 rounded-xl border border-slate-100 hover:border-blue-300 hover:bg-blue-50 transition text-center">
            <HmsIcon :name="a.icon" class="w-5 h-5 text-blue-700" />
            <span class="text-[11px] font-medium text-slate-600">{{ a.label }}</span>
          </router-link>
        </div>
      </section>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
      <!-- Today's queue -->
      <section class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-slate-800">Today's Queue</h2>
          <router-link to="/hospital/opd-consulting" class="text-xs text-blue-700 hover:underline">Open ↗</router-link>
        </div>
        <div v-if="loading" class="space-y-2"><div v-for="i in 4" :key="i" class="h-10 bg-slate-50 rounded-lg animate-pulse"></div></div>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="q in data.today_queue" :key="q.id" class="py-2 flex items-center gap-3">
            <span class="font-mono text-xs font-bold text-blue-700 w-12">#{{ String(q.token_number).padStart(3, '0') }}</span>
            <span class="flex-1 text-sm text-slate-700 truncate">{{ q.patient?.name }}<span class="text-slate-400 text-xs"> · {{ q.patient?.op_number }}</span></span>
            <span :class="statusBadge(q.status)" class="text-[10px] px-2 py-0.5 rounded-full font-medium capitalize">{{ q.status }}</span>
          </li>
          <li v-if="!data.today_queue?.length" class="py-6 text-center text-sm text-slate-400">No tokens today.</li>
        </ul>
      </section>

      <!-- Recent patients -->
      <section class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-slate-800">Recent Patients</h2>
          <router-link to="/hospital/opd-registration" class="text-xs text-blue-700 hover:underline">All ↗</router-link>
        </div>
        <div v-if="loading" class="space-y-2"><div v-for="i in 4" :key="i" class="h-10 bg-slate-50 rounded-lg animate-pulse"></div></div>
        <ul v-else class="divide-y divide-slate-100">
          <li v-for="p in data.recent_patients" :key="p.id" class="py-2 flex items-center gap-3">
            <span class="w-8 h-8 rounded-full bg-blue-50 text-blue-700 text-xs font-bold flex items-center justify-center shrink-0">{{ initials(p.name) }}</span>
            <span class="flex-1 text-sm text-slate-700 truncate">{{ p.name }}<span class="text-slate-400 text-xs"> · {{ p.op_number }}</span></span>
            <span class="text-[11px] text-slate-400">{{ ago(p.created_at) }}</span>
          </li>
          <li v-if="!data.recent_patients?.length" class="py-6 text-center text-sm text-slate-400">No patients yet.</li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import HmsIcon from '../../components/HmsIcon.vue';

const loading = ref(true);
const data = ref({});
const kpi = computed(() => data.value);
const today = new Date().toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const cards = [
  { key: 'new_patients_today', label: 'New Patients Today',  icon: 'user-plus',   tone: 'green' },
  { key: 'revisit_today',      label: 'Revisit Today',        icon: 'stethoscope', tone: 'green' },
  { key: 'total_patients',     label: 'Total Patients',       icon: 'users',       tone: 'slate' },
  { key: 'opd_today',          label: 'OPD Queue',            icon: 'clipboard',   tone: 'amber' },
  { key: 'admitted_now',       label: 'Admissions',           icon: 'bed',         tone: 'green' },
  { key: 'appointments_today', label: "Today's Appointments", icon: 'calendar',    tone: 'green' },
  { key: 'revenue_today',      label: 'Revenue Today',        icon: 'cash',        tone: 'green', money: true },
  { key: 'pending_bills',      label: 'Pending Bills',        icon: 'receipt',     tone: 'red' },
];

const actions = [
  { label: 'New Patient', to: '/hospital/opd-registration', icon: 'user-plus' },
  { label: 'Consulting',  to: '/hospital/opd-consulting',   icon: 'stethoscope' },
  { label: 'Admit',       to: '/hospital/admissions',       icon: 'bed' },
  { label: 'Billing',     to: '/hospital/billing',          icon: 'receipt' },
  { label: 'Reports',     to: '/hospital/reports',          icon: 'chart' },
  { label: 'Beds',        to: '/hospital/beds',             icon: 'layout' },
];

// --- Registration trend chart (daily / weekly / monthly toggle) ---
const periods = [{ key: 'daily', label: 'Daily' }, { key: 'weekly', label: 'Weekly' }, { key: 'monthly', label: 'Monthly' }];
const period = ref('daily');
const hover = ref(null);
const series = computed(() => ({
  daily:   data.value.registration_trend,
  weekly:  data.value.registration_trend_weekly,
  monthly: data.value.registration_trend_monthly,
}[period.value] || []));

const W = 480, H = 200, padL = 26, padB = 22;
function niceMax(m) { if (m <= 5) return 5; const p = Math.pow(10, String(Math.round(m)).length - 1); return Math.ceil(m / p) * p; }
const yMax = computed(() => niceMax(Math.max(1, ...series.value.map((d) => d.count))));
const yTicks = computed(() => [0, Math.round(yMax.value / 2), yMax.value]);
function yPos(v) { return (H - padB) - (v / yMax.value) * (H - padB - 12); }
const slot = computed(() => (W - padL - 6) / Math.max(1, series.value.length));
const barW = computed(() => slot.value * 0.6);
function barX(i) { return padL + slot.value * i + (slot.value * 0.4) / 2; }

onMounted(load);
async function load() {
  loading.value = true;
  try {
    const { data: d } = await axios.get('/api/hospital/reports/dashboard');
    data.value = d;
  } finally { loading.value = false; }
}

function tone(t) {
  return {
    green: 'bg-blue-50 text-blue-700',
    slate: 'bg-slate-100 text-slate-700',
    amber: 'bg-amber-50 text-amber-700',
    red:   'bg-red-50 text-red-600',
  }[t] || 'bg-slate-100 text-slate-700';
}
function statusBadge(s) {
  return {
    waiting: 'bg-amber-100 text-amber-700', 'in-progress': 'bg-blue-100 text-blue-700',
    completed: 'bg-green-200 text-green-900', cancelled: 'bg-red-100 text-red-700',
  }[s] || 'bg-slate-100 text-slate-600';
}
function fmt(n) { return Number(n || 0).toLocaleString('en-IN'); }
function initials(name) { return (name || '?').split(/\s+/).map((w) => w[0]).slice(0, 2).join('').toUpperCase(); }
function ago(d) {
  if (!d) return '';
  const days = Math.floor((Date.now() - new Date(d)) / 864e5);
  return days <= 0 ? 'today' : days === 1 ? '1d' : days + 'd';
}
</script>

<style scoped>
.bar { transition: y .45s cubic-bezier(.4, 0, .2, 1), height .45s cubic-bezier(.4, 0, .2, 1), opacity .15s; }
</style>
