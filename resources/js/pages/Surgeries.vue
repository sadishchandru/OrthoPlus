<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Surgery & OR</h1>
      <button v-if="tab === 'schedule'" @click="openSurgery" class="btn-primary">+ Schedule Surgery</button>
      <button v-else @click="openImplant" class="btn-primary">+ Add Implant</button>
    </div>

    <div class="flex gap-2 border-b border-gray-200">
      <button v-for="t in tabs" :key="t.key" @click="tab = t.key"
              class="px-3 py-2 text-sm font-medium -mb-px border-b-2"
              :class="tab === t.key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'">
        {{ t.label }}
      </button>
    </div>

    <!-- Schedule -->
    <div v-if="tab === 'schedule'" class="space-y-3">
      <div class="flex flex-wrap gap-2">
        <select v-model="statusFilter" @change="loadSurgeries" class="input">
          <option value="">All statuses</option>
          <option value="scheduled">Scheduled</option>
          <option value="in-progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-xs text-gray-500 uppercase">
              <th class="px-4 py-3 text-left">Date / Time</th>
              <th class="px-4 py-3 text-left">Patient</th>
              <th class="px-4 py-3 text-left">Procedure</th>
              <th class="px-4 py-3 text-center hidden sm:table-cell">OR</th>
              <th class="px-4 py-3 text-center">Status</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in surgeries" :key="s.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-700">{{ fmtDate(s.scheduled_date) }}<div class="text-xs text-gray-400">{{ s.scheduled_time || '' }}</div></td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ s.patient?.name }}<div class="text-xs text-gray-400">{{ s.patient?.op_number }}</div></td>
              <td class="px-4 py-3 text-gray-700">{{ s.surgery_name }}<div class="text-xs text-gray-400 capitalize">{{ s.surgery_type }}</div></td>
              <td class="px-4 py-3 text-center text-gray-600 hidden sm:table-cell">{{ s.or_room || '—' }}</td>
              <td class="px-4 py-3 text-center"><span :class="statusClass(s.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ s.status }}</span></td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <button v-if="s.status === 'scheduled'" @click="setStatus(s, 'in-progress')" class="text-xs text-blue-600 hover:underline mr-2">Start</button>
                <button v-if="s.status === 'in-progress'" @click="setStatus(s, 'completed')" class="text-xs text-green-600 hover:underline">Complete</button>
              </td>
            </tr>
            <tr v-if="!surgeries.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No surgeries.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Implants -->
    <div v-if="tab === 'implants'" class="space-y-3">
      <input v-model="implantQuery" @input="loadImplants" class="input w-full sm:w-72" placeholder="Search implants..." />
      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-xs text-gray-500 uppercase">
              <th class="px-4 py-3 text-left">Implant</th>
              <th class="px-4 py-3 text-left hidden sm:table-cell">Ref / Size</th>
              <th class="px-4 py-3 text-right">Qty</th>
              <th class="px-4 py-3 text-right hidden sm:table-cell">Unit Cost</th>
              <th class="px-4 py-3 text-center">Stock</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="im in implants" :key="im.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-800">{{ im.name }}<div class="text-xs text-gray-400">{{ im.manufacturer }}</div></td>
              <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ im.ref_number || '—' }}<span v-if="im.size"> · {{ im.size }}</span></td>
              <td class="px-4 py-3 text-right font-semibold" :class="im.quantity <= im.reorder_level ? 'text-red-600' : 'text-gray-700'">{{ im.quantity }}</td>
              <td class="px-4 py-3 text-right text-gray-700 hidden sm:table-cell">₹{{ im.unit_cost }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="im.quantity <= im.reorder_level ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'" class="text-xs px-2 py-0.5 rounded-full font-medium">
                  {{ im.quantity <= im.reorder_level ? 'Low' : 'OK' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right"><button @click="openAdjust(im)" class="text-xs text-blue-600 hover:underline">Adjust</button></td>
            </tr>
            <tr v-if="!implants.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No implants.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Schedule surgery modal -->
    <Modal v-if="showSurgery" title="Schedule Surgery" @close="showSurgery = false">
      <div class="space-y-3">
        <div v-if="!sForm.patient_id">
          <label class="lbl">Search Patient</label>
          <input v-model="search" @input="searchPatients" class="input w-full" placeholder="Name / OP no..." />
          <div v-if="results.length" class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <button v-for="p in results" :key="p.id" @mousedown.prevent="selectPatient(p)" class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-100 last:border-0">
              {{ p.name }} <span class="text-gray-400">{{ p.op_number }}</span>
            </button>
          </div>
        </div>
        <div v-else class="flex items-center justify-between p-2 bg-blue-50 rounded">
          <span class="text-sm font-medium text-blue-800">{{ sForm.patientName }}</span>
          <button @click="sForm.patient_id = null" class="text-gray-400 hover:text-red-500">✕</button>
        </div>
        <div><label class="lbl">Procedure Name</label><input v-model="sForm.surgery_name" class="input w-full" placeholder="e.g. Total Knee Replacement" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Type</label>
            <select v-model="sForm.surgery_type" class="input w-full">
              <option value="elective">Elective</option><option value="emergency">Emergency</option><option value="revision">Revision</option>
            </select>
          </div>
          <div><label class="lbl">OR Room</label><input v-model="sForm.or_room" class="input w-full" /></div>
          <div><label class="lbl">Date</label><input v-model="sForm.scheduled_date" type="date" class="input w-full" /></div>
          <div><label class="lbl">Time</label><input v-model="sForm.scheduled_time" type="time" class="input w-full" /></div>
        </div>
        <div><label class="lbl">Pre-op Instructions</label><textarea v-model="sForm.pre_op_instructions" rows="2" class="input w-full"></textarea></div>
      </div>
      <template #footer>
        <button @click="showSurgery = false" class="btn-secondary">Cancel</button>
        <button @click="submitSurgery" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Schedule' }}</button>
      </template>
    </Modal>

    <!-- Implant modal -->
    <Modal v-if="showImplant" title="Add Implant" @close="showImplant = false">
      <div class="space-y-3">
        <div><label class="lbl">Name</label><input v-model="iForm.name" class="input w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="lbl">Manufacturer</label><input v-model="iForm.manufacturer" class="input w-full" /></div>
          <div><label class="lbl">Ref Number</label><input v-model="iForm.ref_number" class="input w-full" /></div>
          <div><label class="lbl">Size</label><input v-model="iForm.size" class="input w-full" /></div>
          <div>
            <label class="lbl">Side</label>
            <select v-model="iForm.side" class="input w-full">
              <option :value="null">—</option><option value="left">Left</option><option value="right">Right</option><option value="bilateral">Bilateral</option>
            </select>
          </div>
          <div><label class="lbl">Quantity</label><input v-model.number="iForm.quantity" type="number" min="0" class="input w-full" /></div>
          <div><label class="lbl">Reorder Level</label><input v-model.number="iForm.reorder_level" type="number" min="0" class="input w-full" /></div>
          <div><label class="lbl">Unit Cost (₹)</label><input v-model.number="iForm.unit_cost" type="number" min="0" class="input w-full" /></div>
          <div><label class="lbl">Selling Price (₹)</label><input v-model.number="iForm.selling_price" type="number" min="0" class="input w-full" /></div>
        </div>
      </div>
      <template #footer>
        <button @click="showImplant = false" class="btn-secondary">Cancel</button>
        <button @click="submitImplant" :disabled="saving" class="btn-primary">Save</button>
      </template>
    </Modal>

    <!-- Adjust implant modal -->
    <Modal v-if="showAdjust" title="Adjust Implant Stock" @close="showAdjust = false">
      <p class="text-sm text-gray-600 mb-3">{{ activeImplant?.name }} — current: {{ activeImplant?.quantity }}</p>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="lbl">Change (+/-)</label><input v-model.number="adjustForm.delta" type="number" class="input w-full" /></div>
        <div><label class="lbl">Reason</label><input v-model="adjustForm.reason" class="input w-full" /></div>
      </div>
      <template #footer>
        <button @click="showAdjust = false" class="btn-secondary">Cancel</button>
        <button @click="submitAdjust" :disabled="saving" class="btn-primary">Save</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Modal from '../components/HModal.vue';

const toast = useToast();
const tab = ref('schedule');
const tabs = [{ key: 'schedule', label: 'OR Schedule' }, { key: 'implants', label: 'Implants' }];
const saving = ref(false);

const surgeries = ref([]);
const statusFilter = ref('');
const implants = ref([]);
const implantQuery = ref('');

onMounted(() => { loadSurgeries(); loadImplants(); });

async function loadSurgeries() {
  const { data } = await axios.get('/api/surgeries', { params: { status: statusFilter.value, per_page: 50 } });
  surgeries.value = data.data || data;
}
async function loadImplants() {
  const { data } = await axios.get('/api/implants', { params: { q: implantQuery.value } });
  implants.value = data;
}
async function setStatus(s, status) {
  try { await axios.put(`/api/surgeries/${s.id}`, { status }); loadSurgeries(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
}

// patient search shared
const search = ref('');
const results = ref([]);
let t = null;
function searchPatients() {
  clearTimeout(t);
  t = setTimeout(async () => {
    if (!search.value) { results.value = []; return; }
    const { data } = await axios.get('/api/patients/search', { params: { q: search.value } });
    results.value = data;
  }, 300);
}

// schedule surgery
const showSurgery = ref(false);
const sForm = reactive({ patient_id: null, patientName: '', surgery_name: '', surgery_type: 'elective', or_room: '', scheduled_date: new Date().toISOString().slice(0, 10), scheduled_time: '', pre_op_instructions: '' });
function openSurgery() {
  Object.assign(sForm, { patient_id: null, patientName: '', surgery_name: '', surgery_type: 'elective', or_room: '', scheduled_date: new Date().toISOString().slice(0, 10), scheduled_time: '', pre_op_instructions: '' });
  results.value = []; search.value = ''; showSurgery.value = true;
}
function selectPatient(p) { sForm.patient_id = p.id; sForm.patientName = `${p.name} (${p.op_number})`; results.value = []; }
async function submitSurgery() {
  if (!sForm.patient_id || !sForm.surgery_name) { toast.error('Patient and procedure required.'); return; }
  saving.value = true;
  try { await axios.post('/api/surgeries', sForm); toast.success('Surgery scheduled.'); showSurgery.value = false; loadSurgeries(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

// implant CRUD
const showImplant = ref(false);
const iForm = reactive({ name: '', manufacturer: '', ref_number: '', size: '', side: null, quantity: 0, reorder_level: 0, unit_cost: 0, selling_price: 0 });
function openImplant() {
  Object.assign(iForm, { name: '', manufacturer: '', ref_number: '', size: '', side: null, quantity: 0, reorder_level: 0, unit_cost: 0, selling_price: 0 });
  showImplant.value = true;
}
async function submitImplant() {
  if (!iForm.name) { toast.error('Name required.'); return; }
  saving.value = true;
  try { await axios.post('/api/implants', iForm); toast.success('Implant added.'); showImplant.value = false; loadImplants(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

const showAdjust = ref(false);
const activeImplant = ref(null);
const adjustForm = reactive({ delta: 0, reason: '' });
function openAdjust(im) { activeImplant.value = im; Object.assign(adjustForm, { delta: 0, reason: '' }); showAdjust.value = true; }
async function submitAdjust() {
  saving.value = true;
  try { await axios.post(`/api/implants/${activeImplant.value.id}/adjust`, adjustForm); toast.success('Stock adjusted.'); showAdjust.value = false; loadImplants(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }
function statusClass(s) {
  return {
    scheduled: 'bg-blue-100 text-blue-700', 'in-progress': 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700', cancelled: 'bg-gray-100 text-gray-500',
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
