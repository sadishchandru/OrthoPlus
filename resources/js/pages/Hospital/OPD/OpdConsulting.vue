<template>
<div class="flex flex-col lg:flex-row gap-4 min-h-screen bg-gray-50 p-3">

  <!-- LEFT: Queue Panel -->
  <div class="w-full lg:w-80 flex-shrink-0">
    <div class="bg-white rounded-xl border p-3 mb-3">
      <div class="flex items-center justify-between mb-2">
        <h2 class="font-bold text-gray-800">OPD Queue</h2>
        <span class="text-xs text-gray-500">{{ today }}</span>
      </div>
      <div class="grid grid-cols-3 gap-2 text-center">
        <div class="bg-yellow-50 rounded-lg p-2">
          <div class="text-lg font-bold text-yellow-700">{{ stats.waiting }}</div>
          <div class="text-xs text-yellow-600">Waiting</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-2">
          <div class="text-lg font-bold text-blue-700">{{ stats.in_progress }}</div>
          <div class="text-xs text-blue-600">In Progress</div>
        </div>
        <div class="bg-green-50 rounded-lg p-2">
          <div class="text-lg font-bold text-green-700">{{ stats.completed }}</div>
          <div class="text-xs text-green-600">Done</div>
        </div>
      </div>
      <button @click="showAddToken=true" class="w-full mt-3 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium">
        + Add Patient to Queue
      </button>
    </div>

    <div class="space-y-2 overflow-y-auto max-h-[calc(100vh-220px)]">
      <div v-for="token in queue" :key="token.id" @click="selectToken(token)"
           :class="[
             'bg-white rounded-xl border p-3 cursor-pointer transition-all',
             selectedToken?.id === token.id ? 'border-blue-500 shadow-md' : 'hover:border-gray-300',
             token.priority === 'emergency' ? 'border-l-4 border-l-red-500' :
             token.priority === 'urgent' ? 'border-l-4 border-l-orange-400' : ''
           ]">
        <div class="flex items-center justify-between">
          <span class="text-base font-bold text-blue-700">#{{ pad(token.token_number) }}</span>
          <span :class="statusBadge(token.status)" class="text-xs px-2 py-0.5 rounded-full">{{ token.status }}</span>
        </div>
        <p class="text-sm font-medium mt-1">{{ token.patient?.name }}</p>
        <p class="text-xs text-gray-500 truncate">{{ token.patient?.op_number }}<span v-if="token.chief_complaint"> · {{ token.chief_complaint }}</span></p>
        <div class="flex gap-1 mt-2" v-if="token.status !== 'completed'">
          <button @click.stop="callNext(token)" class="flex-1 text-xs bg-blue-50 text-blue-700 py-1 rounded-lg">Call</button>
          <button @click.stop="markDone(token)" class="flex-1 text-xs bg-green-50 text-green-700 py-1 rounded-lg">Done</button>
        </div>
        <!-- Completed: Edit + Print only (no Complete), role-gated -->
        <div class="flex gap-1 mt-2" v-else-if="canEditConsult || canPrintConsult">
          <button v-if="canEditConsult" @click.stop="selectToken(token)" class="flex-1 text-xs bg-blue-50 text-blue-700 py-1 rounded-lg">Edit</button>
          <button v-if="canPrintConsult" @click.stop="printConsult(token)" class="flex-1 text-xs bg-gray-100 text-gray-700 py-1 rounded-lg">Print</button>
        </div>
      </div>
      <div v-if="!queue.length" class="text-center text-gray-400 py-8 text-sm">No patients in queue today</div>
    </div>
  </div>

  <!-- RIGHT: Consulting Panel -->
  <div class="flex-1 min-w-0">
    <div v-if="!selectedToken" class="h-full flex items-center justify-center bg-white rounded-xl border min-h-[400px]">
      <div class="text-center text-gray-400">
        <div class="text-5xl mb-3">🩺</div>
        <p class="text-lg font-medium">Select a patient from the queue</p>
        <p class="text-sm mt-1">Click any token on the left to start consultation</p>
      </div>
    </div>

    <div v-else class="bg-white rounded-xl border overflow-hidden">
      <div class="text-white p-4" style="background:#1F5523;">
        <div class="flex items-center justify-between flex-wrap gap-2">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-2xl font-bold">#{{ pad(selectedToken.token_number) }}</span>
              <span :class="priorityBadge(selectedToken.priority)" class="text-xs px-2 py-0.5 rounded-full">{{ selectedToken.priority }}</span>
            </div>
            <p class="text-lg font-semibold mt-1">{{ selectedToken.patient?.name }}</p>
            <p class="text-sm text-blue-200">
              {{ selectedToken.patient?.op_number }} · {{ selectedToken.patient?.gender }} · {{ age(selectedToken.patient?.dob) }}
            </p>
          </div>
          <div class="flex gap-2">
            <button @click="viewHistory" class="text-xs bg-blue-700 px-3 py-1.5 rounded-lg">History</button>
            <button v-if="selectedToken.status === 'completed'" @click="printConsultation" class="text-xs bg-blue-700 px-3 py-1.5 rounded-lg">Print</button>
            <button @click="saveConsultation" :disabled="saving" class="text-xs bg-green-500 px-3 py-1.5 rounded-lg font-medium">
              {{ saving ? 'Saving...' : (selectedToken.status === 'completed' ? 'Save Changes' : 'Save & Complete') }}
            </button>
          </div>
        </div>
      </div>

      <div class="p-4 overflow-y-auto max-h-[calc(100vh-200px)]">
        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Vitals</h3>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
            <div><label class="text-xs text-gray-500">BP (mmHg)</label><input v-model="form.vitals.bp" type="text" placeholder="120/80" class="w-full border rounded-lg px-2 py-1.5 text-sm mt-1"/></div>
            <div><label class="text-xs text-gray-500">Pulse (bpm)</label><input v-model="form.vitals.pulse" type="number" placeholder="72" class="w-full border rounded-lg px-2 py-1.5 text-sm mt-1"/></div>
            <div><label class="text-xs text-gray-500">Temp (°F)</label><input v-model="form.vitals.temp" type="number" placeholder="98.6" class="w-full border rounded-lg px-2 py-1.5 text-sm mt-1"/></div>
            <div><label class="text-xs text-gray-500">SpO2 (%)</label><input v-model="form.vitals.spo2" type="number" placeholder="99" class="w-full border rounded-lg px-2 py-1.5 text-sm mt-1"/></div>
          </div>
        </div>

        <div class="mb-4">
          <label class="text-sm font-semibold text-gray-700">Chief Complaint</label>
          <textarea v-model="form.chief_complaint" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" placeholder="Patient's main complaint..."></textarea>
        </div>

        <div class="mb-4"><VASSlider v-model="form.vas_score"/></div>

        <div class="mb-4">
          <BodyMap :key="selectedToken.id" v-model="form.body_map" :gender="selectedToken.patient?.gender"/>
        </div>

        <!-- Pain description (below marked areas): 10–15 words, saved + printed -->
        <div class="mb-4">
          <label class="text-sm font-semibold text-gray-700">Pain Description
            <span class="text-xs font-normal" :class="painValid ? 'text-gray-400' : 'text-red-500'">({{ painWords }} words · need 10–15)</span>
          </label>
          <textarea v-model="form.pain_description" rows="2"
                    class="w-full border rounded-lg px-3 py-2 text-sm mt-1" :class="painValid ? '' : 'border-red-400'"
                    placeholder="Describe patient's pain (10–15 words)"></textarea>
        </div>

        <!-- Patient files (lazy: loads only when a token is open) -->
        <div class="mb-4">
          <FileGallery :key="'files-' + selectedToken.id" :patient-id="selectedToken.patient?.id"
                       module="opd-consulting" :can-upload="canUploadFiles" :can-delete="canDeleteFiles" />
        </div>

        <!-- Visit History (reuses clinic VisitHistory) -->
        <details class="mb-4 group" open>
          <summary class="text-sm font-semibold text-gray-700 cursor-pointer">Visit History</summary>
          <div class="mt-2"><VisitHistory :key="'vh-' + selectedToken.id" :patient-id="selectedToken.patient?.id" /></div>
        </details>

        <!-- Treatment Tracker (reuses clinic TreatmentTracker + /api/treatments) -->
        <details class="mb-4">
          <summary class="text-sm font-semibold text-gray-700 cursor-pointer">Treatments</summary>
          <div class="mt-2"><TreatmentTracker :key="'tt-' + selectedToken.id" :patient-id="selectedToken.patient?.id" /></div>
        </details>

        <!-- Exercises (reuses clinic ExerciseLibrary + /api/exercises/prescribe) -->
        <details class="mb-4">
          <summary class="text-sm font-semibold text-gray-700 cursor-pointer">Exercises</summary>
          <div class="mt-2">
            <ExerciseLibrary v-model="form.exercises" />
            <button type="button" @click="prescribeExercises" :disabled="!form.exercises.length"
                    class="mt-2 text-xs bg-blue-600 text-white px-3 py-1.5 rounded-lg disabled:opacity-50">
              Prescribe {{ form.exercises.length || '' }} Exercise(s)
            </button>
          </div>
        </details>

        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">SOAP Notes</h3>
          <SOAPNotes v-model="form.soap_notes"/>
        </div>

        <div class="mb-4"><ROMTracker v-model="form.rom"/></div>
        <div class="mb-4"><OrthoTests v-model="form.ortho_tests"/></div>

        <div class="mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Quick Prescription</h3>
          <PrescriptionForm :key="'rx-' + selectedToken.id" :patient-id="selectedToken.patient?.id"
                            :prescription-id="editRxId" @saved="onRxSaved"/>
        </div>

        <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div><label class="text-sm text-gray-600">Follow-up Date</label><input v-model="form.follow_up_date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm mt-1"/></div>
          <div><label class="text-sm text-gray-600">Refer To</label><input v-model="form.refer_to" type="text" placeholder="Specialist / Dept" class="w-full border rounded-lg px-3 py-2 text-sm mt-1"/></div>
        </div>

        <div class="flex flex-wrap gap-2 pt-2 border-t">
          <button @click="saveConsultation" :disabled="saving" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium min-w-[120px]">
            {{ saving ? 'Saving...' : 'Save & Complete' }}
          </button>
          <button @click="printConsultation" class="px-4 py-2.5 border rounded-lg text-sm text-gray-600">Print</button>
          <button @click="admitPatient" class="px-4 py-2.5 bg-orange-50 border-orange-300 border text-orange-700 rounded-lg text-sm">Admit</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Add Token Modal -->
