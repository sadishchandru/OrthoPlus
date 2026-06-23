<template>
  <div class="min-h-screen bg-slate-100 px-4 py-8 flex items-center">
    <div class="w-full max-w-5xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-slate-900">OrthoPlus</h1>
        <p class="text-sm text-slate-500 mt-2">Choose the workspace you want to open.</p>
      </div>

      <div class="grid md:grid-cols-2 gap-5">
        <router-link to="/clinic/login" class="portal-card group">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="portal-icon bg-blue-100 text-blue-700">CL</div>
              <h2 class="mt-5 text-xl font-bold text-slate-900">Clinic Portal</h2>
              <p class="mt-2 text-sm text-slate-500">Physiotherapy and clinic management</p>
            </div>
            <span class="arrow text-blue-700">Login</span>
          </div>
          <div class="mt-8 h-1.5 rounded-full bg-blue-100 overflow-hidden">
            <div class="h-full w-2/3 bg-blue-600 transition-all group-hover:w-full"></div>
          </div>
        </router-link>

        <router-link to="/hospital/login" class="portal-card group">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="portal-icon bg-slate-800 text-white">HM</div>
              <h2 class="mt-5 text-xl font-bold text-slate-900">Hospital Portal</h2>
              <p class="mt-2 text-sm text-slate-500">Orthopedic HMS for OPD, beds, surgery, staff and billing</p>
            </div>
            <span class="arrow text-slate-800">Login</span>
          </div>
          <div class="mt-8 h-1.5 rounded-full bg-slate-200 overflow-hidden">
            <div class="h-full w-2/3 bg-[#1e3a5f] transition-all group-hover:w-full"></div>
          </div>
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "tailwindcss";
.portal-card { @apply block rounded-lg border border-white bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl; }
.portal-icon { @apply w-14 h-14 rounded-lg flex items-center justify-center text-sm font-bold tracking-wide; }
.arrow { @apply text-sm font-semibold rounded-full px-3 py-1 bg-slate-50 group-hover:bg-white; }
</style>
