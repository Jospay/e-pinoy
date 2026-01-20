<script setup lang="ts">
import L from 'leaflet';
import { onMounted, ref } from 'vue';

const mapContainer = ref<HTMLDivElement | null>(null);

const locations: { name: string; coords: [number, number] }[] = [
  { name: 'San Nicolas', coords: [14.6021, 120.9629] },
  { name: 'Santa Cruz', coords: [14.6135, 120.9822] },
  { name: 'Sampaloc', coords: [14.6139, 120.9891] },
  { name: 'Ermita', coords: [14.5823, 120.9839] },
  { name: 'Malate', coords: [14.5733, 120.9842] },
];

onMounted(() => {
  if (!mapContainer.value) return;

  // Initialize map
  const map = L.map(mapContainer.value).setView([14.5995, 120.9842], 13);

  // Add tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  // Add markers
  locations.forEach(({ name, coords }) => {
    L.marker(coords).addTo(map).bindPopup(`<b>${name}</b>`);
  });
});
</script>

<template>
  <div ref="mapContainer" class="h-[300px] w-full rounded-md"></div>
</template>

<style scoped>
.leaflet-container {
  border-radius: 0.5rem;
  z-index: 0;
}
</style>
