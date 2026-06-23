<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">In-Patients</h1>
      <button @click="openAdmit" class="btn-primary flex-shrink-0">+ Admit Patient</button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Currently Admitted</div>
        <div class="text-2xl font-bold text-blue-700">{{ occupancy.occupied ?? '—' }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Available Beds</div>
        <div class="text-2xl font-bold text-green-600">{{ occupancy.available ?? '—' }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Total Beds</div>
        <div class="text-2xl font-bold text-gray-700">{{ occupancy.total_beds ?? '—' }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-3">
        <div class="text-xs text-gray-500">Occupancy</div>
        <div class="text-2xl font-bold text-gray-700">{{ occupancy.occupancy_pct ?? 0 }}%</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
              class="px-3 py-2 text-sm font-medium -mb-px border-b-2"
              :class="tab === t.key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
        {{ t.label }}
      </button>
    </div>

    <!-- Admissions list -->
    <div v-if="tab === 'admissions'" class="space-y-3">
      <div class="flex flex-wrap gap-2">
        <select v-model="statusFilter" @change="loadAdmissions" class="input">
          <option value="admitted">Admitted</option>
          <option value="discharged">Discharged</option>
          <option value="">All</option>
        </select>
      </div>
      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-xs text-gray-500 uppercase">
              <th class="text-left px-4 py-3">IP No.</th>
              <th class="text-left px-4 py-3">Patient</th>
              <th class="text-left px-4 py-3 hidden md:table-cell">Ward / Bed</th>
              <th class="text-left px-4 py-3 hidden sm:table-cell">Admitted</th>
              <th class="text-center px-4 py-3">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in admissions" :key="a.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ a.ip_number }}</td>
              <td class="px-4 py-3 font-medium text-gray-800">
                {{ a.patient?.name }}
                <div class="text-xs text-gray-400">{{ a.patient?.op_number }}</div>
              </td>
              <td class="px-4 py-3 text-gray-600 hidden md:table-cell">
                {{ a.ward?.name || '—' }}<span v-if="a.bed"> · {{ a.bed.bed_number }}</span>
              </td>
              <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ fmtDate(a.admission_date) }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="statusClass(a.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ a.status }}</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <template v-if="a.status === 'admitted'">
                  <button @click="openTransfer(a)" class="text-xs text-blue-600 hover:underline mr-3">Transfer</button>
                  <button @click="openDischarge(a)" class="text-xs text-red-600 hover:underline">Discharge</button>
                </template>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
            </tr>
            <tr v-if="!admissions.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No admissions.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Bed board -->
    <div v-if="tab === 'beds'" class="space-y-4">
      <div v-for="w in wards" :key="w.id" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <span class="font-semibold text-gray-800">{{ w.name }}</span>
            <span class="text-xs text-gray-400 ml-2 capitalize">{{ w.type }}</span>
          </div>
          <span class="text-xs text-gray-500">{{ w.available_beds_count }}/{{ w.beds_count }} free</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <span v-for="b in (bedsByWard[w.id] || [])" :key="b.id"
                :title="b.bed_number + ' — ' + b.status"
                :class="bedClass(b.status)"
                class="w-16 h-14 rounded-lg flex flex-col items-center justify-center text-xs font-medium border">
            <span class="text-lg">🛏️</span>
            <span>{{ b.bed_number }}</span>
          </span>
          <span v-if="!(bedsByWard[w.id] || []).length" class="text-xs text-gray-400">No beds in this ward.</span>
        </div>
      </div>
      <p v-if="!wards.length" class="text-center text-gray-400 py-6">No wards yet. Add one in the Wards tab.</p>
    </div>

    <!-- Wards management -->
    <div v-if="tab === 'wards'" class="space-y-3">
      <div class="flex justify-end gap-2">
        <button @click="showWard = true" class="btn-secondary">+ Ward</button>
        <button @click="showBed = true" class="btn-secondary">+ Bed</button>
      </div>
      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-xs text-gray-500 uppercase">
              <th class="text-left px-4 py-3">Ward</th>
              <th class="text-left px-4 py-3">Type</th>
              <th class="text-left px-4 py-3 hidden sm:table-cell">Floor</th>
              <th class="text-right px-4 py-3">Beds</th>
              <th class="text-right px-4 py-3">Occupied</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="w in wards" :key="w.id" class="border-t border-gray-100">
              <td class="px-4 py-3 font-medium text-gray-800">{{ w.name }}</td>
              <td class="px-4 py-3 text-gray-600 capitalize">{{ w.type }}</td>
              <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ w.floor || '—' }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ w.beds_count }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ w.occupied_beds_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Admit modal -->
    <Modal v-if="showAdmit" title="Admit Patient" @close="showAdmit = false">
      <div class="space-y-3">
        <div v-if="!admitForm.patient_id">
          <label class="lbl">Search Patient</label>
          <input v-model="patientSearch" @input="searchPatients" class="input w-full" placeholder="Name / phone / OP no..." />
          <div v-if="patientResults.length" class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <button v-for="p in patientResults" :key="p.id" @mousedown.prevent="selectPatient(p)"
                    class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-100 last:border-0">
              {{ p.name }} <span class="text-gray-400">{{ p.op_number }} · {{ p.phone }}</span>
            </button>
          </div>
        </div>
        <div v-else class="flex items-center justify-between p-2 bg-blue-50 rounded">
          <span class="text-sm font-medium text-blue-800">{{ admitForm.patientName }}</span>
          <button @click="admitForm.patient_id = null" class="text-gray-400 hover:text-red-500">✕</button>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Admission Date</label>
            <input v-model="admitForm.admission_date" type="date" class="input w-full" />
          </div>
          <div>
            <label class="lbl">Type</label>
            <select v-model="admitForm.admission_type" class="input w-full">
              <option value="elective">Elective</option>
              <option value="emergency">Emergency</option>
            </select>
          </div>
          <div>
            <label class="lbl">Ward</label>
            <select v-model="admitForm.ward_id" @change="loadAvailableBeds" class="input w-full">
              <option :value="null">—</option>
              <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>
          <div>
            <label class="lbl">Bed</label>
            <select v-model="admitForm.bed_id" class="input w-full">
              <option :value="null">—</option>
              <option v-for="b in availableBeds" :key="b.id" :value="b.id">{{ b.bed_number }} (₹{{ b.daily_charge }})</option>
            </select>
          </div>
        </div>
        <div>
          <label class="lbl">Diagnosis</label>
          <textarea v-model="admitForm.diagnosis" rows="2" class="input w-full"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Deposit (₹)</label>
            <input v-model.number="admitForm.deposit_amount" type="number" min="0" class="input w-full" />
          </div>
          <label class="flex items-end gap-2 pb-2 text-sm text-gray-700">
            <input v-model="admitForm.surgery_planned" type="checkbox" class="rounded" /> Surgery planned
          </label>
        </div>
      </div>
      <template #footer>
        <button @click="showAdmit = false" class="btn-secondary">Cancel</button>
        <button @click="submitAdmit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Admit' }}</button>
      </template>
    </Modal>

    <!-- Transfer modal -->
    <Modal v-if="showTransfer" title="Transfer Bed" @close="showTransfer = false">
      <p class="text-sm text-gray-600 mb-3">{{ active?.patient?.name }} — {{ active?.ip_number }}</p>
      <label class="lbl">New Bed</label>
      <select v-model="transferForm.to_bed_id" class="input w-full mb-3">
        <option :value="null">Select bed...</option>
        <option v-for="b in allAvailableBeds" :key="b.id" :value="b.id">{{ b.ward?.name }} · {{ b.bed_number }}</option>
      </select>
      <label class="lbl">Reason</label>
      <input v-model="transferForm.reason" class="input w-full" />
      <template #footer>
        <button @click="showTransfer = false" class="btn-secondary">Cancel</button>
        <button @click="submitTransfer" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Transfer' }}</button>
      </template>
    </Modal>

    <!-- Discharge modal -->
    <Modal v-if="showDischarge" title="Discharge Patient" @close="showDischarge = false">
      <p class="text-sm text-gray-600 mb-3">{{ active?.patient?.name }} — {{ active?.ip_number }}</p>
      <div class="space-y-3">
        <div>
          <label class="lbl">Final Diagnosis</label>
          <textarea v-model="dischargeForm.diagnosis_final" rows="2" class="input w-full"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Condition</label>
            <select v-model="dischargeForm.discharge_condition" class="input w-full">
              <option value="stable">Stable</option>
              <option value="critical">Critical</option>
              <option value="lama">LAMA</option>
              <option value="absconded">Absconded</option>
              <option value="death">Death</option>
            </select>
          </div>
          <div>
            <label class="lbl">Follow-up Date</label>
            <input v-model="dischargeForm.follow_up_date" type="date" class="input w-full" />
          </div>
        </div>
        <div>
          <label class="lbl">Instructions</label>
          <textarea v-model="dischargeForm.discharge_instructions" rows="2" class="input w-full"></textarea>
        </div>
      </div>
      <template #footer>
        <button @click="showDischarge = false" class="btn-secondary">Cancel</button>
        <button @click="submitDischarge" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Discharge' }}</button>
      </template>
    </Modal>

    <!-- Ward modal -->
    <Modal v-if="showWard" title="New Ward" @close="showWard = false">
      <div class="space-y-3">
        <div><label class="lbl">Name</label><input v-model="wardForm.name" class="input w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Type</label>
            <select v-model="wardForm.type" class="input w-full">
              <option>general</option><option>icu</option><option>private</option>
              <option>semi-private</option><option>emergency</option>
            </select>
          </div>
          <div><label class="lbl">Floor</label><input v-model="wardForm.floor" class="input w-full" /></div>
        </div>
      </div>
      <template #footer>
        <button @click="showWard = false" class="btn-secondary">Cancel</button>
        <button @click="submitWard" :disabled="saving" class="btn-primary">Save</button>
      </template>
    </Modal>

    <!-- Bed modal -->
    <Modal v-if="showBed" title="New Bed" @close="showBed = false">
      <div class="space-y-3">
        <div>
          <label class="lbl">Ward</label>
          <select v-model="bedForm.ward_id" class="input w-full">
            <option :value="null">Select ward...</option>
            <option v-for="w in wards" :key="w.id" :value="w.id">{{ w.name }}</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="lbl">Bed Number</label><input v-model="bedForm.bed_number" class="input w-full" /></div>
          <div>
            <label class="lbl">Type</label>
            <select v-model="bedForm.bed_type" class="input w-full">
              <option>standard</option><option>icu</option><option>electric</option>
            </select>
          </div>
        </div>
        <div><label class="lbl">Daily Charge (₹)</label><input v-model.number="bedForm.daily_charge" type="number" min="0" class="input w-full" /></div>
      </div>
      <template #footer>
        <button @click="showBed = false" class="btn-secondary">Cancel</button>
        <button @click="submitBed" :disabled="saving" class="btn-primary">Save</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Modal from '../components/HModal.vue';

