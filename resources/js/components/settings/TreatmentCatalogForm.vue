<template>
  <div class="space-y-8">
    <!-- Treatment catalog -->
    <section class="grid md:grid-cols-2 gap-6">
      <div>
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-gray-700">Treatment Catalog</h2>
          <button @click="startNewT" class="btn-primary text-xs">+ New</button>
        </div>
        <div v-if="loadingT" class="text-sm text-gray-400">Loading…</div>
        <ul v-else class="space-y-2 max-h-72 overflow-y-auto pr-1">
          <li v-for="t in treatments" :key="t.id"
              class="border rounded-lg px-3 py-2 flex items-center justify-between"
              :class="tForm.id === t.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <div>
              <div class="font-medium text-sm">{{ t.name }}</div>
              <div class="text-xs text-gray-500">{{ t.category || '—' }} · {{ t.duration_min }}min · ₹{{ t.price }}</div>
            </div>
            <div class="flex gap-2">
              <button @click="editT(t)" class="text-blue-600 text-xs hover:underline">Edit</button>
              <button @click="removeT(t)" class="text-red-500 text-xs hover:underline">Delete</button>
            </div>
          </li>
          <li v-if="!treatments.length" class="text-sm text-gray-400">No treatments.</li>
        </ul>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
        <h2 class="font-semibold text-gray-700">{{ tForm.id ? 'Edit' : 'New' }} Treatment</h2>
        <input v-model="tForm.name" class="input" placeholder="Name *" />
        <select v-model="tForm.category" class="input">
          <option value="">Category…</option>
          <option>Electrotherapy</option><option>Manual</option><option>Exercise</option><option>Modality</option>
        </select>
        <div class="grid grid-cols-2 gap-2">
          <input v-model.number="tForm.duration_min" type="number" class="input" placeholder="Duration (min)" />
          <input v-model.number="tForm.price" type="number" step="0.01" class="input" placeholder="Price" />
        </div>
        <textarea v-model="tForm.description" rows="2" class="input" placeholder="Description"></textarea>
        <div v-if="errorT" class="text-sm text-red-600">{{ errorT }}</div>
        <div class="flex gap-2">
          <button @click="saveT" :disabled="savingT" class="btn-primary">{{ savingT ? 'Saving…' : 'Save' }}</button>
          <button v-if="tForm.id" @click="startNewT" class="btn-ghost">Cancel</button>
        </div>
      </div>
    </section>

    <!-- SOAP templates -->
    <section class="grid md:grid-cols-2 gap-6 border-t pt-6">
      <div>
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-gray-700">SOAP Templates</h2>
          <button @click="startNewS" class="btn-primary text-xs">+ New</button>
        </div>
        <ul class="space-y-2 max-h-72 overflow-y-auto pr-1">
          <li v-for="s in templates" :key="s.id"
              class="border rounded-lg px-3 py-2 flex items-center justify-between"
              :class="sForm.id === s.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <div class="font-medium text-sm">{{ s.name }}</div>
            <div class="flex gap-2">
              <button @click="editS(s)" class="text-blue-600 text-xs hover:underline">Edit</button>
              <button @click="removeS(s)" class="text-red-500 text-xs hover:underline">Delete</button>
            </div>
          </li>
          <li v-if="!templates.length" class="text-sm text-gray-400">No templates.</li>
        </ul>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-2">
        <h2 class="font-semibold text-gray-700">{{ sForm.id ? 'Edit' : 'New' }} Template</h2>
        <input v-model="sForm.name" class="input" placeholder="Template name *" />
        <textarea v-model="sForm.subjective" rows="2" class="input" placeholder="Subjective"></textarea>
        <textarea v-model="sForm.objective" rows="2" class="input" placeholder="Objective"></textarea>
        <textarea v-model="sForm.assessment" rows="2" class="input" placeholder="Assessment"></textarea>
        <textarea v-model="sForm.plan" rows="2" class="input" placeholder="Plan"></textarea>
        <div v-if="errorS" class="text-sm text-red-600">{{ errorS }}</div>
        <div class="flex gap-2">
          <button @click="saveS" :disabled="savingS" class="btn-primary">{{ savingS ? 'Saving…' : 'Save' }}</button>
          <button v-if="sForm.id" @click="startNewS" class="btn-ghost">Cancel</button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

// --- Treatments ---
const treatments = ref([]);
const loadingT = ref(true);
const savingT = ref(false);
const errorT = ref('');
const tForm = ref(blankT());
function blankT() { return { id: null, name: '', category: '', duration_min: 30, price: 0, description: '' }; }

async function loadT() {
  loadingT.value = true;
  const { data } = await axios.get('/api/settings/treatment-catalog');
  treatments.value = data.data ?? data;
  loadingT.value = false;
}
function startNewT() { tForm.value = blankT(); errorT.value = ''; }
function editT(t) { tForm.value = { ...blankT(), ...t }; errorT.value = ''; }
async function saveT() {
  if (!tForm.value.name) { errorT.value = 'Name required.'; return; }
  savingT.value = true; errorT.value = '';
  try {
    if (tForm.value.id) await axios.put(`/api/settings/treatment-catalog/${tForm.value.id}`, tForm.value);
    else await axios.post('/api/settings/treatment-catalog', tForm.value);
    toast.success('Treatment saved.'); startNewT(); await loadT();
  } catch (e) { errorT.value = e.response?.data?.message || 'Save failed.'; }
  finally { savingT.value = false; }
}
async function removeT(t) {
  if (!confirm(`Delete ${t.name}?`)) return;
  await axios.delete(`/api/settings/treatment-catalog/${t.id}`); toast.success('Deleted.'); await loadT();
}

// --- SOAP templates ---
const templates = ref([]);
const savingS = ref(false);
const errorS = ref('');
const sForm = ref(blankS());
function blankS() { return { id: null, name: '', subjective: '', objective: '', assessment: '', plan: '' }; }

async function loadS() {
  const { data } = await axios.get('/api/soap-templates');
  templates.value = data.data ?? data;
}
function startNewS() { sForm.value = blankS(); errorS.value = ''; }
function editS(s) { sForm.value = { ...blankS(), ...s }; errorS.value = ''; }
async function saveS() {
  if (!sForm.value.name) { errorS.value = 'Name required.'; return; }
  savingS.value = true; errorS.value = '';
  try {
    if (sForm.value.id) await axios.put(`/api/settings/soap-templates/${sForm.value.id}`, sForm.value);
    else await axios.post('/api/settings/soap-templates', sForm.value);
    toast.success('Template saved.'); startNewS(); await loadS();
  } catch (e) { errorS.value = e.response?.data?.message || 'Save failed.'; }
  finally { savingS.value = false; }
}
async function removeS(s) {
  if (!confirm(`Delete ${s.name}?`)) return;
  await axios.delete(`/api/settings/soap-templates/${s.id}`); toast.success('Deleted.'); await loadS();
}

onMounted(() => { loadT(); loadS(); });
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-ghost { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