<div v-if="showAddToken" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50">
  <div class="bg-white w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl p-4">
    <h3 class="font-bold mb-3">Add Patient to Queue</h3>
    <PatientSearch @select="onPatientSelect" class="mb-3"/>
    <div v-if="newToken.patient" class="bg-blue-50 rounded-lg p-2 text-sm mb-3">
      {{ newToken.patient.name }} · {{ newToken.patient.op_number }}
    </div>
    <textarea v-model="newToken.chief_complaint" rows="2" placeholder="Chief complaint..." class="w-full border rounded-lg px-3 py-2 text-sm mb-3"></textarea>
    <select v-model="newToken.priority" class="w-full border rounded-lg px-3 py-2 text-sm mb-3">
      <option value="normal">Normal</option>
      <option value="urgent">Urgent</option>
      <option value="emergency">Emergency</option>
    </select>
    <div class="flex gap-2">
      <button @click="showAddToken=false" class="flex-1 border rounded-lg py-2.5 text-sm">Cancel</button>
      <button @click="addToQueue" class="flex-1 bg-blue-600 text-white rounded-lg py-2.5 text-sm font-medium">Add to Queue</button>
    </div>
  </div>
</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../../../stores/auth';
import PatientSearch from '../../../components/PatientSearch.vue';
import FileGallery from '../../../components/FileGallery.vue';
import VisitHistory from '../../../components/VisitHistory.vue';
import TreatmentTracker from '../../../components/TreatmentTracker.vue';
import ExerciseLibrary from '../../../components/ExerciseLibrary.vue';
import BodyMap from '../../../components/BodyMap3D.vue';
import VASSlider from '../../../components/VASSlider.vue';
import SOAPNotes from '../../../components/SOAPNotes.vue';
import ROMTracker from '../../../components/ROMTracker.vue';
import OrthoTests from '../../../components/OrthoTests.vue';
import PrescriptionForm from '../../../components/PrescriptionForm.vue';

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();
// Permission-based file actions (reuse existing page_access / roles).
const canUploadFiles = computed(() => auth.canAccess('patients') || auth.canAccess('opd'));
const canDeleteFiles = computed(() => auth.isRoot || auth.hasRole('doctor'));
// Edit completed consult = clinical roles only; Print = anyone with OPD view.
const canEditConsult = computed(() => auth.isRoot || auth.hasRole('doctor'));
const canPrintConsult = computed(() => auth.canAccess('opd') || auth.hasRole('doctor', 'nurse', 'receptionist'));
const queue = ref([]);
const stats = ref({ waiting: 0, in_progress: 0, completed: 0 });
const selectedToken = ref(null);
const editRxId = ref(null); // existing prescription id when editing a completed consult
const showAddToken = ref(false);
const saving = ref(false);
const today = new Date().toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' });

