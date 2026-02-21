<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { LMap, LMarker, LTileLayer, LTooltip } from '@vue-leaflet/vue-leaflet';
import L, { type Icon, latLngBounds } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { debounce } from 'lodash';
import { Loader2, MapPin, MapPinOff, Search, X } from 'lucide-vue-next';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

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

// --- Search & Autocomplete Logic ---
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const isSearching = ref(false);
const showResults = ref(false);
const searchContainer = ref<HTMLElement | null>(null);

// Main search function
async function performSearch(query: string) {
  if (query.trim().length < 3) {
    searchResults.value = [];
    showResults.value = false;
    return;
  }

  isSearching.value = true;
  try {
    const response = await fetch(
      `https://nominatim.openstreetmap.org/search?format=json&countrycodes=ph&limit=5&q=${encodeURIComponent(query)}`,
    );
    const data = await response.json();
    searchResults.value = data;
    // Always show the results container if search completes and there is a query
    showResults.value = true;
  } catch (error) {
    console.error('Search failed', error);
    showResults.value = false;
  } finally {
    isSearching.value = false;
  }
}

const debouncedSearch = debounce((val: string) => {
  performSearch(val);
}, 500);

watch(searchQuery, (newVal) => {
  if (newVal.trim().length >= 3) {
    debouncedSearch(newVal);
  } else {
    searchResults.value = [];
    showResults.value = false;
  }
});

function selectResult(result: any) {
  const lat = parseFloat(result.lat);
  const lon = parseFloat(result.lon);

  map.value.leafletObject.setView([lat, lon], 16);

  if (props.selectable) {
    emit('locationSelected', { lat, lng: lon });
  }

  searchQuery.value = result.display_name;
  showResults.value = false;
}

function clearSearch() {
  searchQuery.value = '';
  searchResults.value = [];
  showResults.value = false;
}

const handleClickOutside = (event: MouseEvent) => {
  if (
    searchContainer.value &&
    !searchContainer.value.contains(event.target as Node)
  ) {
    showResults.value = false;
  }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

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
  <div class="relative h-[300px] w-full rounded-lg border-gray-200 bg-slate-50">
    <div
      ref="searchContainer"
      class="absolute top-4 right-0 z-[1001] w-full max-w-sm px-4"
    >
      <div class="group relative">
        <div
          class="flex items-center rounded-md bg-white pr-1 shadow-xl ring-1 ring-black/5"
        >
          <Input
            v-model="searchQuery"
            placeholder="Search places in Philippines..."
            class="border-none shadow-none focus-visible:ring-0"
            @focus="searchQuery.length >= 3 ? (showResults = true) : null"
          />

          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="mr-1 rounded-full p-1 text-slate-400 hover:bg-slate-100"
          >
            <X class="h-4 w-4" />
          </button>

          <Button
            size="icon"
            type="button"
            variant="ghost"
            class="h-8 w-8 text-brand-blue"
            :disabled="isSearching"
          >
            <Loader2 v-if="isSearching" class="h-4 w-4 animate-spin" />
            <Search v-else class="h-4 w-4" />
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
              class="flex cursor-pointer items-start space-x-3 border-b px-4 py-3 transition-colors last:border-0 hover:bg-slate-50"
            >
              <MapPin class="mt-0.5 h-5 w-5 flex-shrink-0 text-slate-400" />
              <div class="flex flex-col">
                <span
                  class="text-sm leading-tight font-semibold text-slate-700"
                >
                  {{ result.display_name.split(',')[0] }}
                </span>
                <span class="line-clamp-1 text-[10px] text-slate-500">
                  {{ result.display_name }}
                </span>
              </div>
            </li>
          </ul>

          <div
            v-else-if="!isSearching"
            class="flex flex-col items-center justify-center p-6 text-center"
          >
            <div class="mb-2 rounded-full bg-blue-50 p-3">
              <MapPinOff class="h-6 w-6 text-blue-300" />
            </div>
            <p class="text-sm font-semibold text-slate-700">No results found</p>
            <p class="px-4 text-[10px] text-slate-500">
              We couldn't find "{{ searchQuery }}". Try checking the spelling or
              use a more general location.
            </p>
          </div>
        </div>
      </div>
    </div>

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

    <div
      v-if="selectable"
      class="absolute bottom-4 left-4 z-[1000] rounded-lg border bg-white/90 px-3 py-2 text-xs font-bold text-brand-blue shadow-lg backdrop-blur-sm"
    >
      📍 Click map to pin location
    </div>
  </div>
</template>
