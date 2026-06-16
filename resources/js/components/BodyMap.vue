<template>
  <div class="space-y-3">
    <div class="flex items-center gap-4">
      <label class="text-sm font-medium text-gray-700">Body Map — click to mark pain points</label>
      <div class="flex gap-2 text-xs">
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span>Mild</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span>Moderate</span>
        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-600 inline-block"></span>Severe</span>
      </div>
    </div>

    <div class="relative inline-block border rounded-lg bg-gray-50 p-2">
      <svg
        viewBox="0 0 200 400"
        width="200"
        height="400"
        class="cursor-crosshair"
        @click="addPoint"
        ref="svgRef"
      >
        <!-- Body outline -->
        <!-- Head -->
        <ellipse cx="100" cy="30" rx="25" ry="28" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Neck -->
        <rect x="90" y="55" width="20" height="15" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Torso -->
        <rect x="60" y="70" width="80" height="100" rx="5" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Left arm -->
        <rect x="25" y="70" width="32" height="90" rx="10" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Right arm -->
        <rect x="143" y="70" width="32" height="90" rx="10" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Left leg -->
        <rect x="62" y="170" width="32" height="130" rx="10" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>
        <!-- Right leg -->
        <rect x="106" y="170" width="32" height="130" rx="10" fill="#f3d5b5" stroke="#999" stroke-width="1.5"/>

        <!-- Pain markers -->
        <g v-for="(point, i) in modelValue" :key="i">
          <circle
            :cx="point.x"
            :cy="point.y"
            r="8"
            :fill="severityColor(point.severity)"
            fill-opacity="0.75"
            stroke="white"
            stroke-width="1.5"
            @click.stop="removePoint(i)"
            class="cursor-pointer"
          />
          <text :x="point.x" :y="point.y + 4" text-anchor="middle" font-size="9" fill="white">{{ point.severity }}</text>
        </g>
      </svg>
    </div>

    <!-- Severity selector for next point -->
    <div class="flex items-center gap-3">
      <span class="text-xs text-gray-600">Next marker severity:</span>
      <div class="flex gap-2">
        <button
          v-for="s in [1,2,3]"
          :key="s"
          @click="nextSeverity = s"
          :class="nextSeverity === s ? 'ring-2 ring-offset-1' : ''"
          class="w-7 h-7 rounded-full text-white text-xs font-bold"
          :style="{ backgroundColor: severityColor(s) }"
        >{{ s }}</button>
      </div>
      <span class="text-xs text-gray-400">(click on body to place, click marker to remove)</span>
    </div>

    <!-- Labels list -->
    <div v-if="modelValue.length" class="space-y-1">
      <div
        v-for="(point, i) in modelValue"
        :key="i"
        class="flex items-center gap-2 text-xs text-gray-600"
      >
        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: severityColor(point.severity) }"></span>
        <input
          v-model="point.label"
          @change="emit('update:modelValue', [...modelValue])"
          class="border-b border-gray-300 outline-none flex-1"
          placeholder="Label (e.g. Left Knee)"
        />
        <button @click="removePoint(i)" class="text-gray-400 hover:text-red-500">✕</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const svgRef = ref(null);
const nextSeverity = ref(1);

function severityColor(s) {
  return s <= 1 ? '#facc15' : s <= 2 ? '#f97316' : '#dc2626';
}

function addPoint(e) {
  const svg = svgRef.value;
  const rect = svg.getBoundingClientRect();
  const scaleX = 200 / rect.width;
  const scaleY = 400 / rect.height;
  const x = Math.round((e.clientX - rect.left) * scaleX);
  const y = Math.round((e.clientY - rect.top) * scaleY);
  emit('update:modelValue', [...props.modelValue, { x, y, severity: nextSeverity.value, label: '' }]);
}

function removePoint(i) {
  const updated = [...props.modelValue];
  updated.splice(i, 1);
  emit('update:modelValue', updated);
}
</script>
