<template>
  <div class="body-map-wrapper w-full flex flex-col items-center gap-3">
    <div class="w-full flex items-center justify-between gap-3 flex-wrap">
      <label class="text-sm font-medium text-gray-700">Body Map — tap to mark pain points</label>
      <!-- View toggle -->
      <div class="flex gap-2">
        <button type="button" @click="view = 'front'"
          :class="view === 'front' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-1.5 rounded-full text-sm font-medium">Front</button>
        <button type="button" @click="view = 'back'"
          :class="view === 'back' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-1.5 rounded-full text-sm font-medium">Back</button>
      </div>
    </div>

    <!-- SVG body map -->
    <div class="relative w-full max-w-[300px] sm:max-w-[340px] mx-auto border rounded-xl overflow-hidden bg-gray-50 p-2">
      <svg
        ref="svgRef"
        viewBox="0 0 200 480"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-auto select-none"
        style="touch-action: none;"
      >
        <!-- HEAD -->
        <ellipse cx="100" cy="30" rx="22" ry="26"
          class="body-part" data-part="head" fill="#FDDCB5"
          @click="markPart('head')" @touchend.prevent="markPart('head')"/>

        <!-- NECK -->
        <rect x="90" y="54" width="20" height="14" rx="4"
          class="body-part" data-part="neck" fill="#FDDCB5"
          @click="markPart('neck')" @touchend.prevent="markPart('neck')"/>

        <!-- LEFT SHOULDER -->
        <ellipse cx="62" cy="80" rx="16" ry="12"
          class="body-part" data-part="left_shoulder" fill="#FDDCB5"
          @click="markPart('left_shoulder')" @touchend.prevent="markPart('left_shoulder')"/>

        <!-- RIGHT SHOULDER -->
        <ellipse cx="138" cy="80" rx="16" ry="12"
          class="body-part" data-part="right_shoulder" fill="#FDDCB5"
          @click="markPart('right_shoulder')" @touchend.prevent="markPart('right_shoulder')"/>

        <!-- TORSO (breathe animation) -->
        <rect x="72" y="68" width="56" height="80" rx="10"
          class="body-part breathe" data-part="chest" fill="#FDDCB5"
          @click="markPart('chest')" @touchend.prevent="markPart('chest')"/>

        <!-- ABDOMEN -->
        <rect x="75" y="145" width="50" height="45" rx="8"
          class="body-part" data-part="abdomen" fill="#FDDCB5"
          @click="markPart('abdomen')" @touchend.prevent="markPart('abdomen')"/>

        <!-- LEFT UPPER ARM -->
        <rect x="44" y="90" width="18" height="45" rx="8"
          class="body-part" data-part="left_upper_arm" fill="#FDDCB5"
          @click="markPart('left_upper_arm')" @touchend.prevent="markPart('left_upper_arm')"/>

        <!-- RIGHT UPPER ARM -->
        <rect x="138" y="90" width="18" height="45" rx="8"
          class="body-part" data-part="right_upper_arm" fill="#FDDCB5"
          @click="markPart('right_upper_arm')" @touchend.prevent="markPart('right_upper_arm')"/>

        <!-- LEFT ELBOW -->
        <circle cx="53" cy="138" r="10"
          class="body-part" data-part="left_elbow" fill="#F5C9A0"
          @click="markPart('left_elbow')" @touchend.prevent="markPart('left_elbow')"/>

        <!-- RIGHT ELBOW -->
        <circle cx="147" cy="138" r="10"
          class="body-part" data-part="right_elbow" fill="#F5C9A0"
          @click="markPart('right_elbow')" @touchend.prevent="markPart('right_elbow')"/>

        <!-- LEFT FOREARM -->
        <rect x="44" y="148" width="16" height="40" rx="7"
          class="body-part" data-part="left_forearm" fill="#FDDCB5"
          @click="markPart('left_forearm')" @touchend.prevent="markPart('left_forearm')"/>

        <!-- RIGHT FOREARM -->
        <rect x="140" y="148" width="16" height="40" rx="7"
          class="body-part" data-part="right_forearm" fill="#FDDCB5"
          @click="markPart('right_forearm')" @touchend.prevent="markPart('right_forearm')"/>

        <!-- LEFT WRIST -->
        <rect x="45" y="188" width="14" height="10" rx="4"
          class="body-part" data-part="left_wrist" fill="#F5C9A0"
          @click="markPart('left_wrist')" @touchend.prevent="markPart('left_wrist')"/>

        <!-- RIGHT WRIST -->
        <rect x="141" y="188" width="14" height="10" rx="4"
          class="body-part" data-part="right_wrist" fill="#F5C9A0"
          @click="markPart('right_wrist')" @touchend.prevent="markPart('right_wrist')"/>

        <!-- LEFT HAND + FINGERS -->
        <rect x="43" y="198" width="16" height="18" rx="4"
          class="body-part" data-part="left_hand" fill="#FDDCB5"
          @click="markPart('left_hand')" @touchend.prevent="markPart('left_hand')"/>
        <rect x="38" y="213" width="5" height="12" rx="2" class="body-part" data-part="left_finger_1" fill="#FDDCB5" @click="markPart('left_finger_1')" @touchend.prevent="markPart('left_finger_1')"/>
        <rect x="44" y="212" width="5" height="13" rx="2" class="body-part" data-part="left_finger_2" fill="#FDDCB5" @click="markPart('left_finger_2')" @touchend.prevent="markPart('left_finger_2')"/>
        <rect x="50" y="212" width="5" height="13" rx="2" class="body-part" data-part="left_finger_3" fill="#FDDCB5" @click="markPart('left_finger_3')" @touchend.prevent="markPart('left_finger_3')"/>
        <rect x="56" y="213" width="4" height="12" rx="2" class="body-part" data-part="left_finger_4" fill="#FDDCB5" @click="markPart('left_finger_4')" @touchend.prevent="markPart('left_finger_4')"/>

        <!-- RIGHT HAND + FINGERS -->
        <rect x="141" y="198" width="16" height="18" rx="4"
          class="body-part" data-part="right_hand" fill="#FDDCB5"
          @click="markPart('right_hand')" @touchend.prevent="markPart('right_hand')"/>
        <rect x="157" y="213" width="5" height="12" rx="2" class="body-part" data-part="right_finger_1" fill="#FDDCB5" @click="markPart('right_finger_1')" @touchend.prevent="markPart('right_finger_1')"/>
        <rect x="151" y="212" width="5" height="13" rx="2" class="body-part" data-part="right_finger_2" fill="#FDDCB5" @click="markPart('right_finger_2')" @touchend.prevent="markPart('right_finger_2')"/>
        <rect x="145" y="212" width="5" height="13" rx="2" class="body-part" data-part="right_finger_3" fill="#FDDCB5" @click="markPart('right_finger_3')" @touchend.prevent="markPart('right_finger_3')"/>
        <rect x="140" y="213" width="4" height="12" rx="2" class="body-part" data-part="right_finger_4" fill="#FDDCB5" @click="markPart('right_finger_4')" @touchend.prevent="markPart('right_finger_4')"/>

        <!-- PELVIS / HIP -->
        <rect x="74" y="188" width="52" height="28" rx="10"
          class="body-part" data-part="pelvis" fill="#FDDCB5"
          @click="markPart('pelvis')" @touchend.prevent="markPart('pelvis')"/>

        <!-- LEFT HIP -->
        <ellipse cx="76" cy="200" rx="12" ry="14"
          class="body-part" data-part="left_hip" fill="#F5C9A0"
          @click="markPart('left_hip')" @touchend.prevent="markPart('left_hip')"/>

        <!-- RIGHT HIP -->
        <ellipse cx="124" cy="200" rx="12" ry="14"
          class="body-part" data-part="right_hip" fill="#F5C9A0"
          @click="markPart('right_hip')" @touchend.prevent="markPart('right_hip')"/>

        <!-- LEFT THIGH -->
        <rect x="72" y="215" width="22" height="60" rx="10"
          class="body-part" data-part="left_thigh" fill="#FDDCB5"
          @click="markPart('left_thigh')" @touchend.prevent="markPart('left_thigh')"/>

        <!-- RIGHT THIGH -->
        <rect x="106" y="215" width="22" height="60" rx="10"
          class="body-part" data-part="right_thigh" fill="#FDDCB5"
          @click="markPart('right_thigh')" @touchend.prevent="markPart('right_thigh')"/>

        <!-- LEFT KNEE -->
        <circle cx="83" cy="280" r="12"
          class="body-part" data-part="left_knee" fill="#F5C9A0"
          @click="markPart('left_knee')" @touchend.prevent="markPart('left_knee')"/>

        <!-- RIGHT KNEE -->
        <circle cx="117" cy="280" r="12"
          class="body-part" data-part="right_knee" fill="#F5C9A0"
          @click="markPart('right_knee')" @touchend.prevent="markPart('right_knee')"/>

        <!-- LEFT SHIN/CALF -->
        <rect x="73" y="292" width="20" height="65" rx="9"
          class="body-part" data-part="left_shin" fill="#FDDCB5"
          @click="markPart('left_shin')" @touchend.prevent="markPart('left_shin')"/>

        <!-- RIGHT SHIN/CALF -->
        <rect x="107" y="292" width="20" height="65" rx="9"
          class="body-part" data-part="right_shin" fill="#FDDCB5"
          @click="markPart('right_shin')" @touchend.prevent="markPart('right_shin')"/>

        <!-- LEFT ANKLE -->
        <rect x="74" y="356" width="18" height="10" rx="4"
          class="body-part" data-part="left_ankle" fill="#F5C9A0"
          @click="markPart('left_ankle')" @touchend.prevent="markPart('left_ankle')"/>

        <!-- RIGHT ANKLE -->
        <rect x="108" y="356" width="18" height="10" rx="4"
          class="body-part" data-part="right_ankle" fill="#F5C9A0"
          @click="markPart('right_ankle')" @touchend.prevent="markPart('right_ankle')"/>

        <!-- LEFT FOOT -->
        <ellipse cx="80" cy="374" rx="14" ry="8"
          class="body-part" data-part="left_foot" fill="#FDDCB5"
          @click="markPart('left_foot')" @touchend.prevent="markPart('left_foot')"/>
        <circle cx="68" cy="380" r="3" class="body-part" data-part="left_toe_1" fill="#FDDCB5" @click="markPart('left_toe_1')" @touchend.prevent="markPart('left_toe_1')"/>
        <circle cx="73" cy="382" r="3" class="body-part" data-part="left_toe_2" fill="#FDDCB5" @click="markPart('left_toe_2')" @touchend.prevent="markPart('left_toe_2')"/>
        <circle cx="79" cy="383" r="3" class="body-part" data-part="left_toe_3" fill="#FDDCB5" @click="markPart('left_toe_3')" @touchend.prevent="markPart('left_toe_3')"/>
        <circle cx="85" cy="382" r="3" class="body-part" data-part="left_toe_4" fill="#FDDCB5" @click="markPart('left_toe_4')" @touchend.prevent="markPart('left_toe_4')"/>
        <circle cx="90" cy="380" r="2.5" class="body-part" data-part="left_toe_5" fill="#FDDCB5" @click="markPart('left_toe_5')" @touchend.prevent="markPart('left_toe_5')"/>

        <!-- RIGHT FOOT -->
        <ellipse cx="120" cy="374" rx="14" ry="8"
          class="body-part" data-part="right_foot" fill="#FDDCB5"
          @click="markPart('right_foot')" @touchend.prevent="markPart('right_foot')"/>
        <circle cx="110" cy="380" r="2.5" class="body-part" data-part="right_toe_5" fill="#FDDCB5" @click="markPart('right_toe_5')" @touchend.prevent="markPart('right_toe_5')"/>
        <circle cx="115" cy="382" r="3" class="body-part" data-part="right_toe_4" fill="#FDDCB5" @click="markPart('right_toe_4')" @touchend.prevent="markPart('right_toe_4')"/>
        <circle cx="121" cy="383" r="3" class="body-part" data-part="right_toe_3" fill="#FDDCB5" @click="markPart('right_toe_3')" @touchend.prevent="markPart('right_toe_3')"/>
        <circle cx="127" cy="382" r="3" class="body-part" data-part="right_toe_2" fill="#FDDCB5" @click="markPart('right_toe_2')" @touchend.prevent="markPart('right_toe_2')"/>
        <circle cx="132" cy="380" r="3" class="body-part" data-part="right_toe_1" fill="#FDDCB5" @click="markPart('right_toe_1')" @touchend.prevent="markPart('right_toe_1')"/>

        <!-- SPINE (back view only) -->
        <g v-if="view === 'back'">
          <rect x="96" y="68" width="8" height="8" rx="2" class="body-part" data-part="cervical_spine" fill="#E8B98A" @click="markPart('cervical_spine')" @touchend.prevent="markPart('cervical_spine')"/>
          <rect x="96" y="78" width="8" height="30" rx="2" class="body-part" data-part="thoracic_spine" fill="#E8B98A" @click="markPart('thoracic_spine')" @touchend.prevent="markPart('thoracic_spine')"/>
          <rect x="96" y="110" width="8" height="25" rx="2" class="body-part" data-part="lumbar_spine" fill="#E8B98A" @click="markPart('lumbar_spine')" @touchend.prevent="markPart('lumbar_spine')"/>
          <rect x="96" y="137" width="8" height="12" rx="2" class="body-part" data-part="sacrum" fill="#E8B98A" @click="markPart('sacrum')" @touchend.prevent="markPart('sacrum')"/>
        </g>

        <!-- PAIN MARKERS (animated pulse) -->
        <g v-for="(marker, i) in modelValue" :key="i" @click.stop="removeMarker(i)" @touchend.stop.prevent="removeMarker(i)" class="cursor-pointer">
          <circle
            :cx="marker.x" :cy="marker.y" r="10"
            :fill="markerColor(marker.severity, 'glow')"
            opacity="0.3" class="pulse"/>
          <circle
            :cx="marker.x" :cy="marker.y" r="6"
            :fill="markerColor(marker.severity, 'core')"/>
          <text :x="marker.x" :y="marker.y + 4" text-anchor="middle"
                font-size="7" fill="white" font-weight="bold">{{ marker.severity }}</text>
        </g>
      </svg>
    </div>

    <!-- Severity selector -->
    <div class="flex items-center gap-2 flex-wrap justify-center">
      <span class="text-xs text-gray-500">Severity:</span>
      <button v-for="s in [1, 2, 3]" :key="s" type="button"
        @click="selectedSeverity = s"
        :class="[
          selectedSeverity === s ? 'ring-2 ring-offset-1 scale-110' : '',
          s === 1 ? 'bg-yellow-400' : s === 2 ? 'bg-orange-500' : 'bg-red-500'
        ]"
        class="w-8 h-8 rounded-full text-white text-sm font-bold transition-transform">
        {{ s }}
      </button>
      <span class="text-xs text-gray-400">(tap body to mark, tap marker to remove)</span>
    </div>

    <!-- Marked parts list -->
    <div v-if="modelValue.length" class="w-full">
      <p class="text-xs font-medium text-gray-600 mb-1">Marked areas:</p>
      <div class="flex flex-wrap gap-1">
        <span v-for="(m, i) in modelValue" :key="i"
          class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-red-50 text-red-700 border border-red-200">
          {{ (m.label || m.part || 'area').replace(/_/g, ' ') }}
          <button type="button" @click="removeMarker(i)" class="text-red-400 hover:text-red-600">✕</button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Contract preserved: v-model (modelValue / update:modelValue) so PatientDetail &
