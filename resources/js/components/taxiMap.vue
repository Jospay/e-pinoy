<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  LMap,
  LMarker,
  LTileLayer,
  LTooltip,
  LPolyline,
} from '@vue-leaflet/vue-leaflet';
import L, { type Icon, latLngBounds } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { debounce } from 'lodash';
import { Loader2, MapPin, Search } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';

const props = defineProps<{
  center: { lat: number; lng: number };
  pickup: { lat: number; lng: number; name: string };
  zoom?: number;
}>();

const emit = defineEmits(['location-selected', 'route-found']);
const phBounds = latLngBounds([4.3, 116.0], [21.1, 127.0]);

const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const showResults = ref(false);
const searchContainer = ref<HTMLElement | null>(null);
const map = ref<any>(null);
const selectedLocation = ref<{ lat: number; lng: number } | null>(null);
const routePoints = ref<[number, number][]>([]);

// Decodes OSRM polyline string into coordinates
function decodePolyline(str: string, precision: number = 5) {
  let index = 0,
    lat = 0,
    lng = 0,
    coordinates = [],
    shift = 0,
    result = 0,
    byte = null;
  const factor = Math.pow(10, precision);
  while (index < str.length) {
    byte = null;
    shift = 0;
    result = 0;
    do {
      byte = str.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);
    lat += result & 1 ? ~(result >> 1) : result >> 1;
    byte = null;
    shift = 0;
    result = 0;
    do {
      byte = str.charCodeAt(index++) - 63;
      result |= (byte & 0x1f) << shift;
      shift += 5;
    } while (byte >= 0x20);
    lng += result & 1 ? ~(result >> 1) : result >> 1;
    coordinates.push([lat / factor, lng / factor]);
  }
  return coordinates;
}

async function calculateRoute(destLat: number, destLng: number) {
  try {
    // Note: Changed overview=full to get the actual road path geometry
    const response = await fetch(
      `https://router.project-osrm.org/route/v1/driving/${props.pickup.lng},${props.pickup.lat};${destLng},${destLat}?overview=full`,
    );
    const data = await response.json();

    if (data.code === 'Ok') {
      const route = data.routes[0];
      routePoints.value = decodePolyline(route.geometry) as [number, number][];

      emit('route-found', {
        distanceText: (route.distance / 1000).toFixed(2) + ' km',
        distanceValue: route.distance,
        duration: Math.round(route.duration / 60) + ' mins',
      });
    }
  } catch (error) {
    console.error('Routing failed', error);
  }
}

// ... (Search Logic remains the same as your provided sample) ...
async function performSearch(query: string) {
  if (query.trim().length < 3) return;
  isSearching.value = true;
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&limit=5&q=${encodeURIComponent(query)}`,
    );
    searchResults.value = await response.json();
    showResults.value = true;
  } catch (error) {
    console.error('Search failed', error);
  } finally {
    isSearching.value = false;
  }
}
const debouncedSearch = debounce((val: string) => performSearch(val), 500);
watch(searchQuery, (newVal) => {
  if (newVal.trim().length >= 3) debouncedSearch(newVal);
  else showResults.value = false;
});

function selectResult(result: any) {
  const lat = parseFloat(result.lat);
  const lng = parseFloat(result.lon);
  selectedLocation.value = { lat, lng };
  map.value.leafletObject.setView([lat, lng], 15);
  emit('location-selected', { lat, lng, address: result.display_name });
  calculateRoute(lat, lng);
  searchQuery.value = result.display_name;
  showResults.value = false;
}

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

const markers = computed(() => {
  const list = [
    {
      id: 'pk',
      lat: props.pickup.lat,
      lng: props.pickup.lng,
      type: 'Start',
      name: 'Pickup',
    },
  ];
  if (selectedLocation.value)
    list.push({
      id: 'ds',
      lat: selectedLocation.value.lat,
      lng: selectedLocation.value.lng,
      type: 'End',
      name: 'Drop-off',
    });
  return list;
});

const handleClickOutside = (e: MouseEvent) => {
  if (
    searchContainer.value &&
    !searchContainer.value.contains(e.target as Node)
  )
    showResults.value = false;
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
  <div
    class="relative h-full min-h-[400px] w-full overflow-hidden bg-slate-100"
  >
    <div
      ref="searchContainer"
      class="absolute top-4 right-0 left-0 z-[1001] mx-auto max-w-md px-4"
    >
      <div class="group relative">
        <div
          class="flex items-center rounded-xl bg-white pr-1 shadow-2xl ring-1 ring-black/5"
        >
          <Input
            v-model="searchQuery"
            placeholder="Where to?"
            class="border-none font-bold shadow-none focus-visible:ring-0"
          />
          <Button
            size="icon"
            variant="ghost"
            class="h-10 w-10 text-slate-400"
            :disabled="isSearching"
          >
            <Loader2
              v-if="isSearching"
              class="h-5 w-5 animate-spin text-brand-blue"
            />
            <Search v-else class="h-5 w-5" />
          </Button>
        </div>
        <div
          v-if="showResults"
          class="absolute mt-2 w-full overflow-hidden rounded-xl border bg-white shadow-2xl"
        >
          <ul
            v-if="searchResults.length > 0"
            class="max-h-60 overflow-y-auto py-2"
          >
            <li
              v-for="result in searchResults"
              :key="result.place_id"
              @click="selectResult(result)"
              class="flex cursor-pointer items-start space-x-3 border-b px-4 py-3 hover:bg-slate-50"
            >
              <MapPin class="mt-0.5 h-5 w-5 text-slate-400" />
              <div class="flex flex-col text-left">
                <span class="text-sm font-semibold text-slate-700">{{
                  result.display_name.split(',')[0]
                }}</span>
                <span class="line-clamp-1 text-[10px] text-slate-500">{{
                  result.display_name
                }}</span>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <l-map
      ref="map"
      :center="[center.lat, center.lng]"
      :zoom="zoom ?? 13"
      :min-zoom="6"
      :max-bounds="phBounds"
      :options="{ zoomControl: false }"
      class="z-0"
    >
      <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

      <l-polyline
        v-if="routePoints.length > 0"
        :lat-lngs="routePoints"
        color="#3b82f6"
        :weight="5"
        :opacity="0.7"
      />

      <l-marker
        v-for="m in markers"
        :key="m.id"
        :lat-lng="[m.lat, m.lng]"
        :icon="m.type === 'Start' ? startIcon : endIcon"
      >
        <l-tooltip
          :options="{ permanent: true, direction: 'top', offset: [0, -32] }"
        >
          <span class="font-bold">{{ m.name }}</span>
        </l-tooltip>
      </l-marker>
    </l-map>
  </div>
</template>
