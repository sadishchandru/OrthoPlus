<template>
  <div>
    <!-- Thumbnails (optional — host can hide and drive open() externally) -->
    <div v-if="showThumbnails" class="flex flex-wrap gap-2">
      <button v-for="(img, i) in normalized" :key="i" type="button" @click="open(i)"
              class="relative group rounded-lg overflow-hidden border border-gray-200 hover:border-blue-400 focus:outline-none">
        <img :src="img.url" :alt="img.name || 'file'" :class="thumbClass" class="object-cover bg-gray-50" />
        <span class="absolute inset-0 bg-black/0 group-hover:bg-black/10 flex items-center justify-center">
          <span class="opacity-0 group-hover:opacity-100 text-white text-xs">🔍</span>
        </span>
      </button>
    </div>

    <!-- Popup -->
    <teleport to="body">
      <div v-if="shown" class="fixed inset-0 z-[70] bg-black/80 flex flex-col" @click.self="close">
        <!-- toolbar -->
        <div class="flex items-center justify-between p-3 text-white text-sm">
          <span class="truncate max-w-[60%]">{{ current.name || `Image ${index + 1} / ${normalized.length}` }}</span>
          <div class="flex items-center gap-1">
            <button @click="zoomOut" class="lb-btn" title="Zoom out">−</button>
            <span class="w-12 text-center tabular-nums">{{ Math.round(scale * 100) }}%</span>
            <button @click="zoomIn" class="lb-btn" title="Zoom in">+</button>
            <button @click="reset" class="lb-btn" title="Reset">⤢</button>
            <button @click="close" class="lb-btn" title="Close">✕</button>
          </div>
        </div>

        <!-- image stage -->
        <div ref="stage" class="flex-1 overflow-hidden flex items-center justify-center select-none"
             :style="{ cursor: scale > 1 ? (dragging ? 'grabbing' : 'grab') : 'auto' }"
             @wheel.prevent="onWheel" @pointerdown="onDown" @pointermove="onMove" @pointerup="onUp" @pointerleave="onUp">
          <img :src="current.url" :alt="current.name || ''" draggable="false"
               class="max-h-full max-w-full transition-transform duration-75 will-change-transform"
               :style="{ transform: `translate(${tx}px, ${ty}px) scale(${scale})` }" />
        </div>

        <!-- nav -->
        <div v-if="normalized.length > 1" class="flex items-center justify-center gap-6 p-3 text-white">
          <button @click="prev" class="lb-btn px-4">‹ Prev</button>
          <span class="text-xs text-gray-300">{{ index + 1 }} / {{ normalized.length }}</span>
          <button @click="next" class="lb-btn px-4">Next ›</button>
        </div>
      </div>
    </teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  // string url | { url, name }
  images: { type: Array, default: () => [] },
  thumbClass: { type: String, default: 'h-20 w-20' },
  showThumbnails: { type: Boolean, default: true },
});
defineExpose({ open: (i = 0) => open(i) });

const normalized = computed(() =>
  (props.images || [])
    .map((x) => (typeof x === 'string' ? { url: x } : x))
    .filter((x) => x && x.url)
);

const shown = ref(false);
const index = ref(0);
const scale = ref(1);
const tx = ref(0);
const ty = ref(0);
const current = computed(() => normalized.value[index.value] || { url: '' });

function open(i) { index.value = i; reset(); shown.value = true; }
function close() { shown.value = false; }
function reset() { scale.value = 1; tx.value = 0; ty.value = 0; }
function zoomIn() { scale.value = Math.min(scale.value * 1.3, 6); }
function zoomOut() { scale.value = Math.max(scale.value / 1.3, 1); if (scale.value === 1) { tx.value = 0; ty.value = 0; } }
function prev() { index.value = (index.value - 1 + normalized.value.length) % normalized.value.length; reset(); }
function next() { index.value = (index.value + 1) % normalized.value.length; reset(); }

function onWheel(e) { e.deltaY < 0 ? zoomIn() : zoomOut(); }

// drag-to-pan when zoomed
let start = null;
const dragging = ref(false);
function onDown(e) { if (scale.value <= 1) return; dragging.value = true; start = { x: e.clientX - tx.value, y: e.clientY - ty.value }; }
function onMove(e) { if (!dragging.value || !start) return; tx.value = e.clientX - start.x; ty.value = e.clientY - start.y; }
function onUp() { dragging.value = false; start = null; }

function onKey(e) {
  if (!shown.value) return;
  if (e.key === 'Escape') close();
  else if (e.key === 'ArrowLeft') prev();
  else if (e.key === 'ArrowRight') next();
  else if (e.key === '+' || e.key === '=') zoomIn();
  else if (e.key === '-') zoomOut();
}
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<style scoped>
@reference "tailwindcss";
.lb-btn { @apply w-9 h-9 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white text-lg leading-none; }
</style>
