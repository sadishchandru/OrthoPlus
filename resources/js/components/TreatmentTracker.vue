<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Treatment Tracker</label>
      <button type="button" @click="showAssign = true" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded">
        + Assign Treatment
      </button>
    </div>

    <!-- Kanban columns -->
    <div class="grid grid-cols-3 gap-3">
      <div v-for="col in COLUMNS" :key="col.status" class="bg-gray-50 rounded-lg p-3 min-h-24">
        <h4 class="text-xs font-semibold uppercase tracking-wide mb-3" :class="col.color">{{ col.label }}</h4>
        <div class="space-y-2">
          <div
            v-for="t in byStatus(col.status)"
            :key="t.id"
            class="bg-white rounded-lg p-3 shadow-sm border border-gray-100"
          >
            <div class="font-medium text-sm text-gray-800">{{ t.catalog?.name || '—' }}</div>
            <div class="text-xs text-gray-500 mt-1">{{ t.therapist?.name || 'Unassigned' }}</div>
            <div class="mt-2 flex gap-2">
              <button
                v-if="col.status === 'assigned'"
                @click="complete(t)"
                class="text-xs bg-green-50 hover:bg-green-100 text-green-700 px-2 py-1 rounded transition-colors"
              >
                Mark Complete
              </button>
              <span
                v-if="col.status === 'completed'"
                class="text-xs text-gray-400"
              >{{ t.completed_at ? new Date(t.completed_at).toLocaleDateString() : '' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Assign Modal -->
    <div v-if="showAssign" class="fixed inset-0 bg-black/50 z-50 flex items-stretch md:items-center justify-center p-0 md:p-4">
      <div class="bg-white rounded-none md:rounded-xl p-4 md:p-6 w-full md:max-w-md shadow-xl h-full md:h-auto max-h-screen md:max-h-[90vh] overflow-y-auto">
        <h3 class="font-semibold text-gray-900 mb-4">Assign Treatment</h3>
        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Treatment</label>
            <select v-model="assignForm.treatment_id" class="input w-full">
              <option value="">Select treatment...</option>
              <option v-for="t in catalog" :key="t.id" :value="t.id">{{ t.name }} (₹{{ t.price }})</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Therapist</label>
            <select v-model="assignForm.therapist_id" class="input w-full">
              <option value="">Select therapist...</option>
              <option v-for="t in therapists" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Commission %</label>
            <input v-model.number="assignForm.commission_pct" type="number" min="0" max="100" class="input w-full" />
          </div>
        </div>
        <div v-if="assignError" class="mt-3 text-sm text-red-600">{{ assignError }}</div>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="showAssign = false" class="btn-secondary">Cancel</button>
          <button @click="submitAssign" :disabled="assigning" class="btn-primary">
            {{ assigning ? 'Assigning...' : 'Assign' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
  patientId: { type: Number, required: true },
  clinicalRecordId: { type: Number, default: null },
  treatments: { type: Array, default: () => [] },
});
const emit = defineEmits(['updated']);
const toast = useToast();

const COLUMNS = [
  { status: 'assigned', label: 'Assigned', color: 'text-blue-600' },
  { status: 'in_progress', label: 'In Progress', color: 'text-orange-600' },
  { status: 'completed', label: 'Completed', color: 'text-green-600' },
];

const localTreatments = ref([...props.treatments]);
const catalog = ref([]);
const therapists = ref([]);
const showAssign = ref(false);
const assigning = ref(false);
const assignError = ref('');
const assignForm = reactive({ treatment_id: '', therapist_id: '', commission_pct: 0 });

onMounted(async () => {
  const [catRes, therRes] = await Promise.all([
    axios.get('/api/treatments/catalog').catch(() => ({ data: [] })),
    axios.get('/api/therapists').catch(() => ({ data: [] })),
  ]);
  catalog.value = catRes.data;
  therapists.value = therRes.data;
});

function byStatus(status) {
  return localTreatments.value.filter(t => t.status === status);
}

async function complete(treatment) {
  try {
    const { data } = await axios.put(`/api/treatments/${treatment.id}/complete`);
    const idx = localTreatments.value.findIndex(t => t.id === treatment.id);
    if (idx >= 0) localTreatments.value[idx] = data;
    toast.success('Treatment marked complete.');
    emit('updated', localTreatments.value);
  } catch {
    toast.error('Failed to update treatment.');
  }
}

async function submitAssign() {
  if (!assignForm.treatment_id) { assignError.value = 'Select a treatment.'; return; }
  assigning.value = true;
  assignError.value = '';
  try {
    const { data } = await axios.post('/api/treatments/assign', {
      patient_id: props.patientId,
      clinical_record_id: props.clinicalRecordId,
      ...assignForm,
    });
    localTreatments.value.push(data);
    showAssign.value = false;
    toast.success('Treatment assigned.');
    emit('updated', localTreatments.value);
  } catch (e) {
    assignError.value = e.response?.data?.message || 'Failed to assign.';
  } finally {
    assigning.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
