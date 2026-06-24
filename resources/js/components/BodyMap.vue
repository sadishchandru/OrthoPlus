<template>
  <div class="body-map-wrapper w-full flex flex-col items-center gap-3">
    <!-- Reactive overlay: tints each SELECTED part shape by severity (CSS fill beats the gradient). -->
    <component :is="'style'" v-html="highlightCss"></component>
    <div class="w-full flex items-center justify-between gap-3 flex-wrap">
      <label class="text-sm font-medium text-gray-700">Body Map — tap to mark pain points</label>
      <div class="flex gap-2">
        <button type="button" @click="view = 'front'"
          :class="view === 'front' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-1.5 rounded-full text-sm font-medium">Front</button>
        <button type="button" @click="view = 'back'"
          :class="view === 'back' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-1.5 rounded-full text-sm font-medium">Back</button>
      </div>
    </div>

    <!-- Single flat teal fill, no per-part strokes → overlapping rounded shapes merge
         into one smooth silhouette. viewBox cropped to body (head y14 → feet y462) so
         no empty space. Stops propagation so a body tap never steals form focus. -->
    <div class="relative w-full max-w-[280px] sm:max-w-[320px] mx-auto border rounded-2xl overflow-hidden bg-white"
         @click.stop @mousedown.stop>
      <svg
        ref="svgRef"
        viewBox="0 0 200 472"
        preserveAspectRatio="xMidYMid meet"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-auto block"
        style="display:block; max-height:74vh; touch-action:none; pointer-events:all;"
        @click.stop @mousedown.stop @touchstart.stop
      >
        <!-- 3D-style shading: radial gradient gives the silhouette rounded depth. -->
        <defs>
          <radialGradient id="bodyShade" cx="42%" cy="28%" r="85%">
            <stop offset="0%" stop-color="#8ECBCD"/>
            <stop offset="55%" stop-color="#5B9EA0"/>
            <stop offset="100%" stop-color="#33696B"/>
          </radialGradient>
        </defs>
        <!-- ===== SILHOUETTE (gradient fill; selected parts re-tinted via highlightCss) ===== -->
        <g fill="url(#bodyShade)" stroke="none">
          <!-- HEAD / NECK -->
          <ellipse cx="100" cy="44" rx="26" ry="30" class="body-part" data-part="head" @click.stop="mark('head','Head')" @touchend.stop.prevent="mark('head','Head')"/>
          <rect x="86" y="72" width="28" height="22" rx="9" class="body-part" data-part="neck" @click.stop="mark('neck','Neck')" @touchend.stop.prevent="mark('neck','Neck')"/>

          <!-- SHOULDERS (gender: narrower for female) -->
          <ellipse cx="66" cy="102" :rx="shoulderRx" ry="15" class="body-part" data-part="left_shoulder" @click.stop="mark('left_shoulder','Left Shoulder')" @touchend.stop.prevent="mark('left_shoulder','Left Shoulder')"/>
          <ellipse cx="134" cy="102" :rx="shoulderRx" ry="15" class="body-part" data-part="right_shoulder" @click.stop="mark('right_shoulder','Right Shoulder')" @touchend.stop.prevent="mark('right_shoulder','Right Shoulder')"/>

          <!-- TORSO — front: chest/abdomen · back: upper/lower back -->
          <template v-if="view === 'front'">
            <path :d="torsoPath" class="body-part breathe" data-part="chest" @click.stop="mark('chest','Chest')" @touchend.stop.prevent="mark('chest','Chest')"/>
            <path d="M66,168 Q66,202 78,206 L122,206 Q134,202 134,168 Z" class="body-part" data-part="abdomen" @click.stop="mark('abdomen','Abdomen')" @touchend.stop.prevent="mark('abdomen','Abdomen')"/>
          </template>
          <template v-else>
            <path :d="torsoPath" class="body-part" data-part="upper_back" @click.stop="mark('upper_back','Upper Back')" @touchend.stop.prevent="mark('upper_back','Upper Back')"/>
            <path d="M66,168 Q66,202 78,206 L122,206 Q134,202 134,168 Z" class="body-part" data-part="lower_back" @click.stop="mark('lower_back','Lower Back')" @touchend.stop.prevent="mark('lower_back','Lower Back')"/>
          </template>

          <!-- ARMS — hang vertically at sides -->
          <rect x="46" y="100" width="18" height="70" rx="9" class="body-part" data-part="left_upper_arm" @click.stop="mark('left_upper_arm','Left Upper Arm')" @touchend.stop.prevent="mark('left_upper_arm','Left Upper Arm')"/>
          <rect x="136" y="100" width="18" height="70" rx="9" class="body-part" data-part="right_upper_arm" @click.stop="mark('right_upper_arm','Right Upper Arm')" @touchend.stop.prevent="mark('right_upper_arm','Right Upper Arm')"/>
          <circle cx="55" cy="174" r="10" class="body-part" data-part="left_elbow" @click.stop="mark('left_elbow','Left Elbow')" @touchend.stop.prevent="mark('left_elbow','Left Elbow')"/>
          <circle cx="145" cy="174" r="10" class="body-part" data-part="right_elbow" @click.stop="mark('right_elbow','Right Elbow')" @touchend.stop.prevent="mark('right_elbow','Right Elbow')"/>
          <rect x="47" y="176" width="16" height="52" rx="8" class="body-part" data-part="left_forearm" @click.stop="mark('left_forearm','Left Forearm')" @touchend.stop.prevent="mark('left_forearm','Left Forearm')"/>
          <rect x="137" y="176" width="16" height="52" rx="8" class="body-part" data-part="right_forearm" @click.stop="mark('right_forearm','Right Forearm')" @touchend.stop.prevent="mark('right_forearm','Right Forearm')"/>

          <!-- HANDS + FINGERS (larger, visible) -->
          <ellipse cx="55" cy="236" rx="12" ry="11" class="body-part" data-part="left_hand" @click.stop="mark('left_hand','Left Hand')" @touchend.stop.prevent="mark('left_hand','Left Hand')"/>
          <rect x="43" y="240" width="8" height="16" rx="4" class="body-part" data-part="left_thumb" @click.stop="mark('left_thumb','Left Thumb')" @touchend.stop.prevent="mark('left_thumb','Left Thumb')"/>
          <rect x="49" y="244" width="7" height="20" rx="3" class="body-part" data-part="left_index" @click.stop="mark('left_index','Left Index')" @touchend.stop.prevent="mark('left_index','Left Index')"/>
          <rect x="56" y="245" width="7" height="21" rx="3" class="body-part" data-part="left_middle" @click.stop="mark('left_middle','Left Middle')" @touchend.stop.prevent="mark('left_middle','Left Middle')"/>
          <rect x="63" y="244" width="7" height="19" rx="3" class="body-part" data-part="left_ring" @click.stop="mark('left_ring','Left Ring')" @touchend.stop.prevent="mark('left_ring','Left Ring')"/>

          <ellipse cx="145" cy="236" rx="12" ry="11" class="body-part" data-part="right_hand" @click.stop="mark('right_hand','Right Hand')" @touchend.stop.prevent="mark('right_hand','Right Hand')"/>
          <rect x="149" y="240" width="8" height="16" rx="4" class="body-part" data-part="right_thumb" @click.stop="mark('right_thumb','Right Thumb')" @touchend.stop.prevent="mark('right_thumb','Right Thumb')"/>
          <rect x="144" y="244" width="7" height="20" rx="3" class="body-part" data-part="right_index" @click.stop="mark('right_index','Right Index')" @touchend.stop.prevent="mark('right_index','Right Index')"/>
          <rect x="137" y="245" width="7" height="21" rx="3" class="body-part" data-part="right_middle" @click.stop="mark('right_middle','Right Middle')" @touchend.stop.prevent="mark('right_middle','Right Middle')"/>
          <rect x="130" y="244" width="7" height="19" rx="3" class="body-part" data-part="right_ring" @click.stop="mark('right_ring','Right Ring')" @touchend.stop.prevent="mark('right_ring','Right Ring')"/>

          <!-- PELVIS / HIPS (gender: wider for female) -->
          <path :d="pelvisPath" class="body-part" data-part="pelvis" @click.stop="mark('pelvis','Pelvis')" @touchend.stop.prevent="mark('pelvis','Pelvis')"/>
          <ellipse cx="80" cy="216" :rx="hipRx" ry="13" class="body-part" data-part="left_hip" @click.stop="mark('left_hip','Left Hip')" @touchend.stop.prevent="mark('left_hip','Left Hip')"/>
          <ellipse cx="120" cy="216" :rx="hipRx" ry="13" class="body-part" data-part="right_hip" @click.stop="mark('right_hip','Right Hip')" @touchend.stop.prevent="mark('right_hip','Right Hip')"/>

          <!-- THIGHS (gender shape) — 12px centre gap -->
          <path :d="leftThighPath" class="body-part" data-part="left_thigh" @click.stop="mark('left_thigh','Left Thigh')" @touchend.stop.prevent="mark('left_thigh','Left Thigh')"/>
          <path :d="rightThighPath" class="body-part" data-part="right_thigh" @click.stop="mark('right_thigh','Right Thigh')" @touchend.stop.prevent="mark('right_thigh','Right Thigh')"/>

          <!-- KNEES / SHINS -->
          <ellipse cx="80" cy="340" rx="15" ry="14" class="body-part" data-part="left_knee" @click.stop="mark('left_knee','Left Knee')" @touchend.stop.prevent="mark('left_knee','Left Knee')"/>
          <ellipse cx="120" cy="340" rx="15" ry="14" class="body-part" data-part="right_knee" @click.stop="mark('right_knee','Right Knee')" @touchend.stop.prevent="mark('right_knee','Right Knee')"/>
          <path d="M72,352 Q69,378 70,412 L70,426 Q70,434 82,434 L92,434 Q97,432 97,424 L97,402 Q99,376 94,352 Z" class="body-part" data-part="left_shin" @click.stop="mark('left_shin','Left Shin')" @touchend.stop.prevent="mark('left_shin','Left Shin')"/>
          <path d="M128,352 Q131,378 130,412 L130,426 Q130,434 118,434 L108,434 Q103,432 103,424 L103,402 Q101,376 106,352 Z" class="body-part" data-part="right_shin" @click.stop="mark('right_shin','Right Shin')" @touchend.stop.prevent="mark('right_shin','Right Shin')"/>

          <!-- ANKLES / FEET / TOES -->
          <ellipse cx="84" cy="436" rx="12" ry="8" class="body-part" data-part="left_ankle" @click.stop="mark('left_ankle','Left Ankle')" @touchend.stop.prevent="mark('left_ankle','Left Ankle')"/>
          <ellipse cx="116" cy="436" rx="12" ry="8" class="body-part" data-part="right_ankle" @click.stop="mark('right_ankle','Right Ankle')" @touchend.stop.prevent="mark('right_ankle','Right Ankle')"/>
          <path d="M72,440 Q66,446 68,458 Q70,464 82,464 L94,464 Q99,462 97,452 L95,444 Q90,440 80,440 Z" class="body-part" data-part="left_foot" @click.stop="mark('left_foot','Left Foot')" @touchend.stop.prevent="mark('left_foot','Left Foot')"/>
          <ellipse cx="71" cy="463" rx="4" ry="5" class="body-part" data-part="left_big_toe" @click.stop="mark('left_big_toe','Left Big Toe')" @touchend.stop.prevent="mark('left_big_toe','Left Big Toe')"/>
          <ellipse cx="79" cy="465" rx="3.5" ry="4.5" class="body-part" data-part="left_toe_2" @click.stop="mark('left_toe_2','Left Toe 2')" @touchend.stop.prevent="mark('left_toe_2','Left Toe 2')"/>
          <ellipse cx="86" cy="464" rx="3" ry="4" class="body-part" data-part="left_toe_3" @click.stop="mark('left_toe_3','Left Toe 3')" @touchend.stop.prevent="mark('left_toe_3','Left Toe 3')"/>
          <path d="M128,440 Q134,446 132,458 Q130,464 118,464 L106,464 Q101,462 103,452 L105,444 Q110,440 120,440 Z" class="body-part" data-part="right_foot" @click.stop="mark('right_foot','Right Foot')" @touchend.stop.prevent="mark('right_foot','Right Foot')"/>
          <ellipse cx="129" cy="463" rx="4" ry="5" class="body-part" data-part="right_big_toe" @click.stop="mark('right_big_toe','Right Big Toe')" @touchend.stop.prevent="mark('right_big_toe','Right Big Toe')"/>
          <ellipse cx="121" cy="465" rx="3.5" ry="4.5" class="body-part" data-part="right_toe_2" @click.stop="mark('right_toe_2','Right Toe 2')" @touchend.stop.prevent="mark('right_toe_2','Right Toe 2')"/>
          <ellipse cx="114" cy="464" rx="3" ry="4" class="body-part" data-part="right_toe_3" @click.stop="mark('right_toe_3','Right Toe 3')" @touchend.stop.prevent="mark('right_toe_3','Right Toe 3')"/>
        </g>

        <!-- Guide lines + spine (lighter, no clicks) -->
        <g :stroke="CLine" stroke-width="1" fill="none" opacity="0.45" pointer-events="none">
          <template v-if="view === 'front'">
            <path d="M88,128 Q100,135 112,128"/>
            <line x1="100" y1="140" x2="100" y2="196"/>
          </template>
          <template v-else>
            <line x1="100" y1="96" x2="100" y2="220"/>
            <path d="M82,120 Q100,132 118,120"/>
          </template>
        </g>

        <!-- SPINE segments (back view, clickable) -->
        <g v-if="view === 'back'" :fill="CDark" stroke="none">
          <rect x="95" y="94" width="10" height="10" rx="2" class="body-part" data-part="cervical" @click.stop="mark('cervical','Cervical Spine')" @touchend.stop.prevent="mark('cervical','Cervical Spine')"/>
          <rect x="95" y="106" width="10" height="38" rx="2" class="body-part" data-part="thoracic" @click.stop="mark('thoracic','Thoracic Spine')" @touchend.stop.prevent="mark('thoracic','Thoracic Spine')"/>
          <rect x="95" y="146" width="10" height="30" rx="2" class="body-part" data-part="lumbar" @click.stop="mark('lumbar','Lumbar Spine')" @touchend.stop.prevent="mark('lumbar','Lumbar Spine')"/>
          <rect x="95" y="178" width="10" height="18" rx="2" class="body-part" data-part="sacrum" @click.stop="mark('sacrum','Sacrum')" @touchend.stop.prevent="mark('sacrum','Sacrum')"/>
        </g>

        <!-- PAIN MARKERS — concentric rings. Tap to remove. -->
        <g v-for="m in markers" :key="m.id" class="cursor-pointer"
           @click.stop="removeMarker(m.id)" @touchend.stop.prevent="removeMarker(m.id)">
          <circle :cx="m.x" :cy="m.y" r="13" fill="none" :stroke="sevColor(m.severity)" stroke-width="1.5" opacity="0.4"/>
          <circle :cx="m.x" :cy="m.y" r="8.5" fill="none" :stroke="sevColor(m.severity)" stroke-width="1.5" opacity="0.7"/>
          <circle :cx="m.x" :cy="m.y" r="5" :fill="sevColor(m.severity)"/>
          <text :x="m.x" :y="m.y + 2.5" text-anchor="middle" font-size="6" fill="white" font-weight="bold" pointer-events="none">{{ m.severity }}</text>
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
    <div v-if="markers.length" class="w-full">
      <p class="text-xs font-medium text-gray-600 mb-1">Marked areas:</p>
      <div class="flex flex-wrap gap-1">
        <span v-for="m in markers" :key="m.id"
          class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-red-50 text-red-700 border border-red-200">
          {{ m.label || (m.part || 'area').replace(/_/g, ' ') }}
          <button type="button" @click="removeMarker(m.id)" class="text-red-400 hover:text-red-600">✕</button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

