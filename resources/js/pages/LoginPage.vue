<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary to-primary/70 px-4 py-8">
    <Card class="w-full max-w-md shadow-2xl">
      <CardHeader class="items-center text-center">
        <Logo variant="full" class="h-12 mx-auto" />
        <CardDescription class="mt-1">Clinic Management — Sign in</CardDescription>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submit" class="space-y-4">
          <div class="space-y-1.5">
            <Label for="username">Username</Label>
            <Input id="username" v-model="username" type="text" placeholder="e.g. doctor" autofocus />
          </div>
          <div class="space-y-1.5">
            <Label for="password">Password</Label>
            <Input id="password" v-model="password" type="password" placeholder="Password" />
          </div>

          <div v-if="error" class="text-sm text-destructive bg-destructive/10 rounded-md px-3 py-2">{{ error }}</div>

          <div v-if="loggedRole" class="text-sm rounded-md px-3 py-2 flex items-center gap-2 bg-accent text-accent-foreground">
            Signed in as
            <Badge variant="success" class="uppercase">{{ loggedRole }}</Badge>
          </div>

          <Button type="submit" :disabled="loading" class="w-full">
            {{ loading ? 'Signing in...' : 'Sign In' }}
          </Button>
        </form>

        <div class="mt-5 flex items-center justify-between gap-3 text-xs">
          <router-link to="/" class="text-muted-foreground hover:text-primary">Choose portal</router-link>
          <router-link to="/hospital/login" class="text-primary font-medium hover:underline">Hospital login</router-link>
        </div>

        <div class="mt-6 text-xs text-muted-foreground text-center leading-relaxed">
          <p class="font-medium mb-1">Demo accounts (username / password)</p>
          root / root123, doctor / doc123, frontoffice / fo123<br />
          billing / bill123, pharmacy / ph123, therapist / th123
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Logo from '../components/Logo.vue';
import { useToast } from 'vue-toastification';
import { useAuthStore } from '../stores/auth';
import { Card, CardHeader, CardDescription, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

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
