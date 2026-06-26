<template>
  <div>
    <h1 class="text-2xl font-bold text-foreground mb-4">Settings &amp; Master Data</h1>

    <div class="border-b border-border mb-6 flex overflow-x-auto scrollbar-hide gap-1">
      <button
        v-for="t in tabs"
        :key="t.key"
        @click="active = t.key"
        :class="[
          'flex-shrink-0 whitespace-nowrap px-4 py-2 text-sm font-medium -mb-px border-b-2 transition-colors',
          active === t.key
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground',
        ]"
      >
        {{ t.label }}
      </button>
    </div>

    <Card class="p-4 sm:p-6">
      <component :is="activeComponent" />
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, defineAsyncComponent } from 'vue';
import { Card } from '@/components/ui/card';

// Lazy-load each settings form → smaller initial Settings chunk (perf).
const tabs = [
  { key: 'appearance', label: 'Appearance',     comp: defineAsyncComponent(() => import('../components/settings/AppearanceSettings.vue')) },
  { key: 'print',      label: 'Print Designer',  comp: defineAsyncComponent(() => import('../components/settings/PrintSettings.vue')) },
  { key: 'treatments', label: 'Treatments',      comp: defineAsyncComponent(() => import('../components/settings/TreatmentCatalogForm.vue')) },
  { key: 'exercises',  label: 'Exercises',       comp: defineAsyncComponent(() => import('../components/settings/ExerciseForm.vue')) },
  { key: 'medicines',  label: 'Medicines',       comp: defineAsyncComponent(() => import('../components/settings/MedicineForm.vue')) },
  { key: 'therapists', label: 'Therapists',      comp: defineAsyncComponent(() => import('../components/settings/TherapistForm.vue')) },
  { key: 'users',      label: 'Users',           comp: defineAsyncComponent(() => import('../components/settings/UserForm.vue')) },
];

const active = ref('appearance');
const activeComponent = computed(() => tabs.find((t) => t.key === active.value).comp);
</script>
