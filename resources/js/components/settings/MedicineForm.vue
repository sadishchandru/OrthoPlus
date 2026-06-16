<template>
  <div class="grid md:grid-cols-2 gap-6">
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700">Medicines</h2>
        <button @click="startNew" class="btn-primary text-xs">+ New</button>
      </div>
      <input v-model="search" @input="debouncedLoad" class="input mb-2" placeholder="Search name / generic…" />
      <div v-if="loading" class="text-sm text-gray-400">Loading…</div>
      <ul v-else class="space-y-2 max-h-[28rem] overflow-y-auto pr-1">
        <li v-for="m in list" :key="m.id"
            class="border rounded-lg px-3 py-2 flex items-center justify-between"
            :class="form.id === m.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
          <div>
            <div class="font-medium text-sm">{{ m.name }}
              <span class="text-xs" :class="m.quantity <= m.reorder_level ? 'text-red-500' : 'text-gray-400'">· stock {{ m.quantity }}</span>
            </div>
            <div class="text-xs text-gray-500">{{ m.generic_name || '—' }} · {{ m.strength || '' }} {{ m.unit || '' }} · ₹{{ m.sell_price }}</div>
          </div>
          <div class="flex gap-2">
            <button @click="edit(m)" class="text-blue-600 text-xs hover:underline">Edit</button>
            <button @click="remove(m)" class="text-red-500 text-xs hover:underline">Discontinue</button>
          </div>
        </li>
        <li v-if="!list.length" class="text-sm text-gray-400">No medicines.</li>
      </ul>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
      <h2 class="font-semibold text-gray-700">{{ form.id ? 'Edit' : 'New' }} Medicine</h2>
      <div class="grid grid-cols-2 gap-2">
        <input v-model="form.name" class="input" placeholder="Name *" />
        <input v-model="form.generic_name" class="input" placeholder="Generic name" />
        <input v-model="form.manufacturer" class="input" placeholder="Manufacturer" />
        <select v-model="form.unit" class="input">
          <option value="">Unit…</option>
          <option>tablet</option><option>ml</option><option>capsule</option><option>sachet</option><option>cream</option>
        </select>
        <input v-model="form.strength" class="input" placeholder="Strength (e.g. 500mg)" />
        <input v-model.number="form.quantity" type="number" class="input" placeholder="Quantity (stock)" />
        <input v-model.number="form.reorder_level" type="number" class="input" placeholder="Reorder level" />
        <input v-model="form.expiry_date" type="date" class="input" />
        <input v-model.number="form.cost_price" type="number" step="0.01" class="input" placeholder="Purchase price" />
        <input v-model.number="form.sell_price" type="number" step="0.01" class="input" placeholder="Selling price" />
        <input v-model="form.hsn_code" class="input" placeholder="HSN code" />
      </div>
      <div v-if="error" class="text-sm text-red-600">{{ error }}</div>
      <div class="flex gap-2">
        <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
        <button v-if="form.id" @click="startNew" class="btn-ghost">Cancel</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const list = ref([]);
const search = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const form = ref(blank());

function blank() {
  return { id: null, name: '', generic_name: '', manufacturer: '', unit: '', strength: '',
    quantity: 0, reorder_level: 0, expiry_date: '', cost_price: 0, sell_price: 0, hsn_code: '' };
}

async function load() {
  loading.value = true;
  const { data } = await axios.get('/api/settings/medicines', { params: { search: search.value } });
  list.value = data.data ?? data;
  loading.value = false;
}
let timer;
function debouncedLoad() { clearTimeout(timer); timer = setTimeout(load, 300); }

function startNew() { form.value = blank(); error.value = ''; }
function edit(m) { form.value = { ...blank(), ...m, expiry_date: m.expiry_date ? String(m.expiry_date).slice(0, 10) : '' }; error.value = ''; }

async function save() {
  if (!form.value.name) { error.value = 'Name required.'; return; }
  saving.value = true;
  error.value = '';
  try {
    const payload = { ...form.value };
    if (!payload.expiry_date) delete payload.expiry_date;
    if (form.value.id) await axios.put(`/api/settings/medicines/${form.value.id}`, payload);
    else await axios.post('/api/settings/medicines', payload);
    toast.success('Medicine saved.');
    startNew();
    await load();
  } catch (e) {
    error.value = e.response?.data?.message || 'Save failed.';
  } finally {
    saving.value = false;
  }
}

async function remove(m) {
  if (!confirm(`Discontinue ${m.name}?`)) return;
  await axios.delete(`/api/settings/medicines/${m.id}`);
  toast.success('Discontinued.');
  await load();
}

onMounted(load);
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-ghost { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
