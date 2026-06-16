<template>
  <div class="grid md:grid-cols-2 gap-6">
    <div>
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-700">Exercises</h2>
        <button @click="startNew" class="btn-primary text-xs">+ New</button>
      </div>
      <div class="flex gap-2 mb-2">
        <input v-model="search" @input="debouncedLoad" class="input" placeholder="Search…" />
        <select v-model="filterCat" @change="load" class="input !w-40">
          <option value="">All joints</option>
          <option v-for="j in joints" :key="j">{{ j }}</option>
        </select>
      </div>
      <div v-if="loading" class="text-sm text-gray-400">Loading…</div>
      <ul v-else class="space-y-2 max-h-[26rem] overflow-y-auto pr-1">
        <li v-for="x in list" :key="x.id"
            class="border rounded-lg px-3 py-2 flex items-center justify-between"
            :class="form.id === x.id ? 'border-blue-400 bg-blue-50' : 'border-gray-200'">
          <div>
            <div class="font-medium text-sm">{{ x.name }}</div>
            <div class="text-xs text-gray-500">{{ x.category || '—' }}</div>
          </div>
          <div class="flex gap-2">
            <button @click="edit(x)" class="text-blue-600 text-xs hover:underline">Edit</button>
            <button @click="remove(x)" class="text-red-500 text-xs hover:underline">Delete</button>
          </div>
        </li>
        <li v-if="!list.length" class="text-sm text-gray-400">No exercises.</li>
      </ul>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-3">
      <h2 class="font-semibold text-gray-700">{{ form.id ? 'Edit' : 'New' }} Exercise</h2>
      <input v-model="form.name" class="input" placeholder="Name *" />
      <select v-model="form.category" class="input">
        <option value="">Joint / Category…</option>
        <option v-for="j in joints" :key="j">{{ j }}</option>
      </select>
      <textarea v-model="form.instructions" rows="3" class="input" placeholder="Instructions"></textarea>
      <input v-model="form.image_url" class="input" placeholder="Image URL" />
      <input v-model="form.video_url" class="input" placeholder="Video URL" />

      <!-- Tags chip input -->
      <div>
        <label class="text-xs font-medium text-gray-600 block mb-1">Tags</label>
        <div class="flex flex-wrap gap-1 mb-1">
          <span v-for="(tag, i) in form.tags" :key="i"
                class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full flex items-center gap-1">
            {{ tag }}
            <button @click="form.tags.splice(i, 1)" type="button" class="hover:text-red-500">✕</button>
          </span>
        </div>
        <input v-model="tagInput" @keydown.enter.prevent="addTag" @keydown.,.prevent="addTag"
               class="input" placeholder="Type tag, press Enter" />
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
const joints = ['Cervical', 'Shoulder', 'Elbow', 'Wrist', 'Hip', 'Knee', 'Ankle', 'Lumbar', 'Thoracic'];

const list = ref([]);
const search = ref('');
const filterCat = ref('');
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const tagInput = ref('');
const form = ref(blank());

function blank() {
  return { id: null, name: '', category: '', instructions: '', image_url: '', video_url: '', tags: [], is_active: true };
}

async function load() {
  loading.value = true;
  const { data } = await axios.get('/api/exercises', {
    params: { all: 1, q: search.value, category: filterCat.value },
  });
  list.value = data.data ?? data;
  loading.value = false;
}
let timer;
function debouncedLoad() { clearTimeout(timer); timer = setTimeout(load, 300); }

function startNew() { form.value = blank(); error.value = ''; tagInput.value = ''; }
function edit(x) { form.value = { ...blank(), ...x, tags: Array.isArray(x.tags) ? [...x.tags] : [] }; error.value = ''; }

function addTag() {
  const v = tagInput.value.trim().replace(/,$/, '');
  if (v && !form.value.tags.includes(v)) form.value.tags.push(v);
  tagInput.value = '';
}

async function save() {
  if (!form.value.name) { error.value = 'Name required.'; return; }
  saving.value = true; error.value = '';
  try {
    if (form.value.id) await axios.put(`/api/settings/exercises/${form.value.id}`, form.value);
    else await axios.post('/api/settings/exercises', form.value);
    toast.success('Exercise saved.'); startNew(); await load();
  } catch (e) { error.value = e.response?.data?.message || 'Save failed.'; }
  finally { saving.value = false; }
}

async function remove(x) {
  if (!confirm(`Delete ${x.name}?`)) return;
  await axios.delete(`/api/settings/exercises/${x.id}`); toast.success('Deleted.'); await load();
}

onMounted(load);
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none w-full; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-ghost { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