// Contract preserved: v-model (modelValue / update:modelValue) so PatientDetail &
// DirectDoctorMode `v-model="record.body_map"` keep saving the same JSON column.
// `gender` only changes the silhouette shape — never the saved data.
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  gender: { type: String, default: 'male' },
});
const emit = defineEmits(['update:modelValue']);

const svgRef = ref(null);
const selectedSeverity = ref(1);
const view = ref('front');

// Flat clinical-teal silhouette palette.
const C = '#5B9EA0';
const CLine = '#3D7A7C';
const CDark = '#3D7A7C';

const isFemale = computed(() => (props.gender || '').toLowerCase() === 'female');
const shoulderRx = computed(() => (isFemale.value ? 18 : 22));
const hipRx = computed(() => (isFemale.value ? 19 : 14));
const torsoPath = computed(() => isFemale.value
  // female: tapered waist curve inward
  ? 'M72,92 Q68,102 66,120 Q56,150 66,176 Q68,196 80,200 L120,200 Q132,196 134,176 Q144,150 134,120 Q132,102 128,92 Z'
  : 'M68,92 Q63,102 62,122 L62,196 Q62,204 76,206 L124,206 Q138,204 138,196 L138,122 Q137,102 132,92 Z');
const pelvisPath = computed(() => isFemale.value
  ? 'M60,200 Q58,224 74,230 L126,230 Q142,224 140,200 Z'
  : 'M66,204 Q64,224 76,228 L124,228 Q136,224 134,204 Z');
