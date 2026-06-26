import { ref, computed } from 'vue';

// Global busy state. Driven by axios pending requests + router navigation.
const pending = ref(0);
const navigating = ref(false);

export function startLoading() { pending.value++; }
export function stopLoading() { pending.value = Math.max(0, pending.value - 1); }
export function setNavigating(v) { navigating.value = !!v; }

// True whenever any request is in-flight OR a route is loading.
export const isBusy = computed(() => pending.value > 0 || navigating.value);
