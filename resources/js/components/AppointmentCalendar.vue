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
    <div v-if="showBook" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
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
import { ref, reactive, onMounted } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
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

const calOptions = {
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'timeGridWeek',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  slotMinTime: '07:00:00',
  slotMaxTime: '21:00:00',
  allDaySlot: false,
  height: 600,
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

onMounted(async () => {
  const { data } = await axios.get('/api/therapists').catch(() => ({ data: [] }));
  therapists.value = data;
});

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
