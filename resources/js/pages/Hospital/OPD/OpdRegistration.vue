<template>
  <div class="space-y-4 p-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div>
        <h1 class="text-xl font-bold text-gray-900">OPD Registration</h1>
        <p class="text-xs text-gray-500">Register new patients or bring a returning patient back into the queue.</p>
      </div>
      <button @click="showNew = true" class="btn-primary flex-shrink-0">+ New Patient</button>
    </div>

    <input v-model="q" @input="searchDebounced" class="input w-full sm:w-96" placeholder="Search name / phone / OP number..." />

    <div class="overflow-x-auto rounded-xl border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-500 uppercase">
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">OP Number</th>
            <th class="px-4 py-3 text-left hidden sm:table-cell">Phone</th>
            <th class="px-4 py-3 text-center">Visits</th>
            <th class="px-4 py-3 text-center">Type</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in patients" :key="p.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">{{ p.name }}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ p.op_number }}</td>
            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ p.phone || '—' }}</td>
            <td class="px-4 py-3 text-center text-gray-700">{{ p.visit_count ?? p.clinical_records_count ?? 0 }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="(p.visit_type === 'revisit') ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
                    class="text-xs px-2 py-0.5 rounded-full font-medium">
                {{ p.visit_type === 'revisit' ? 'Revisit' : 'New' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button @click="openEdit(p)" class="text-xs text-gray-500 hover:underline mr-3">Edit</button>
              <button @click="openFiles(p)" class="text-xs text-gray-500 hover:underline mr-3">Files</button>
              <button @click="openQueue(p)" class="text-xs text-blue-600 hover:underline font-medium">Add to Queue</button>
            </td>
          </tr>
          <tr v-if="!patients.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No patients found.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- New patient modal -->
    <Modal v-if="showNew" title="Register New Patient" @close="showNew = false">
      <PatientForm @created="onCreated" @cancel="showNew = false" />
    </Modal>

    <!-- Add to queue modal -->
    <Modal v-if="showQueue" title="Add to OPD Queue" @close="showQueue = false">
      <div class="space-y-3">
        <div class="p-2 bg-blue-50 rounded text-sm">
          <span class="font-medium text-blue-800">{{ active?.name }}</span>
          <span class="text-blue-500 ml-1">{{ active?.op_number }}</span>
          <span class="text-xs text-gray-500 ml-2">· {{ active?.visit_count ?? 0 }} prior visit(s) — {{ (active?.visit_count ?? 0) > 0 ? 'Revisit' : 'New' }}</span>
        </div>
        <div>
          <label class="lbl">Chief Complaint</label>
          <textarea v-model="queueForm.chief_complaint" rows="2" class="input w-full" placeholder="Reason for visit..."></textarea>
        </div>
        <div>
          <label class="lbl">Priority</label>
          <select v-model="queueForm.priority" class="input w-full">
            <option value="normal">Normal</option>
            <option value="urgent">Urgent</option>
            <option value="emergency">Emergency</option>
          </select>
        </div>
      </div>
      <template #footer>
        <button @click="showQueue = false" class="btn-secondary">Cancel</button>
        <button @click="addToQueue" :disabled="saving" class="btn-primary">{{ saving ? 'Adding...' : 'Add to Queue' }}</button>
      </template>
    </Modal>

    <!-- Files modal (lazy: FileGallery loads files only when opened) -->
    <Modal v-if="showFiles" :title="`Files — ${active?.name}`" @close="showFiles = false">
      <FileGallery :patient-id="active.id" module="opd-registration" :can-upload="canUploadFiles" :can-delete="canDeleteFiles" />
    </Modal>

    <!-- Edit patient modal -->
    <Modal v-if="showEdit" title="Edit Patient" @close="showEdit = false">
      <PatientForm v-if="editPatient" :patient="editPatient" @updated="onEdited" @cancel="showEdit = false" />
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../../../stores/auth';
import Modal from '../../../components/HModal.vue';
import PatientForm from '../../../components/PatientForm.vue';
import FileGallery from '../../../components/FileGallery.vue';

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();
const canUploadFiles = computed(() => auth.canAccess('patients') || auth.canAccess('opd'));
const canDeleteFiles = computed(() => auth.isRoot || auth.hasRole('doctor'));
const patients = ref([]);
const q = ref('');
const saving = ref(false);

// Files modal
const showFiles = ref(false);
function openFiles(p) { active.value = p; showFiles.value = true; }

// Edit modal — fetch full patient (list is trimmed) then prefill the form
const showEdit = ref(false);
const editPatient = ref(null);
async function openEdit(p) {
  const { data } = await axios.get(`/api/patients/${p.id}`);
  editPatient.value = data;
  showEdit.value = true;
}
function onEdited() { showEdit.value = false; editPatient.value = null; toast.success('Patient updated'); load(); }

onMounted(load);

async function load() {
  const { data } = await axios.get('/api/patients', { params: { q: q.value, per_page: 25 } });
  patients.value = data.data || data;
}
let t = null;
function searchDebounced() { clearTimeout(t); t = setTimeout(load, 300); }

// New patient
const showNew = ref(false);
function onCreated(patient) {
  showNew.value = false;
  toast.success('Patient registered');
  load();
  if (patient?.id) openQueue({ ...patient, visit_count: 0, visit_type: 'new' });
}

// Add to queue
const showQueue = ref(false);
const active = ref(null);
const queueForm = reactive({ chief_complaint: '', priority: 'normal' });
function openQueue(p) {
  active.value = p;
  Object.assign(queueForm, { chief_complaint: '', priority: 'normal' });
  showQueue.value = true;
}
async function addToQueue() {
  saving.value = true;
  try {
    await axios.post('/api/hospital/opd/queue', {
      patient_id: active.value.id,
      chief_complaint: queueForm.chief_complaint,
      priority: queueForm.priority,
    });
    toast.success(`${active.value.name} added to queue`);
    showQueue.value = false;
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to add');
  } finally { saving.value = false; }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
