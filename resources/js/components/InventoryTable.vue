<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <Input v-model="query" @input="load" type="text" class="w-full sm:w-72" placeholder="Search medicines..." />
      <Button @click="openNew" class="flex-shrink-0">+ Adjust Stock</Button>
    </div>

    <Card class="overflow-hidden">
      <div v-if="loading" class="p-4 space-y-3">
        <Skeleton v-for="i in 6" :key="i" class="h-9 w-full" />
      </div>
      <Table v-else>
        <TableHeader class="bg-muted/50">
          <TableRow>
            <TableHead>Medicine</TableHead>
            <TableHead class="hidden sm:table-cell">Generic</TableHead>
            <TableHead class="text-right">Stock</TableHead>
            <TableHead class="text-right hidden sm:table-cell">Price</TableHead>
            <TableHead class="text-center hidden sm:table-cell">Status</TableHead>
            <TableHead></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="med in medicines" :key="med.id">
            <TableCell class="font-medium text-foreground">{{ med.name }}</TableCell>
            <TableCell class="text-muted-foreground hidden sm:table-cell">{{ med.generic_name || '—' }}</TableCell>
            <TableCell class="text-right" :class="totalStock(med) <= 10 ? 'text-destructive font-semibold' : 'text-foreground'">
              {{ totalStock(med) }}
            </TableCell>
            <TableCell class="text-right text-foreground hidden sm:table-cell">₹{{ med.sell_price }}</TableCell>
            <TableCell class="text-center hidden sm:table-cell">
              <Badge :variant="stockVariant(med)">{{ stockLabel(med) }}</Badge>
            </TableCell>
            <TableCell class="text-right">
              <Button variant="link" size="sm" class="h-auto p-0" @click="openAdjust(med)">Adjust</Button>
            </TableCell>
          </TableRow>
          <TableRow v-if="!medicines.length">
            <TableCell colspan="6" class="text-center text-muted-foreground py-6">No medicines found.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </Card>

    <!-- Adjust dialog -->
    <Dialog v-model:open="showAdjust">
      <DialogContent class="max-w-md">
        <DialogHeader><DialogTitle>Adjust Stock</DialogTitle></DialogHeader>
        <div class="space-y-3">
          <div v-if="!adjustForm.medicine_id" class="relative">
            <Label class="mb-1 block">Search Medicine</Label>
            <Input v-model="adjustSearch" @input="searchMeds" type="text" placeholder="Type to search..." />
            <div v-if="medResults.length" class="mt-1 border border-border rounded-lg overflow-hidden bg-popover">
              <button v-for="m in medResults" :key="m.id" @mousedown.prevent="selectMed(m)"
                class="w-full text-left px-3 py-2 hover:bg-muted text-sm border-b border-border last:border-0">
                {{ m.name }} <span class="text-muted-foreground">({{ m.generic_name }})</span>
              </button>
            </div>
          </div>
          <div v-else class="flex items-center justify-between p-2 bg-accent text-accent-foreground rounded-md">
            <span class="text-sm font-medium">{{ adjustForm.medicineName }}</span>
            <button @click="adjustForm.medicine_id = null; adjustForm.medicineName = ''" class="text-muted-foreground hover:text-destructive">✕</button>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <Label class="mb-1 block">Type</Label>
              <select v-model="adjustForm.type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <option value="in">Stock In</option>
                <option value="out">Stock Out</option>
              </select>
            </div>
            <div>
              <Label class="mb-1 block">Quantity</Label>
              <Input v-model.number="adjustForm.qty" type="number" min="1" />
            </div>
          </div>
          <div>
            <Label class="mb-1 block">Reason</Label>
            <Input v-model="adjustForm.reason" type="text" placeholder="e.g. Purchase, Dispensed" />
          </div>
        </div>
        <div v-if="adjustError" class="text-sm text-destructive">{{ adjustError }}</div>
        <DialogFooter>
          <Button variant="secondary" @click="showAdjust = false">Cancel</Button>
          <Button :disabled="adjusting" @click="submitAdjust">{{ adjusting ? 'Saving...' : 'Save' }}</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';

const toast = useToast();
const medicines = ref([]);
const query = ref('');
const loading = ref(true);
const showAdjust = ref(false);
const adjustSearch = ref('');
const medResults = ref([]);
const adjusting = ref(false);
const adjustError = ref('');
const adjustForm = reactive({ medicine_id: null, medicineName: '', type: 'in', qty: 1, reason: '' });

onMounted(load);

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/api/inventory', { params: { q: query.value } });
    medicines.value = data.data || data;
  } finally {
    loading.value = false;
  }
}

function totalStock(med) {
  return med.stock?.reduce((s, b) => s + b.quantity_in_stock, 0) ?? 0;
}
function stockVariant(med) {
  const s = totalStock(med);
  return s <= 10 ? 'destructive' : s <= 50 ? 'secondary' : 'success';
}
function stockLabel(med) {
  const s = totalStock(med);
  return s <= 10 ? 'Low Stock' : s <= 50 ? 'Limited' : 'In Stock';
}

function openNew() {
  Object.assign(adjustForm, { medicine_id: null, medicineName: '', type: 'in', qty: 1, reason: '' });
  showAdjust.value = true;
}
function openAdjust(med) {
  adjustForm.medicine_id = med.id;
  adjustForm.medicineName = med.name;
  showAdjust.value = true;
}

let medTimer = null;
async function searchMeds() {
  clearTimeout(medTimer);
  medTimer = setTimeout(async () => {
    const { data } = await axios.get('/api/medicines/search', { params: { q: adjustSearch.value } });
    medResults.value = data;
  }, 300);
}

function selectMed(m) {
  adjustForm.medicine_id = m.id;
  adjustForm.medicineName = m.name;
  medResults.value = [];
}

async function submitAdjust() {
  if (!adjustForm.medicine_id || !adjustForm.qty) { adjustError.value = 'Select medicine and quantity.'; return; }
  adjusting.value = true;
  adjustError.value = '';
  try {
    await axios.post('/api/inventory/adjust', adjustForm);
    toast.success('Stock adjusted.');
    showAdjust.value = false;
    load();
    Object.assign(adjustForm, { medicine_id: null, medicineName: '', type: 'in', qty: 1, reason: '' });
  } catch (e) {
    adjustError.value = e.response?.data?.message || 'Failed to adjust stock.';
  } finally {
    adjusting.value = false;
  }
}
</script>
