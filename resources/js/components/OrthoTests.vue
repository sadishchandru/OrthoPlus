<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Orthopedic Tests</label>
      <button type="button" @click="showCustom = true" class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-1 rounded">
        + Custom Test
      </button>
    </div>

    <!-- Quick-add from common list -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="test in COMMON_TESTS"
        :key="test"
        type="button"
        @click="toggleTest(test)"
        :class="isSelected(test)
          ? 'bg-blue-600 text-white'
          : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
        class="text-xs px-3 py-1 rounded-full transition-colors"
      >
        {{ test }}
      </button>
    </div>

    <!-- Results table -->
    <div v-if="rows.length" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-xs text-gray-500 uppercase">
            <th class="text-left px-3 py-2">Test</th>
            <th class="text-center px-3 py-2">Result</th>
            <th class="text-left px-3 py-2">Finding</th>
            <th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, i) in rows" :key="i" class="border-b border-gray-100">
            <td class="px-3 py-2 font-medium">{{ row.name }}</td>
            <td class="px-3 py-2 text-center">
              <div class="flex justify-center gap-2">
                <button
                  type="button"
                  @click="setResult(i, '+')"
                  :class="row.result === '+' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600'"
                  class="w-8 h-8 rounded font-bold text-sm transition-colors"
                >+</button>
                <button
                  type="button"
                  @click="setResult(i, '-')"
                  :class="row.result === '-' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600'"
                  class="w-8 h-8 rounded font-bold text-sm transition-colors"
                >-</button>
              </div>
            </td>
            <td class="px-3 py-2">
              <input v-model="row.finding" @input="emit('update:modelValue', [...rows])" type="text" class="border-b border-gray-200 outline-none w-full text-sm" placeholder="Optional finding" />
            </td>
            <td class="px-2 py-2">
              <button @click="removeRow(i)" type="button" class="text-gray-300 hover:text-red-500">✕</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Custom test input -->
    <div v-if="showCustom" class="flex gap-2">
      <input v-model="customTest" type="text" class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm" placeholder="Test name" @keydown.enter="addCustom" />
      <button type="button" @click="addCustom" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm">Add</button>
      <button type="button" @click="showCustom = false" class="text-gray-400 px-2">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const COMMON_TESTS = [
  'Lachman', 'Anterior Drawer', 'Posterior Drawer', 'McMurray', 'Apley Grind',
  'Valgus Stress', 'Varus Stress', 'Patella Grind', 'Clarke\'s Sign',
  'Straight Leg Raise', 'Bragard\'s', 'FABER', 'FADIR',
  'Hawkins-Kennedy', 'Neer\'s', 'Empty Can', 'Drop Arm',
  'Spurling\'s', 'Phalen\'s', 'Tinel\'s', 'Finkelstein\'s',
];

const props = defineProps({ modelValue: { type: Array, default: () => [] } });
const emit = defineEmits(['update:modelValue']);

const rows = ref(props.modelValue.length ? [...props.modelValue] : []);
const showCustom = ref(false);
const customTest = ref('');

watch(() => props.modelValue, v => { rows.value = [...v]; });

function isSelected(test) { return rows.value.some(r => r.name === test); }

function toggleTest(test) {
  const idx = rows.value.findIndex(r => r.name === test);
  if (idx >= 0) {
    rows.value.splice(idx, 1);
  } else {
    rows.value.push({ name: test, result: '', finding: '' });
  }
  emit('update:modelValue', [...rows.value]);
}

function setResult(i, val) {
  rows.value[i].result = val;
  emit('update:modelValue', [...rows.value]);
}

function removeRow(i) {
  rows.value.splice(i, 1);
  emit('update:modelValue', [...rows.value]);
}

function addCustom() {
  if (!customTest.value.trim()) return;
  rows.value.push({ name: customTest.value.trim(), result: '', finding: '' });
  emit('update:modelValue', [...rows.value]);
  customTest.value = '';
  showCustom.value = false;
}
</script>
