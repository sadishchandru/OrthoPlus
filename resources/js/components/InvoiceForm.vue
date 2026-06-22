<template>
  <div class="space-y-4">
    <h3 class="font-semibold text-gray-900">Generate Invoice</h3>

    <!-- Line items -->
    <div class="space-y-2">
      <div class="hidden sm:grid grid-cols-12 gap-2 text-xs text-gray-500 font-medium uppercase px-1">
        <span class="col-span-5">Description</span>
        <span class="col-span-2 text-right">Qty</span>
        <span class="col-span-2 text-right">Rate (₹)</span>
        <span class="col-span-2 text-right">Amount</span>
        <span class="col-span-1"></span>
      </div>
      <div v-for="(item, i) in items" :key="i" class="grid grid-cols-12 gap-2 items-center">
        <input v-model="item.description" type="text" class="input col-span-12 sm:col-span-5" placeholder="Service/item" />
        <input v-model.number="item.qty" type="number" min="1" class="input col-span-4 sm:col-span-2 text-right" placeholder="Qty" />
        <input v-model.number="item.rate" type="number" min="0" class="input col-span-4 sm:col-span-2 text-right" placeholder="Rate (₹)" />
        <div class="col-span-3 sm:col-span-2 text-right text-sm font-medium text-gray-700">
          ₹{{ ((item.qty || 0) * (item.rate || 0)).toFixed(0) }}
        </div>
        <button @click="items.splice(i, 1)" type="button" class="col-span-1 text-gray-300 hover:text-red-500 text-center">✕</button>
      </div>
      <div class="flex flex-wrap items-center gap-2 mt-1">
        <button @click="addItem" type="button" class="text-xs text-blue-600 hover:text-blue-700">+ Add free-text line</button>
        <span v-if="catalog.length" class="text-gray-300">|</span>
        <select v-if="catalog.length" @change="addFromCatalog($event.target.value); $event.target.value=''" class="input text-xs !py-1">
          <option value="">+ Add from treatment catalog…</option>
          <option v-for="c in catalog" :key="c.id" :value="c.id">{{ c.name }} — ₹{{ c.price }}</option>
        </select>
      </div>
    </div>

    <!-- Package selector -->
    <div v-if="packages.length">
      <label class="text-xs text-gray-600 block mb-1">Apply Package</label>
      <select @change="applyPackage($event.target.value)" class="input w-full">
        <option value="">Select package...</option>
        <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }} — ₹{{ p.price }} ({{ p.sessions }} sessions)</option>
      </select>
    </div>

    <!-- Totals -->
    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Subtotal</span>
        <span class="font-medium">₹{{ subtotal.toFixed(2) }}</span>
      </div>
      <div class="flex justify-between items-center text-sm">
        <span class="text-gray-600">Discount</span>
        <input v-model.number="discount" type="number" min="0" class="w-24 border border-gray-200 rounded px-2 py-1 text-right text-sm" />
      </div>
      <div class="flex justify-between items-center text-sm">
        <span class="text-gray-600">Tax (₹)</span>
        <input v-model.number="tax" type="number" min="0" class="w-24 border border-gray-200 rounded px-2 py-1 text-right text-sm" />
      </div>
      <div class="flex justify-between text-base font-bold border-t border-gray-200 pt-2">
        <span>Total</span>
        <span class="text-blue-700">₹{{ total.toFixed(2) }}</span>
      </div>
    </div>

    <!-- Payment -->
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-xs text-gray-600 block mb-1">Payment Method</label>
        <select v-model="paymentMethod" class="input w-full">
          <option value="">Select</option>
          <option>Cash</option><option>Card</option>
          <option>UPI</option><option>Net Banking</option>
          <option>Insurance</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-600 block mb-1">Status</label>
        <select v-model="status" class="input w-full">
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="due">Due</option>
        </select>
      </div>
    </div>

    <div v-if="error" class="text-sm text-red-600">{{ error }}</div>

    <div class="flex gap-3">
      <button @click="submit" :disabled="loading || !items.length" class="btn-primary flex-1">
        {{ loading ? 'Saving...' : 'Save Invoice' }}
      </button>
      <button v-if="savedInvoice" @click="printInvoice" class="btn-secondary px-4">
        Print
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const props = defineProps({ patientId: { type: Number, required: true } });
const emit = defineEmits(['saved']);
const toast = useToast();

const items = ref([{ description: '', qty: 1, rate: 0 }]);
const discount = ref(0);
const tax = ref(0);
const paymentMethod = ref('');
const status = ref('pending');
const packages = ref([]);
const catalog = ref([]);
const loading = ref(false);
const error = ref('');
const savedInvoice = ref(null);

const subtotal = computed(() => items.value.reduce((s, i) => s + (i.qty || 0) * (i.rate || 0), 0));
const total = computed(() => subtotal.value - discount.value + tax.value);

onMounted(async () => {
  const [{ data: pkgs }, { data: cat }] = await Promise.all([
    axios.get('/api/packages').catch(() => ({ data: [] })),
    axios.get('/api/treatments/catalog').catch(() => ({ data: [] })),
  ]);
  packages.value = pkgs;
  catalog.value = cat.data ?? cat;
});

function addItem() { items.value.push({ description: '', qty: 1, rate: 0 }); }

function addFromCatalog(id) {
  const c = catalog.value.find(x => x.id == id);
  if (!c) return;
  items.value.push({ description: c.name, qty: 1, rate: Number(c.price) || 0 });
}

function applyPackage(id) {
  const pkg = packages.value.find(p => p.id == id);
  if (!pkg) return;
  items.value.push({ description: pkg.name, qty: 1, rate: pkg.price });
}

async function submit() {
  const invalid = items.value.some(i => !i.description);
  if (invalid) { error.value = 'All items need a description.'; return; }
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/api/invoices', {
      patient_id: props.patientId,
      items: items.value,
      discount: discount.value,
      tax: tax.value,
      payment_method: paymentMethod.value,
      status: status.value,
    });
    savedInvoice.value = data;
    toast.success(`Invoice ${data.invoice_no} created.`);
    emit('saved', data);
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to create invoice.';
  } finally {
    loading.value = false;
  }
}

function printInvoice() {
  window.open(`/print/invoice/${savedInvoice.value.id}`, '_blank');
}
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none; }
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
