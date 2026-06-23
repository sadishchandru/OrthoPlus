<template>
  <div class="min-h-[70vh] flex items-center justify-center px-4">
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-8 max-w-md text-center">
      <div class="mx-auto mb-4 w-12 h-12 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold">!</div>
      <h1 class="text-xl font-bold text-gray-900 mb-1">Access Denied</h1>
      <p class="text-sm text-gray-500 mb-6">You do not have permission to view this page. Contact your administrator if you need access.</p>
      <div class="flex justify-center gap-3">
        <button @click="$router.back()" class="btn-secondary">Back</button>
        <router-link to="/" class="btn-primary">Choose Portal</router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
.btn-secondary { @apply bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors; }
</style>
