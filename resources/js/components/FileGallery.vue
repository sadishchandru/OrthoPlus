<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2 flex-wrap">
      <h4 class="text-sm font-semibold text-gray-700">Files <span class="text-xs text-gray-400">({{ files.length }})</span></h4>
      <div v-if="canUpload" class="flex gap-2">
        <button type="button" @click="pick.click()" class="btn-xs">📎 Upload</button>
        <button type="button" @click="cam.click()" class="btn-xs">📷 Take Photo</button>
        <input ref="pick" type="file" multiple accept="image/*,application/pdf,.doc,.docx" class="hidden" @change="onFiles" />
        <input ref="cam" type="file" accept="image/*" capture="environment" class="hidden" @change="onFiles" />
      </div>
    </div>

    <p v-if="uploading" class="text-xs text-blue-600">Uploading… {{ progress }}</p>
    <p v-if="loading" class="text-xs text-gray-400">Loading files…</p>

    <div v-if="!loading && !files.length" class="text-xs text-gray-400 py-2">No files yet.</div>

    <div v-if="files.length" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
      <div v-for="f in files" :key="f.id" class="relative group rounded-lg border border-gray-200 overflow-hidden bg-gray-50">
        <!-- image thumbnail → lightbox -->
        <img v-if="f.category === 'image' && !f._err" :src="f.url" :alt="f.original_name"
             class="h-20 w-full object-cover cursor-zoom-in" @click="openImage(f)" @error="f._err = true" />
        <!-- non-image (or broken image) tile -->
        <button v-else type="button" @click="openFile(f)" class="h-20 w-full flex flex-col items-center justify-center text-center px-1">
          <span class="text-2xl">{{ icon(f.category) }}</span>
          <span class="text-[9px] text-gray-500 truncate w-full mt-0.5">{{ f.original_name }}</span>
        </button>

        <!-- action overlay -->
        <div class="absolute top-0.5 right-0.5 flex gap-0.5 opacity-0 group-hover:opacity-100 transition">
          <a :href="f.url" :download="f.original_name" target="_blank" class="act-btn" title="Download">⬇</a>
          <button v-if="canDelete" type="button" @click="del(f)" class="act-btn text-red-600" title="Delete">🗑</button>
        </div>
        <span class="absolute bottom-0.5 left-0.5 text-[8px] bg-black/40 text-white px-1 rounded">{{ fmtSize(f.size) }}</span>
      </div>
    </div>

    <!-- shared lightbox (driven externally) -->
    <ImageLightbox ref="lb" :images="imageList" :show-thumbnails="false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ImageLightbox from './ImageLightbox.vue';

const props = defineProps({
  patientId: { type: [Number, String], required: true },
  module: { type: String, default: '' },
  canUpload: { type: Boolean, default: true },
  canDelete: { type: Boolean, default: false },
});

const toast = useToast();
const files = ref([]);
const loading = ref(false);
const uploading = ref(false);
const progress = ref('');
const pick = ref(null);
const cam = ref(null);
const lb = ref(null);

const imageList = computed(() => files.value.filter((f) => f.category === 'image').map((f) => ({ url: f.url, name: f.original_name })));

onMounted(load);

async function load() {
  loading.value = true;
  try {
    // Show ALL of the patient's files (module is a tag, not a filter) so files
    // attached at registration also appear in consulting and elsewhere.
    const { data } = await axios.get(`/api/patients/${props.patientId}/files`);
    files.value = Array.isArray(data) ? data : (data.data || []);
  } finally { loading.value = false; }
}

function openImage(f) {
  const i = imageList.value.findIndex((x) => x.url === f.url);
  lb.value?.open(Math.max(0, i));
}
function openFile(f) { window.open(f.url, '_blank'); }

async function onFiles(e) {
  const picked = Array.from(e.target.files || []);
  e.target.value = '';
  if (!picked.length) return;

  uploading.value = true;
  try {
    const added = [];
    let i = 0;
    for (const file of picked) {
      progress.value = `${++i}/${picked.length}`;
      if (file.size > 10 * 1024 * 1024) { toast.error(`${file.name} skipped (over 10 MB)`); continue; }
      const out = file.type.startsWith('image/') ? await compress(file) : file;
      const fd = new FormData();
      if (props.module) fd.append('module', props.module);
      fd.append('files[]', out, out.name || file.name);
      // One request per file → avoids PHP post_max_size (8M) limit with many files.
      const { data } = await axios.post(`/api/patients/${props.patientId}/files`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      added.push(...data);
    }
    files.value = [...added, ...files.value]; // preserve existing, prepend new
    if (added.length) toast.success(`${added.length} file(s) uploaded`);
  } catch (err) {
    toast.error(err.response?.data?.message || 'Upload failed');
  } finally { uploading.value = false; progress.value = ''; }
}

// Client-side compress: cap longest edge at 1600px, JPEG q0.82. Cuts camera photos ~10x.
function compress(file) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      const max = 1600;
      let { width: w, height: h } = img;
      if (w <= max && h <= max && file.size < 700 * 1024) return resolve(file); // already small
      const scale = Math.min(1, max / Math.max(w, h));
      const canvas = document.createElement('canvas');
      canvas.width = Math.round(w * scale);
      canvas.height = Math.round(h * scale);
      canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
      canvas.toBlob((blob) => {
        if (!blob) return resolve(file);
        const name = (file.name || 'photo').replace(/\.\w+$/, '') + '.jpg';
        resolve(new File([blob], name, { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.82);
    };
    img.onerror = () => resolve(file);
    img.src = URL.createObjectURL(file);
  });
}

async function del(f) {
  if (!props.canDelete) return;
  if (!confirm(`Delete "${f.original_name}"?`)) return;
  try {
    await axios.delete(`/api/patient-files/${f.id}`);
    files.value = files.value.filter((x) => x.id !== f.id); // delete selected only
    toast.success('File deleted');
  } catch (err) { toast.error('Delete failed'); }
}

function icon(cat) { return cat === 'pdf' ? '📕' : cat === 'doc' ? '📘' : '📄'; }
function fmtSize(b) { if (!b || isNaN(b)) return ''; return b > 1048576 ? (b / 1048576).toFixed(1) + 'M' : Math.ceil(b / 1024) + 'K'; }
</script>

<style scoped>
@reference "tailwindcss";
.btn-xs { @apply text-xs px-2.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium; }
.act-btn { @apply w-6 h-6 flex items-center justify-center rounded bg-white/90 shadow text-xs hover:bg-white; }
</style>
