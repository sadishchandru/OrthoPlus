<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <input v-model="query" @input="load" type="text" class="input w-72" placeholder="Search medicines..." />
      <button @click="showAdjust = true" class="btn-primary">+ Adjust Stock</button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-500 uppercase">
            <th class="text-left px-4 py-3">Medicine</th>
            <th class="text-left px-4 py-3">Generic</th>
            <th class="text-right px-4 py-3">Stock</th>
            <th class="text-right px-4 py-3">Price</th>
            <th class="text-center px-4 py-3">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="med in medicines" :key="med.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">{{ med.name }}</td>
            <td class="px-4 py-3 text-gray-500">{{ med.generic_name || '—' }}</td>
            <td class="px-4 py-3 text-right" :class="totalStock(med) <= 10 ? 'text-red-600 font-semibold' : 'text-gray-700'">
              {{ totalStock(med) }}
            </td>
            <td class="px-4 py-3 text-right text-gray-700">₹{{ med.sell_price }}</td>
            <td class="px-4 py-3 text-center">
              <span
                :class="totalStock(med) <= 10 ? 'bg-red-100 text-red-700' : totalStock(med) <= 50 ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
                class="text-xs px-2 py-0.5 rounded-full font-medium"
              >
                {{ totalStock(med) <= 10 ? 'Low Stock' : totalStock(med) <= 50 ? 'Limited' : 'In Stock' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <button @click="openAdjust(med)" class="text-xs text-blue-600 hover:underline">Adjust</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Adjust modal -->
    <div v-if="showAdjust" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-semibold text-gray-900 mb-4">Adjust Stock</h3>
        <div class="space-y-3">
          <div v-if="!adjustForm.medicine_id">
            <label class="text-xs text-gray-600 mb-1 block">Search Medicine</label>
            <input v-model="adjustSearch" @input="searchMeds" type="text" class="input w-full" placeholder="Type to search..." />
            <div v-if="medResults.length" class="mt-1 border border-gray-200 rounded-lg overflow-hidden">
              <button v-for="m in medResults" :key="m.id" @click="selectMed(m)" class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-100 last:border-0">
                {{ m.name }} <span class="text-gray-400">({{ m.generic_name }})</span>
              </button>
            </div>
          </div>
          <div v-else class="flex items-center justify-between p-2 bg-blue-50 rounded">
            <span class="text-sm font-medium text-blue-800">{{ adjustForm.medicineName }}</span>
            <button @click="adjustForm.medicine_id = null; adjustForm.medicineName = ''" class="text-gray-400 hover:text-red-500">✕</button>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Type</label>
              <select v-model="adjustForm.type" class="input w-full">
                <option value="in">Stock In</option>
                <option value="out">Stock Out</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Quantity</label>
              <input v-model.number="adjustForm.qty" type="number" min="1" class="input w-full" />
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Reason</label>
            <input v-model="adjustForm.reason" type="text" class="input w-full" placeholder="e.g. Purchase, Dispensed" />
          </div>
        </div>
        <div v-if="adjustError" class="mt-3 text-sm text-red-600">{{ adjustError }}</div>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="showAdjust = false" class="btn-secondary">Cancel</button>
          <button @click="submitAdjust" :disabled="adjusting" class="btn-primary">{{ adjusting ? 'Saving...' : 'Save' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const medicines = ref([]);
const query = ref('');
const showAdjust = ref(false);
const adjustSearch = ref('');
const medResults = ref([]);
const adjusting = ref(false);
const adjustError = ref('');
const adjustForm = reactive({ medicine_id: null, medicineName: '', type: 'in', qty: 1, reason: '' });

onMounted(load);

async function load() {
  const { data } = await axios.get('/api/inventory', { params: { q: query.value } });
  medicines.value = data.data || data;
}

function totalStock(med) {
  return med.stock?.reduce((s, b) => s + b.quantity_in_stock, 0) ?? 0;
}

function openAdjust(med) {
  adjustForm.medicine_id = med.id;
  adjustForm.medicineName = med.name;
  showAdjust.value = true;
}

let medTimer = null;
async function searchMeds() {
  clearTimeout(medTimer);
  medTimer = setTimeout(async () => {
    const { data } = await axios.get('/api/medicines/search', { params: { q: adjustSearch.value } });
    medResults.value = data;
  }, 300);
}

function selectMed(m) {
  adjustForm.medicine_id = m.id;
  adjustForm.medicineName = m.name;
  medResults.value = [];
}

async function submitAdjust() {
  if (!adjustForm.medicine_id || !adjustForm.qty) { adjustError.value = 'Select medicine and quantity.'; return; }
  adjusting.value = true;
  adjustError.value = '';
  try {
    await axios.post('/api/inventory/adjust', adjustForm);
    toast.success('Stock adjusted.');
    showAdjust.value = false;
    load();
    Object.assign(adjustForm, { medicine_id: null, medicineName: '', type: 'in', qty: 1, reason: '' });
  } catch (e) {
    adjustError.value = e.response?.data?.message || 'Failed to adjust stock.';
  } finally {
    adjusting.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
