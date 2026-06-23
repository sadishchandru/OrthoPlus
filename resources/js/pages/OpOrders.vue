<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Orthotics & Prosthetics</h1>
      <button @click="openOrder" class="btn-primary">+ New Order</button>
    </div>

    <div class="flex flex-wrap gap-2">
      <select v-model="statusFilter" @change="load" class="input">
        <option value="">All statuses</option>
        <option value="ordered">Ordered</option>
        <option value="fabricating">Fabricating</option>
        <option value="fitting">Fitting</option>
        <option value="delivered">Delivered</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-500 uppercase">
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">Device</th>
            <th class="px-4 py-3 text-left hidden sm:table-cell">Type / Limb</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">{{ o.patient?.name }}<div class="text-xs text-gray-400">{{ o.patient?.op_number }}</div></td>
            <td class="px-4 py-3 text-gray-700">{{ o.device_name }}</td>
            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell capitalize">{{ o.order_type }}<span v-if="o.affected_limb"> · {{ o.affected_limb }}</span></td>
            <td class="px-4 py-3 text-center"><span :class="statusClass(o.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ o.status }}</span></td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button @click="openFitting(o)" class="text-xs text-blue-600 hover:underline mr-3">Fitting</button>
              <button @click="advance(o)" v-if="o.status !== 'delivered'" class="text-xs text-green-600 hover:underline">Advance</button>
            </td>
          </tr>
          <tr v-if="!orders.length"><td colspan="5" class="px-4 py-8 text-center text-gray-400">No O&P orders.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Order modal -->
    <Modal v-if="showOrder" title="New O&P Order" @close="showOrder = false">
      <div class="space-y-3">
        <div v-if="!form.patient_id">
          <label class="lbl">Search Patient</label>
          <input v-model="search" @input="searchPatients" class="input w-full" placeholder="Name / OP no..." />
          <div v-if="results.length" class="mt-1 border border-gray-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <button v-for="p in results" :key="p.id" @mousedown.prevent="select(p)" class="w-full text-left px-3 py-2 hover:bg-gray-50 text-sm border-b border-gray-100 last:border-0">
              {{ p.name }} <span class="text-gray-400">{{ p.op_number }}</span>
            </button>
          </div>
        </div>
        <div v-else class="flex items-center justify-between p-2 bg-blue-50 rounded">
          <span class="text-sm font-medium text-blue-800">{{ form.patientName }}</span>
          <button @click="form.patient_id = null" class="text-gray-400 hover:text-red-500">✕</button>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Type</label>
            <select v-model="form.order_type" class="input w-full"><option value="orthotic">Orthotic</option><option value="prosthetic">Prosthetic</option></select>
          </div>
          <div><label class="lbl">Device Name</label><input v-model="form.device_name" class="input w-full" /></div>
          <div><label class="lbl">Affected Limb</label><input v-model="form.affected_limb" class="input w-full" /></div>
          <div><label class="lbl">Material</label><input v-model="form.material" class="input w-full" /></div>
          <div><label class="lbl">Cost (₹)</label><input v-model.number="form.cost" type="number" min="0" class="input w-full" /></div>
          <div><label class="lbl">Fitting Date</label><input v-model="form.fitting_date" type="date" class="input w-full" /></div>
        </div>
      </div>
      <template #footer>
        <button @click="showOrder = false" class="btn-secondary">Cancel</button>
        <button @click="submitOrder" :disabled="saving" class="btn-primary">Order</button>
      </template>
    </Modal>

    <!-- Fitting modal -->
    <Modal v-if="showFitting" title="Record Fitting" @close="showFitting = false">
      <p class="text-sm text-gray-600 mb-3">{{ active?.patient?.name }} — {{ active?.device_name }}</p>
      <div class="space-y-3">
        <div><label class="lbl">Fitting Date</label><input v-model="fittingForm.fitting_date" type="date" class="input w-full" /></div>
        <div><label class="lbl">Adjustments Made</label><textarea v-model="fittingForm.adjustments_made" rows="2" class="input w-full"></textarea></div>
        <div><label class="lbl">Outcome</label><textarea v-model="fittingForm.outcome" rows="2" class="input w-full"></textarea></div>
        <div><label class="lbl">Next Fitting</label><input v-model="fittingForm.next_fitting_date" type="date" class="input w-full" /></div>
      </div>
      <template #footer>
        <button @click="showFitting = false" class="btn-secondary">Cancel</button>
        <button @click="submitFitting" :disabled="saving" class="btn-primary">Save</button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import Modal from '../components/HModal.vue';

const toast = useToast();
const orders = ref([]);
const statusFilter = ref('');
const saving = ref(false);
const flow = ['ordered', 'fabricating', 'fitting', 'delivered'];

onMounted(load);
async function load() {
  const { data } = await axios.get('/api/op-orders', { params: { status: statusFilter.value, per_page: 50 } });
  orders.value = data.data || data;
}

const search = ref('');
const results = ref([]);
let t = null;
function searchPatients() {
  clearTimeout(t);
  t = setTimeout(async () => {
    if (!search.value) { results.value = []; return; }
    const { data } = await axios.get('/api/patients/search', { params: { q: search.value } });
    results.value = data;
  }, 300);
}

const showOrder = ref(false);
const form = reactive({ patient_id: null, patientName: '', order_type: 'orthotic', device_name: '', affected_limb: '', material: '', cost: 0, fitting_date: '' });
function openOrder() { Object.assign(form, { patient_id: null, patientName: '', order_type: 'orthotic', device_name: '', affected_limb: '', material: '', cost: 0, fitting_date: '' }); results.value = []; search.value = ''; showOrder.value = true; }
function select(p) { form.patient_id = p.id; form.patientName = `${p.name} (${p.op_number})`; results.value = []; }
async function submitOrder() {
  if (!form.patient_id || !form.device_name) { toast.error('Patient and device required.'); return; }
  saving.value = true;
  try { await axios.post('/api/op-orders', form); toast.success('Order created.'); showOrder.value = false; load(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

async function advance(o) {
  const next = flow[Math.min(flow.indexOf(o.status) + 1, flow.length - 1)];
  try { await axios.put(`/api/op-orders/${o.id}`, { status: next }); load(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
}

const showFitting = ref(false);
const active = ref(null);
const fittingForm = reactive({ fitting_date: new Date().toISOString().slice(0, 10), adjustments_made: '', outcome: '', next_fitting_date: '' });
function openFitting(o) { active.value = o; Object.assign(fittingForm, { fitting_date: new Date().toISOString().slice(0, 10), adjustments_made: '', outcome: '', next_fitting_date: '' }); showFitting.value = true; }
async function submitFitting() {
  saving.value = true;
  try { await axios.post(`/api/op-orders/${active.value.id}/fitting`, fittingForm); toast.success('Fitting recorded.'); showFitting.value = false; load(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function statusClass(s) {
  return {
    ordered: 'bg-gray-100 text-gray-600', fabricating: 'bg-blue-100 text-blue-700',
    fitting: 'bg-yellow-100 text-yellow-700', delivered: 'bg-green-100 text-green-700',
  }[s] || 'bg-gray-100';
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
