<script setup lang="ts">
import { LMap, LMarker, LPopup, LTileLayer } from '@vue-leaflet/vue-leaflet';
import L, { type Icon } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, nextTick, ref, watch } from 'vue';

export interface MarkerData {
  id: number;
  latitude: number;
  longitude: number;
  type?: 'Start' | 'End';
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
  const colorMap = { green: '#16a34a', red: '#dc2626' };
  const svgIcon = `<svg width="25" height="41" viewBox="0 0 25 41" xmlns="http://www.w3.org/2000/svg"><path d="M12.5 0C5.596 0 0 5.596 0 12.5c0 9.375 12.5 28.5 12.5 28.5S25 21.875 25 12.5C25 5.596 19.404 0 12.5 0z" fill="${colorMap[color]}" stroke="#fff" stroke-width="1"/><circle cx="12.5" cy="12.5" r="4" fill="#fff"/></svg>`;

  return L.icon({
    iconUrl: `data:image/svg+xml;base64,${btoa(svgIcon)}`,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
  });
};

const startIcon = createCustomIcon('green');
const endIcon = createCustomIcon('red');

const getMarkerIcon = (location: MarkerData) => {
  return location.type === 'Start' ? startIcon : endIcon;
};

// --- State ---
const map = ref<any>(null);
const mapReady = ref(false);

// 📍 Define the "All Philippines" center and zoom
const PH_CENTER: [number, number] = [12.8797, 121.774];
const PH_ZOOM = 6;

const defaultCenter = computed<[number, number]>(() => {
  if (props.locations.length === 1) {
    return [props.locations[0].latitude, props.locations[0].longitude];
  }
  return props.center ?? PH_CENTER;
});

const defaultZoom = computed(() => {
  if (props.locations.length === 1) return 15;
  return props.zoom ?? PH_ZOOM;
});

// IMPORTANT: You were missing this definition!
const locationsSignature = computed(() => {
  return props.locations
    .map((l) => `${l.id}:${l.latitude},${l.longitude}`)
    .sort()
    .join('|');
});

function fitMapToBounds() {
  if (!mapReady.value || !map.value?.leafletObject) return;

  const mapInstance = map.value.leafletObject;

  // 1. Logic for Single Selection
  if (props.locations.length === 1) {
    mapInstance.setView(
      [props.locations[0].latitude, props.locations[0].longitude],
      15,
      { animate: true },
    );
  }
  // 2. Logic for "Show All" (Philippines View)
  else {
    mapInstance.setView(PH_CENTER, PH_ZOOM, { animate: true });
  }

  mapInstance.invalidateSize();
}

function onMapReady() {
  nextTick(() => {
    mapReady.value = true;
    fitMapToBounds();
  });
}

// Watch changes to trigger updates
watch(
  [locationsSignature, () => props.locations.length],
  () => {
    nextTick(() => {
      setTimeout(() => {
        fitMapToBounds();
      }, 50);
    });
  },
  { immediate: true },
);
</script>

<template>
  <div class="isolate z-0 h-full w-full overflow-hidden rounded-lg">
    <l-map
      ref="map"
      :center="defaultCenter"
      :zoom="defaultZoom"
      :use-global-leaflet="false"
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
            <div class="p-2 font-sans text-sm">
              <strong>Location ID:</strong> {{ location.id }}
            </div>
          </slot>
        </l-popup>
      </l-marker>
    </l-map>
  </div>
</template>

<style>
.leaflet-container {
  font-family: inherit;
}
.leaflet-top,
.leaflet-bottom {
  z-index: 400 !important;
}
.leaflet-pane {
  z-index: 1 !important;
}
</style>
