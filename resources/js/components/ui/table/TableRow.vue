<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: { type: null, default: '' } });
</script>
<template>
  <tr :class="cn('border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted', props.class)"><slot /></tr>
</template>
