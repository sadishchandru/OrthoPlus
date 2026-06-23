<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Bed Management</h1>
      <button @click="refresh" class="btn-secondary">↻ Refresh</button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Total Beds</div>
        <div class="text-2xl font-bold text-gray-700">{{ beds.length }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Occupied</div>
        <div class="text-2xl font-bold text-red-600">{{ countBy('occupied') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Available</div>
        <div class="text-2xl font-bold text-green-600">{{ countBy('available') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Out of Service</div>
        <div class="text-2xl font-bold text-gray-500">{{ countBy('maintenance') + countBy('reserved') }}</div>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap gap-4 text-xs text-gray-600">
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-300 border border-green-400"></span> Available</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-300 border border-red-400"></span> Occupied</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-yellow-300 border border-yellow-400"></span> Reserved</span>
      <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-gray-300 border border-gray-400"></span> Maintenance</span>
    </div>

    <!-- Ward grids -->
    <div v-for="w in wards" :key="w.id" class="bg-white rounded-xl border border-gray-200 p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <span class="font-semibold text-gray-800">{{ w.name }}</span>
          <span class="text-xs text-gray-400 ml-2 capitalize">{{ w.type }}</span>
          <span v-if="w.floor" class="text-xs text-gray-400 ml-1">· Floor {{ w.floor }}</span>
        </div>
        <span class="text-xs text-gray-500">{{ wardFree(w.id) }}/{{ (bedsByWard[w.id] || []).length }} free</span>
      </div>
      <div class="grid grid-cols-3 sm:grid-cols-6 md:grid-cols-8 gap-2">
        <button v-for="b in (bedsByWard[w.id] || [])" :key="b.id" @click="openBed(b)"
                :title="b.bed_number + ' — ' + b.status"
                :class="bedClass(b.status)"
                class="aspect-square rounded-lg flex flex-col items-center justify-center text-xs font-medium border transition hover:ring-2 hover:ring-blue-400">
          <span class="text-xl leading-none">🛏️</span>
          <span class="mt-0.5">{{ b.bed_number }}</span>
          <span v-if="occupant(b.id)" class="text-[10px] truncate w-full px-1 text-center opacity-80">{{ occupant(b.id).patient?.name }}</span>
        </button>
        <span v-if="!(bedsByWard[w.id] || []).length" class="text-xs text-gray-400 col-span-full">No beds.</span>
      </div>
    </div>
    <p v-if="!wards.length" class="text-center text-gray-400 py-8">No wards. Add wards + beds under In-Patients → Wards &amp; Beds.</p>

    <!-- Bed detail modal -->
    <Modal v-if="showBed" :title="'Bed ' + (active?.bed_number || '')" @close="showBed = false">
      <div class="space-y-3">
        <div class="flex items-center gap-2">
          <span :class="badgeClass(active?.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ active?.status }}</span>
          <span class="text-xs text-gray-400">₹{{ active?.daily_charge }}/day · {{ active?.bed_type }}</span>
        </div>

        <!-- occupied -->
        <div v-if="activeOccupant" class="p-3 bg-red-50 rounded-lg text-sm">
          <div class="font-medium text-gray-800">{{ activeOccupant.patient?.name }}</div>
          <div class="text-xs text-gray-500">{{ activeOccupant.patient?.op_number }} · {{ activeOccupant.ip_number }}</div>
          <div class="text-xs text-gray-500 mt-1">Admitted {{ fmtDate(activeOccupant.admission_date) }}</div>
          <router-link to="/admissions" class="text-xs text-blue-600 hover:underline">Go to In-Patients →</router-link>
        </div>

        <!-- not occupied → status change -->
        <div v-else>
          <label class="lbl">Set Status</label>
          <select v-model="newStatus" class="input w-full">
            <option value="available">Available</option>
            <option value="reserved">Reserved</option>
            <option value="maintenance">Maintenance</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button @click="showBed = false" class="btn-secondary">Close</button>
        <button v-if="!activeOccupant" @click="saveStatus" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save' }}</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Modal from '../components/HModal.vue';

const toast = useToast();
const wards = ref([]);
const beds = ref([]);
const admissions = ref([]);
const saving = ref(false);

const bedsByWard = computed(() => {
  const m = {};
  for (const b of beds.value) (m[b.ward_id] ||= []).push(b);
  return m;
});
const occByBed = computed(() => {
  const m = {};
  for (const a of admissions.value) if (a.bed_id) m[a.bed_id] = a;
  return m;
});

onMounted(refresh);

async function refresh() {
  const [w, b, a] = await Promise.all([
    axios.get('/api/wards'),
    axios.get('/api/beds'),
    axios.get('/api/admissions', { params: { status: 'admitted', per_page: 200 } }),
  ]);
  wards.value = w.data;
  beds.value = b.data;
  admissions.value = a.data.data || a.data;
}

function countBy(status) { return beds.value.filter((b) => b.status === status).length; }
function wardFree(wid) { return (bedsByWard.value[wid] || []).filter((b) => b.status === 'available').length; }
function occupant(bedId) { return occByBed.value[bedId]; }

const showBed = ref(false);
const active = ref(null);
const newStatus = ref('available');
const activeOccupant = computed(() => active.value ? occByBed.value[active.value.id] : null);

function openBed(b) { active.value = b; newStatus.value = b.status === 'occupied' ? 'available' : b.status; showBed.value = true; }
async function saveStatus() {
  saving.value = true;
  try {
    await axios.put(`/api/beds/${active.value.id}`, { status: newStatus.value });
    toast.success('Bed updated.');
    showBed.value = false;
    refresh();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }
function bedClass(s) {
  return {
    available: 'bg-green-50 border-green-300 text-green-800',
    occupied: 'bg-red-50 border-red-300 text-red-800',
    maintenance: 'bg-gray-100 border-gray-300 text-gray-500',
    reserved: 'bg-yellow-50 border-yellow-300 text-yellow-800',
  }[s] || 'bg-gray-50 border-gray-200';
}
function badgeClass(s) {
  return {
    available: 'bg-green-100 text-green-700', occupied: 'bg-red-100 text-red-700',
    maintenance: 'bg-gray-100 text-gray-600', reserved: 'bg-yellow-100 text-yellow-700',
  }[s] || 'bg-gray-100';
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
