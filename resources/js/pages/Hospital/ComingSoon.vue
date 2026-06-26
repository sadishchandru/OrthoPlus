<template>
  <div class="max-w-xl mx-auto py-8">
    <h1 class="text-xl font-bold text-foreground mb-4">{{ title }}</h1>
    <Alert variant="warning">
      <Construction class="w-5 h-5" />
      <AlertTitle>Page under construction</AlertTitle>
      <AlertDescription>
        <p>{{ note || 'This module is not available yet. The API is ready — the screen is being built.' }}</p>
        <RouterLink :to="back" class="inline-block mt-3 text-sm font-medium text-primary hover:underline">← Back</RouterLink>
      </AlertDescription>
    </Alert>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { Construction } from 'lucide-vue-next';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';

const route = useRoute();
const title = computed(() => route.meta?.title || 'Coming soon');
const note = computed(() => route.meta?.note || '');
const back = computed(() => route.meta?.back || '/hospital/dashboard');
</script>
