<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Range of Motion</label>
      <button type="button" @click="addRow" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded">
        + Add Joint
      </button>
    </div>

    <div v-if="rows.length" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
            <th class="text-left px-2 py-2">Joint</th>
            <th class="text-left px-2 py-2">Movement</th>
            <th class="text-right px-2 py-2">Degrees</th>
            <th class="text-right px-2 py-2">Normal</th>
            <th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in rows" :key="i" class="border-b border-gray-100">
            <td class="px-2 py-1.5">
              <select v-model="row.joint" @change="onChange" class="input-sm w-full">
                <option value="">Select</option>
                <option v-for="j in JOINTS" :key="j" :value="j">{{ j }}</option>
              </select>
            </td>
            <td class="px-2 py-1.5">
              <input v-model="row.movement" @input="onChange" type="text" class="input-sm w-full" placeholder="Flexion" />
            </td>
            <td class="px-2 py-1.5">
              <input v-model.number="row.degrees" @input="onChange" type="number" min="0" max="360" class="input-sm w-20 text-right" />
            </td>
            <td class="px-2 py-1.5">
              <input v-model.number="row.normal" @input="onChange" type="number" min="0" max="360" class="input-sm w-20 text-right" placeholder="—" />
            </td>
            <td class="px-2 py-1.5">
              <button @click="removeRow(i)" type="button" class="text-gray-300 hover:text-red-500">✕</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else class="text-xs text-gray-400 italic">No ROM measurements added. Click "+ Add Joint".</p>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const JOINTS = ['Cervical', 'Shoulder', 'Elbow', 'Wrist', 'Hip', 'Knee', 'Ankle', 'Lumbar', 'Thoracic'];

const props = defineProps({ modelValue: { type: Array, default: () => [] } });
const emit = defineEmits(['update:modelValue']);

const rows = ref(Array.isArray(props.modelValue) ? [...props.modelValue] : []);

watch(() => props.modelValue, v => { const a = Array.isArray(v) ? v : []; if (JSON.stringify(a) !== JSON.stringify(rows.value)) rows.value = [...a]; });

function addRow() { rows.value.push({ joint: '', movement: '', degrees: null, normal: null }); onChange(); }
function removeRow(i) { rows.value.splice(i, 1); onChange(); }
function onChange() { emit('update:modelValue', rows.value.map(r => ({ ...r }))); }
</script>

<style scoped>
@reference "tailwindcss";
.input-sm { @apply border border-gray-200 rounded px-2 py-1 text-sm focus:ring-1 focus:ring-blue-400 outline-none; }
</style>
