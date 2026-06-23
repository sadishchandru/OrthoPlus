<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-stretch md:items-center justify-center p-0 md:p-4" @click.self="$emit('close')">
    <div class="bg-white rounded-none md:rounded-xl w-full md:max-w-lg shadow-xl h-full md:h-auto max-h-screen md:max-h-[90vh] overflow-y-auto flex flex-col">
      <div class="flex items-center justify-between p-4 border-b border-gray-100 sticky top-0 bg-white">
        <h3 class="font-semibold text-gray-900">{{ title }}</h3>
        <button @click="$emit('close')" class="w-9 h-9 flex items-center justify-center rounded hover:bg-gray-100 text-gray-400" aria-label="Close">✕</button>
      </div>
      <div class="p-4 md:p-6 flex-1">
        <slot />
      </div>
      <div v-if="$slots.footer" class="flex justify-end gap-2 p-4 border-t border-gray-100 sticky bottom-0 bg-white">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ title: { type: String, default: '' } });
defineEmits(['close']);
</script>
