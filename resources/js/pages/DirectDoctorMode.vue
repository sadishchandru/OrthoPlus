<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Direct Doctor Mode</h1>
        <p class="text-sm text-gray-500 mt-1">Single-screen patient flow — no appointment step.</p>
      </div>
      <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">Role: Doctor</span>
    </div>

    <!-- Step 1: Patient search / create -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
      <h2 class="text-base sm:text-lg font-semibold text-gray-800 mb-4">Step 1 — Find or Register Patient</h2>

      <div class="flex gap-3 mb-4">
        <button @click="mode = 'search'" :class="mode === 'search' ? 'bg-blue-600 text-white' : 'btn-secondary'" class="px-4 py-2 rounded-lg text-sm font-medium">Search Existing</button>
        <button @click="mode = 'new'" :class="mode === 'new' ? 'bg-blue-600 text-white' : 'btn-secondary'" class="px-4 py-2 rounded-lg text-sm font-medium">New Patient</button>
      </div>

      <div v-if="mode === 'search'">
        <PatientSearch @select="selectPatient" :duplicate-warning="dupeWarning" />
      </div>
      <div v-if="mode === 'new'">
        <PatientForm @created="selectPatient" @cancel="mode = 'search'" @duplicate-warning="dupeWarning = $event" />
      </div>
    </div>

    <!-- Selected patient banner -->
    <div v-if="patient" class="bg-blue-50 border border-blue-200 rounded-xl px-5 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-blue-200 flex items-center justify-center text-blue-800 font-bold">
          {{ patient.name?.charAt(0) }}
        </div>
        <div>
          <div class="font-semibold text-blue-900">{{ patient.name }}</div>
          <div class="text-sm text-blue-700">{{ patient.op_number }} · {{ patient.phone }}</div>
        </div>
      </div>
      <button @click="patient = null" class="text-blue-400 hover:text-blue-700 text-sm">Change patient</button>
    </div>

    <div v-if="patient">
      <!-- Step 2: Past history quick view -->
      <details open class="bg-white rounded-xl border border-gray-200 group">
        <summary class="step-sum">Step 2 — Visit History <span class="step-chev">▾</span></summary>
        <div class="px-4 sm:px-5 pb-4 sm:pb-5">
          <VisitHistory :patient-id="patient.id" />
        </div>
      </details>

      <!-- Step 3: SOAP + clinical -->
      <details open class="bg-white rounded-xl border border-gray-200 group">
        <summary class="step-sum">Step 3 — Clinical Assessment <span class="step-chev">▾</span></summary>
        <div class="px-4 sm:px-5 pb-4 sm:pb-5 space-y-6">
          <VASSlider v-model="record.vas_score" />

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="max-h-[320px] md:max-h-none overflow-auto">
              <BodyMap v-model="record.body_map" />
            </div>
            <SOAPNotes v-model="record.soap_notes" />
          </div>

          <ROMTracker v-model="record.rom" />
          <OrthoTests v-model="record.ortho_tests" />
          <OutcomeMeasures v-model="record.outcome_measures" />

          <div v-if="recordError" class="text-sm text-red-600">{{ recordError }}</div>
          <div class="sticky bottom-16 sm:static z-10 bg-white pt-2 -mx-4 sm:mx-0 px-4 sm:px-0 border-t sm:border-0 border-gray-100">
            <button @click="saveRecord" :disabled="saving" class="btn-primary w-full sm:w-auto">
              {{ saving ? 'Saving...' : 'Save Clinical Record' }}
            </button>
          </div>
        </div>
      </details>

      <!-- Step 4: Treatment + Exercise (shown after record saved) -->
      <details open v-if="savedRecordId" class="bg-white rounded-xl border border-gray-200 group">
        <summary class="step-sum">Step 4 — Treatment &amp; Exercise <span class="step-chev">▾</span></summary>
        <div class="px-4 sm:px-5 pb-4 sm:pb-5 space-y-6">
          <TreatmentTracker :patient-id="patient.id" :clinical-record-id="savedRecordId" :treatments="[]" />

          <div class="border-t border-gray-100 pt-4">
            <h3 class="font-medium text-gray-700 mb-3">Exercise Prescription</h3>
            <ExerciseLibrary v-model="exercises" />
            <button v-if="exercises.length" @click="saveExercises" class="btn-primary mt-3">
              Save Exercise Prescription
            </button>
          </div>
        </div>
      </details>

      <!-- Step 5: Prescription (optional) -->
      <details open v-if="savedRecordId" class="bg-white rounded-xl border border-gray-200 group">
        <summary class="step-sum">Step 5 — Prescription (optional) <span class="step-chev">▾</span></summary>
        <div class="px-4 sm:px-5 pb-4 sm:pb-5">
          <PrescriptionForm :patient-id="patient.id" :clinical-record-id="savedRecordId" />
        </div>
      </details>

      <!-- Step 6: Invoice + Print -->
      <details open v-if="savedRecordId" class="bg-white rounded-xl border border-gray-200 group">
        <summary class="step-sum">Step 6 — Invoice &amp; Print <span class="step-chev">▾</span></summary>
        <div class="px-4 sm:px-5 pb-4 sm:pb-5">
        <InvoiceForm :patient-id="patient.id" @saved="invoiceSaved = $event" />

        <div v-if="invoiceSaved" class="mt-4 flex flex-wrap gap-3 pt-4 border-t border-gray-100">
          <a :href="`/print/soap/${savedRecordId}`" target="_blank" class="btn-secondary text-sm">🖨️ Print SOAP</a>
          <a :href="`/print/exercises/${savedRecordId}`" target="_blank" class="btn-secondary text-sm">🖨️ Print Exercises</a>
          <a :href="`/print/invoice/${invoiceSaved.id}`" target="_blank" class="btn-secondary text-sm">🖨️ Print Invoice</a>
          <a :href="`/print/prescription/${savedRecordId}`" target="_blank" class="btn-secondary text-sm">🖨️ Print Rx</a>
          <button @click="reset" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium ml-auto">
            ✓ Done — Next Patient
          </button>
        </div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import PatientSearch from '../components/PatientSearch.vue';
