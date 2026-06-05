<script setup lang="ts">
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  latitude?: number | null;
  longitude?: number | null;
}>();

const emit = defineEmits<{
  locationSelected: [
    {
      lat: number;
      lng: number;
    },
  ];
}>();

const selectedLocation = ref<{
  lat: number;
  lng: number;
} | null>(null);

watch(
  () => [props.latitude, props.longitude],
  ([lat, lng]) => {
    if (lat != null && lng != null) {
      selectedLocation.value = {
        lat,
        lng,
      };
    } else {
      selectedLocation.value = null;
    }
  },
  { immediate: true },
);

const center = computed<[number, number]>(() => {
  if (selectedLocation.value) {
    return [selectedLocation.value.lat, selectedLocation.value.lng];
  }

  return [15.1465, 120.5794];
});

const handleMapClick = (e: any) => {
  selectedLocation.value = {
    lat: e.latlng.lat,
    lng: e.latlng.lng,
  };

  emit('locationSelected', selectedLocation.value);
};
</script>

<template>
  <div class="h-[400px] overflow-hidden rounded-lg border">
    <LMap :zoom="15" :center="center" @click="handleMapClick">
      <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

      <LMarker
        v-if="selectedLocation"
        :lat-lng="[selectedLocation.lat, selectedLocation.lng]"
      />
    </LMap>
  </div>
</template>

<style scoped>
.leaflet-container {
  z-index: 0;
}
.leaflet-pane {
  z-index: 1;
}
</style>
