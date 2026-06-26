<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-foreground">Patients</h1>
      <Button @click="showNew = true">+ New Patient</Button>
    </div>

    <!-- Search -->
    <Card class="p-4">
      <PatientSearch @select="goToPatient" placeholder="Search patients by name, phone, or OP No..." />
    </Card>

    <!-- Duplicate warning -->
    <Card v-if="dupeWarning" class="border-amber-300 bg-amber-50 dark:bg-amber-950/30 p-4">
      <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2">⚠️ Possible duplicate detected</p>
      <p class="text-xs text-amber-700 dark:text-amber-400 mb-2">{{ dupeWarning.message }}</p>
      <div class="flex flex-wrap gap-2">
        <Button v-for="p in dupeWarning.patients" :key="p.id" variant="outline" size="sm" @click="goToPatient(p)">
          Open: {{ p.name }} ({{ p.op_number }})
        </Button>
        <Button variant="ghost" size="sm" @click="dupeWarning = null">Dismiss</Button>
      </div>
    </Card>

    <!-- Recent patients list -->
    <Card class="overflow-hidden">
      <CardHeader class="py-4 flex-row items-center justify-between">
        <CardTitle class="text-base">Recent Patients</CardTitle>
        <span class="text-xs text-muted-foreground">{{ total }} total</span>
      </CardHeader>

      <!-- loading skeleton -->
      <div v-if="loading" class="divide-y divide-border border-t border-border">
        <div v-for="i in 6" :key="i" class="flex items-center gap-3 px-5 py-3">
          <Skeleton class="w-9 h-9 rounded-full" />
          <div class="space-y-1.5 flex-1"><Skeleton class="h-3.5 w-32" /><Skeleton class="h-3 w-24" /></div>
          <Skeleton class="h-5 w-14 rounded" />
        </div>
      </div>

      <div v-else class="divide-y divide-border border-t border-border">
        <router-link
          v-for="p in patients"
          :key="p.id"
          :to="`/patients/${p.id}`"
          class="flex items-center justify-between px-5 py-3 hover:bg-muted/50 transition-colors"
        >
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-accent flex items-center justify-center text-accent-foreground font-bold text-sm">
              {{ p.name?.charAt(0) }}
            </div>
            <div>
              <div class="font-medium text-sm text-foreground">{{ p.name }}</div>
              <div class="text-xs text-muted-foreground">{{ p.phone }} · {{ p.gender }}</div>
            </div>
          </div>
          <div class="text-right flex items-center gap-2">
            <Badge v-if="p.visit_type" :variant="p.visit_type === 'new' ? 'success' : 'secondary'" class="uppercase text-[10px]">
              {{ p.visit_type === 'new' ? 'New' : 'Revisit' }}
            </Badge>
            <Badge variant="secondary">{{ p.op_number }}</Badge>
          </div>
        </router-link>
        <div v-if="!patients.length" class="px-5 py-6 text-center text-muted-foreground text-sm">No patients found.</div>
      </div>

      <div class="px-5 py-3 border-t border-border flex justify-center items-center gap-3">
        <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--; load()">← Prev</Button>
        <span class="text-xs text-muted-foreground">Page {{ page }}</span>
        <Button variant="outline" size="sm" :disabled="patients.length < 20" @click="page++; load()">Next →</Button>
      </div>
    </Card>

    <!-- New patient dialog -->
    <Dialog v-model:open="showNew">
      <DialogContent class="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Register New Patient</DialogTitle>
        </DialogHeader>
        <PatientForm
          @created="onCreated"
          @cancel="showNew = false"
          @duplicate-warning="dupeWarning = $event"
        />
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PatientSearch from '../components/PatientSearch.vue';
import PatientForm from '../components/PatientForm.vue';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const router = useRouter();
const patients = ref([]);
const total = ref(0);
const page = ref(1);
const loading = ref(true);
const showNew = ref(false);
const dupeWarning = ref(null);

onMounted(load);

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/patients', { params: { page: page.value } });
    patients.value = data.data || data;
    total.value = data.total || patients.value.length;
  } finally {
    loading.value = false;
  }
}

function goToPatient(p) { router.push(`/patients/${p.id}`); }

function onCreated(patient) {
  showNew.value = false;
  router.push(`/patients/${patient.id}`);
}
</script>
