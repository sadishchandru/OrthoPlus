<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-gray-900">Patients</h1>
      <button @click="showNew = true" class="btn-primary">+ New Patient</button>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <PatientSearch @select="goToPatient" placeholder="Search patients by name, phone, or OP No..." />
    </div>

    <!-- Duplicate warning -->
    <div v-if="dupeWarning" class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
      <p class="text-sm font-semibold text-yellow-800 mb-2">⚠️ Possible duplicate detected</p>
      <p class="text-xs text-yellow-700 mb-2">{{ dupeWarning.message }}</p>
      <div class="flex gap-2">
        <button v-for="p in dupeWarning.patients" :key="p.id" @click="goToPatient(p)" class="text-xs bg-yellow-200 hover:bg-yellow-300 text-yellow-900 px-3 py-1 rounded">
          Open: {{ p.name }} ({{ p.op_number }})
        </button>
        <button @click="dupeWarning = null" class="text-xs text-gray-500 ml-2">Dismiss</button>
      </div>
    </div>

    <!-- Recent patients list -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
        <span class="font-semibold text-gray-800">Recent Patients</span>
        <span class="text-xs text-gray-400">{{ total }} total</span>
      </div>
      <div class="divide-y divide-gray-100">
        <router-link
          v-for="p in patients"
          :key="p.id"
          :to="`/patients/${p.id}`"
          class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-sm">
              {{ p.name?.charAt(0) }}
            </div>
            <div>
              <div class="font-medium text-sm text-gray-900">{{ p.name }}</div>
              <div class="text-xs text-gray-400">{{ p.phone }} · {{ p.gender }}</div>
            </div>
          </div>
          <div class="text-right">
            <div class="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ p.op_number }}</div>
          </div>
        </router-link>
      </div>
      <div class="px-5 py-3 border-t border-gray-100 flex justify-center gap-3">
        <button @click="page--; load()" :disabled="page <= 1" class="btn-secondary px-3 py-1 text-xs">← Prev</button>
        <span class="text-xs text-gray-500 self-center">Page {{ page }}</span>
        <button @click="page++; load()" :disabled="patients.length < 20" class="btn-secondary px-3 py-1 text-xs">Next →</button>
      </div>
    </div>

    <!-- New patient modal -->
    <div v-if="showNew" class="fixed inset-0 bg-black/50 z-50 flex items-stretch md:items-center justify-center p-0 md:p-4">
      <div class="bg-white rounded-none md:rounded-xl p-4 md:p-6 w-full md:max-w-2xl shadow-xl h-full md:h-auto max-h-screen md:max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="font-semibold text-gray-900">Register New Patient</h3>
          <button @click="showNew = false" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-500 text-xl flex-shrink-0">✕</button>
        </div>
        <PatientForm
          @created="onCreated"
          @cancel="showNew = false"
          @duplicate-warning="dupeWarning = $event"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PatientSearch from '../components/PatientSearch.vue';
import PatientForm from '../components/PatientForm.vue';

const router = useRouter();
const patients = ref([]);
const total = ref(0);
const page = ref(1);
const showNew = ref(false);
const dupeWarning = ref(null);

onMounted(load);

async function load() {
  const { data } = await axios.get('/api/patients', { params: { page: page.value } });
  patients.value = data.data || data;
  total.value = data.total || patients.value.length;
}

function goToPatient(p) { router.push(`/patients/${p.id}`); }

function onCreated(patient) {
  showNew.value = false;
  router.push(`/patients/${patient.id}`);
}
</script>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-40; }
</style>
