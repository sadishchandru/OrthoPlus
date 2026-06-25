<template>
  <div class="max-w-4xl space-y-6">
    <div class="flex items-start justify-between gap-3 flex-wrap">
      <div>
        <h2 class="font-semibold text-gray-800">Print Designer</h2>
        <p class="text-xs text-gray-500">Applies to all print templates (prescription, invoice, discharge, surgery note…).</p>
      </div>
      <div class="flex gap-2">
        <a href="/print/consent" target="_blank" class="btn-secondary">Open sample ↗</a>
        <button @click="save" :disabled="saving" class="btn-primary">{{ saving ? 'Saving…' : 'Save' }}</button>
      </div>
    </div>

    <!-- Live header preview -->
    <section class="card">
      <h3 class="sec">Header Preview</h3>
      <div class="border rounded-lg p-4 bg-white" :style="previewWrap">
        <div :style="headerStyle">
          <div style="display:flex; align-items:center; gap:10px;">
            <img v-if="print.show_logo && brand.logo" :src="brand.logo" style="height:44px;width:44px;object-fit:contain" />
            <svg v-else-if="print.show_logo" width="42" height="42" viewBox="0 0 64 64">
              <path d="M52 18 A28 28 0 1 0 53 44" fill="none" :stroke="print.color" stroke-width="4" stroke-linecap="round"/>
              <path d="M16 44 q16 16 32 2 q-6 12 -20 11 q-12 -1 -12 -13 Z" :fill="print.color"/>
            </svg>
            <div>
              <div :style="`font-size:20px;font-weight:700;color:${nameColor}`">{{ brand.name || 'OrthoPlus' }}</div>
              <div style="font-size:11px;opacity:.85">{{ brand.tagline }}</div>
              <div style="font-size:11px;opacity:.85">{{ brand.address }}</div>
              <div style="font-size:11px;opacity:.85">{{ [brand.phone, brand.email, brand.website].filter(Boolean).join(' · ') }}</div>
            </div>
          </div>
          <div style="text-align:right;font-size:10.5px;opacity:.85">
            <div v-if="brand.reg_no">Reg No: {{ brand.reg_no }}</div>
            <div v-if="brand.gst_no">GST: {{ brand.gst_no }}</div>
            <div v-if="brand.accreditation">{{ brand.accreditation }}</div>
          </div>
        </div>
      </div>
    </section>

    <section class="card">
      <h3 class="sec">Header Style</h3>
      <div class="flex flex-wrap gap-2">
        <button v-for="s in ['classic','modern','premium','minimal']" :key="s" @click="print.header_style = s"
                :class="print.header_style === s ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                class="px-3 py-1.5 rounded-lg text-sm capitalize">{{ s }}</button>
      </div>
    </section>

    <section class="card">
      <h3 class="sec">Hospital Details</h3>
      <div class="grid sm:grid-cols-2 gap-3">
        <div><label class="lbl">Name</label><input v-model="brand.name" class="input w-full" /></div>
        <div><label class="lbl">Tagline</label><input v-model="brand.tagline" class="input w-full" /></div>
        <div class="sm:col-span-2"><label class="lbl">Address</label><input v-model="brand.address" class="input w-full" /></div>
        <div><label class="lbl">Phone</label><input v-model="brand.phone" class="input w-full" /></div>
        <div><label class="lbl">Email</label><input v-model="brand.email" class="input w-full" /></div>
        <div><label class="lbl">Website</label><input v-model="brand.website" class="input w-full" /></div>
        <div><label class="lbl">Registration No</label><input v-model="brand.reg_no" class="input w-full" /></div>
        <div><label class="lbl">GST No</label><input v-model="brand.gst_no" class="input w-full" /></div>
        <div><label class="lbl">NABH / ISO</label><input v-model="brand.accreditation" class="input w-full" /></div>
        <div class="sm:col-span-2">
          <label class="lbl">Logo</label>
          <input type="file" accept="image/*" @change="onLogo" class="text-sm text-gray-500" />
        </div>
      </div>
    </section>

    <section class="card">
      <h3 class="sec">Footer, Watermark &amp; Layout</h3>
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="flex items-center gap-2 text-sm"><input v-model="print.show_logo" type="checkbox" class="rounded" /> Show logo</label>
        <label class="flex items-center gap-2 text-sm"><input v-model="print.show_footer" type="checkbox" class="rounded" /> Show footer</label>
        <div class="sm:col-span-2"><label class="lbl">Footer text</label><input v-model="print.footer" class="input w-full" /></div>
        <div><label class="lbl">Watermark (blank = none)</label><input v-model="print.watermark" class="input w-full" placeholder="e.g. ORIGINAL" /></div>
        <div><label class="lbl">Signature label</label><input v-model="print.signature" class="input w-full" /></div>
        <div><label class="lbl">Accent / heading color</label>
          <div class="flex items-center gap-2"><input type="color" v-model="print.color" class="h-9 w-9 rounded border p-0" /><input v-model="print.color" class="input w-full text-xs font-mono" /></div>
        </div>
        <div><label class="lbl">Print font size: {{ print.font_size }}px</label><input v-model.number="print.font_size" type="range" min="10" max="16" class="w-full" /></div>
      </div>
      <div class="grid grid-cols-4 gap-2 mt-3">
        <div v-for="side in ['top','right','bottom','left']" :key="side">
          <label class="lbl capitalize">{{ side }} (mm)</label>
          <input v-model.number="print.margins[side]" type="number" min="0" max="40" class="input w-full" />
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const saving = ref(false);
const brand = reactive({});
const print = reactive({ margins: {} });

onMounted(async () => {
  const { data } = await axios.get('/api/settings/branding');
  Object.assign(brand, data.brand);
  Object.assign(print, data.print);
});

const isModern = computed(() => print.header_style === 'modern');
const nameColor = computed(() => isModern.value ? '#fff' : print.color);
const headerStyle = computed(() => {
  const base = 'display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding-bottom:10px;';
  if (isModern.value) return base + `background:${print.color};color:#fff;padding:10px 12px;border-radius:8px;`;
  if (print.header_style === 'premium') return base + 'border-bottom:1px solid #e5e7eb;';
  if (print.header_style === 'minimal') return base.replace('padding-bottom:10px;', 'padding-bottom:6px;') + 'border-bottom:1px solid #e5e7eb;';
  return base + `border-bottom:2px solid ${print.color};`; // classic
});
const previewWrap = computed(() => print.header_style === 'premium' ? `border-top:6px solid ${print.color}` : '');

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
    await axios.put('/api/settings/branding', { brand, print });
    toast.success('Print settings saved.');
  } catch (e) {
    toast.error(e.response?.data?.message || 'Save failed');
  } finally { saving.value = false; }
}
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
