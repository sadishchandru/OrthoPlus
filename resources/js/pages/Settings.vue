<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Settings &amp; Master Data</h1>

    <div class="border-b border-gray-200 mb-6 flex flex-wrap gap-1">
      <button
        v-for="t in tabs"
        :key="t.key"
        @click="active = t.key"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-t-lg -mb-px border-b-2 transition-colors',
          active === t.key
            ? 'border-blue-600 text-blue-700 bg-white'
            : 'border-transparent text-gray-500 hover:text-gray-700'
        ]"
      >
        {{ t.label }}
      </button>
    </div>

    <component :is="activeComponent" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import TreatmentCatalogForm from '../components/settings/TreatmentCatalogForm.vue';
import ExerciseForm from '../components/settings/ExerciseForm.vue';
import MedicineForm from '../components/settings/MedicineForm.vue';
import TherapistForm from '../components/settings/TherapistForm.vue';
import UserForm from '../components/settings/UserForm.vue';

const tabs = [
  { key: 'treatments', label: 'Treatments',  comp: TreatmentCatalogForm },
  { key: 'exercises',  label: 'Exercises',   comp: ExerciseForm },
  { key: 'medicines',  label: 'Medicines',   comp: MedicineForm },
  { key: 'therapists', label: 'Therapists',  comp: TherapistForm },
  { key: 'users',      label: 'Users',       comp: UserForm },
];

const active = ref('treatments');
const activeComponent = computed(() => tabs.find((t) => t.key === active.value).comp);
</script>
