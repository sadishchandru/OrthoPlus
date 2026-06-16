<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">Pain Score (VAS)</label>
      <div class="flex items-center gap-2">
        <span class="text-2xl">{{ emoji }}</span>
        <span class="text-lg font-bold" :class="scoreColor">{{ modelValue }}/10</span>
      </div>
    </div>
    <input
      type="range"
      min="0" max="10" step="0.5"
      :value="modelValue"
      @input="$emit('update:modelValue', parseFloat($event.target.value))"
      class="w-full h-2 rounded-full appearance-none cursor-pointer"
      :style="sliderStyle"
    />
    <div class="flex justify-between text-xs text-gray-400">
      <span>0 No Pain</span>
      <span>5 Moderate</span>
      <span>10 Worst</span>
    </div>
    <div class="text-center text-sm font-medium" :class="scoreColor">{{ label }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ modelValue: { type: Number, default: 0 } });
defineEmits(['update:modelValue']);

const emoji = computed(() => {
  const v = props.modelValue;
  if (v <= 1) return '😊';
  if (v <= 3) return '🙂';
  if (v <= 5) return '😐';
  if (v <= 7) return '😟';
  if (v <= 9) return '😣';
  return '😭';
});

const label = computed(() => {
  const v = props.modelValue;
  if (v === 0) return 'No Pain';
  if (v <= 2) return 'Mild Pain';
  if (v <= 4) return 'Moderate Pain';
  if (v <= 6) return 'Moderate-Severe Pain';
  if (v <= 8) return 'Severe Pain';
  return 'Unbearable Pain';
});

const scoreColor = computed(() => {
  const v = props.modelValue;
  if (v <= 3) return 'text-green-600';
  if (v <= 6) return 'text-yellow-600';
  return 'text-red-600';
});

const sliderStyle = computed(() => {
  const pct = (props.modelValue / 10) * 100;
  const color = props.modelValue <= 3 ? '#16a34a' : props.modelValue <= 6 ? '#d97706' : '#dc2626';
  return `background: linear-gradient(to right, ${color} ${pct}%, #e5e7eb ${pct}%)`;
});
</script>
