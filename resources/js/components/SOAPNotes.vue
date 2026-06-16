<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <label class="text-sm font-medium text-gray-700">SOAP Notes</label>
      <select v-model="selectedTemplate" @change="applyTemplate" class="text-xs border border-gray-200 rounded px-2 py-1">
        <option value="">Load template...</option>
        <option v-for="t in templates" :key="t.name" :value="t.name">{{ t.name }}</option>
      </select>
    </div>

    <div v-for="section in SECTIONS" :key="section.key" class="space-y-1">
      <label class="text-xs font-semibold uppercase tracking-wide" :class="section.color">
        {{ section.label }}
      </label>
      <textarea
        v-model="local[section.key]"
        @input="emit('update:modelValue', { ...local })"
        :rows="section.rows"
        :placeholder="section.placeholder"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none resize-none"
      ></textarea>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue';
import axios from 'axios';

const SECTIONS = [
  { key: 'subjective', label: 'S — Subjective', color: 'text-blue-600', rows: 3, placeholder: 'Chief complaint, pain history, patient description...' },
  { key: 'objective', label: 'O — Objective', color: 'text-green-600', rows: 3, placeholder: 'Examination findings, VAS, ROM, orthopedic tests...' },
  { key: 'assessment', label: 'A — Assessment', color: 'text-orange-600', rows: 2, placeholder: 'Clinical impression, diagnosis...' },
  { key: 'plan', label: 'P — Plan', color: 'text-purple-600', rows: 3, placeholder: 'Treatment plan, exercises, follow-up...' },
];

const TEMPLATES = [
  {
    name: 'Knee Pain',
    subjective: 'Patient complains of pain in the knee. Pain score: /10. Onset: gradual/acute.',
    objective: 'Swelling: present/absent. ROM: Flexion °, Extension °. McMurray: +/-.',
    assessment: 'Knee pain — likely [diagnosis].',
    plan: 'TENS 20 min, Manual therapy, Exercise prescription. Review in 1 week.',
  },
  {
    name: 'Shoulder Pain',
    subjective: 'Patient presents with shoulder pain. Unable to lift arm above shoulder height.',
    objective: 'ROM: Abduction °, Flexion °. Hawkins-Kennedy: +/-. Neer\'s: +/-.',
    assessment: 'Shoulder impingement / rotator cuff pathology.',
    plan: 'US therapy, Pendulum exercises, Strengthening. Avoid overhead activities.',
  },
  {
    name: 'Low Back Pain',
    subjective: 'Patient complains of low back pain radiating to [location]. Onset after [activity].',
    objective: 'SLR: +/-. Lumbar ROM limited in [direction]. Paravertebral tenderness: present.',
    assessment: 'Lumbar disc pathology / mechanical low back pain.',
    plan: 'McKenzie exercises, Core strengthening, Heat therapy. Avoid prolonged sitting.',
  },
];

const props = defineProps({ modelValue: { type: Object, default: () => ({}) } });
const emit = defineEmits(['update:modelValue']);

// Templates from DB (Settings > Treatments > SOAP Templates); fall back to defaults.
const templates = ref(TEMPLATES);
onMounted(async () => {
  try {
    const { data } = await axios.get('/api/soap-templates');
    const rows = data.data ?? data;
    if (Array.isArray(rows) && rows.length) templates.value = rows;
  } catch { /* keep defaults */ }
});

const local = reactive({
  subjective: props.modelValue.subjective || '',
  objective: props.modelValue.objective || '',
  assessment: props.modelValue.assessment || '',
  plan: props.modelValue.plan || '',
});
const selectedTemplate = ref('');

watch(() => props.modelValue, v => {
  Object.assign(local, { subjective: v.subjective || '', objective: v.objective || '', assessment: v.assessment || '', plan: v.plan || '' });
});

function applyTemplate() {
  const t = templates.value.find(t => t.name === selectedTemplate.value);
  if (!t) return;
  Object.assign(local, {
    subjective: t.subjective || '', objective: t.objective || '',
    assessment: t.assessment || '', plan: t.plan || '',
  });
  emit('update:modelValue', { ...local });
}
</script>
