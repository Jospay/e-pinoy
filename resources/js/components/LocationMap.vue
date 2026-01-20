<script setup lang="ts">
import { LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import L, { type Icon } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, watch } from 'vue';

export interface MarkerData {
  id: number;
  latitude: number;
  longitude: number;
  [key: string]: any;
}

const props = defineProps<{
  locations: MarkerData[];
  center?: [number, number];
  zoom?: number;
  fitBounds?: boolean;
}>();

// --- Custom Marker Icons ---
const createCustomIcon = (color: 'green' | 'red'): Icon => {
  const colorMap = {
    green: '#16a34a',
    red: '#dc2626',
  };

  const svgIcon = `
    <svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
      <path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 9.375 12.5 28.5 12.5 28.5S25 21.875 25 12.5C25 5.596 19.404 0 12.5 0z"
            fill="${colorMap[color]}"
            stroke="#fff"
            stroke-width="1"/>
      <circle cx="12.5" cy="12.5" r="4" fill="#fff"/>
    </svg>
  `;

  return L.icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(svgIcon)}`,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
  });
};

const startIcon = createCustomIcon('green');
const endIcon = createCustomIcon('red');

// Helper to get the correct icon (Modified slightly to use 'type' property from Details.vue)
const getMarkerIcon = (location: MarkerData) => {
  // Use the 'type' property we added in mapLocations
  return location.type === 'Start' ? startIcon : endIcon;
};

// --- State ---
const map = ref<any>(null);
const mapReady = ref(false);

const defaultCenter = computed<[number, number]>(
  () => props.center ?? [15.1465, 120.5794],
);
const defaultZoom = computed(() => props.zoom ?? 13);

// ---  Smart Bounds Logic ---
const locationsSignature = computed(() => {
  // Use location properties to track changes
  return props.locations
    .map((l) => `${l.id}:${l.latitude},${l.longitude}`)
    .sort()
    .join('|');
});

function fitMapToBounds() {
  if (!mapReady.value || !map.value?.leafletObject) return;
  if (!props.fitBounds || props.locations.length === 0) return;

  const bounds = L.latLngBounds(
    props.locations.map((loc) => [loc.latitude, loc.longitude]),
  );

  if (bounds.isValid()) {
    map.value.leafletObject.fitBounds(bounds, { padding: [50, 50] });
  }
}

function onMapReady() {
  nextTick(() => {
    mapReady.value = true;
    fitMapToBounds();
  });
}

// Watcher 1: Watch the SIGNATURE, which changes when locations change.
watch(locationsSignature, () => {
  fitMapToBounds();
});
</script>

<template>
  <div
    class="isolate z-0 h-full w-full overflow-hidden rounded-lg border border-gray-200 shadow-inner"
  >
    <l-map
      ref="map"
      :center="defaultCenter"
      :zoom="defaultZoom"
      @ready="onMapReady"
    >
      <l-tile-layer
        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        layer-type="base"
        name="OpenStreetMap"
        attribution='&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      />

      <l-marker
        v-for="location in props.locations"
        :key="location.id"
        :lat-lng="[location.latitude, location.longitude]"
        :icon="getMarkerIcon(location)"
      >
        <l-popup>
          <slot name="popup" :item="location">
            <div class="p-2">
              <span class="font-bold">ID: {{ location.id }}</span>
            </div>
          </slot>
        </l-popup>
      </l-marker>
    </l-map>
  </div>
</template>

<style scoped>
/* Ensure map controls sit above other UI elements if necessary */
/* This style block may need to be in a separate CSS file or your global styles
   if you are using Vue's scoped styles for LeafletMap.vue */
.leaflet-pane {
  z-index: 10;
}
.leaflet-bottom {
  z-index: 20;
}
</style>
