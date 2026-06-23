<template>
  <div class="grid md:grid-cols-2 gap-6">
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700">Users</h2>
        <button @click="startNew" class="btn-primary text-xs">+ New</button>
      </div>
      <div v-if="loading" class="text-sm text-gray-400">Loading…</div>
      <ul v-else class="space-y-2">
        <li v-for="u in list" :key="u.id"
            class="border rounded-lg px-3 py-2 flex items-center justify-between"
            :class="form.id === u.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
          <div>
            <div class="font-medium text-sm">{{ u.name }} <span class="text-gray-400 text-xs">@{{ u.username }}</span></div>
            <div class="text-[11px] uppercase tracking-wide text-gray-400 mt-0.5">{{ u.module || 'clinic' }}</div>
            <div class="flex flex-wrap gap-1 mt-0.5">
              <span v-for="r in u.roles" :key="r.id" class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">{{ r.name }}</span>
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="edit(u)" class="text-blue-600 text-xs hover:underline">Edit</button>
            <button @click="remove(u)" class="text-red-500 text-xs hover:underline">Delete</button>
          </div>
        </li>
        <li v-if="!list.length" class="text-sm text-gray-400">No users.</li>
      </ul>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
      <h2 class="font-semibold text-gray-700">{{ form.id ? 'Edit' : 'New' }} User</h2>
      <div class="grid grid-cols-2 gap-2">
        <input v-model="form.name" class="input" placeholder="Full name *" />
        <input v-model="form.username" class="input" placeholder="Username *" />
        <input v-model="form.email" class="input" placeholder="Email (optional)" />
        <input v-model="form.password" type="password" class="input" :placeholder="form.id ? 'New password (blank=keep)' : 'Password *'" />
        <select v-model="form.module" class="input">
          <option value="clinic">Clinic</option>
          <option value="hospital">Hospital</option>
          <option value="both">Both</option>
        </select>
      </div>

      <div>
        <label class="text-xs font-medium text-gray-600 block mb-1">Roles *</label>
        <div class="flex flex-wrap gap-2">
          <label v-for="r in allRoles" :key="r.id" class="flex items-center gap-1 text-sm border rounded-lg px-2 py-1 cursor-pointer"
                 :class="form.roles.includes(r.name) ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <input type="checkbox" :value="r.name" v-model="form.roles" @change="seedPagesFromRoles" class="rounded" />
            {{ r.label || r.name }}
          </label>
        </div>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="text-xs font-medium text-gray-600">Pages</label>
          <button type="button" @click="seedPagesFromRoles" class="text-xs text-blue-600 hover:underline">Reset from roles</button>
        </div>
        <div class="flex flex-wrap gap-2">
          <label v-for="p in PAGES" :key="p.key" class="flex items-center gap-1 text-sm border rounded-lg px-2 py-1 cursor-pointer"
                 :class="form.page_access.includes(p.key) ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
            <input type="checkbox" :value="p.key" v-model="form.page_access" class="rounded" />
            {{ p.label }}
          </label>
        </div>
        <p v-if="form.roles.includes('root')" class="text-xs text-amber-600 mt-1">Root = full access (page selection ignored).</p>
        <p v-else class="text-xs text-gray-400 mt-1">Auto-filled from roles. Tick/untick to override per user.</p>
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
const allRoles = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const form = ref(blank());

const PAGES = [
  { key: 'dashboard',     label: 'Dashboard' },
  { key: 'patients',      label: 'Patients' },
  { key: 'appointments',  label: 'Appointments' },
  { key: 'direct-doctor', label: 'Direct Doctor' },
  { key: 'opd',           label: 'OPD' },
  { key: 'inpatients',    label: 'In-Patients' },
  { key: 'beds',          label: 'Beds' },
  { key: 'surgery',       label: 'Surgery' },
  { key: 'imaging',       label: 'Imaging' },
  { key: 'staff',         label: 'Staff' },
  { key: 'ip-billing',    label: 'IP Billing' },
  { key: 'op-devices',    label: 'O&P' },
  { key: 'hospital-reports', label: 'Reports' },
  { key: 'inventory',     label: 'Inventory' },
  { key: 'pharmacy',      label: 'Pharmacy' },
  { key: 'invoices',      label: 'Invoices' },
  { key: 'treatments',    label: 'Treatments' },
  { key: 'settings',      label: 'Settings' },
];

function blank() { return { id: null, name: '', username: '', email: '', password: '', module: 'clinic', roles: [], page_access: [] }; }

// Union of selected roles' page_access ('*' => all pages).
function unionFor(roleNames) {
  const set = new Set();
  let star = false;
  allRoles.value.forEach((r) => {
    if (!roleNames.includes(r.name)) return;
    (r.page_access || []).forEach((p) => (p === '*' ? (star = true) : set.add(p)));
  });
  return star ? PAGES.map((p) => p.key) : [...set];
}

function seedPagesFromRoles() { form.value.page_access = unionFor(form.value.roles); }

async function load() {
  loading.value = true;
  const [{ data: users }, { data: roles }] = await Promise.all([
    axios.get('/api/settings/users'),
    axios.get('/api/settings/users/roles'),
  ]);
  list.value = users.data ?? users;
  allRoles.value = roles;
  loading.value = false;
}

function startNew() { form.value = blank(); error.value = ''; }
function edit(u) {
  const roleNames = (u.roles || []).map((r) => r.name);
  form.value = {
    id: u.id, name: u.name, username: u.username, email: u.email || '', password: '',
    module: u.module || 'clinic',
    roles: roleNames,
    page_access: (u.page_access && u.page_access.length) ? [...u.page_access] : unionFor(roleNames),
  };
  error.value = '';
}

async function save() {
  if (!form.value.name || !form.value.username || !form.value.roles.length) {
    error.value = 'Name, username and at least one role required.'; return;
  }
  if (!form.value.id && !form.value.password) { error.value = 'Password required.'; return; }
  saving.value = true; error.value = '';
  try {
    const payload = { ...form.value };
    if (form.value.id && !payload.password) delete payload.password;
    if (!payload.email) delete payload.email;
    if (form.value.id) await axios.put(`/api/settings/users/${form.value.id}`, payload);
    else await axios.post('/api/settings/users', payload);
    toast.success('User saved.'); startNew(); await load();
  } catch (e) { error.value = e.response?.data?.message || 'Save failed.'; }
  finally { saving.value = false; }
}

async function remove(u) {
  if (!confirm(`Delete ${u.name}?`)) return;
  await axios.delete(`/api/settings/users/${u.id}`); toast.success('Deleted.'); await load();
}

onMounted(load);
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-ghost { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
