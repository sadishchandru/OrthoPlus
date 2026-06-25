<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-800 to-blue-600 px-4 py-8">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-md p-8">
      <div class="text-center mb-6">
        <Logo variant="full" class="h-12 mx-auto" />
        <p class="text-gray-500 text-sm mt-2">Clinic Management — Sign in</p>
      </div>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="text-xs font-medium text-gray-600 block mb-1">Username</label>
          <input v-model="username" type="text" class="input w-full" placeholder="e.g. doctor" autofocus />
        </div>
        <div>
          <label class="text-xs font-medium text-gray-600 block mb-1">Password</label>
          <input v-model="password" type="password" class="input w-full" placeholder="Password" />
        </div>

        <div v-if="error" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ error }}</div>

        <div v-if="loggedRole" class="text-sm text-green-700 bg-green-50 rounded-lg px-3 py-2 flex items-center gap-2">
          Signed in as
          <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-600 text-white uppercase">{{ loggedRole }}</span>
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full">
          {{ loading ? 'Signing in...' : 'Sign In' }}
        </button>
      </form>

      <div class="mt-5 flex items-center justify-between gap-3 text-xs">
        <router-link to="/" class="text-gray-500 hover:text-blue-700">Choose portal</router-link>
        <router-link to="/hospital/login" class="text-blue-700 font-medium hover:underline">Hospital login</router-link>
      </div>

      <div class="mt-6 text-xs text-gray-400 text-center leading-relaxed">
        <p class="font-medium text-gray-500 mb-1">Demo accounts (username / password)</p>
        root / root123, doctor / doc123, frontoffice / fo123<br />
        billing / bill123, pharmacy / ph123, therapist / th123
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
  module: { type: String, default: 'clinic' },
});

const router = useRouter();
const toast = useToast();
const auth = useAuthStore();

const username = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');
const loggedRole = ref('');

async function submit() {
  loading.value = true;
  error.value = '';
  try {
    const user = await auth.login(username.value.trim(), password.value, props.module);
    loggedRole.value = user.role || (user.roles?.[0] ?? '');
    toast.success(`Welcome, ${user.name}`);

    const redirect = router.currentRoute.value.query.redirect;
    const fallback = auth.firstAccessiblePath(props.module);
    setTimeout(() => router.replace(redirect || fallback), 400);
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.error || 'Login failed.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
</style>
