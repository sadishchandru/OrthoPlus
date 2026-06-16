<template>
  <div class="space-y-4">
    <h1 class="text-xl font-bold text-gray-900">Inventory</h1>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <InventoryTable />
    </div>
  </div>
</template>

<script setup>
import InventoryTable from '../components/InventoryTable.vue';
</script>
