<script setup>
import { isBusy } from '@/composables/useLoading';
</script>

<template>
  <transition name="loader-fade">
    <div v-if="isBusy" class="loader-track fixed inset-x-0 top-0 z-[9999] h-[3px] bg-primary/20 print:hidden">
      <div class="loader-bar h-full bg-primary"></div>
    </div>
  </transition>
</template>

<style scoped>
.loader-bar { width: 40%; border-radius: 9999px; animation: loader-slide 1.1s ease-in-out infinite; }
@keyframes loader-slide {
  0%   { transform: translateX(-100%); }
  50%  { transform: translateX(140%); }
  100% { transform: translateX(260%); }
}
.loader-fade-enter-active, .loader-fade-leave-active { transition: opacity 0.2s; }
.loader-fade-enter-from, .loader-fade-leave-to { opacity: 0; }
</style>
