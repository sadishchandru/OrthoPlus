<script setup>
import { cn } from '@/lib/utils';
import { alertVariants } from './index.js';
const props = defineProps({ variant: { type: String, default: 'default' }, class: { type: null, default: '' } });
</script>
<template>
  <div role="alert" :class="cn(alertVariants({ variant }), props.class)"><slot /></div>
</template>
