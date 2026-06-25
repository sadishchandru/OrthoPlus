<template>
  <svg :viewBox="variant === 'mark' ? '0 0 64 64' : '0 0 232 64'" role="img" aria-label="OrthoPlus"
       xmlns="http://www.w3.org/2000/svg" class="block">
    <defs>
      <linearGradient :id="gid" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" :stop-color="mono ? '#FFFFFF' : '#2E7D32'" />
        <stop offset="1" :stop-color="mono ? '#FFFFFF' : '#81C784'" />
      </linearGradient>
    </defs>

    <!-- ===== Mark: open ring + hand swoosh + graduated spine dots ===== -->
    <g :transform="variant === 'mark' ? '' : 'translate(0,2)'">
      <!-- ring (open at top-right) -->
      <path d="M52 18 A28 28 0 1 0 53 44" fill="none" :stroke="`url(#${gid})`" stroke-width="4" stroke-linecap="round" />
      <!-- hand cradle swoosh (bottom) -->
      <path d="M16 44 q16 16 32 2 q-6 12 -20 11 q-12 -1 -12 -13 Z" :fill="`url(#${gid})`" opacity="0.92" />
      <!-- spine: dots small→large -->
      <g :fill="mono ? '#FFFFFF' : '#3F8F43'">
        <circle cx="30" cy="14" r="2" />
        <circle cx="31" cy="20" r="2.6" />
        <circle cx="30.5" cy="26.5" r="3.1" />
        <circle cx="31.5" cy="33" r="3.6" />
        <circle cx="31" cy="40" r="4.1" />
        <circle cx="32" cy="47" r="4.6" />
      </g>
    </g>

    <!-- ===== Wordmark ===== -->
    <g v-if="variant !== 'mark'" transform="translate(64,0)">
      <text x="0" y="34" font-family="'Instrument Sans',ui-sans-serif,system-ui,sans-serif" font-size="28" font-weight="700">
        <tspan :fill="mono ? '#FFFFFF' : '#1B5E20'">Ortho</tspan><tspan :fill="mono ? '#C8E6C9' : '#4CAF50'">Plus</tspan>
      </text>
      <text x="1" y="50" font-family="'Instrument Sans',ui-sans-serif,system-ui,sans-serif" font-size="8.5"
            letter-spacing="1.6" :fill="mono ? '#A5D6A7' : '#6B8E6E'" font-weight="600">ORTHOPEDIC &amp; PHYSIOTHERAPY</text>
    </g>
  </svg>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  variant: { type: String, default: 'full' }, // 'full' | 'mark'
  mono: { type: Boolean, default: false },     // white for dark backgrounds
});
const gid = computed(() => 'opg-' + (props.mono ? 'w' : 'c') + '-' + Math.random().toString(36).slice(2, 7));
</script>
