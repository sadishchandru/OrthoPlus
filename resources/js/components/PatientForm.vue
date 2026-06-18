<template>
  <form @submit.prevent="submit" class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
        <input v-model="form.name" required type="text" class="input" placeholder="Patient full name" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Phone <span class="text-red-500">*</span></label>
        <input v-model="form.phone" required type="tel" class="input" placeholder="+91 XXXXX XXXXX" />
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
      <img v-if="photoPreview" :src="photoPreview" class="mt-2 h-24 w-24 rounded-full object-cover border" />
    </div>

    <!-- Documents -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">Documents</label>
      <input @change="onDocuments" type="file" multiple class="text-sm text-gray-500" />
    </div>

    <!-- Error -->
    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
      {{ error }}
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
      <button type="button" @click="$emit('cancel')" class="btn-secondary w-full sm:w-auto">Cancel</button>
      <button type="submit" :disabled="loading" class="btn-primary w-full sm:w-auto flex items-center justify-center">
        <span v-if="loading" class="animate-spin mr-2">⌛</span>
        {{ loading ? 'Registering...' : 'Register Patient' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const emit = defineEmits(['created', 'cancel', 'duplicate-warning']);
const toast = useToast();

const form = reactive({
  name: '', phone: '', dob: '', gender: '',
  address: { line1: '', city: '', state: '', pincode: '' },
  photo: null, documents: [],
});

const loading = ref(false);
const error = ref('');
const photoPreview = ref('');

function onPhoto(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => { photoPreview.value = ev.target.result; form.photo = ev.target.result; };
  reader.readAsDataURL(file);
}

function onDocuments(e) {
  form.documents = Array.from(e.target.files).map(f => f.name);
}

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/api/patients', {
      name: form.name,
      phone: form.phone,
      dob: form.dob || null,
      gender: form.gender || null,
      photo: form.photo || null,
      documents: form.documents,
      address: form.address,
    });

    if (data.warning) {
      emit('duplicate-warning', data.warning);
    }

    toast.success(`Patient registered: ${data.patient.op_number}`);
    emit('created', data.patient);
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to register patient.';
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
