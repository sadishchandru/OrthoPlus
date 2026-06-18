<template>
  <div class="space-y-4">
    <!-- Medicines -->
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Medicines</label>
      <button type="button" @click="addItem" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded">
        + Add Medicine
      </button>
    </div>

    <div v-for="(item, i) in items" :key="i" class="border border-gray-100 rounded-lg p-3 space-y-2 bg-gray-50">
      <div class="flex gap-2">
        <div class="flex-1 relative">
          <input v-model="item.search" @input="searchMeds(i)" type="text" class="input w-full" placeholder="Search medicine..." />
          <div v-if="item.suggestions?.length" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-40 overflow-y-auto">
            <button v-for="m in item.suggestions" :key="m.id" @mousedown.prevent="selectMed(i, m)"
                    class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm">
              <span class="font-medium">{{ m.name }}</span>
              <span v-if="m.generic_name" class="text-gray-500 ml-2 text-xs">({{ m.generic_name }})</span>
              <span class="text-gray-400 ml-2 text-xs">stock {{ m.quantity }} · ₹{{ m.sell_price }}</span>
            </button>
          </div>
        </div>
        <button @click="removeItem(i)" type="button" class="text-gray-300 hover:text-red-500 px-2">✕</button>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div>
          <label class="text-xs text-gray-500">Dose</label>
          <input v-model="item.dose" type="text" class="input w-full" placeholder="500mg" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Frequency</label>
          <select v-model="item.frequency" class="input w-full">
            <option value="">Select</option>
            <option>OD</option><option>BD</option><option>TDS</option><option>QDS</option>
            <option>SOS</option><option>HS</option><option>Stat</option>
          </select>
        </div>
        <div>
          <label class="text-xs text-gray-500">Duration</label>
          <input v-model="item.duration" type="text" class="input w-full" placeholder="5 days" />
        </div>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <div>
          <label class="text-xs text-gray-500">Qty</label>
          <input v-model.number="item.qty" type="number" min="0" class="input w-full" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Unit price</label>
          <input v-model.number="item.unit_price" type="number" step="0.01" min="0" class="input w-full" />
        </div>
        <div>
          <label class="text-xs text-gray-500">Amount</label>
          <div class="input w-full bg-gray-100 text-gray-600">₹{{ (item.qty * item.unit_price || 0).toFixed(2) }}</div>
        </div>
      </div>
    </div>

    <!-- Services (free-text) -->
    <div class="flex items-center justify-between pt-2 border-t">
      <label class="text-sm font-medium text-gray-700">Services</label>
      <button type="button" @click="addService" class="text-xs bg-purple-50 hover:bg-purple-100 text-purple-700 px-3 py-1 rounded">
        + Add Service
      </button>
    </div>
    <div v-for="(s, i) in services" :key="'s' + i" class="grid grid-cols-12 gap-2 items-center">
      <input v-model="s.service_name" class="input col-span-6" placeholder="Service name" />
      <input v-model.number="s.qty" type="number" min="1" class="input col-span-2" placeholder="Qty" />
      <input v-model.number="s.unit_price" type="number" step="0.01" class="input col-span-2" placeholder="Price" />
      <div class="col-span-1 text-sm text-gray-600">₹{{ (s.qty * s.unit_price || 0).toFixed(0) }}</div>
      <button @click="services.splice(i, 1)" type="button" class="col-span-1 text-gray-300 hover:text-red-500">✕</button>
    </div>

    <div>
      <label class="text-xs text-gray-600 block mb-1">Notes</label>
      <textarea v-model="notes" rows="2" class="input w-full" placeholder="Special instructions..."></textarea>
    </div>

    <!-- Estimated total -->
    <div class="flex justify-between items-center bg-blue-50 rounded-lg px-4 py-2 text-sm font-semibold text-blue-800">
      <span>Estimated Total</span>
      <span>₹{{ estimatedTotal.toFixed(2) }}</span>
    </div>

    <div v-if="error" class="text-sm text-red-600">{{ error }}</div>

    <div class="flex gap-2">
      <button @click="submit" :disabled="loading || !items.length" class="btn-primary flex-1">
        {{ loading ? 'Saving...' : 'Save Prescription' }}
      </button>
      <button v-if="savedId" @click="printRx" type="button" class="btn-secondary px-4">Print Rx</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({
  patientId: { type: Number, required: true },
  clinicalRecordId: { type: Number, default: null },
});
const emit = defineEmits(['saved']);
const toast = useToast();

const items = ref([]);
const services = ref([]);
const notes = ref('');
const loading = ref(false);
const error = ref('');
const savedId = ref(null);

const estimatedTotal = computed(() =>
  items.value.reduce((s, it) => s + (it.qty * it.unit_price || 0), 0) +
  services.value.reduce((s, sv) => s + (sv.qty * sv.unit_price || 0), 0)
);

function addItem() {
  items.value.push({ medicine_id: null, medicine_name: '', search: '', dose: '', frequency: '', duration: '', qty: 1, unit_price: 0, suggestions: [] });
}
function removeItem(i) { items.value.splice(i, 1); }
function addService() { services.value.push({ service_name: '', qty: 1, unit_price: 0 }); }

let debounceTimers = {};
async function searchMeds(i) {
  clearTimeout(debounceTimers[i]);
  const q = items.value[i].search;
  if (q.length < 2) { items.value[i].suggestions = []; return; }
  debounceTimers[i] = setTimeout(async () => {
    const { data } = await axios.get('/api/medicines/search', { params: { q } });
    items.value[i].suggestions = data;
  }, 300);
}

function selectMed(i, med) {
  const it = items.value[i];
  it.medicine_id = med.id;
  it.medicine_name = med.name;
  it.search = med.name + (med.generic_name ? ` (${med.generic_name})` : '');
  it.unit_price = med.sell_price || 0;
  it.suggestions = [];
}

async function submit() {
  const invalid = items.value.some(it => !it.medicine_id || !it.dose || !it.frequency);
  if (invalid) { error.value = 'Fill medicine, dose and frequency for all medicines.'; return; }
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/api/prescriptions', {
      patient_id: props.patientId,
      clinical_record_id: props.clinicalRecordId,
      items: items.value.map(({ medicine_id, medicine_name, dose, frequency, duration, qty, unit_price }) =>
        ({ medicine_id, medicine_name, dose, frequency, duration, qty, unit_price })),
      services: services.value.filter(s => s.service_name),
      notes: notes.value,
    });
    savedId.value = data.id;
    toast.success('Prescription saved.');
    emit('saved', data);
    items.value = [];
    services.value = [];
    notes.value = '';
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save.';
  } finally {
    loading.value = false;
  }
}

function printRx() {
  if (savedId.value) window.open(`/print/prescription/${savedId.value}`, '_blank');
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