// DirectDoctorMode `v-model="record.body_map"` keep saving the same JSON column.
// Marker shape is a superset of the old {x,y,severity,label} (adds `part`), so
// existing records and the print template still render.
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const svgRef = ref(null);
const selectedSeverity = ref(1);
const view = ref('front');

function markerColor(s, kind) {
  if (kind === 'glow') return s === 1 ? '#FCD34D' : s === 2 ? '#FB923C' : '#EF4444';
  return s === 1 ? '#F59E0B' : s === 2 ? '#EA580C' : '#DC2626';
}

function markPart(partName) {
  const el = svgRef.value?.querySelector(`[data-part="${partName}"]`);
  if (!el) return;
  const bbox = el.getBBox();
  const x = Math.round(bbox.x + bbox.width / 2);
  const y = Math.round(bbox.y + bbox.height / 2);

  // Toggle: tap an already-marked part to clear it.
  const idx = props.modelValue.findIndex((m) => m.part === partName);
  if (idx >= 0) {
    const updated = [...props.modelValue];
    updated.splice(idx, 1);
    emit('update:modelValue', updated);
    return;
  }
  emit('update:modelValue', [
    ...props.modelValue,
    { part: partName, x, y, severity: selectedSeverity.value, label: partName.replace(/_/g, ' ') },
  ]);
}

function removeMarker(i) {
  const updated = [...props.modelValue];
  updated.splice(i, 1);
  emit('update:modelValue', updated);
}
</script>

<style scoped>
.body-part { cursor: pointer; transition: fill 0.2s, filter 0.2s; }
.body-part:hover { filter: brightness(0.92); }
.breathe { animation: breathe 3s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
@keyframes breathe {
  0%, 100% { transform: scaleY(1); }
  50% { transform: scaleY(1.015); }
}
.pulse { animation: pulse 1.5s ease-in-out infinite; }
@keyframes pulse {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.1; }
}
</style>
