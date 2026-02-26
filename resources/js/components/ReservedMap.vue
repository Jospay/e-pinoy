<script setup lang="ts">
import { LMap, LMarker, LTileLayer, LTooltip } from '@vue-leaflet/vue-leaflet';
import L, { type Icon, latLngBounds } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, ref } from 'vue';

export interface MarkerData {
  id: number | string;
  latitude: number;
  longitude: number;
  name?: string;
  type?: 'Start' | 'End' | 'Pin';
  [key: string]: any;
}

const props = defineProps<{
  locations: MarkerData[];
  center?: [number, number];
  zoom?: number;
  fitBounds?: boolean;
  selectable?: boolean;
}>();

const emit = defineEmits(['locationSelected']);

// --- Philippines Map Constraints ---
const phBounds = latLngBounds([4.3, 116.0], [21.1, 127.0]);

// --- Leaflet Setup ---
const map = ref<any>(null);
const mapReady = ref(false);

const createCustomIcon = (color: string): Icon => {
  const svgIcon = `<svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 9.375 12.5 28.5 12.5 28.5S25 21.875 25 12.5C25 5.596 19.404 0 12.5 0z" fill="${color}" stroke="#fff" stroke-width="1"/><circle cx="12.5" cy="12.5" r="4" fill="#fff"/></svg>`;
  return L.icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(svgIcon)}`,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
  });
};

const startIcon = createCustomIcon('#16a34a');
const endIcon = createCustomIcon('#dc2626');
const pinIcon = createCustomIcon('#2563eb');

const getMarkerIcon = (loc: MarkerData) => {
  if (loc.type === 'Start') return startIcon;
  if (loc.type === 'Pin') return pinIcon;
  return endIcon;
};

const handleMapClick = (e: any) => {
  if (!props.selectable) return;
  emit('locationSelected', { lat: e.latlng.lat, lng: e.latlng.lng });
};

function onMapReady() {
  nextTick(() => {
    mapReady.value = true;
  });
}
</script>

<template>
  <div class="relative h-full w-full rounded-lg border-gray-200 bg-slate-50">
    <l-map
      ref="map"
      :center="center ?? [14.5995, 120.9842]"
      :zoom="zoom ?? 13"
      :min-zoom="6"
      :max-bounds="phBounds"
      @ready="onMapReady"
      @click="handleMapClick"
    >
      <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

      <l-marker
        v-for="location in props.locations"
        :key="location.id"
        :lat-lng="[location.latitude, location.longitude]"
        :icon="getMarkerIcon(location)"
      >
        <l-tooltip
          v-if="location.name"
          :options="{ permanent: true, direction: 'top', offset: [0, -32] }"
        >
          <span class="font-bold text-slate-800">{{ location.name }}</span>
        </l-tooltip>
      </l-marker>
    </l-map>
  </div>
</template>