const blankForm = () => ({
  vitals: { bp: '', pulse: '', temp: '', spo2: '', weight: '', height: '' },
  chief_complaint: '',
  vas_score: 0,
  body_map: [],
  pain_description: '',
  soap_notes: { subjective: '', objective: '', assessment: '', plan: '' },
  rom: [],
  ortho_tests: [],
  exercises: [],
  follow_up_date: '',
  refer_to: '',
});
// Pain note word-count rule (10–15 words).
const painWords = computed(() => (form.value.pain_description || '').trim().split(/\s+/).filter(Boolean).length);
const painValid = computed(() => painWords.value === 0 || (painWords.value >= 10 && painWords.value <= 15));
const form = ref(blankForm());
const newToken = ref({ patient: null, chief_complaint: '', priority: 'normal' });

async function loadQueue() {
  const { data } = await axios.get('/api/hospital/opd/queue');
  queue.value = data.queue;
  stats.value = data.stats;
}

async function selectToken(token) {
  selectedToken.value = token;
  editRxId.value = null;
  form.value = {
    ...blankForm(),
    vitals: token.vitals || blankForm().vitals,
    chief_complaint: token.chief_complaint || '',
  };
  if (token.status === 'completed') {
    // Edit mode → load the saved consultation + prescription into the form (one call).
    try {
      const { data } = await axios.get(`/api/hospital/opd/queue/${token.id}/consult`);
      editRxId.value = data.prescription?.id || null;
      const r = data.clinical_record;
      if (r) {
        const arr = (v) => (Array.isArray(v) ? v : []); // saved JSON may be object/null
        form.value = {
          ...form.value,
          body_map: arr(r.body_map),
          pain_description: r.pain_description || '',
          vas_score: r.vas_score || 0,
          soap_notes: (r.soap_notes && !Array.isArray(r.soap_notes)) ? r.soap_notes : form.value.soap_notes,
          rom: arr(r.rom),
          ortho_tests: arr(r.ortho_tests),
          follow_up_date: r.follow_up_date || '',
          refer_to: r.refer_to || '',
        };
      }
    } catch { /* ignore */ }
  } else if (token.status === 'waiting') {
    callNext(token);
  }
}

