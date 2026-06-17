<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-800">Pharmacy Counter</h1>

    <!-- Patient search -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
      <label class="text-xs font-medium text-gray-600 block mb-1">Patient (OP No / name / phone) — optional for walk-in</label>
      <div class="relative max-w-md">
        <input v-model="patientQuery" @input="searchPatient" class="input" placeholder="Search patient…" />
        <div v-if="patientResults.length" class="absolute z-10 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1">
          <button v-for="p in patientResults" :key="p.id" @click="selectPatient(p)"
                  class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm">
            <span class="font-medium">{{ p.name }}</span>
            <span class="text-gray-500 text-xs ml-2">{{ p.op_number }} · {{ p.phone }}</span>
          </button>
        </div>
      </div>
      <div v-if="patient" class="mt-2 text-sm flex items-center gap-2">
        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ patient.name }} ({{ patient.op_number }})</span>
        <button @click="clearPatient" class="text-xs text-gray-400 hover:text-red-500">clear</button>
      </div>
    </div>

    <!-- Active prescriptions -->
    <div v-if="prescriptions.length" class="bg-white border border-gray-200 rounded-xl p-4">
      <h2 class="font-semibold text-gray-700 mb-2">Active Prescriptions</h2>
      <div v-for="rx in prescriptions" :key="rx.id" class="border border-gray-100 rounded-lg p-3 mb-2">
        <div class="flex items-center justify-between mb-1">
          <span class="text-sm text-gray-500">Rx #{{ rx.id }} · {{ rx.date || '—' }}</span>
          <button @click="loadRx(rx)" class="text-xs btn-primary">Load items</button>
        </div>
        <div class="text-xs text-gray-500">{{ rx.items.map(i => i.medicine_name).filter(Boolean).join(', ') || 'No medicines' }}</div>
      </div>
    </div>

    <!-- Line items -->
    <div class="bg-white border border-gray-200 rounded-xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700">Bill Items</h2>
        <button @click="addAdhocRow" class="btn-ghost text-xs">+ Ad-hoc item</button>
      </div>

      <!-- Medicine search — results show Generic Name / HSN Code / Expiry Date inline -->
      <div class="relative mb-3">
        <input v-model="medQuery" @input="searchMedGlobal" class="input" placeholder="Search medicine to add (name / generic)…" autocomplete="off" />
        <div v-if="medResults.length" class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-72 overflow-y-auto">
          <button v-for="m in medResults" :key="m.id" @click="addMed(m)"
                  class="w-full text-left px-3 py-2 hover:bg-blue-50 text-xs border-b border-gray-50 last:border-0 flex flex-wrap items-center gap-x-3 gap-y-0.5">
            <span class="font-medium text-sm text-gray-800">{{ m.name }}</span>
            <span class="text-gray-500">Generic: {{ m.generic_name || '—' }}</span>
            <span class="text-gray-500">HSN: {{ m.hsn_code || '—' }}</span>
            <span class="text-gray-500">Exp: {{ fmtDate(m.expiry_date) }}</span>
            <span class="text-gray-400 ml-auto">stock {{ m.quantity }} · ₹{{ m.sell_price }}</span>
          </button>
        </div>
        <div v-else-if="medQuery.length >= 2 && medSearched" class="text-xs text-gray-400 mt-1">No medicines found.</div>
      </div>

      <div class="table-responsive"><table class="w-full text-sm">
        <thead>
          <tr class="text-left text-gray-400 text-xs border-b">
            <th class="py-1">Item</th><th class="w-20">Qty</th><th class="w-24">Price</th><th class="w-24">Amount</th><th class="w-8"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(it, i) in items" :key="i" class="border-b border-gray-50">
            <td class="py-1 pr-2">
              <input v-model="it.medicine_name" class="input" placeholder="Item name" />
            </td>
            <td><input v-model.number="it.qty" type="number" min="1" class="input !px-2" /></td>
            <td><input v-model.number="it.unit_price" type="number" step="0.01" class="input !px-2" /></td>
            <td class="text-gray-700">₹{{ (it.qty * it.unit_price || 0).toFixed(2) }}</td>
            <td><button @click="items.splice(i, 1)" class="text-red-400 hover:text-red-600">✕</button></td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="text-gray-400 py-3 text-center">No items. Add medicine or ad-hoc.</td></tr>
        </tbody>
      </table></div>

      <!-- Totals -->
      <div class="flex justify-end mt-4">
        <div class="w-64 space-y-1 text-sm">
          <div class="flex justify-between"><span>Subtotal</span><span>₹{{ subtotal.toFixed(2) }}</span></div>
          <div class="flex justify-between items-center"><span>Discount</span>
            <input v-model.number="discount" type="number" min="0" class="input !w-24 !py-1 text-right" /></div>
          <div class="flex justify-between items-center"><span>Tax</span>
            <input v-model.number="tax" type="number" min="0" class="input !w-24 !py-1 text-right" /></div>
          <div class="flex justify-between font-bold text-base border-t pt-1"><span>Total</span><span>₹{{ total.toFixed(2) }}</span></div>
        </div>
      </div>

      <div class="flex items-center justify-between mt-4">
        <select v-model="paymentMethod" class="input !w-40">
          <option>cash</option><option>card</option><option>upi</option>
        </select>
        <button @click="save" :disabled="saving || !items.length" class="btn-primary">
          {{ saving ? 'Saving…' : 'Generate Bill & Deduct Stock' }}
        </button>
      </div>
    </div>

    <!-- Last invoice -->
    <div v-if="lastInvoice" class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
      <span class="text-sm text-green-800">Invoice <b>{{ lastInvoice.invoice_no }}</b> · ₹{{ lastInvoice.total }} created.</span>
      <button @click="printBill" class="btn-primary text-xs">Print Bill</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const patientQuery = ref('');
