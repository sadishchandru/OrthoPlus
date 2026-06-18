<template>
  <div class="relative">
    <input
      v-model="query"
      @input="onInput"
      @focus="open = results.length > 0"
      @keydown.escape="close"
      type="text"
      :placeholder="placeholder"
      autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" inputmode="search"
      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
    />

    <!-- Loading -->
    <div v-if="loading" class="absolute right-3 top-2.5">
      <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
    </div>

    <!-- Dropdown -->
    <div v-if="results.length && open" class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto">
      <button
        v-for="p in results"
        :key="p.id"
        @mousedown.prevent="select(p)"
        class="w-full text-left px-4 py-3 hover:bg-blue-50 border-b border-gray-100 last:border-0"
      >
        <div class="flex items-center justify-between">
          <div>
            <span class="font-medium text-gray-900">{{ p.name }}</span>
            <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ p.op_number }}</span>
          </div>
          <span class="text-sm text-gray-500">{{ p.phone }}</span>
        </div>
        <div v-if="p.gender || p.dob" class="text-xs text-gray-400 mt-0.5">
          {{ p.gender }} {{ p.dob ? '· DOB: ' + p.dob : '' }}
        </div>
      </button>
    </div>

    <!-- Duplicate Warning -->
    <div v-if="duplicateWarning" class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-yellow-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
          <p class="text-sm font-medium text-yellow-800">Possible duplicate patient</p>
          <p class="text-xs text-yellow-700 mt-1">{{ duplicateWarning.message }}</p>
          <div class="mt-2 flex gap-2">
            <button
              v-for="ep in duplicateWarning.patients"
              :key="ep.id"
              @click="select(ep)"
              class="text-xs bg-yellow-200 hover:bg-yellow-300 text-yellow-800 px-2 py-1 rounded"
            >
              Use: {{ ep.name }} ({{ ep.op_number }})
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  placeholder: { type: String, default: 'Search by name, phone or OP No...' },
  duplicateWarning: { type: Object, default: null },
});

const emit = defineEmits(['select']);

const query = ref('');
const results = ref([]);
const loading = ref(false);
const open = ref(false);
let debounceTimer = null;

function onInput() {
  clearTimeout(debounceTimer);
  if (query.value.length < 2) { results.value = []; open.value = false; return; }
  debounceTimer = setTimeout(search, 300);
}

async function search() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/patients/search', { params: { q: query.value } });
    results.value = data;
    open.value = true;
  } finally {
    loading.value = false;
  }
}

function select(patient) {
  query.value = `${patient.name} (${patient.op_number})`;
  open.value = false;
  emit('select', patient);
}

function close() { open.value = false; }
</script>
