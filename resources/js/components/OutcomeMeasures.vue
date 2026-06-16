<template>
  <div class="space-y-4">
    <div class="flex items-center gap-4">
      <label class="text-sm font-medium text-gray-700">Outcome Measure</label>
      <select v-model="selected" @change="onSelect" class="border border-gray-200 rounded px-3 py-1.5 text-sm">
        <option value="">None</option>
        <option value="quickdash">QuickDASH (Upper Limb)</option>
        <option value="womac">WOMAC (Hip/Knee OA)</option>
        <option value="koos">KOOS (Knee)</option>
      </select>
    </div>

    <!-- QuickDASH -->
    <div v-if="selected === 'quickdash'" class="space-y-3">
      <p class="text-xs text-gray-500">Rate your ability over the past week (1=No difficulty, 5=Unable)</p>
      <div v-for="(q, i) in QUICKDASH" :key="i" class="flex items-center justify-between gap-4">
        <span class="text-sm text-gray-700 flex-1">{{ q }}</span>
        <div class="flex gap-1">
          <button v-for="n in 5" :key="n" @click="setAnswer(i, n)"
            :class="answers[i] === n ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-600'"
            class="w-8 h-8 rounded text-sm font-medium">{{ n }}</button>
        </div>
      </div>
      <div v-if="score !== null" class="p-3 bg-blue-50 rounded-lg text-sm">
        <span class="font-semibold text-blue-800">QuickDASH Score: {{ score }}/100</span>
        <span class="text-blue-600 ml-2">({{ scoreLabel }})</span>
      </div>
    </div>

    <!-- WOMAC (simplified) -->
    <div v-if="selected === 'womac'" class="space-y-3">
      <p class="text-xs text-gray-500">Rate pain/stiffness/function (0=None, 4=Extreme)</p>
      <div v-for="(q, i) in WOMAC" :key="i" class="flex items-center justify-between gap-4">
        <span class="text-sm text-gray-700 flex-1">{{ q }}</span>
        <div class="flex gap-1">
          <button v-for="n in [0,1,2,3,4]" :key="n" @click="setAnswer(i, n)"
            :class="answers[i] === n ? 'bg-orange-500 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-600'"
            class="w-8 h-8 rounded text-sm font-medium">{{ n }}</button>
        </div>
      </div>
      <div v-if="score !== null" class="p-3 bg-orange-50 rounded-lg text-sm">
        <span class="font-semibold text-orange-800">WOMAC Score: {{ score }} / {{ WOMAC.length * 4 }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const QUICKDASH = [
  'Open a tight jar', 'Write', 'Turn a key', 'Prepare a meal',
  'Push open a heavy door', 'Place an object on shelf above head',
  'Do heavy household chores', 'Garden or do yard work',
  'Make a bed', 'Carry a shopping bag',
  'Wash your back', 'Use a knife to cut food',
];

const WOMAC = [
  'Pain when walking on a flat surface', 'Pain when going up/down stairs',
  'Pain at night while in bed', 'Pain when sitting or lying',
  'Pain when standing upright', 'Morning stiffness',
  'Stiffness occurring later in the day', 'Difficulty going down stairs',
  'Difficulty going up stairs', 'Difficulty rising from sitting',
];

const props = defineProps({ modelValue: { type: Object, default: () => ({}) } });
const emit = defineEmits(['update:modelValue']);

const selected = ref(props.modelValue.type || '');
const answers = ref([]);

watch(() => props.modelValue, v => { selected.value = v.type || ''; });

function onSelect() {
  answers.value = [];
  emit('update:modelValue', { type: selected.value, score: null, answers: [] });
}

function setAnswer(i, val) {
  answers.value[i] = val;
  emit('update:modelValue', { type: selected.value, score: score.value, answers: [...answers.value] });
}

const score = computed(() => {
  if (!selected.value || !answers.value.length) return null;
  if (selected.value === 'quickdash') {
    const filled = answers.value.filter(a => a !== undefined);
    if (filled.length < 11) return null;
    const sum = answers.value.slice(0, 11).reduce((a, b) => a + (b || 1), 0);
    return Math.round(((sum / 11) - 1) / 4 * 100);
  }
  if (selected.value === 'womac') {
    return answers.value.reduce((a, b) => a + (b || 0), 0);
  }
  return null;
});

const scoreLabel = computed(() => {
  if (score.value === null) return '';
  if (score.value <= 25) return 'Mild';
  if (score.value <= 50) return 'Moderate';
  if (score.value <= 75) return 'Severe';
  return 'Very Severe';
});
</script>
