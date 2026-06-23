<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Today's OPD Queue</h1>
        <p class="text-xs text-gray-500">{{ prettyDate }} · auto-refresh 30s
          <span class="text-gray-300">·</span> updated {{ lastUpdated }}</p>
      </div>
      <div class="flex gap-2">
        <input v-model="date" @change="load" type="date" class="input" />
        <button @click="openNewPatient" class="btn-secondary flex-shrink-0">+ New Patient</button>
        <button @click="openAdd" class="btn-primary flex-shrink-0">+ Token</button>
      </div>
    </div>

    <div class="flex flex-wrap gap-4 bg-white rounded-xl border border-gray-200 px-4 py-3 text-sm">
      <span><span class="text-gray-500">Issued:</span> <b class="text-gray-800">{{ stats.total ?? 0 }}</b></span>
      <span class="text-gray-300">|</span>
      <span><span class="text-gray-500">Seen:</span> <b class="text-green-600">{{ stats.completed ?? 0 }}</b></span>
      <span class="text-gray-300">|</span>
      <span><span class="text-gray-500">In-progress:</span> <b class="text-blue-600">{{ stats.in_progress ?? 0 }}</b></span>
      <span class="text-gray-300">|</span>
      <span><span class="text-gray-500">Waiting:</span> <b class="text-yellow-600">{{ stats.waiting ?? 0 }}</b></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div v-for="col in columns" :key="col.key">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ col.label }}</h3>
          <span class="text-xs text-gray-400">{{ grouped[col.key].length }}</span>
        </div>
        <div :class="col.bg" class="rounded-xl border p-2 space-y-2 min-h-[6rem]">
          <div
            v-for="q in grouped[col.key]"
            :key="q.id"
            class="bg-white rounded-lg border border-gray-200 p-2.5 shadow-sm"
            :class="q.priority === 'emergency' ? 'ring-1 ring-red-300' : ''"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-gray-800">#{{ pad(q.token_number) }}</span>
              <span
                v-if="q.priority !== 'normal'"
                :class="q.priority === 'emergency' ? 'text-red-600' : 'text-orange-600'"
                class="text-xs font-semibold"
              >
                {{ q.priority === 'emergency' ? 'Emergency' : 'Urgent' }}
              </span>
            </div>
            <div class="text-sm font-medium text-gray-800 mt-0.5">{{ q.patient?.name }}</div>
            <div class="text-xs text-gray-400">{{ q.patient?.op_number }}</div>
            <div class="flex items-center gap-2 mt-2">
              <button v-if="q.status === 'waiting'" @click="setStatus(q, 'in-progress')" class="act text-blue-600">Start</button>
              <button v-if="q.status === 'in-progress'" @click="setStatus(q, 'completed')" class="act text-green-600">Done</button>
              <button @click="printSlip(q)" class="act text-gray-500">Slip</button>
              <button v-if="q.status !== 'completed' && q.status !== 'cancelled'" @click="setStatus(q, 'cancelled')" class="act text-gray-400 ml-auto">Cancel</button>
            </div>
          </div>
          <p v-if="!grouped[col.key].length" class="text-xs text-gray-400 text-center py-4">Empty</p>
        </div>
      </div>
    </div>

    <Modal v-if="showAdd" title="Add to Queue" @close="showAdd = false">
      <div class="space-y-3">
        <div v-if="!form.patient_id">
          <div class="flex items-center justify-between gap-3">
            <label class="lbl mb-0">Search Patient</label>
            <button @click="openNewPatientFromQueue" type="button" class="text-xs font-medium text-blue-600 hover:underline">
              New patient
            </button>
          </div>
          <input v-model="search" @input="searchPatients" class="input w-full" placeholder="Name / phone / OP no..." />
          <div v-if="results.length" class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <button
              v-for="p in results"
              :key="p.id"
              @mousedown.prevent="select(p)"
              class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-100 last:border-0"
            >
              {{ p.name }} <span class="text-gray-400">{{ p.op_number }}</span>
            </button>
          </div>
          <p class="text-xs text-gray-500">Patient not found? Register them here, then return to issue the token.</p>
        </div>
        <div v-else class="flex items-center justify-between p-2 bg-blue-50 rounded">
          <span class="text-sm font-medium text-blue-800">{{ form.patientName }}</span>
          <button @click="clearSelectedPatient" class="text-gray-400 hover:text-red-500">X</button>
        </div>
        <div>
          <label class="lbl">Priority</label>
          <select v-model="form.priority" class="input w-full">
            <option value="normal">Normal</option>
            <option value="urgent">Urgent</option>
            <option value="emergency">Emergency</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button @click="showAdd = false" class="btn-secondary">Cancel</button>
        <button @click="submit" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Add Token' }}</button>
      </template>
    </Modal>

    <div v-if="showNewPatient" class="fixed inset-0 bg-black/50 z-50 flex items-stretch md:items-center justify-center p-0 md:p-4">
      <div class="bg-white rounded-none md:rounded-xl p-4 md:p-6 w-full md:max-w-2xl shadow-xl h-full md:h-auto max-h-screen md:max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h3 class="font-semibold text-gray-900">Register New OPD Patient</h3>
            <p class="text-xs text-gray-500 mt-1">Create the patient record and bring them straight back into the queue flow.</p>
          </div>
          <button @click="showNewPatient = false" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-xl flex-shrink-0">X</button>
        </div>
        <PatientForm
          @created="onPatientCreated"
          @cancel="showNewPatient = false"
          @duplicate-warning="handleDuplicateWarning"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Modal from '../components/HModal.vue';
