<template>
  <div class="max-w-3xl space-y-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div>
        <h2 class="font-semibold text-gray-800">Appearance &amp; Branding</h2>
        <p class="text-xs text-gray-500">Live preview applies instantly. Save to persist for everyone.</p>
      </div>
      <div class="flex gap-2">
        <button @click="reset" class="btn-secondary">Reset</button>
        <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
      </div>
    </div>

    <!-- Hospital identity -->
    <section class="card">
      <h3 class="sec">Hospital Identity</h3>
      <div class="grid sm:grid-cols-2 gap-3">
        <div><label class="lbl">Hospital Name</label><input v-model="brand.name" @input="applyLive" class="input w-full" /></div>
        <div><label class="lbl">Tagline</label><input v-model="brand.tagline" class="input w-full" /></div>
        <div class="sm:col-span-2">
          <label class="lbl">Logo</label>
          <div class="flex items-center gap-3">
            <img v-if="brand.logo" :src="brand.logo" class="h-12 w-12 object-contain border rounded-lg bg-white" />
            <input type="file" accept="image/*" @change="onLogo" class="text-sm text-gray-500" />
            <button v-if="brand.logo" @click="brand.logo = null" class="text-xs text-red-600 hover:underline">Remove (use default mark)</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Colors -->
    <section class="card">
      <h3 class="sec">Theme Colors</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <ColorField v-model="theme.primary"    @change="applyLive" label="Primary" />
        <ColorField v-model="theme.secondary"  @change="applyLive" label="Secondary" />
        <ColorField v-model="theme.accent"     @change="applyLive" label="Accent" />
        <ColorField v-model="theme.background" @change="applyLive" label="Background" />
        <ColorField v-model="theme.sidebar"    @change="applyLive" label="Sidebar" />
        <ColorField v-model="theme.header"     @change="applyLive" label="Header" />
        <ColorField v-model="theme.text"       label="Text" />
      </div>
    </section>

    <!-- Status colors -->
    <section class="card">
      <h3 class="sec">Status Colors</h3>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <ColorField v-model="theme.status.waiting"     label="Waiting" />
        <ColorField v-model="theme.status.in_progress" label="In Progress" />
        <ColorField v-model="theme.status.completed"   label="Completed" />
        <ColorField v-model="theme.status.cancelled"   label="Cancelled" />
      </div>
    </section>

    <!-- Typography & shape -->
    <section class="card">
      <h3 class="sec">Typography &amp; Shape</h3>
      <div class="grid sm:grid-cols-3 gap-3">
        <div>
          <label class="lbl">Font Family</label>
          <select v-model="theme.font_family" @change="applyLive" class="input w-full">
            <option value="'Instrument Sans', ui-sans-serif, system-ui, sans-serif">Instrument Sans</option>
            <option value="system-ui, sans-serif">System</option>
            <option value="'Inter', sans-serif">Inter</option>
            <option value="'Roboto', sans-serif">Roboto</option>
            <option value="Georgia, serif">Georgia (serif)</option>
          </select>
        </div>
        <div>
          <label class="lbl">Base Font Size: {{ theme.font_size }}px</label>
          <input v-model.number="theme.font_size" @input="applyLive" type="range" min="13" max="20" class="w-full" />
        </div>
        <div>
          <label class="lbl">Corner Radius: {{ theme.radius }}px</label>
          <input v-model.number="theme.radius" @input="applyLive" type="range" min="0" max="20" class="w-full" />
        </div>
      </div>
      <label class="flex items-center gap-2 mt-3 text-sm text-gray-700">
        <input v-model="theme.dark" @change="applyLive" type="checkbox" class="rounded" /> Dark mode (beta)
      </label>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, h } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const saving = ref(false);
const theme = reactive({ status: {} });
const brand = reactive({});

onMounted(load);

async function load() {
  const { data } = await axios.get('/api/settings/branding');
  Object.assign(theme, data.theme);
  Object.assign(brand, data.brand);
}

// Mirror app.blade.php global injection → instant recolor without reload.
function applyLive() {
  const s = document.documentElement.style;
  s.setProperty('--color-blue-700', theme.primary);
  s.setProperty('--color-blue-600', theme.primary);
  s.setProperty('--color-blue-500', theme.secondary);
  s.setProperty('--color-blue-400', theme.secondary);
  s.setProperty('--color-blue-300', theme.accent);
  s.setProperty('--color-gray-50', theme.background);
  s.setProperty('--font-sans', theme.font_family);
  s.setProperty('--radius-lg', theme.radius + 'px');
  s.setProperty('--radius-xl', (theme.radius + 2) + 'px');
  s.setProperty('--brand-sidebar', theme.sidebar);
  s.setProperty('--brand-header', theme.header);
  document.documentElement.style.fontSize = theme.font_size + 'px';
  document.documentElement.classList.toggle('dark', !!theme.dark);
}

function onLogo(e) {
  const f = e.target.files[0];
  if (!f) return;
  const r = new FileReader();
  r.onload = (ev) => { brand.logo = ev.target.result; };
  r.readAsDataURL(f);
}

async function save() {
  saving.value = true;
  try {
    await axios.put('/api/settings/branding', { theme, brand });
    toast.success('Appearance saved — applied for everyone.');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed');
  } finally { saving.value = false; }
}

function reset() { window.location.reload(); } // restore last-saved (server-rendered)

// Tiny color+hex field
const ColorField = {
  props: ['modelValue', 'label'],
  emits: ['update:modelValue', 'change'],
  setup(props, { emit }) {
    const set = (v) => { emit('update:modelValue', v); emit('change'); };
    return () => h('div', [
      h('label', { class: 'lbl' }, props.label),
      h('div', { class: 'flex items-center gap-2' }, [
        h('input', { type: 'color', value: props.modelValue, onInput: (e) => set(e.target.value), class: 'h-9 w-9 rounded border cursor-pointer p-0' }),
        h('input', { type: 'text', value: props.modelValue, onInput: (e) => set(e.target.value), class: 'input w-full text-xs font-mono' }),
      ]),
    ]);
  },
};
</script>

<style scoped>
@reference "tailwindcss";
.card { @apply bg-white rounded-xl border border-gray-200 p-4; }
.sec { @apply text-sm font-semibold text-gray-700 mb-3; }
.lbl { @apply text-xs text-gray-600 mb-1 block; }
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium; }
</style>
