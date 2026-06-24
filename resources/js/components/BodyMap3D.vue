<template>
  <div class="w-full flex flex-col items-center gap-3" @click.stop @mousedown.stop>
    <div class="w-full flex items-center justify-between gap-3 flex-wrap">
      <label class="text-sm font-medium text-gray-700">Body Map — tap the model to mark pain</label>
      <div class="flex gap-2">
        <button type="button" @click="setView('front')" class="px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Front</button>
        <button type="button" @click="setView('back')" class="px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Back</button>
        <button type="button" @click="setView('reset')" class="px-3 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">Reset</button>
      </div>
    </div>

    <!-- Severity picker -->
    <div class="w-full flex items-center gap-2 flex-wrap justify-center">
      <span class="text-xs text-gray-500">Severity:</span>
      <button v-for="s in [1, 2, 3]" :key="s" type="button" @click="selectedSeverity = s"
        :class="[selectedSeverity === s ? 'ring-2 ring-offset-1 scale-110' : '', s === 1 ? 'bg-yellow-400' : s === 2 ? 'bg-orange-500' : 'bg-red-500']"
        class="w-8 h-8 rounded-full text-white text-sm font-bold transition-transform">{{ s }}</button>
      <span class="text-xs text-gray-400">drag to rotate · tap a marker to remove</span>
    </div>

    <div ref="container" class="relative w-full max-w-[420px] mx-auto rounded-2xl overflow-hidden border bg-white"
         style="height:60vh; min-height:340px; touch-action:none;">
      <div v-if="loading" class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">Loading 3D model…</div>
      <div v-if="loadError" class="absolute inset-0 flex items-center justify-center text-sm text-red-500 text-center px-4">{{ loadError }}</div>
    </div>

    <!-- Marked parts -->
    <div v-if="markers.length" class="w-full">
      <p class="text-xs font-medium text-gray-600 mb-1">Marked areas:</p>
      <div class="flex flex-wrap gap-1">
        <span v-for="m in markers" :key="m.id"
          class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs border"
          :style="{ background: sevColor(m.severity) + '22', color: sevColor(m.severity), borderColor: sevColor(m.severity) + '55' }">
          {{ m.label }} · {{ m.severity }}
          <button type="button" @click="removeMarker(m.id)" class="opacity-70 hover:opacity-100">✕</button>
        </span>
      </div>
    </div>

    <p class="text-[10px] text-gray-400 text-center leading-tight">{{ credit }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  gender: { type: String, default: 'male' }, // picks the 3D model
});
const emit = defineEmits(['update:modelValue']);

const isFemale = computed(() => (props.gender || '').toLowerCase() === 'female');
const modelUrl = computed(() => isFemale.value
  ? '/models/female_body/scene.gltf'
  : '/models/human_body/scene.gltf');
const credit = computed(() => isFemale.value
  ? '3D model: “Female body base” by Dori Mur (Sketchfab), CC-BY-4.0.'
  : '3D model: “HUMAN_BODY” by vistaalienprime (Sketchfab), CC-BY-4.0.');

const container = ref(null);
const loading = ref(true);
const loadError = ref('');
const selectedSeverity = ref(1);
const markers = ref([]);          // [{id, part, label, severity, point:{x,y,z}}]
const passthrough = ref([]);      // legacy entries without a 3D point (kept, re-emitted)

let renderer, scene, camera, controls, model, raf, ro;
const markerGroup = new THREE.Group();
let bbox, center = new THREE.Vector3(), size = new THREE.Vector3(), maxDim = 1;

function sevColor(s) { return s === 1 ? '#FCD34D' : s === 2 ? '#F97316' : '#EF4444'; }

onMounted(() => {
  const el = container.value;
  const w = el.clientWidth, h = el.clientHeight;

  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(45, w / h, 0.01, 100);
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(w, h);
  el.appendChild(renderer.domElement);

  scene.add(new THREE.HemisphereLight(0xffffff, 0x8899aa, 1.1));
  const dir = new THREE.DirectionalLight(0xffffff, 1.0);
  dir.position.set(1, 2, 2);
  scene.add(dir);
  scene.add(markerGroup);

  controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.enablePan = false;

  new GLTFLoader().load(
    modelUrl.value,
    (gltf) => {
      model = gltf.scene;
      model.traverse((o) => {
        if (o.isMesh) {
          o.material = new THREE.MeshStandardMaterial({ color: 0xd8c3a8, roughness: 0.7, metalness: 0.0 });
        }
      });
      scene.add(model);

      bbox = new THREE.Box3().setFromObject(model);
      bbox.getCenter(center);
      bbox.getSize(size);
      maxDim = Math.max(size.x, size.y, size.z);

      controls.target.copy(center);
      setView('front');

      // Rebuild markers from incoming v-model (with 3D points); keep legacy ones.
      for (const m of props.modelValue || []) {
        if (m && m.point) addSphere(m);
        else if (m) passthrough.value.push(m);
      }
      syncOut(false);

      loading.value = false;
    },
    undefined,
    () => { loading.value = false; loadError.value = 'Failed to load 3D model.'; }
  );

  renderer.domElement.addEventListener('pointerdown', onDown);
  renderer.domElement.addEventListener('pointerup', onUp);

  ro = new ResizeObserver(() => resize());
  ro.observe(el);

  const loop = () => { raf = requestAnimationFrame(loop); controls.update(); renderer.render(scene, camera); };
  loop();
});