async function callNext(token) {
  await axios.patch(`/api/hospital/opd/queue/${token.id}/status`, { status: 'in-progress' });
  loadQueue();
}
async function markDone(token) {
  await axios.patch(`/api/hospital/opd/queue/${token.id}/status`, { status: 'completed' });
  loadQueue();
}

async function addToQueue() {
  if (!newToken.value.patient) return toast.error('Select a patient');
  await axios.post('/api/hospital/opd/queue', {
    patient_id: newToken.value.patient.id,
    chief_complaint: newToken.value.chief_complaint,
    priority: newToken.value.priority,
  });
  showAddToken.value = false;
  newToken.value = { patient: null, chief_complaint: '', priority: 'normal' };
  loadQueue();
  toast.success('Patient added to queue');
}
function onPatientSelect(patient) { newToken.value.patient = patient; }
function onRxSaved() { toast.success('Prescription saved'); }

async function prescribeExercises() {
  if (!form.value.exercises.length) return;
  try {
    await axios.post('/api/exercises/prescribe', {
      patient_id: selectedToken.value.patient.id,
      exercises: form.value.exercises.map((e) => ({
        exercise_id: e.exercise_id ?? e.id,
        sets: e.sets, reps: e.reps, hold: e.hold, notes: e.notes,
      })),
    });
    toast.success('Exercises prescribed');
    form.value.exercises = [];
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to prescribe');
  }
}

