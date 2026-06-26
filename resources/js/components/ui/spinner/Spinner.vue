<script setup>
import { Loader2 } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>
<template>
  <Loader2 :class="cn('h-4 w-4 animate-spin text-muted-foreground', props.class)" />
</template>