function resize() {
  if (!renderer || !container.value) return;
  const w = container.value.clientWidth, h = container.value.clientHeight;
  camera.aspect = w / h; camera.updateProjectionMatrix();
  renderer.setSize(w, h);
}

// --- camera presets (Y-up, Z-depth per Sketchfab export) ---
function setView(which) {
  if (!model) return;
  const d = maxDim * 1.9;
  if (which === 'back') camera.position.set(center.x, center.y, center.z - d);
  else camera.position.set(center.x, center.y, center.z + d); // front + reset
  camera.up.set(0, 1, 0);
  controls.target.copy(center);
  controls.update();
}

// --- tap vs drag detection ---
let downXY = null;
function onDown(e) { downXY = { x: e.clientX, y: e.clientY }; }
function onUp(e) {
  if (!downXY) return;
  const moved = Math.hypot(e.clientX - downXY.x, e.clientY - downXY.y);
  downXY = null;
  if (moved > 6) return; // was a drag/rotate
  handleTap(e);
}

const raycaster = new THREE.Raycaster();
const ndc = new THREE.Vector2();
function handleTap(e) {
  if (!model) return;
  const rect = renderer.domElement.getBoundingClientRect();
  ndc.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
  ndc.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
  raycaster.setFromCamera(ndc, camera);

  // Hit an existing marker first → remove it.
  const hitMarkers = raycaster.intersectObjects(markerGroup.children, false);
  if (hitMarkers.length) { removeMarker(hitMarkers[0].object.userData.id); return; }

  // Else hit the body → add a marker at the hit point.
  const hit = raycaster.intersectObject(model, true)[0];
  if (!hit) return;
  const p = hit.point;
  const zone = zoneFor(p);
  const m = {
    id: Date.now() + Math.random(),
    part: zone.part,
    label: zone.label,
    severity: selectedSeverity.value,
    point: { x: p.x, y: p.y, z: p.z },
  };
  addSphere(m);
  syncOut();
}

function addSphere(m) {
  const r = maxDim * 0.018;
  const mesh = new THREE.Mesh(
    new THREE.SphereGeometry(r, 16, 16),
    new THREE.MeshBasicMaterial({ color: sevColor(m.severity) })
  );
  mesh.position.set(m.point.x, m.point.y, m.point.z);
  mesh.userData.id = m.id;
  markerGroup.add(mesh);
  markers.value.push(m);
}

function removeMarker(id) {
  const mesh = markerGroup.children.find((c) => c.userData.id === id);
  if (mesh) { markerGroup.remove(mesh); mesh.geometry.dispose(); mesh.material.dispose(); }
  markers.value = markers.value.filter((m) => m.id !== id);
  syncOut();
}

function syncOut(touch = true) {
  // Emit clean records (no three internals) + preserved legacy entries.
  const out = markers.value.map(({ id, part, label, severity, point }) => ({ id, part, label, severity, point }));
  emit('update:modelValue', [...passthrough.value, ...out]);
}

// Height-band + lateral zoning (single-mesh model → approximate region).
function zoneFor(p) {
  const t = (p.y - bbox.min.y) / (size.y || 1);          // 0 = feet, 1 = head
  const latN = (p.x - center.x) / ((size.x / 2) || 1);   // -1..1 across width
  const side = latN >= 0 ? 'Right' : 'Left';
  const limb = Math.abs(latN) > 0.42;                    // far from midline = arm/limb

  const reg = (label, part, sided = true) =>
    sided ? { label: `${side} ${label}`, part: `${side.toLowerCase()}_${part}` } : { label, part };

  if (t > 0.93) return reg('Head', 'head', false);
  if (t > 0.88) return reg('Neck', 'neck', false);
  if (t > 0.82) return reg('Shoulder', 'shoulder');
  if (t > 0.66) return limb ? reg('Upper Arm', 'upper_arm') : reg('Chest', 'chest', false);
  if (t > 0.58) return limb ? reg('Forearm', 'forearm') : reg('Abdomen', 'abdomen', false);
  if (t > 0.50) return limb ? reg('Hand', 'hand') : reg('Hip', 'hip');
  if (t > 0.34) return reg('Thigh', 'thigh');
  if (t > 0.29) return reg('Knee', 'knee');
  if (t > 0.12) return reg('Leg', 'leg');
  if (t > 0.06) return reg('Ankle', 'ankle');
  return reg('Foot', 'foot');
}

onBeforeUnmount(() => {
  cancelAnimationFrame(raf);
  ro?.disconnect();
  renderer?.domElement?.removeEventListener('pointerdown', onDown);
  renderer?.domElement?.removeEventListener('pointerup', onUp);
  controls?.dispose();
  renderer?.dispose();
  if (renderer?.domElement?.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement);
});
</script>