async function saveConsultation() {
  if (!selectedToken.value) return;
  if (!painValid.value) { toast.error('Pain description must be 10–15 words.'); return; }
  saving.value = true;
  try {
    // Re-saving a completed token UPDATES the same record (no duplicate consultation).
    await axios.post(`/api/hospital/opd/queue/${selectedToken.value.id}/consult`, form.value);
    toast.success('Consultation saved');
    selectedToken.value = null;
    loadQueue();
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to save');
  } finally { saving.value = false; }
}

// Open the full prescription/consultation print for a completed token.
async function printConsult(token) {
  try {
    const { data } = await axios.get(`/api/hospital/opd/queue/${token.id}/consult`);
    if (data.prescription?.id) window.open(`/print/prescription/${data.prescription.id}`, '_blank');
    else if (data.clinical_record?.id) window.open(`/print/soap/${data.clinical_record.id}`, '_blank');
    else if (token.patient?.id) window.open(`/print/patient/${token.patient.id}`, '_blank'); // fallback: full record
    else toast.error('Nothing to print for this visit.');
  } catch { toast.error('Could not load consultation.'); }
}

function viewHistory() {
  if (selectedToken.value?.patient) window.open(`/patients/${selectedToken.value.patient.id}`, '_blank');
}
function printConsultation() { if (selectedToken.value) printConsult(selectedToken.value); }
function admitPatient() {
  if (selectedToken.value?.patient) router.push(`/hospital/admissions/new?patient_id=${selectedToken.value.patient.id}`);
}

function age(dob) {
  if (!dob) return '';
  return Math.floor((Date.now() - new Date(dob)) / 3.15576e10) + 'y';
}
function pad(n) { return 'T-' + String(n ?? 0).padStart(3, '0'); }
function statusBadge(s) {
  // Brand status colors: waiting=amber, in-progress=green, completed=dark green, cancelled=red.
  return {
    waiting: 'bg-amber-100 text-amber-700', 'in-progress': 'bg-green-100 text-green-700',
    completed: 'bg-green-200 text-green-900', cancelled: 'bg-red-100 text-red-700',
  }[s] || 'bg-gray-100 text-gray-600';
}
function priorityBadge(p) {
  return { emergency: 'bg-red-500 text-white', urgent: 'bg-orange-400 text-white', normal: 'bg-gray-200 text-gray-700' }[p] || '';
}

let timer;
onMounted(async () => {
  await loadQueue();
  timer = setInterval(loadQueue, 30000);
});
onUnmounted(() => clearInterval(timer));
</script>