const leftThighPath = computed(() => isFemale.value
  ? 'M66,228 Q56,246 56,300 L56,336 Q56,344 74,344 L92,344 Q98,342 98,332 L98,294 Q100,246 92,228 Z'
  : 'M68,228 Q60,246 60,300 L60,336 Q60,344 74,344 L92,344 Q97,342 97,332 L97,294 Q99,246 92,228 Z');
const rightThighPath = computed(() => isFemale.value
  ? 'M134,228 Q144,246 144,300 L144,336 Q144,344 126,344 L108,344 Q102,342 102,332 L102,294 Q100,246 108,228 Z'
  : 'M132,228 Q140,246 140,300 L140,336 Q140,344 126,344 L108,344 Q103,342 103,332 L103,294 Q101,246 108,228 Z');

function sevColor(s) {
  return s === 1 ? '#FCD34D' : s === 2 ? '#F97316' : '#EF4444';
}

// Reactive CSS: every selected part's shape filled by its severity colour (the
// "visible colour overlay"). data-part is unique, so one block tints exactly it.
const highlightCss = computed(() =>
  markers.value
    .filter((m) => m.part)
    .map((m) => `[data-part="${m.part}"]{fill:${sevColor(m.severity)} !important}`)
    .join('')
);

// Backfill ids for legacy points ({x,y,severity,label} with no id) so v-for keys
// stay unique and removeMarker(id) works.
let seq = 0;
const withIds = (arr) => (arr || []).map((m) => (m.id != null ? m : { ...m, id: `legacy-${++seq}` }));
const markers = ref(withIds(props.modelValue));

