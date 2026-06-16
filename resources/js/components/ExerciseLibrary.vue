<template>
  <div class="space-y-4">
    <div class="flex items-center gap-3">
      <input v-model="query" @input="search" type="text" class="input flex-1" placeholder="Search exercises..." />
      <select v-model="category" @change="search" class="input w-36">
        <option value="">All categories</option>
        <option v-for="c in CATEGORIES" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <!-- Selected list -->
    <div v-if="selected.length" class="space-y-2">
      <h4 class="text-sm font-medium text-gray-700">Prescribed Exercises ({{ selected.length }})</h4>
      <div v-for="(ex, i) in selected" :key="ex.id" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
        <img v-if="ex.image_url" :src="ex.image_url" class="w-10 h-10 rounded object-cover" />
        <div class="flex-1">
          <div class="font-medium text-sm">{{ ex.name }}</div>
          <div class="flex gap-2 mt-1">
            <input v-model.number="ex.sets" type="number" min="1" placeholder="Sets" class="w-14 text-xs border border-blue-200 rounded px-1 py-0.5" />
            <input v-model.number="ex.reps" type="number" min="1" placeholder="Reps" class="w-14 text-xs border border-blue-200 rounded px-1 py-0.5" />
            <input v-model.number="ex.hold" type="number" min="0" placeholder="Hold(s)" class="w-16 text-xs border border-blue-200 rounded px-1 py-0.5" />
          </div>
        </div>
        <button @click="remove(i)" type="button" class="text-gray-400 hover:text-red-500">✕</button>
      </div>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
      <div
        v-for="ex in results"
        :key="ex.id"
        @click="add(ex)"
        :class="isAdded(ex) ? 'ring-2 ring-blue-500 bg-blue-50' : 'hover:shadow-md'"
        class="border border-gray-200 rounded-lg p-3 cursor-pointer transition-all"
      >
        <div class="aspect-square bg-gray-100 rounded-lg mb-2 overflow-hidden flex items-center justify-center">
          <img v-if="ex.image_url" :src="ex.image_url" class="w-full h-full object-cover" />
          <span v-else class="text-3xl">🏋️</span>
        </div>
        <div class="text-sm font-medium text-gray-800 truncate">{{ ex.name }}</div>
        <div class="text-xs text-gray-400">{{ ex.category }}</div>
        <div v-if="isAdded(ex)" class="text-xs text-blue-600 font-medium mt-1">✓ Added</div>
      </div>
    </div>

    <div v-if="!results.length && !loading" class="text-center text-gray-400 py-8 text-sm">
      No exercises found. Try a different search.
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const CATEGORIES = ['Knee', 'Hip', 'Shoulder', 'Ankle', 'Back', 'Neck', 'Wrist', 'General'];

const props = defineProps({ modelValue: { type: Array, default: () => [] } });
const emit = defineEmits(['update:modelValue']);

const query = ref('');
const category = ref('');
const results = ref([]);
const loading = ref(false);
const selected = ref([...(props.modelValue || [])]);

onMounted(search);

let timer = null;
function search() {
  clearTimeout(timer);
  timer = setTimeout(doSearch, 300);
}

async function doSearch() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/exercises', { params: { q: query.value, category: category.value } });
    results.value = data.data || data;
  } finally {
    loading.value = false;
  }
}

function isAdded(ex) { return selected.value.some(s => s.id === ex.id); }

function add(ex) {
  if (isAdded(ex)) { remove(selected.value.findIndex(s => s.id === ex.id)); return; }
  selected.value.push({ ...ex, sets: 3, reps: 10, hold: 0 });
  emit('update:modelValue', selected.value);
}

function remove(i) {
  selected.value.splice(i, 1);
  emit('update:modelValue', selected.value);
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
</style>
