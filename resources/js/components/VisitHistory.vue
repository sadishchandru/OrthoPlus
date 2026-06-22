<template>
  <div class="space-y-3">
    <h3 class="text-sm font-semibold text-gray-700">Visit History</h3>

    <div v-if="loading" class="text-center py-6 text-gray-400 text-sm">Loading visits...</div>

    <div v-else-if="!visits.length" class="text-center py-6 text-gray-400 text-sm italic">No visits recorded yet.</div>

    <div v-else class="space-y-2">
      <div
        v-for="visit in visits"
        :key="visit.id"
        class="border border-gray-200 rounded-lg overflow-hidden"
      >
        <!-- Accordion header -->
        <button
          @click="toggle(visit.id)"
          class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
        >
          <div class="flex items-center gap-3">
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-medium">Visit #{{ visit.visit_no }}</span>
            <span class="text-sm font-medium text-gray-800">{{ formatDate(visit.created_at) }}</span>
            <span v-if="visit.vas_score !== null" class="text-xs text-gray-500">
              VAS: <span :class="vasColor(visit.vas_score)" class="font-semibold">{{ visit.vas_score }}/10</span>
            </span>
          </div>
          <svg class="h-4 w-4 text-gray-400 transition-transform" :class="open.has(visit.id) ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Accordion body -->
        <div v-if="open.has(visit.id)" class="px-4 py-4 space-y-4">
          <!-- SOAP -->
          <div v-if="visit.soap_notes" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="key in ['subjective','objective','assessment','plan']" :key="key" v-if="visit.soap_notes[key]">
              <div class="text-xs font-semibold uppercase text-gray-400 mb-1">{{ key }}</div>
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ visit.soap_notes[key] }}</p>
            </div>
          </div>

          <!-- ROM -->
          <div v-if="visit.rom?.length">
            <div class="text-xs font-semibold uppercase text-gray-400 mb-2">ROM</div>
            <table class="text-xs w-full">
              <tr v-for="r in visit.rom" :key="r.joint + r.movement" class="border-b border-gray-100">
                <td class="py-1 pr-3 text-gray-600">{{ r.joint }} - {{ r.movement }}</td>
                <td class="py-1 font-medium">{{ r.degrees }}°</td>
                <td class="py-1 text-gray-400">/ {{ r.normal ?? '—' }}°</td>
              </tr>
            </table>
          </div>

          <!-- Ortho tests -->
          <div v-if="visit.ortho_tests?.length">
            <div class="text-xs font-semibold uppercase text-gray-400 mb-2">Orthopedic Tests</div>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="t in visit.ortho_tests"
                :key="t.name"
                :class="t.result === '+' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                class="text-xs px-2 py-0.5 rounded-full font-medium"
              >
                {{ t.name }} {{ t.result }}
              </span>
            </div>
          </div>

          <!-- Print buttons -->
          <div class="flex gap-2 pt-2 border-t border-gray-100">
            <a :href="`/print/soap/${visit.id}`" target="_blank" class="text-xs text-blue-600 hover:underline">Print SOAP</a>
            <a :href="`/print/exercises/${visit.id}`" target="_blank" class="text-xs text-blue-600 hover:underline">Print Exercises</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ patientId: { type: Number, required: true } });

const visits = ref([]);
const loading = ref(true);
const open = ref(new Set());

onMounted(async () => {
  try {
    const { data } = await axios.get(`/api/patients/${props.patientId}/visits`);
    visits.value = data;
  } finally {
    loading.value = false;
  }
});

function toggle(id) {
  open.value.has(id) ? open.value.delete(id) : open.value.add(id);
}

function formatDate(dt) {
  return new Date(dt).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
}

function vasColor(v) {
  return v <= 3 ? 'text-green-600' : v <= 6 ? 'text-yellow-600' : 'text-red-600';
}
</script>
