<template>
  <div class="grid md:grid-cols-2 gap-6">
    <!-- List -->
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700">Therapists</h2>
        <button @click="startNew" class="btn-primary text-xs">+ New</button>
      </div>
      <div v-if="loading" class="text-sm text-gray-400">Loading…</div>
      <ul v-else class="space-y-2">
        <li v-for="t in list" :key="t.id"
            class="border rounded-lg px-3 py-2 flex items-center justify-between"
            :class="form.id === t.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
          <div>
            <div class="font-medium text-sm">{{ t.name }}
              <span v-if="!t.is_active" class="text-xs text-red-500">(inactive)</span>
            </div>
            <div class="text-xs text-gray-500">{{ t.specialization || '—' }} · {{ t.commission_pct }}%</div>
          </div>
          <div class="flex gap-2">
            <button @click="edit(t)" class="text-blue-600 text-xs hover:underline">Edit</button>
            <button @click="remove(t)" class="text-red-500 text-xs hover:underline">Delete</button>
          </div>
        </li>
        <li v-if="!list.length" class="text-sm text-gray-400">No therapists yet.</li>
      </ul>
    </div>

    <!-- Form -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
      <h2 class="font-semibold text-gray-700">{{ form.id ? 'Edit' : 'New' }} Therapist</h2>
      <div class="grid grid-cols-2 gap-2">
        <input v-model="form.name" class="input" placeholder="Name *" />
        <input v-model="form.phone" class="input" placeholder="Phone" />
        <input v-model="form.email" class="input" placeholder="Email" />
        <input v-model="form.specialization" class="input" placeholder="Specialization" />
        <input v-model.number="form.commission_pct" type="number" min="0" max="100" class="input" placeholder="Commission %" />
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.is_active" type="checkbox" class="rounded" /> Active
        </label>
      </div>

      <!-- Schedule repeater -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="text-xs font-medium text-gray-600">Schedule</label>
          <button @click="addSlot" type="button" class="text-xs text-blue-600">+ Add day</button>
        </div>
        <div v-for="(s, i) in form.schedule" :key="i" class="grid grid-cols-6 gap-1 mb-1 items-center">
          <select v-model="s.day" class="input !px-1 text-xs">
            <option v-for="d in days" :key="d">{{ d }}</option>
          </select>
          <input v-model="s.start" type="time" class="input !px-1 text-xs" />
          <input v-model="s.end" type="time" class="input !px-1 text-xs" />
          <input v-model="s.break_start" type="time" class="input !px-1 text-xs" title="Break start" />
          <input v-model="s.break_end" type="time" class="input !px-1 text-xs" title="Break end" />
          <button @click="form.schedule.splice(i, 1)" type="button" class="text-red-400 text-xs">✕</button>
        </div>
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
const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

const list = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const form = ref(blank());

function blank() {
  return { id: null, name: '', phone: '', email: '', specialization: '', commission_pct: 0, is_active: true, schedule: [] };
}

async function load() {
  loading.value = true;
  const { data } = await axios.get('/api/settings/therapists');
  list.value = data.data ?? data;
  loading.value = false;
}

function startNew() { form.value = blank(); error.value = ''; }
function edit(t) { form.value = { ...blank(), ...t, schedule: t.schedule ? [...t.schedule] : [] }; error.value = ''; }
function addSlot() { form.value.schedule.push({ day: 'Mon', start: '09:00', end: '17:00', break_start: '13:00', break_end: '14:00' }); }

async function save() {
  if (!form.value.name) { error.value = 'Name required.'; return; }
  saving.value = true;
  error.value = '';
  try {
    if (form.value.id) {
      await axios.put(`/api/settings/therapists/${form.value.id}`, form.value);
    } else {
      await axios.post('/api/settings/therapists', form.value);
    }
    toast.success('Therapist saved.');
    startNew();
    await load();
  } catch (e) {
    error.value = e.response?.data?.message || 'Save failed.';
  } finally {
    saving.value = false;
  }
}

async function remove(t) {
  if (!confirm(`Delete ${t.name}?`)) return;
  await axios.delete(`/api/settings/therapists/${t.id}`);
  toast.success('Deleted.');
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
