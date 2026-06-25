<template>
  <div class="min-h-screen flex items-center justify-center bg-[#1F5523] px-4 py-8 relative overflow-hidden">
    <div class="absolute inset-0 opacity-10 bone-pattern"></div>

    <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md p-8">
      <div class="text-center mb-6">
        <Logo variant="full" class="h-12 mx-auto mb-2" />
        <p class="text-gray-500 text-sm font-medium">Hospital Management System</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="text-xs font-medium text-gray-600 block mb-1">Username</label>
          <input v-model="username" type="text" class="input w-full" placeholder="e.g. hadmin" autofocus />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600 block mb-1">Password</label>
          <input v-model="password" type="password" class="input w-full" placeholder="Password" />
        </div>

        <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ error }}</div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <div class="mt-5 flex items-center justify-between gap-3 text-xs">
        <router-link to="/" class="text-gray-500 hover:text-[#1F5523]">Choose portal</router-link>
        <router-link to="/clinic/login" class="text-[#1F5523] font-medium hover:underline">Clinic login</router-link>
      </div>

      <div class="mt-6 text-xs text-gray-400 text-center leading-relaxed">
        <p class="font-medium text-gray-500 mb-1">Hospital demo accounts</p>
        hadmin / hadmin123, surgeon / surg123, nurse / nurse123<br />
        reception / recep123, hbilling / hbill123, hpharma / hph123
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Logo from '../components/Logo.vue';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  module: { type: String, default: 'hospital' },
});

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();

const username = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const user = await auth.login(username.value.trim(), password.value, props.module);
    toast.success(`Welcome, ${user.name}`);

    const redirect = router.currentRoute.value.query.redirect;
    setTimeout(() => router.replace(redirect || auth.firstAccessiblePath('hospital')), 400);
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.error || 'Login failed.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 outline-none; --tw-ring-color: #1F5523; }
.btn-primary { @apply text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; background-color: #1F5523; }
.btn-primary:hover { background-color: #172f4d; }
.bone-pattern {
  background-image:
    radial-gradient(circle at 16px 16px, white 0 2px, transparent 3px),
    linear-gradient(45deg, transparent 45%, white 46% 54%, transparent 55%);
  background-size: 44px 44px;
}
</style>
