<template>
  <div v-if="patient" class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-5 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-700">
          {{ patient.name?.charAt(0) }}
        </div>
        <div>
          <h1 class="text-xl font-bold text-gray-900">{{ patient.name }}</h1>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-sm font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ patient.op_number }}</span>
            <span class="text-sm text-gray-500">{{ patient.phone }}</span>
            <span v-if="patient.gender" class="text-xs text-gray-400 capitalize">{{ patient.gender }}</span>
          </div>
        </div>
      </div>
      <div class="flex gap-2">
        <button @click="activeTab = 'soap'" class="btn-primary">+ New Visit</button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200">
      <button
        v-for="tab in TABS"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="activeTab === tab.key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700'"
        class="px-4 py-2.5 border-b-2 text-sm font-medium transition-colors"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Visit History -->
    <div v-if="activeTab === 'history'">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <VisitHistory :patient-id="patient.id" />
      </div>
    </div>

    <!-- Tab: New SOAP -->
    <div v-if="activeTab === 'soap'">
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-6">
        <h2 class="font-semibold text-gray-900">New Clinical Record</h2>

        <VASSlider v-model="record.vas_score" />
        <BodyMap v-model="record.body_map" />
        <SOAPNotes v-model="record.soap_notes" />
        <ROMTracker v-model="record.rom" />
        <OrthoTests v-model="record.ortho_tests" />
        <OutcomeMeasures v-model="record.outcome_measures" />

        <div v-if="saveError" class="text-sm text-red-600">{{ saveError }}</div>
        <button @click="saveRecord" :disabled="saving" class="btn-primary">
          {{ saving ? 'Saving...' : 'Save Clinical Record' }}
        </button>
      </div>
    </div>

    <!-- Tab: Treatments -->
    <div v-if="activeTab === 'treatments'">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <TreatmentTracker :patient-id="patient.id" :treatments="treatments" />
      </div>
    </div>

    <!-- Tab: Exercises -->
    <div v-if="activeTab === 'exercises'">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <ExerciseLibrary v-model="selectedExercises" />
        <div class="mt-4 flex justify-end">
          <button @click="saveExercises" :disabled="!selectedExercises.length" class="btn-primary">
            Save Exercise Prescription
          </button>
        </div>
      </div>
    </div>

    <!-- Tab: Prescription -->
    <div v-if="activeTab === 'rx'">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <PrescriptionForm :patient-id="patient.id" :clinical-record-id="lastRecordId" />
      </div>
    </div>

    <!-- Tab: Invoice -->
    <div v-if="activeTab === 'invoice'">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <InvoiceForm :patient-id="patient.id" />
      </div>
    </div>
  </div>

  <!-- Loading skeleton -->
  <div v-else class="space-y-6 animate-pulse">
    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
      <div class="w-14 h-14 rounded-full bg-gray-200"></div>
      <div class="space-y-2">
        <div class="h-5 w-48 bg-gray-200 rounded"></div>
        <div class="h-3 w-32 bg-gray-100 rounded"></div>
      </div>
    </div>
    <div class="flex gap-4 border-b border-gray-200 pb-2">
      <div v-for="n in 6" :key="n" class="h-4 w-20 bg-gray-100 rounded"></div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
      <div class="h-4 w-1/3 bg-gray-200 rounded"></div>
      <div class="h-24 bg-gray-100 rounded-lg"></div>
      <div class="h-24 bg-gray-100 rounded-lg"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, defineAsyncComponent, h } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import VisitHistory from '../components/VisitHistory.vue';
import VASSlider from '../components/VASSlider.vue';
import SOAPNotes from '../components/SOAPNotes.vue';
import ROMTracker from '../components/ROMTracker.vue';
import OrthoTests from '../components/OrthoTests.vue';
import TreatmentTracker from '../components/TreatmentTracker.vue';
import ExerciseLibrary from '../components/ExerciseLibrary.vue';
import PrescriptionForm from '../components/PrescriptionForm.vue';
import InvoiceForm from '../components/InvoiceForm.vue';

// Lazy-loaded heavy components (code-split + render skeleton while loading)
const skeleton = { render: () => h('div', { class: 'animate-pulse h-24 bg-gray-100 rounded-lg' }) };
const BodyMap = defineAsyncComponent({
  loader: () => import('../components/BodyMap.vue'), loadingComponent: skeleton,
});
const OutcomeMeasures = defineAsyncComponent({
  loader: () => import('../components/OutcomeMeasures.vue'), loadingComponent: skeleton,
});

const route = useRoute();
const toast = useToast();
const patient = ref(null);
const treatments = ref([]);
const activeTab = ref('history');
const saving = ref(false);
const saveError = ref('');
const lastRecordId = ref(null);
const selectedExercises = ref([]);

const TABS = [
  { key: 'history', label: 'Visit History' },
  { key: 'soap', label: 'New Visit' },
  { key: 'treatments', label: 'Treatments' },
  { key: 'exercises', label: 'Exercises' },
  { key: 'rx', label: 'Prescription' },
  { key: 'invoice', label: 'Invoice' },
];

const record = reactive({
  vas_score: 0, body_map: [], soap_notes: {}, rom: [], ortho_tests: [], outcome_measures: {},
});

onMounted(async () => {
  const { data } = await axios.get(`/api/patients/${route.params.id}`);
  patient.value = data;
});

async function saveRecord() {
  saving.value = true;
  saveError.value = '';
  try {
    const { data } = await axios.post('/api/clinical-records', {
      patient_id: patient.value.id,
      ...record,
    });
    lastRecordId.value = data.id;
    toast.success('Clinical record saved!');
    activeTab.value = 'history';
  } catch (e) {
    saveError.value = e.response?.data?.message || 'Failed to save.';
  } finally {
    saving.value = false;
  }
}

async function saveExercises() {
  await axios.post('/api/exercises/prescribe', {
    patient_id: patient.value.id,
    clinical_record_id: lastRecordId.value,
    exercises: selectedExercises.value.map(e => ({
      exercise_id: e.id, sets: e.sets, reps: e.reps, hold: e.hold,
    })),
    frequency: 'Daily',
  });
  toast.success('Exercise prescription saved!');
}
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
</style>