import PatientForm from '../components/PatientForm.vue';

const toast = useToast();
const date = ref(new Date().toISOString().slice(0, 10));
const queue = ref([]);
const stats = ref({});
const saving = ref(false);
const lastUpdated = ref('-');
const showAdd = ref(false);
const showNewPatient = ref(false);
const search = ref('');
const results = ref([]);
const form = reactive({ patient_id: null, patientName: '', priority: 'normal' });

let timer = null;
let searchTimer = null;

const columns = [
  { key: 'waiting', label: 'Waiting', bg: 'bg-yellow-50 border-yellow-200' },
  { key: 'in-progress', label: 'In-Progress', bg: 'bg-blue-50 border-blue-200' },
  { key: 'completed', label: 'Completed', bg: 'bg-green-50 border-green-200' },
];

const grouped = computed(() => ({
  waiting: queue.value.filter((q) => q.status === 'waiting'),
  'in-progress': queue.value.filter((q) => q.status === 'in-progress'),
  completed: queue.value.filter((q) => q.status === 'completed'),
}));

const prettyDate = computed(() =>
  new Date(date.value).toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })
);

onMounted(() => {
  load();
  timer = setInterval(load, 30000);
});

onUnmounted(() => {
  clearInterval(timer);
  clearTimeout(searchTimer);
});

async function load() {
  const { data } = await axios.get('/api/opd/queue/today', { params: { date: date.value } });
  queue.value = data.queue;
  stats.value = data.stats;
  lastUpdated.value = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

async function setStatus(q, status) {
  try {
    await axios.put(`/api/opd/queue/${q.id}/status`, { status });
    load();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed.');
  }
}

function printSlip(q) {
  window.open(`/print/opd-token/${q.id}`, '_blank');
}

function resetQueueForm() {
  Object.assign(form, { patient_id: null, patientName: '', priority: 'normal' });
  results.value = [];
  search.value = '';
}

function clearSelectedPatient() {
  form.patient_id = null;
  form.patientName = '';
}

function openAdd() {
  resetQueueForm();
  showAdd.value = true;
}

function openNewPatient() {
  showNewPatient.value = true;
}

function openNewPatientFromQueue() {
  showAdd.value = false;
  showNewPatient.value = true;
}

function searchPatients() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(async () => {
    if (!search.value) {
      results.value = [];
      return;
    }

    const { data } = await axios.get('/api/patients/search', { params: { q: search.value } });
    results.value = data;
  }, 300);
}

function select(p) {
  form.patient_id = p.id;
  form.patientName = `${p.name} (${p.op_number})`;
  results.value = [];
}

function handleDuplicateWarning(warning) {
  toast.warning(warning?.message || 'Possible duplicate patient found.');
}

function onPatientCreated(patient) {
  form.patient_id = patient.id;
  form.patientName = `${patient.name} (${patient.op_number})`;
  search.value = '';
  results.value = [];
  showNewPatient.value = false;
  showAdd.value = true;
  toast.info('Patient registered. Review priority and add the token.');
}

async function submit() {
  if (!form.patient_id) {
    toast.error('Select a patient.');
    return;
  }

  saving.value = true;
  try {
    await axios.post('/api/opd/queue', { patient_id: form.patient_id, priority: form.priority, date: date.value });
    toast.success('Token added.');
    showAdd.value = false;
    load();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed.');
  } finally {
    saving.value = false;
  }
}

function pad(n) {
  return String(n).padStart(3, '0');
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.act { @apply text-xs font-medium hover:underline; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
