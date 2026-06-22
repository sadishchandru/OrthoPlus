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
    <div class="relative w-full max-w-[260px] sm:max-w-[300px] mx-auto border rounded-2xl overflow-hidden bg-gradient-to-b from-blue-50 to-gray-50">
      <svg
        ref="svgRef"
        viewBox="0 0 200 520"
        xmlns="http://www.w3.org/2000/svg"
        class="w-full h-auto block"
        style="touch-action:none; display:block;"
      >
        <!-- Background subtle grid + skin gradients -->
        <defs>
          <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
            <path d="M 10 0 L 0 0 0 10" fill="none" stroke="#e2e8f0" stroke-width="0.3"/>
          </pattern>
          <radialGradient id="skinGrad" cx="50%" cy="30%" r="70%">
            <stop offset="0%" stop-color="#FDDCB5"/>
            <stop offset="100%" stop-color="#F0B97C"/>
          </radialGradient>
          <radialGradient id="skinDark" cx="50%" cy="50%" r="50%">
            <stop offset="0%" stop-color="#F5C89A"/>
            <stop offset="100%" stop-color="#E8A870"/>
          </radialGradient>
        </defs>
        <rect width="200" height="520" fill="url(#grid)"/>

        <!-- HEAD -->
        <ellipse cx="100" cy="38" rx="24" ry="28"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.8"
          class="body-part" data-part="head"
          @click="markPart('head','Head')" @touchend.prevent="markPart('head','Head')"/>

        <!-- EARS -->
        <ellipse cx="76" cy="40" rx="5" ry="7" fill="#F0B97C"
          class="body-part" data-part="left_ear"
          @click="markPart('left_ear','Left Ear')" @touchend.prevent="markPart('left_ear','Left Ear')"/>
        <ellipse cx="124" cy="40" rx="5" ry="7" fill="#F0B97C"
          class="body-part" data-part="right_ear"
          @click="markPart('right_ear','Right Ear')" @touchend.prevent="markPart('right_ear','Right Ear')"/>

        <!-- NECK -->
        <rect x="91" y="64" width="18" height="18" rx="6"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="neck"
          @click="markPart('neck','Neck')" @touchend.prevent="markPart('neck','Neck')"/>

        <!-- SHOULDERS -->
        <ellipse cx="68" cy="88" rx="18" ry="14"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_shoulder"
          @click="markPart('left_shoulder','Left Shoulder')" @touchend.prevent="markPart('left_shoulder','Left Shoulder')"/>
        <ellipse cx="132" cy="88" rx="18" ry="14"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_shoulder"
          @click="markPart('right_shoulder','Right Shoulder')" @touchend.prevent="markPart('right_shoulder','Right Shoulder')"/>

        <!-- TORSO/CHEST (breathe) -->
        <path d="M72,82 Q68,88 65,105 L65,165 Q65,175 75,178 L125,178 Q135,175 135,165 L135,105 Q132,88 128,82 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.8"
          class="body-part breathe" data-part="chest"
          @click="markPart('chest','Chest')" @touchend.prevent="markPart('chest','Chest')"/>

        <!-- ABDOMEN -->
        <path d="M68,165 Q68,178 75,182 L125,182 Q132,178 132,165 L132,145 L68,145 Z"
          fill="#FDDCB5" stroke="#E8A870" stroke-width="0.5" opacity="0.8"
          class="body-part" data-part="abdomen"
          @click="markPart('abdomen','Abdomen')" @touchend.prevent="markPart('abdomen','Abdomen')"/>

        <!-- UPPER ARMS -->
        <path d="M58,88 Q50,92 46,110 L44,148 Q44,155 50,156 L62,156 Q68,155 68,148 L68,105 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_upper_arm"
          @click="markPart('left_upper_arm','Left Upper Arm')" @touchend.prevent="markPart('left_upper_arm','Left Upper Arm')"/>
        <path d="M142,88 Q150,92 154,110 L156,148 Q156,155 150,156 L138,156 Q132,155 132,148 L132,105 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_upper_arm"
          @click="markPart('right_upper_arm','Right Upper Arm')" @touchend.prevent="markPart('right_upper_arm','Right Upper Arm')"/>

        <!-- ELBOWS -->
        <ellipse cx="53" cy="156" rx="9" ry="8"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_elbow"
          @click="markPart('left_elbow','Left Elbow')" @touchend.prevent="markPart('left_elbow','Left Elbow')"/>
        <ellipse cx="147" cy="156" rx="9" ry="8"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_elbow"
          @click="markPart('right_elbow','Right Elbow')" @touchend.prevent="markPart('right_elbow','Right Elbow')"/>

        <!-- FOREARMS -->
        <path d="M46,162 Q43,170 42,188 L42,200 Q42,206 48,207 L60,207 Q66,206 66,200 L66,168 Q62,162 56,162 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_forearm"
          @click="markPart('left_forearm','Left Forearm')" @touchend.prevent="markPart('left_forearm','Left Forearm')"/>
        <path d="M154,162 Q157,170 158,188 L158,200 Q158,206 152,207 L140,207 Q134,206 134,200 L134,168 Q138,162 144,162 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_forearm"
          @click="markPart('right_forearm','Right Forearm')" @touchend.prevent="markPart('right_forearm','Right Forearm')"/>

        <!-- WRISTS -->
        <rect x="43" y="206" width="22" height="10" rx="5"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_wrist"
          @click="markPart('left_wrist','Left Wrist')" @touchend.prevent="markPart('left_wrist','Left Wrist')"/>
        <rect x="135" y="206" width="22" height="10" rx="5"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_wrist"
          @click="markPart('right_wrist','Right Wrist')" @touchend.prevent="markPart('right_wrist','Right Wrist')"/>

        <!-- LEFT HAND + FINGERS -->
        <ellipse cx="54" cy="222" rx="11" ry="9"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_hand"
          @click="markPart('left_hand','Left Hand')" @touchend.prevent="markPart('left_hand','Left Hand')"/>
        <rect x="38" y="226" width="6" height="14" rx="3" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_thumb" @click="markPart('left_thumb','Left Thumb')" @touchend.prevent="markPart('left_thumb','Left Thumb')"/>
        <rect x="44" y="224" width="5" height="16" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_index" @click="markPart('left_index','Left Index')" @touchend.prevent="markPart('left_index','Left Index')"/>
        <rect x="50" y="223" width="5" height="17" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_middle" @click="markPart('left_middle','Left Middle')" @touchend.prevent="markPart('left_middle','Left Middle')"/>
        <rect x="56" y="224" width="5" height="16" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_ring" @click="markPart('left_ring','Left Ring')" @touchend.prevent="markPart('left_ring','Left Ring')"/>
        <rect x="62" y="226" width="4" height="13" rx="2" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_pinky" @click="markPart('left_pinky','Left Pinky')" @touchend.prevent="markPart('left_pinky','Left Pinky')"/>

        <!-- RIGHT HAND + FINGERS -->
        <ellipse cx="146" cy="222" rx="11" ry="9"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_hand"
          @click="markPart('right_hand','Right Hand')" @touchend.prevent="markPart('right_hand','Right Hand')"/>
        <rect x="156" y="226" width="6" height="14" rx="3" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_thumb" @click="markPart('right_thumb','Right Thumb')" @touchend.prevent="markPart('right_thumb','Right Thumb')"/>
        <rect x="151" y="224" width="5" height="16" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_index" @click="markPart('right_index','Right Index')" @touchend.prevent="markPart('right_index','Right Index')"/>
        <rect x="145" y="223" width="5" height="17" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_middle" @click="markPart('right_middle','Right Middle')" @touchend.prevent="markPart('right_middle','Right Middle')"/>
        <rect x="139" y="224" width="5" height="16" rx="2.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_ring" @click="markPart('right_ring','Right Ring')" @touchend.prevent="markPart('right_ring','Right Ring')"/>
        <rect x="134" y="226" width="4" height="13" rx="2" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_pinky" @click="markPart('right_pinky','Right Pinky')" @touchend.prevent="markPart('right_pinky','Right Pinky')"/>

        <!-- PELVIS -->
        <path d="M72,178 Q70,192 75,198 L125,198 Q130,192 128,178 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.8"
          class="body-part" data-part="pelvis"
          @click="markPart('pelvis','Pelvis')" @touchend.prevent="markPart('pelvis','Pelvis')"/>

        <!-- HIPS -->
        <ellipse cx="76" cy="196" rx="14" ry="12"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_hip"
          @click="markPart('left_hip','Left Hip')" @touchend.prevent="markPart('left_hip','Left Hip')"/>
        <ellipse cx="124" cy="196" rx="14" ry="12"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_hip"
          @click="markPart('right_hip','Right Hip')" @touchend.prevent="markPart('right_hip','Right Hip')"/>

        <!-- THIGHS -->
        <path d="M68,204 Q62,215 60,260 L60,285 Q60,292 70,294 L88,294 Q96,292 96,285 L96,250 Q98,215 92,204 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_thigh"
          @click="markPart('left_thigh','Left Thigh')" @touchend.prevent="markPart('left_thigh','Left Thigh')"/>
        <path d="M132,204 Q138,215 140,260 L140,285 Q140,292 130,294 L112,294 Q104,292 104,285 L104,250 Q102,215 108,204 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_thigh"
          @click="markPart('right_thigh','Right Thigh')" @touchend.prevent="markPart('right_thigh','Right Thigh')"/>

        <!-- KNEES -->
        <ellipse cx="78" cy="294" rx="14" ry="12"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_knee"
          @click="markPart('left_knee','Left Knee')" @touchend.prevent="markPart('left_knee','Left Knee')"/>
        <ellipse cx="122" cy="294" rx="14" ry="12"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_knee"
          @click="markPart('right_knee','Right Knee')" @touchend.prevent="markPart('right_knee','Right Knee')"/>

        <!-- SHINS -->
        <path d="M65,304 Q62,320 62,360 L62,375 Q62,382 70,383 L88,383 Q94,382 94,375 L94,355 Q96,318 92,304 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_shin"
          @click="markPart('left_shin','Left Shin')" @touchend.prevent="markPart('left_shin','Left Shin')"/>
        <path d="M135,304 Q138,320 138,360 L138,375 Q138,382 130,383 L112,383 Q106,382 106,375 L106,355 Q104,318 108,304 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_shin"
          @click="markPart('right_shin','Right Shin')" @touchend.prevent="markPart('right_shin','Right Shin')"/>

        <!-- ANKLES -->
        <ellipse cx="78" cy="384" rx="12" ry="8"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_ankle"
          @click="markPart('left_ankle','Left Ankle')" @touchend.prevent="markPart('left_ankle','Left Ankle')"/>
        <ellipse cx="122" cy="384" rx="12" ry="8"
          fill="url(#skinDark)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_ankle"
          @click="markPart('right_ankle','Right Ankle')" @touchend.prevent="markPart('right_ankle','Right Ankle')"/>

        <!-- LEFT FOOT + TOES -->
        <path d="M66,390 Q60,394 56,402 L56,410 Q56,416 66,416 L92,416 Q96,414 96,408 L94,394 Q90,390 80,390 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="left_foot"
          @click="markPart('left_foot','Left Foot')" @touchend.prevent="markPart('left_foot','Left Foot')"/>
        <ellipse cx="58" cy="417" rx="4" ry="5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_big_toe" @click="markPart('left_big_toe','Left Big Toe')" @touchend.prevent="markPart('left_big_toe','Left Big Toe')"/>
        <ellipse cx="66" cy="419" rx="3.5" ry="4.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_toe_2" @click="markPart('left_toe_2','Left Toe 2')" @touchend.prevent="markPart('left_toe_2','Left Toe 2')"/>
        <ellipse cx="73" cy="420" rx="3" ry="4" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_toe_3" @click="markPart('left_toe_3','Left Toe 3')" @touchend.prevent="markPart('left_toe_3','Left Toe 3')"/>
        <ellipse cx="80" cy="419" rx="3" ry="4" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_toe_4" @click="markPart('left_toe_4','Left Toe 4')" @touchend.prevent="markPart('left_toe_4','Left Toe 4')"/>
        <ellipse cx="86" cy="418" rx="2.5" ry="3.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="left_toe_5" @click="markPart('left_toe_5','Left Toe 5')" @touchend.prevent="markPart('left_toe_5','Left Toe 5')"/>

        <!-- RIGHT FOOT + TOES -->
        <path d="M134,390 Q140,394 144,402 L144,410 Q144,416 134,416 L108,416 Q104,414 104,408 L106,394 Q110,390 120,390 Z"
          fill="url(#skinGrad)" stroke="#E8A870" stroke-width="0.5"
          class="body-part" data-part="right_foot"
          @click="markPart('right_foot','Right Foot')" @touchend.prevent="markPart('right_foot','Right Foot')"/>
        <ellipse cx="142" cy="417" rx="4" ry="5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_big_toe" @click="markPart('right_big_toe','Right Big Toe')" @touchend.prevent="markPart('right_big_toe','Right Big Toe')"/>
        <ellipse cx="134" cy="419" rx="3.5" ry="4.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_toe_2" @click="markPart('right_toe_2','Right Toe 2')" @touchend.prevent="markPart('right_toe_2','Right Toe 2')"/>
        <ellipse cx="127" cy="420" rx="3" ry="4" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_toe_3" @click="markPart('right_toe_3','Right Toe 3')" @touchend.prevent="markPart('right_toe_3','Right Toe 3')"/>
        <ellipse cx="120" cy="419" rx="3" ry="4" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_toe_4" @click="markPart('right_toe_4','Right Toe 4')" @touchend.prevent="markPart('right_toe_4','Right Toe 4')"/>
        <ellipse cx="114" cy="418" rx="2.5" ry="3.5" fill="#FDDCB5" stroke="#E8A870" stroke-width="0.4" class="body-part" data-part="right_toe_5" @click="markPart('right_toe_5','Right Toe 5')" @touchend.prevent="markPart('right_toe_5','Right Toe 5')"/>

        <!-- SPINE (back view only) -->
        <g v-if="view === 'back'">
          <rect x="95" y="68" width="10" height="8" rx="2" class="body-part" fill="#D4956A" data-part="cervical_1" @click="markPart('cervical_1','C1-C2')" @touchend.prevent="markPart('cervical_1','C1-C2')"/>
          <rect x="95" y="78" width="10" height="7" rx="2" class="body-part" fill="#D4956A" data-part="cervical_2" @click="markPart('cervical_2','C3-C4')" @touchend.prevent="markPart('cervical_2','C3-C4')"/>
          <rect x="95" y="87" width="10" height="7" rx="2" class="body-part" fill="#D4956A" data-part="cervical_3" @click="markPart('cervical_3','C5-C7')" @touchend.prevent="markPart('cervical_3','C5-C7')"/>
          <rect x="95" y="96" width="10" height="25" rx="2" class="body-part" fill="#C8845A" data-part="thoracic" @click="markPart('thoracic','Thoracic Spine')" @touchend.prevent="markPart('thoracic','Thoracic Spine')"/>
          <rect x="95" y="123" width="10" height="22" rx="2" class="body-part" fill="#C07848" data-part="lumbar" @click="markPart('lumbar','Lumbar Spine')" @touchend.prevent="markPart('lumbar','Lumbar Spine')"/>
          <rect x="95" y="147" width="10" height="14" rx="2" class="body-part" fill="#B86C3C" data-part="sacrum" @click="markPart('sacrum','Sacrum')" @touchend.prevent="markPart('sacrum','Sacrum')"/>
        </g>

        <!-- PAIN MARKERS (pulse). Tap to remove. -->
        <g v-for="m in markers" :key="m.id" class="cursor-pointer"
           @click.stop="removeMarker(m.id)" @touchend.stop.prevent="removeMarker(m.id)">
          <circle :cx="m.x" :cy="m.y" r="12"
            :fill="m.severity === 1 ? '#FCD34D' : m.severity === 2 ? '#FB923C' : '#EF4444'"
            opacity="0.25" class="pulse"/>
          <circle :cx="m.x" :cy="m.y" r="7"
            :fill="m.severity === 1 ? '#D97706' : m.severity === 2 ? '#EA580C' : '#DC2626'"
            stroke="white" stroke-width="1.5"/>
          <text :x="m.x" :y="m.y + 3" text-anchor="middle"
            font-size="7" fill="white" font-weight="bold" pointer-events="none">{{ m.severity }}</text>
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
import { ref, watch } from 'vue';

