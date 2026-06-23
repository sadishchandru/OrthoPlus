<template>
  <div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900">In-Patient Billing</h1>

    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3">
      <label class="lbl">Select Admission</label>
      <select v-model="admissionId" @change="loadBills" class="input w-full sm:w-96">
        <option :value="null">Choose an admission...</option>
        <option v-for="a in admissions" :key="a.id" :value="a.id">
          {{ a.ip_number }} — {{ a.patient?.name }} ({{ a.status }})
        </option>
      </select>
    </div>

    <div v-if="admissionId" class="space-y-4">
      <button @click="openGenerate" class="btn-primary">+ Generate Bill</button>

      <div v-for="b in bills" :key="b.id" class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <div>
            <span class="font-semibold text-gray-800">Bill #{{ b.id }}</span>
            <span class="text-xs text-gray-400 ml-2">{{ fmtDate(b.bill_date) }}</span>
          </div>
          <span :class="statusClass(b.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ b.status }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-sm">
          <div><span class="text-gray-500">Room:</span> ₹{{ b.room_charges }}</div>
          <div><span class="text-gray-500">Pharmacy:</span> ₹{{ b.pharmacy_charges }}</div>
          <div><span class="text-gray-500">Surgery:</span> ₹{{ b.surgery_charges }}</div>
          <div><span class="text-gray-500">Implants:</span> ₹{{ b.implant_charges }}</div>
          <div><span class="text-gray-500">Misc:</span> ₹{{ b.misc_charges }}</div>
          <div><span class="text-gray-500">Discount:</span> ₹{{ b.discount }}</div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-3 pt-3 border-t border-gray-100">
          <div class="text-sm">
            <span class="text-gray-500">Total:</span> <span class="font-bold text-gray-800">₹{{ b.total }}</span>
            <span class="text-gray-500 ml-3">Paid:</span> ₹{{ b.paid }}
            <span class="text-gray-500 ml-3">Balance:</span> <span :class="b.balance > 0 ? 'text-red-600 font-semibold' : 'text-green-600'">₹{{ b.balance }}</span>
          </div>
          <button v-if="b.status !== 'paid'" @click="openFinalize(b)" class="text-xs text-blue-600 hover:underline">Record Payment</button>
        </div>
      </div>
      <p v-if="!bills.length" class="text-center text-gray-400 py-6">No bills generated for this admission yet.</p>
    </div>

    <!-- Generate modal -->
    <Modal v-if="showGenerate" title="Generate Bill" @close="showGenerate = false">
      <p class="text-xs text-gray-500 mb-3">Room &amp; implant charges are calculated automatically. Add any extra charges below.</p>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="lbl">Pharmacy (₹)</label><input v-model.number="genForm.pharmacy_charges" type="number" min="0" class="input w-full" /></div>
        <div><label class="lbl">Surgery (₹)</label><input v-model.number="genForm.surgery_charges" type="number" min="0" class="input w-full" /></div>
        <div><label class="lbl">Misc (₹)</label><input v-model.number="genForm.misc_charges" type="number" min="0" class="input w-full" /></div>
        <div><label class="lbl">Discount (₹)</label><input v-model.number="genForm.discount" type="number" min="0" class="input w-full" /></div>
        <div><label class="lbl">GST (₹)</label><input v-model.number="genForm.gst" type="number" min="0" class="input w-full" /></div>
        <div><label class="lbl">Paid (₹)</label><input v-model.number="genForm.paid" type="number" min="0" class="input w-full" /></div>
      </div>
      <template #footer>
        <button @click="showGenerate = false" class="btn-secondary">Cancel</button>
        <button @click="submitGenerate" :disabled="saving" class="btn-primary">{{ saving ? 'Generating...' : 'Generate' }}</button>
      </template>
    </Modal>

    <!-- Finalize modal -->
    <Modal v-if="showFinalize" title="Record Payment" @close="showFinalize = false">
      <p class="text-sm text-gray-600 mb-3">Bill #{{ activeBill?.id }} — Total ₹{{ activeBill?.total }}</p>
      <label class="lbl">Amount Paid (₹)</label>
      <input v-model.number="finalizeForm.paid" type="number" min="0" class="input w-full" />
      <template #footer>
        <button @click="showFinalize = false" class="btn-secondary">Cancel</button>
        <button @click="submitFinalize" :disabled="saving" class="btn-primary">Save</button>
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
const admissions = ref([]);
const admissionId = ref(null);
const bills = ref([]);
const saving = ref(false);

onMounted(loadAdmissions);
async function loadAdmissions() {
  const { data } = await axios.get('/api/admissions', { params: { per_page: 100 } });
  admissions.value = data.data || data;
}
async function loadBills() {
  if (!admissionId.value) { bills.value = []; return; }
  const { data } = await axios.get(`/api/ip-bills/${admissionId.value}`);
  bills.value = data;
}

const showGenerate = ref(false);
const genForm = reactive({ pharmacy_charges: 0, surgery_charges: 0, misc_charges: 0, discount: 0, gst: 0, paid: 0 });
function openGenerate() { Object.assign(genForm, { pharmacy_charges: 0, surgery_charges: 0, misc_charges: 0, discount: 0, gst: 0, paid: 0 }); showGenerate.value = true; }
async function submitGenerate() {
  saving.value = true;
  try {
    await axios.post('/api/ip-bills', { admission_id: admissionId.value, ...genForm });
    toast.success('Bill generated.');
    showGenerate.value = false;
    loadBills();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

const showFinalize = ref(false);
const activeBill = ref(null);
const finalizeForm = reactive({ paid: 0 });
function openFinalize(b) { activeBill.value = b; finalizeForm.paid = b.paid; showFinalize.value = true; }
async function submitFinalize() {
  saving.value = true;
  try { await axios.post(`/api/ip-bills/${activeBill.value.id}/finalize`, finalizeForm); toast.success('Payment recorded.'); showFinalize.value = false; loadBills(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }
function statusClass(s) {
  return {
    draft: 'bg-gray-100 text-gray-600', final: 'bg-blue-100 text-blue-700',
    paid: 'bg-green-100 text-green-700', 'partially-paid': 'bg-yellow-100 text-yellow-700',
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
