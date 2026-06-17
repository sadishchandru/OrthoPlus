<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex flex-wrap gap-3 items-center">
      <select v-model="therapistId" @change="loadEvents" class="input w-48">
        <option value="">All Therapists</option>
        <option v-for="t in therapists" :key="t.id" :value="t.id">{{ t.name }}</option>
      </select>
      <button @click="showBook = true" class="btn-primary">+ Book Appointment</button>
    </div>

    <!-- Calendar -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <FullCalendar
        ref="calRef"
        :options="calOptions"
      />
    </div>

    <!-- Book Modal -->
    <div v-if="showBook" class="fixed inset-0 bg-black/50 z-50 flex items-stretch md:items-center justify-center p-0 md:p-4">
      <div class="bg-white rounded-none md:rounded-xl p-4 md:p-6 w-full md:max-w-md shadow-xl h-full md:h-auto max-h-screen md:max-h-[90vh] overflow-y-auto">
        <h3 class="font-semibold text-gray-900 mb-4">Book Appointment</h3>
        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Patient</label>
            <PatientSearch @select="bookForm.patient_id = $event.id; bookForm.patientName = $event.name" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Date</label>
              <input v-model="bookForm.scheduled_date" type="date" class="input w-full" />
            </div>
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Time</label>
              <input v-model="bookForm.scheduled_time" type="time" class="input w-full" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Duration</label>
              <select v-model="bookForm.duration_minutes" class="input w-full">
                <option value="15">15 min</option>
                <option value="30">30 min</option>
                <option value="45">45 min</option>
                <option value="60">60 min</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-600 mb-1 block">Therapist</label>
              <select v-model="bookForm.therapist_id" class="input w-full">
                <option value="">Any</option>
                <option v-for="t in therapists" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-600 mb-1 block">Notes</label>
            <input v-model="bookForm.notes" type="text" class="input w-full" placeholder="Optional notes..." />
          </div>
        </div>
        <div v-if="bookError" class="mt-3 text-sm text-red-600">{{ bookError }}</div>
        <div class="flex justify-end gap-2 mt-4">
          <button @click="showBook = false" class="btn-secondary">Cancel</button>
          <button @click="submitBook" :disabled="booking" class="btn-primary">
            {{ booking ? 'Booking...' : 'Confirm' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import PatientSearch from './PatientSearch.vue';

const toast = useToast();
const calRef = ref(null);
const therapistId = ref('');
const therapists = ref([]);
const showBook = ref(false);
const booking = ref(false);
const bookError = ref('');

const bookForm = reactive({
  patient_id: null, patientName: '',
  scheduled_date: '', scheduled_time: '',
  duration_minutes: '30', therapist_id: '', notes: '',
});

// 3 tiers: phone <640 = agenda list, tablet <1024 = 3-day, desktop = week
const tier = (w = window.innerWidth) => (w < 640 ? 'm' : w < 1024 ? 't' : 'd');
const viewFor = (k) => ({ m: 'listWeek', t: 'timeGridThreeDay', d: 'timeGridWeek' }[k]);
const toolbarFor = (k) => ({
  m: { left: 'prev,next', center: 'title', right: 'listWeek,timeGridDay' },
  t: { left: 'prev,next today', center: 'title', right: 'timeGridDay,timeGridThreeDay' },
  d: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
}[k]);

const calOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin, listPlugin],
  views: { timeGridThreeDay: { type: 'timeGrid', duration: { days: 3 }, buttonText: '3 day' } },
  initialView: viewFor(tier()),
  headerToolbar: toolbarFor(tier()),
  slotMinTime: '07:00:00',
  slotMaxTime: '21:00:00',
  allDaySlot: false,
  height: tier() === 'm' ? 'auto' : 600,
  expandRows: true,
  events: loadEvents,
  dateClick(info) {
    bookForm.scheduled_date = info.dateStr.split('T')[0];
    bookForm.scheduled_time = info.dateStr.split('T')[1]?.slice(0, 5) || '09:00';
    showBook.value = true;
  },
  eventClick(info) {
    toast.info(`${info.event.title} — ${info.event.extendedProps.status}`);
  },
};

let lastTier = tier();
function onResize() {
  const k = tier();
  if (k === lastTier) return; // only act when crossing a tier boundary
  lastTier = k;
  const api = calRef.value?.getApi();
  if (!api) return;
  api.changeView(viewFor(k));
  api.setOption('height', k === 'm' ? 'auto' : 600);
  api.setOption('headerToolbar', toolbarFor(k));
}

onMounted(async () => {
  window.addEventListener('resize', onResize);
  const { data } = await axios.get('/api/therapists').catch(() => ({ data: [] }));
  therapists.value = data;
});

onBeforeUnmount(() => window.removeEventListener('resize', onResize));

async function loadEvents(fetchInfo, successCb, failureCb) {
  try {
    const { data } = await axios.get('/api/appointments/calendar', {
      params: {
        start: fetchInfo?.startStr?.split('T')[0] || new Date().toISOString().split('T')[0],
        end: fetchInfo?.endStr?.split('T')[0] || new Date().toISOString().split('T')[0],
        therapist_id: therapistId.value || undefined,
      },
    });
    successCb?.(data);
    return data;
  } catch {
    failureCb?.();
  }
}

async function submitBook() {
  if (!bookForm.patient_id) { bookError.value = 'Select a patient.'; return; }
  if (!bookForm.scheduled_date || !bookForm.scheduled_time) { bookError.value = 'Date and time required.'; return; }
  booking.value = true;
  bookError.value = '';
  try {
    await axios.post('/api/appointments', bookForm);
    showBook.value = false;
    toast.success('Appointment booked.');
    calRef.value?.getApi().refetchEvents();
  } catch (e) {
    bookError.value = e.response?.data?.error || 'Failed to book appointment.';
  } finally {
    booking.value = false;
  }
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