import PatientForm from '../components/PatientForm.vue';
import VisitHistory from '../components/VisitHistory.vue';
import VASSlider from '../components/VASSlider.vue';
import BodyMap from '../components/BodyMap.vue';
import SOAPNotes from '../components/SOAPNotes.vue';
import ROMTracker from '../components/ROMTracker.vue';
import OrthoTests from '../components/OrthoTests.vue';
import OutcomeMeasures from '../components/OutcomeMeasures.vue';
import TreatmentTracker from '../components/TreatmentTracker.vue';
import ExerciseLibrary from '../components/ExerciseLibrary.vue';
import PrescriptionForm from '../components/PrescriptionForm.vue';
import InvoiceForm from '../components/InvoiceForm.vue';

const toast = useToast();

const mode = ref('search');
const patient = ref(null);
const dupeWarning = ref(null);
const saving = ref(false);
const recordError = ref('');
const savedRecordId = ref(null);
const invoiceSaved = ref(null);
const exercises = ref([]);

const record = reactive({
  vas_score: 0, body_map: [], soap_notes: {}, rom: [], ortho_tests: [], outcome_measures: {},
});

function selectPatient(p) {
  patient.value = p;
  dupeWarning.value = null;
  mode.value = 'search';
}

async function saveRecord() {
  saving.value = true;
  recordError.value = '';
  try {
    const { data } = await axios.post('/api/clinical-records', {
      patient_id: patient.value.id,
      ...record,
    });
    savedRecordId.value = data.id;
    toast.success('Clinical record saved!');
  } catch (e) {
    recordError.value = e.response?.data?.message || 'Failed to save record.';
  } finally {
    saving.value = false;
  }
}

async function saveExercises() {
  await axios.post('/api/exercises/prescribe', {
    patient_id: patient.value.id,
    clinical_record_id: savedRecordId.value,
    exercises: exercises.value.map(e => ({ exercise_id: e.id, sets: e.sets, reps: e.reps, hold: e.hold })),
    frequency: 'Daily',
  });
  toast.success('Exercise prescription saved!');
}

function reset() {
  patient.value = null;
  savedRecordId.value = null;
  invoiceSaved.value = null;
  exercises.value = [];
  Object.assign(record, { vas_score: 0, body_map: [], soap_notes: {}, rom: [], ortho_tests: [], outcome_measures: {} });
  mode.value = 'search';
}
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
.step-sum { @apply flex items-center justify-between cursor-pointer select-none text-base sm:text-lg font-semibold text-gray-800 p-4 sm:p-5; list-style: none; }
.step-sum::-webkit-details-marker { display: none; }
.step-chev { @apply text-gray-400 text-sm; transition: transform 0.2s; }
.group[open] .step-chev { transform: rotate(180deg); }
</style>
