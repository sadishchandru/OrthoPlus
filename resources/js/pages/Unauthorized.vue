<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8 max-w-md text-center">
      <div class="text-5xl mb-3">🔒</div>
      <h1 class="text-xl font-bold text-gray-900 mb-1">Access Denied</h1>
      <p class="text-sm text-gray-500 mb-6">You don't have permission to view this page. Contact your administrator if you need access.</p>
      <div class="flex justify-center gap-3">
        <button @click="$router.back()" class="btn-secondary">← Back</button>
        <router-link to="/" class="btn-primary">Go to Dashboard</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
