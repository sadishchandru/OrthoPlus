<template>
  <div>
    <!-- Print toolbar (hidden when printing) -->
    <div class="print:hidden flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
      <div class="flex gap-2">
        <button
          v-for="lang in LANGS"
          :key="lang.code"
          @click="$emit('update:lang', lang.code)"
          :class="modelLang === lang.code ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300'"
          class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
        >
          {{ lang.label }}
        </button>
      </div>
      <button @click="window.print()" class="ml-auto bg-gray-900 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
        🖨️ Print
      </button>
    </div>

    <!-- Content slot -->
    <slot />
  </div>
</template>

<script setup>
const LANGS = [
  { code: 'en', label: 'English' },
  { code: 'ta', label: 'தமிழ்' },
  { code: 'hi', label: 'हिन्दी' },
];

const props = defineProps({ modelLang: { type: String, default: 'en' } });
defineEmits(['update:lang']);
</script>
