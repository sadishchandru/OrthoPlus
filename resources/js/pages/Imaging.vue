<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Imaging</h1>
      <button @click="openOrder" class="btn-primary">+ New Order</button>
    </div>

    <div class="flex flex-wrap gap-2">
      <select v-model="statusFilter" @change="load" class="input">
        <option value="">All statuses</option>
        <option value="ordered">Ordered</option>
        <option value="scheduled">Scheduled</option>
        <option value="completed">Completed</option>
        <option value="reported">Reported</option>
      </select>
      <select v-model="modalityFilter" @change="load" class="input">
        <option value="">All modalities</option>
        <option value="xray">X-Ray</option><option value="mri">MRI</option>
        <option value="ct">CT</option><option value="ultrasound">Ultrasound</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-500 uppercase">
            <th class="px-4 py-3 text-left">Patient</th>
            <th class="px-4 py-3 text-left">Modality</th>
            <th class="px-4 py-3 text-left hidden sm:table-cell">Body Part</th>
            <th class="px-4 py-3 text-center">Studies</th>
            <th class="px-4 py-3 text-center">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-800">{{ o.patient?.name }}<div class="text-xs text-gray-400">{{ o.patient?.op_number }}</div></td>
            <td class="px-4 py-3 uppercase text-gray-700">{{ o.modality }}</td>
            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ o.body_part || '—' }}</td>
            <td class="px-4 py-3 text-center text-gray-700">{{ o.studies?.length || 0 }}</td>
            <td class="px-4 py-3 text-center"><span :class="statusClass(o.status)" class="text-xs px-2 py-0.5 rounded-full font-medium capitalize">{{ o.status }}</span></td>
            <td class="px-4 py-3 text-right"><button @click="openUpload(o)" class="text-xs text-blue-600 hover:underline">Add Study</button></td>
          </tr>
          <tr v-if="!orders.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No imaging orders.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Order modal -->
    <Modal v-if="showOrder" title="New Imaging Order" @close="showOrder = false">
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
            <label class="lbl">Modality</label>
            <select v-model="form.modality" class="input w-full">
              <option value="xray">X-Ray</option><option value="mri">MRI</option><option value="ct">CT</option><option value="ultrasound">Ultrasound</option>
            </select>
          </div>
          <div><label class="lbl">Body Part</label><input v-model="form.body_part" class="input w-full" /></div>
        </div>
        <div><label class="lbl">Clinical Indication</label><textarea v-model="form.clinical_indication" rows="2" class="input w-full"></textarea></div>
      </div>
      <template #footer>
        <button @click="showOrder = false" class="btn-secondary">Cancel</button>
        <button @click="submitOrder" :disabled="saving" class="btn-primary">Order</button>
      </template>
    </Modal>

    <!-- Upload study modal -->
    <Modal v-if="showUpload" title="Add Study" @close="showUpload = false">
      <p class="text-sm text-gray-600 mb-3">{{ activeOrder?.patient?.name }} — {{ activeOrder?.modality?.toUpperCase() }}</p>
      <div class="space-y-3">
        <div>
          <label class="lbl">Image URLs (one per line)</label>
          <textarea v-model="imageUrls" rows="3" class="input w-full" placeholder="https://...jpg"></textarea>
        </div>
        <div><label class="lbl">Report URL (optional)</label><input v-model="uploadForm.report_url" class="input w-full" /></div>
        <div><label class="lbl">Radiologist Notes</label><textarea v-model="uploadForm.radiologist_notes" rows="2" class="input w-full"></textarea></div>
      </div>
      <template #footer>
        <button @click="showUpload = false" class="btn-secondary">Cancel</button>
        <button @click="submitUpload" :disabled="saving" class="btn-primary">Save Study</button>
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
const modalityFilter = ref('');
const saving = ref(false);

onMounted(load);
async function load() {
  const { data } = await axios.get('/api/imaging-orders', { params: { status: statusFilter.value, modality: modalityFilter.value, per_page: 50 } });
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
const form = reactive({ patient_id: null, patientName: '', modality: 'xray', body_part: '', clinical_indication: '' });
function openOrder() { Object.assign(form, { patient_id: null, patientName: '', modality: 'xray', body_part: '', clinical_indication: '' }); results.value = []; search.value = ''; showOrder.value = true; }
function select(p) { form.patient_id = p.id; form.patientName = `${p.name} (${p.op_number})`; results.value = []; }
async function submitOrder() {
  if (!form.patient_id) { toast.error('Select a patient.'); return; }
  saving.value = true;
  try { await axios.post('/api/imaging-orders', form); toast.success('Order created.'); showOrder.value = false; load(); }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

const showUpload = ref(false);
const activeOrder = ref(null);
const imageUrls = ref('');
const uploadForm = reactive({ report_url: '', radiologist_notes: '' });
function openUpload(o) { activeOrder.value = o; imageUrls.value = ''; Object.assign(uploadForm, { report_url: '', radiologist_notes: '' }); showUpload.value = true; }
async function submitUpload() {
  const images = imageUrls.value.split('\n').map((u) => u.trim()).filter(Boolean).map((url) => ({ url, type: activeOrder.value.modality }));
  if (!images.length) { toast.error('Add at least one image URL.'); return; }
  saving.value = true;
  try {
    await axios.post(`/api/imaging-orders/${activeOrder.value.id}/upload`, { ...uploadForm, images });
    toast.success('Study saved.');
    showUpload.value = false;
    load();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

function statusClass(s) {
  return {
    ordered: 'bg-gray-100 text-gray-600', scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-yellow-100 text-yellow-700', reported: 'bg-green-100 text-green-700',
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
