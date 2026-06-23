<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h1 class="text-xl font-bold text-gray-900">Staff</h1>
      <button @click="openStaff" class="btn-primary">+ Add Staff</button>
    </div>

    <div class="flex flex-wrap gap-2">
      <input v-model="query" @input="load" class="input w-full sm:w-64" placeholder="Search name / ID / phone..." />
      <select v-model="roleFilter" @change="load" class="input">
        <option value="">All roles</option>
        <option value="doctor">Doctor</option>
        <option value="nurse">Nurse</option>
        <option value="technician">Technician</option>
        <option value="admin">Admin</option>
        <option value="housekeeping">Housekeeping</option>
      </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-xs text-gray-500 uppercase">
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Role</th>
            <th class="px-4 py-3 text-left hidden sm:table-cell">Department</th>
            <th class="px-4 py-3 text-left hidden md:table-cell">Phone</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="s in staff" :key="s.id" class="border-t border-gray-100 hover:bg-gray-50">
            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ s.staff_id }}</td>
            <td class="px-4 py-3 font-medium text-gray-800">{{ s.name }}<span v-if="!s.is_active" class="text-xs text-gray-400 ml-1">(inactive)</span></td>
            <td class="px-4 py-3 capitalize text-gray-700">{{ s.role }}</td>
            <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ s.department || '—' }}</td>
            <td class="px-4 py-3 text-gray-600 hidden md:table-cell">{{ s.phone || '—' }}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <button @click="openShift(s)" class="text-xs text-blue-600 hover:underline mr-3">Shift</button>
              <button @click="openStaff(s)" class="text-xs text-gray-500 hover:underline">Edit</button>
            </td>
          </tr>
          <tr v-if="!staff.length"><td colspan="6" class="px-4 py-8 text-center text-gray-400">No staff found.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Staff modal -->
    <Modal v-if="showStaff" :title="form.id ? 'Edit Staff' : 'Add Staff'" @close="showStaff = false">
      <div class="space-y-3">
        <div><label class="lbl">Name</label><input v-model="form.name" class="input w-full" /></div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="lbl">Role</label>
            <select v-model="form.role" class="input w-full">
              <option value="doctor">Doctor</option><option value="nurse">Nurse</option>
              <option value="technician">Technician</option><option value="admin">Admin</option>
              <option value="housekeeping">Housekeeping</option>
            </select>
          </div>
          <div><label class="lbl">Department</label><input v-model="form.department" class="input w-full" /></div>
          <div><label class="lbl">Phone</label><input v-model="form.phone" class="input w-full" /></div>
          <div><label class="lbl">Email</label><input v-model="form.email" type="email" class="input w-full" /></div>
          <div><label class="lbl">Qualification</label><input v-model="form.qualification" class="input w-full" /></div>
          <div><label class="lbl">License No.</label><input v-model="form.license_number" class="input w-full" /></div>
          <div><label class="lbl">Join Date</label><input v-model="form.join_date" type="date" class="input w-full" /></div>
          <div>
            <label class="lbl">Salary Type</label>
            <select v-model="form.salary_type" class="input w-full"><option value="fixed">Fixed</option><option value="hourly">Hourly</option></select>
          </div>
          <div><label class="lbl">Salary (₹)</label><input v-model.number="form.salary" type="number" min="0" class="input w-full" /></div>
          <label class="flex items-end gap-2 pb-2 text-sm text-gray-700"><input v-model="form.is_active" type="checkbox" class="rounded" /> Active</label>
        </div>
      </div>
      <template #footer>
        <button @click="showStaff = false" class="btn-secondary">Cancel</button>
        <button @click="submitStaff" :disabled="saving" class="btn-primary">{{ saving ? 'Saving...' : 'Save' }}</button>
      </template>
    </Modal>

    <!-- Shift modal -->
    <Modal v-if="showShift" title="Assign Shift" @close="showShift = false">
      <p class="text-sm text-gray-600 mb-3">{{ active?.name }} — {{ active?.staff_id }}</p>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="lbl">Date</label><input v-model="shiftForm.date" type="date" class="input w-full" /></div>
        <div>
          <label class="lbl">Shift</label>
          <select v-model="shiftForm.shift" class="input w-full">
            <option value="morning">Morning</option><option value="afternoon">Afternoon</option>
            <option value="night">Night</option><option value="custom">Custom</option>
          </select>
        </div>
        <div><label class="lbl">Start</label><input v-model="shiftForm.start_time" type="time" class="input w-full" /></div>
        <div><label class="lbl">End</label><input v-model="shiftForm.end_time" type="time" class="input w-full" /></div>
      </div>
      <template #footer>
        <button @click="showShift = false" class="btn-secondary">Cancel</button>
        <button @click="submitShift" :disabled="saving" class="btn-primary">Assign</button>
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
const staff = ref([]);
const query = ref('');
const roleFilter = ref('');
const saving = ref(false);

onMounted(load);

async function load() {
  const { data } = await axios.get('/api/staff', { params: { q: query.value, role: roleFilter.value, per_page: 50 } });
  staff.value = data.data || data;
}

const showStaff = ref(false);
const blank = () => ({ id: null, name: '', role: 'nurse', department: '', phone: '', email: '', qualification: '', license_number: '', join_date: '', salary_type: 'fixed', salary: 0, is_active: true });
const form = reactive(blank());
function openStaff(s = null) {
  Object.assign(form, blank());
  if (s && s.id) Object.assign(form, { ...s, join_date: s.join_date ? s.join_date.slice(0, 10) : '' });
  showStaff.value = true;
}
async function submitStaff() {
  if (!form.name) { toast.error('Name required.'); return; }
  saving.value = true;
  try {
    if (form.id) await axios.put(`/api/staff/${form.id}`, form);
    else await axios.post('/api/staff', form);
    toast.success('Saved.');
    showStaff.value = false;
    load();
  } catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}

const showShift = ref(false);
const active = ref(null);
const shiftForm = reactive({ date: new Date().toISOString().slice(0, 10), shift: 'morning', start_time: '', end_time: '' });
function openShift(s) { active.value = s; Object.assign(shiftForm, { date: new Date().toISOString().slice(0, 10), shift: 'morning', start_time: '', end_time: '' }); showShift.value = true; }
async function submitShift() {
  saving.value = true;
  try { await axios.post(`/api/staff/${active.value.id}/shift`, shiftForm); toast.success('Shift assigned.'); showShift.value = false; }
  catch (e) { toast.error(e.response?.data?.message || 'Failed.'); }
  finally { saving.value = false; }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