watch(() => props.modelValue, (val) => {
  if (JSON.stringify(val) !== JSON.stringify(markers.value)) {
    markers.value = withIds(val);
  }
});

function mark(partId, partLabel) {
  const el = svgRef.value?.querySelector(`[data-part="${partId}"]`);
  if (!el) return;
  const bbox = el.getBBox ? el.getBBox() : { x: 0, y: 0, width: 20, height: 20 };
  const cx = Math.round(bbox.x + bbox.width / 2);
  const cy = Math.round(bbox.y + bbox.height / 2);

  const idx = markers.value.findIndex((m) => m.part === partId);
  if (idx >= 0) {
    markers.value.splice(idx, 1);
    emit('update:modelValue', markers.value);
    return;
  }
  markers.value.push({ id: Date.now(), part: partId, label: partLabel, x: cx, y: cy, severity: selectedSeverity.value });
  emit('update:modelValue', markers.value);
}

function removeMarker(id) {
  markers.value = markers.value.filter((m) => m.id !== id);
  emit('update:modelValue', markers.value);
}
</script>

<style scoped>
.body-part { cursor: pointer; transition: filter 0.15s; }
.body-part:hover, .body-part:active { filter: brightness(1.15); }
.breathe {
  animation: breathe 4s ease-in-out infinite;
  transform-origin: 100px 145px;
  transform-box: view-box;
}
@keyframes breathe {
  0%, 100% { transform: scaleX(1) scaleY(1); }
  50% { transform: scaleX(1.015) scaleY(1.008); }
}
</style>