const toast = useToast();
const tab = ref('admissions');
const tabs = [
  { key: 'admissions', label: 'Admissions' },
  { key: 'beds', label: 'Bed Board' },
  { key: 'wards', label: 'Wards & Beds' },
];

const admissions = ref([]);
const wards = ref([]);
const beds = ref([]);
const occupancy = ref({});
const statusFilter = ref('admitted');
const saving = ref(false);

const bedsByWard = computed(() => {
  const map = {};
  for (const b of beds.value) (map[b.ward_id] ||= []).push(b);
  return map;
});
const allAvailableBeds = computed(() => beds.value.filter((b) => b.status === 'available'));

onMounted(() => { loadAdmissions(); loadWards(); loadBeds(); loadOccupancy(); });

async function loadAdmissions() {
  const { data } = await axios.get('/api/admissions', { params: { status: statusFilter.value, per_page: 50 } });
  admissions.value = data.data || data;
}
async function loadWards() {
  const { data } = await axios.get('/api/wards');
  wards.value = data;
}
async function loadBeds() {
  const { data } = await axios.get('/api/beds');
  beds.value = data;
}
async function loadOccupancy() {
  const { data } = await axios.get('/api/reports/bed-occupancy');
  occupancy.value = data;
}

// ---- Admit ----
const showAdmit = ref(false);
const patientSearch = ref('');
const patientResults = ref([]);
const availableBeds = ref([]);
const admitForm = reactive({
  patient_id: null, patientName: '', admission_date: new Date().toISOString().slice(0, 10),
  admission_type: 'elective', ward_id: null, bed_id: null, diagnosis: '',
  deposit_amount: 0, surgery_planned: false,
});

