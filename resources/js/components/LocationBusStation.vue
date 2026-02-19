<script setup lang="ts">
import { LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import L, { type Icon } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref } from 'vue';

export interface MarkerData {
  id: number;
  latitude: number;
  longitude: number;
  name: string;
  code_no: string;
  status: string;
}

const props = defineProps<{
  locations: MarkerData[];
  center?: [number, number];
  zoom?: number;
}>();

// Status → color map
const STATUS_COLORS: Record<string, string> = {
  active: '#3b82f6', // blue
  pending: '#d97706', // amber
  inactive: '#dc2626', // red
};

const createIcon = (color: string): Icon => {
  const svg = `
    <svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg">
      <path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 9.375 12.5 28.5 12.5 28.5S25 21.875 25 12.5C25 5.596 19.404 0 12.5 0z"
            fill="${color}" stroke="#fff" stroke-width="1"/>
      <circle cx="12.5" cy="12.5" r="4" fill="#fff"/>
    </svg>`;
  return L.icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(svg)}`,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
  });
};

// Cache icons to avoid re-creating on every render
const iconCache = new Map<string, Icon>();
const getIcon = (status: string): Icon => {
  if (!iconCache.has(status)) {
    iconCache.set(status, createIcon(STATUS_COLORS[status] ?? '#6b7280'));
  }
  return iconCache.get(status)!;
};

const map = ref<any>(null);
const mapReady = ref(false);

const defaultCenter = computed<[number, number]>(() => {
  if (props.center) return props.center;
  const valid = props.locations.filter((l) => l.latitude && l.longitude);
  if (valid.length) return [valid[0].latitude, valid[0].longitude];
  return [14.5995, 120.9842]; // Manila fallback
});

function fitBounds() {
  if (!mapReady.value || !map.value?.leafletObject) return;
  const valid = props.locations.filter((l) => l.latitude && l.longitude);
  if (!valid.length) return;

  const bounds = L.latLngBounds(valid.map((l) => [l.latitude, l.longitude]));
  if (bounds.isValid()) {
    map.value.leafletObject.fitBounds(bounds, { padding: [40, 40] });
  }
}

function onMapReady() {
  nextTick(() => {
    mapReady.value = true;
    fitBounds();
  });
}
</script>

<template>
  <l-map
    ref="map"
    :center="defaultCenter"
    :zoom="props.zoom ?? 14"
    class="h-full w-full"
    @ready="onMapReady"
  >
    <l-tile-layer
      url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
      layer-type="base"
      name="OpenStreetMap"
      attribution='&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    />
    <template v-for="loc in locations" :key="loc.id">
      <l-marker
        v-if="loc.latitude && loc.longitude"
        :lat-lng="[loc.latitude, loc.longitude]"
        :icon="getIcon(loc.status)"
      >
        <l-popup>
          <div class="p-1 text-xs">
            <p class="font-bold">{{ loc.name }}</p>
            <p class="text-slate-500">{{ loc.code_no }}</p>
            <span
              class="inline-block rounded px-1 py-0.5 text-white"
              :style="{ background: STATUS_COLORS[loc.status] ?? '#6b7280' }"
              >{{ loc.status }}</span
            >
          </div>
        </l-popup>
      </l-marker>
    </template>
  </l-map>
</template>
