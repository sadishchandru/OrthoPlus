<template>
  <form @submit.prevent="submit" class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
        <input v-model="form.name" required type="text" class="input" placeholder="Patient full name" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
        <!-- Single combined field: dial code + national number share one bordered box. -->
        <div class="flex items-stretch border border-gray-300 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-blue-500"
             :class="phoneError ? 'border-red-400' : ''">
          <select v-model="form.country_code" class="bg-gray-50 border-0 border-r border-gray-200 text-sm pl-2 pr-1 outline-none focus:ring-0 flex-shrink-0">
            <option v-for="c in DIAL" :key="c.code" :value="c.code">{{ c.label }}</option>
          </select>
          <input v-model="form.phone" @input="onPhoneInput" required type="tel" inputmode="numeric" maxlength="10"
                 class="flex-1 min-w-0 border-0 px-3 py-2 text-sm outline-none focus:ring-0" placeholder="10-digit number" />
        </div>
        <p v-if="phoneError" class="text-xs text-red-600 mt-1">{{ phoneError }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
        <input v-model="form.dob" type="date" class="input" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
        <select v-model="form.gender" class="input">
          <option value="">Select</option>
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>

    <!-- Address -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
      <input v-model="form.address.line1" type="text" class="input mb-2" placeholder="Street address" />
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <input v-model="form.address.city" type="text" class="input" placeholder="City" />
        <input v-model="form.address.state" type="text" class="input" placeholder="State" />
        <input v-model="form.address.pincode" type="text" class="input" placeholder="Pincode" />
      </div>
    </div>

    <!-- Photo upload -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Photo</label>
      <input @change="onPhoto" type="file" accept="image/*" class="text-sm text-gray-500" />
      <div v-if="photoPreview" class="mt-2">
        <ImageLightbox :images="[{ url: photoPreview, name: 'Patient photo' }]" thumb-class="h-24 w-24" />
        <p class="text-[10px] text-gray-400 mt-1">Click photo to zoom</p>
      </div>
    </div>

    <!-- Documents: real upload to patient_files (image/pdf/doc) -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Documents</label>
      <input @change="onDocuments" type="file" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf" class="text-sm text-gray-500" />
      <p v-if="docFiles.length" class="text-xs text-gray-500 mt-1">{{ docFiles.length }} file(s) ready — saved on {{ isEdit ? 'update' : 'register' }}.</p>

      <!-- In edit mode, manage existing files inline (preserved across edits) -->
      <div v-if="isEdit" class="mt-3 border-t border-gray-100 pt-3">
        <FileGallery :patient-id="form.id" module="registration" :can-upload="true" :can-delete="true" />
      </div>
    </div>

    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">{{ error }}</div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
      <button type="button" @click="$emit('cancel')" class="btn-secondary w-full sm:w-auto">Cancel</button>
      <button type="submit" :disabled="loading" class="btn-primary w-full sm:w-auto flex items-center justify-center">
        <span v-if="loading" class="animate-spin mr-2">⌛</span>
        {{ loading ? 'Saving...' : (isEdit ? 'Update Patient' : 'Register Patient') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import ImageLightbox from './ImageLightbox.vue';
import FileGallery from './FileGallery.vue';

const props = defineProps({
  patient: { type: Object, default: null }, // present = edit mode
});
const emit = defineEmits(['created', 'updated', 'cancel', 'duplicate-warning']);
const toast = useToast();

const DIAL = [
  { code: '+91', label: '🇮🇳 +91' }, { code: '+1', label: '🇺🇸 +1' }, { code: '+44', label: '🇬🇧 +44' },
  { code: '+971', label: '🇦🇪 +971' }, { code: '+966', label: '🇸🇦 +966' }, { code: '+65', label: '+65' }, { code: '+61', label: '+61' },
];

const isEdit = computed(() => !!props.patient?.id);

const form = reactive({
  id: props.patient?.id || null,
  name: props.patient?.name || '',
  phone: props.patient?.phone || '',
  country_code: props.patient?.country_code || '+91',
  dob: props.patient?.dob ? String(props.patient.dob).slice(0, 10) : '',
  gender: props.patient?.gender || '',
  address: {
    line1: props.patient?.address?.line1 || '',
    city: props.patient?.address?.city || '',
    state: props.patient?.address?.state || '',
    pincode: props.patient?.address?.pincode || '',
  },
  photo: props.patient?.photo || null,
});

const loading = ref(false);
const error = ref('');
const photoPreview = ref(props.patient?.photo || '');
const docFiles = ref([]);

async function onPhoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const small = await compress(file); // shrink before base64 → keeps the patient POST small
  const reader = new FileReader();
  reader.onload = ev => { photoPreview.value = ev.target.result; form.photo = ev.target.result; };
  reader.readAsDataURL(small);
}

function onDocuments(e) {
  docFiles.value = Array.from(e.target.files || []); // real File objects
}

const phoneError = ref('');
function onPhoneInput(e) {
  form.phone = (e.target.value || '').replace(/\D/g, '').slice(0, 10);
  phoneError.value = !form.phone ? '' : (form.phone.length === 10 ? '' : 'Phone must be exactly 10 digits.');
}

// Upload picked documents into patient_files (compress images client-side).
// One request per file → never trips PHP post_max_size (8M) with many docs.
async function uploadDocs(patientId) {
  for (const f of docFiles.value) {
    if (f.size > 25 * 1024 * 1024) { toast.error(`${f.name} skipped (over 25 MB)`); continue; }
    const out = f.type.startsWith('image/') ? await compress(f) : f;
    const fd = new FormData();
    fd.append('module', 'registration');
    fd.append('files[]', out, out.name || f.name);
    await axios.post(`/api/patients/${patientId}/files`, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
  }
}

function compress(file) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => {
      const max = 1600; let { width: w, height: h } = img;
      if (w <= max && h <= max && file.size < 700 * 1024) return resolve(file);
      const s = Math.min(1, max / Math.max(w, h));
      const c = document.createElement('canvas'); c.width = Math.round(w * s); c.height = Math.round(h * s);
      c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
      c.toBlob((b) => resolve(b ? new File([b], (file.name || 'photo').replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg' }) : file), 'image/jpeg', 0.82);
    };
    img.onerror = () => resolve(file);
    img.src = URL.createObjectURL(file);
  });
}

async function submit() {
  if (!/^\d{10}$/.test(form.phone)) { phoneError.value = 'Phone must be exactly 10 digits (numbers only).'; return; }
  loading.value = true;
  error.value = '';
  try {
    const payload = {
      name: form.name, phone: form.phone, country_code: form.country_code,
      dob: form.dob || null, gender: form.gender || null,
      photo: form.photo || null, address: form.address,
    };
    // 1) Save the patient record (this is what determines success/failure).
    let patient;
    if (isEdit.value) {
      const { data } = await axios.put(`/api/patients/${form.id}`, payload);
      patient = data.patient;
    } else {
      const { data } = await axios.post('/api/patients', payload);
      patient = data.patient;
      if (data.warning) emit('duplicate-warning', data.warning);
    }

    // 2) Upload documents separately — a file error must NOT report "save failed".
    try {
      await uploadDocs(patient.id);
    } catch (fe) {
      toast.error('Patient saved, but some files failed to upload.');
    }

    toast.success(isEdit.value ? 'Patient updated.' : `Patient registered: ${patient.op_number}`);
    emit(isEdit.value ? 'updated' : 'created', patient);
  } catch (e) {
    const res = e.response?.data;
    error.value = res?.errors ? Object.values(res.errors).flat().join(' ') : (res?.message || 'Failed to save patient.');
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