const patientResults = ref([]);
const patient = ref(null);
const prescriptions = ref([]);

const items = ref([]);
const discount = ref(0);
const tax = ref(0);
const paymentMethod = ref('cash');
const saving = ref(false);
const lastInvoice = ref(null);

const subtotal = computed(() => items.value.reduce((s, it) => s + (it.qty * it.unit_price || 0), 0));
const total = computed(() => Math.max(0, subtotal.value - (discount.value || 0) + (tax.value || 0)));

function fmtDate(d) { return d ? String(d).slice(0, 10) : '—'; }

let pt;
function searchPatient() {
  clearTimeout(pt);
  if (patientQuery.value.length < 2) { patientResults.value = []; return; }
  pt = setTimeout(async () => {
    try {
      const { data } = await axios.get('/api/pharmacy/patients/search', { params: { q: patientQuery.value } });
      patientResults.value = data;
    } catch { patientResults.value = []; }
  }, 300);
}

async function selectPatient(p) {
  patient.value = p;
  patientResults.value = [];
  patientQuery.value = '';
  try {
    const { data } = await axios.get(`/api/pharmacy/patients/${p.id}/prescriptions`);
    prescriptions.value = data;
  } catch { prescriptions.value = []; }
}

function clearPatient() { patient.value = null; prescriptions.value = []; }

function loadRx(rx) {
  rx.items.forEach((it) => {
    if (!it.medicine_id) return;
    items.value.push({ medicine_id: it.medicine_id, medicine_name: it.medicine_name, qty: it.qty || 1, unit_price: it.unit_price || 0 });
  });
  toast.success('Prescription items loaded.');
}

function addAdhocRow() { items.value.push({ medicine_id: null, medicine_name: '', qty: 1, unit_price: 0 }); }

// Medicine search (above the table — not clipped by overflow)
const medQuery = ref('');
const medResults = ref([]);
const medSearched = ref(false);
let mt;
function searchMedGlobal() {
  clearTimeout(mt);
  if (medQuery.value.length < 2) { medResults.value = []; medSearched.value = false; return; }
  mt = setTimeout(async () => {
    try {
      const { data } = await axios.get('/api/medicines/search', { params: { q: medQuery.value } });
      medResults.value = data;
      medSearched.value = true;
    } catch { medResults.value = []; }
  }, 300);
}
function addMed(m) {
  items.value.push({ medicine_id: m.id, medicine_name: m.name, qty: 1, unit_price: Number(m.sell_price) || 0 });
  medQuery.value = '';
  medResults.value = [];
  medSearched.value = false;
}

async function save() {
  saving.value = true;
  try {
    const payload = {
      patient_id: patient.value?.id || null,
      items: items.value
        .filter((it) => it.medicine_name && it.qty > 0)
        .map((it) => ({
          medicine_id: it.medicine_id || null,
          medicine_name: it.medicine_name,
          qty: it.qty,
          unit_price: it.unit_price,
        })),
      discount: discount.value || 0,
      tax: tax.value || 0,
      payment_method: paymentMethod.value,
    };
    if (!payload.items.length) { toast.error('Add at least one valid item.'); return; }
    const { data } = await axios.post('/api/pharmacy/invoices', payload);
    lastInvoice.value = data;
    toast.success(`Bill ${data.invoice_no} created. Stock deducted.`);
    items.value = []; discount.value = 0; tax.value = 0;
  } catch (e) {
    toast.error(e.response?.data?.message || 'Failed to generate bill.');
  } finally {
    saving.value = false;
  }
}

function printBill() {
  if (lastInvoice.value) window.open(`/print/invoice/${lastInvoice.value.id}`, '_blank');
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-ghost { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