function openAdmit() {
  Object.assign(admitForm, {
    patient_id: null, patientName: '', admission_date: new Date().toISOString().slice(0, 10),
    admission_type: 'elective', ward_id: null, bed_id: null, diagnosis: '', deposit_amount: 0, surgery_planned: false,
  });
  patientResults.value = []; patientSearch.value = '';
  showAdmit.value = true;
}
let pTimer = null;
function searchPatients() {
  clearTimeout(pTimer);
  pTimer = setTimeout(async () => {
    if (!patientSearch.value) { patientResults.value = []; return; }
    const { data } = await axios.get('/api/patients/search', { params: { q: patientSearch.value } });
    patientResults.value = data;
  }, 300);
}
function selectPatient(p) {
  admitForm.patient_id = p.id; admitForm.patientName = `${p.name} (${p.op_number})`;
  patientResults.value = [];
}
async function loadAvailableBeds() {
  admitForm.bed_id = null;
  if (!admitForm.ward_id) { availableBeds.value = []; return; }
  const { data } = await axios.get('/api/beds/available', { params: { ward_id: admitForm.ward_id } });
  availableBeds.value = data;
}
async function submitAdmit() {
  if (!admitForm.patient_id) { toast.error('Select a patient.'); return; }
  saving.value = true;
  try {
    await axios.post('/api/admissions', admitForm);
    toast.success('Patient admitted.');
    showAdmit.value = false;
    refresh();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed to admit.'); }
  finally { saving.value = false; }
}

// ---- Transfer ----
const showTransfer = ref(false);
const active = ref(null);
const transferForm = reactive({ to_bed_id: null, reason: '' });
function openTransfer(a) { active.value = a; transferForm.to_bed_id = null; transferForm.reason = ''; showTransfer.value = true; }
async function submitTransfer() {
  if (!transferForm.to_bed_id) { toast.error('Pick a bed.'); return; }
  saving.value = true;
  try {
    await axios.post(`/api/admissions/${active.value.id}/transfer-bed`, transferForm);
    toast.success('Bed transferred.');
    showTransfer.value = false;
    refresh();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed to transfer.'); }
  finally { saving.value = false; }
}

// ---- Discharge ----
const showDischarge = ref(false);
const dischargeForm = reactive({ diagnosis_final: '', discharge_condition: 'stable', follow_up_date: '', discharge_instructions: '' });
function openDischarge(a) {
  active.value = a;
  Object.assign(dischargeForm, { diagnosis_final: a.diagnosis || '', discharge_condition: 'stable', follow_up_date: '', discharge_instructions: '' });
  showDischarge.value = true;
}
async function submitDischarge() {
  saving.value = true;
  try {
    await axios.post(`/api/admissions/${active.value.id}/discharge`, dischargeForm);
    toast.success('Patient discharged.');
    showDischarge.value = false;
    refresh();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed to discharge.'); }
  finally { saving.value = false; }
}

// ---- Wards / Beds ----
const showWard = ref(false);
const wardForm = reactive({ name: '', type: 'general', floor: '' });
async function submitWard() {
  saving.value = true;
  try {
    await axios.post('/api/wards', wardForm);
    toast.success('Ward added.');
    showWard.value = false;
    Object.assign(wardForm, { name: '', type: 'general', floor: '' });
    loadWards();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}
const showBed = ref(false);
const bedForm = reactive({ ward_id: null, bed_number: '', bed_type: 'standard', daily_charge: 0 });
async function submitBed() {
  if (!bedForm.ward_id) { toast.error('Select a ward.'); return; }
  saving.value = true;
  try {
    await axios.post('/api/beds', bedForm);
    toast.success('Bed added.');
    showBed.value = false;
    Object.assign(bedForm, { ward_id: null, bed_number: '', bed_type: 'standard', daily_charge: 0 });
    loadBeds(); loadWards(); loadOccupancy();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function refresh() { loadAdmissions(); loadBeds(); loadWards(); loadOccupancy(); }

// ---- helpers ----
function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }
function statusClass(s) {
  return { admitted: 'bg-green-100 text-green-700', discharged: 'bg-gray-100 text-gray-600', transferred: 'bg-yellow-100 text-yellow-700' }[s] || 'bg-gray-100 text-gray-600';
}
function bedClass(s) {
  return {
    available: 'bg-green-50 border-green-300 text-green-700',
    occupied: 'bg-red-50 border-red-300 text-red-700',
    maintenance: 'bg-gray-100 border-gray-300 text-gray-500',
    reserved: 'bg-yellow-50 border-yellow-300 text-yellow-700',
  }[s] || 'bg-gray-50 border-gray-200';
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