// Contract preserved: v-model (modelValue / update:modelValue) so PatientDetail &
// DirectDoctorMode `v-model="record.body_map"` keep saving the same JSON column.
// `markers` mirrors modelValue; superset shape {part,label,x,y,severity} stays
// backward compatible with old {x,y,severity,label} records + the print template.
const props = defineProps({
  modelValue: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:modelValue']);

const svgRef = ref(null);
const selectedSeverity = ref(1);
const view = ref('front');
// Backfill ids for legacy points ({x,y,severity,label} with no id) so v-for keys
// stay unique and removeMarker(id) works.
let seq = 0;
const withIds = (arr) => (arr || []).map((m) => (m.id != null ? m : { ...m, id: `legacy-${++seq}` }));
const markers = ref(withIds(props.modelValue));

// External changes (e.g. parent reset to []) → resync internal state.
watch(() => props.modelValue, (val) => {
  if (JSON.stringify(val) !== JSON.stringify(markers.value)) {
    markers.value = withIds(val);
  }
});

function markPart(partId, partLabel) {
  const el = svgRef.value?.querySelector(`[data-part="${partId}"]`);
  if (!el) return;
  const bbox = el.getBBox ? el.getBBox() : { x: 0, y: 0, width: 20, height: 20 };
  const cx = Math.round(bbox.x + bbox.width / 2);
  const cy = Math.round(bbox.y + bbox.height / 2);

  // Toggle: tap an already-marked part to clear it.
  const idx = markers.value.findIndex((m) => m.part === partId);
  if (idx >= 0) {
    markers.value.splice(idx, 1);
    emit('update:modelValue', markers.value);
    return;
  }
  markers.value.push({
    id: Date.now(),
    part: partId,
    label: partLabel,
    x: cx,
    y: cy,
    severity: selectedSeverity.value,
  });
  emit('update:modelValue', markers.value);
}

function removeMarker(id) {
  markers.value = markers.value.filter((m) => m.id !== id);
  emit('update:modelValue', markers.value);
}
</script>

<style scoped>
.body-part { cursor: pointer; transition: filter 0.15s; }
.body-part:hover, .body-part:active { filter: brightness(0.85) saturate(1.3); }
.breathe {
  animation: breathe 4s ease-in-out infinite;
  transform-origin: 100px 125px;
  transform-box: view-box;
}
@keyframes breathe {
  0%, 100% { transform: scaleX(1) scaleY(1); }
  50% { transform: scaleX(1.02) scaleY(1.01); }
}
.pulse { animation: pulse 2s ease-in-out infinite; }
@keyframes pulse {
  0%, 100% { r: 12; opacity: 0.25; }
  50% { r: 16; opacity: 0.1; }
}
</style>
