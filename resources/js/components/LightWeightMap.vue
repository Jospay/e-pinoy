<script setup lang="ts">
import { LMap, LMarker, LTileLayer } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';

defineProps<{
  latitude: number;
  longitude: number;
}>();
</script>

<template>
  <div class="h-[350px] overflow-hidden rounded-lg border">
    <LMap :zoom="16" :center="[latitude, longitude]">
      <LTileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

      <LMarker :lat-lng="[latitude, longitude]" />
    </LMap>
  </div>
</template>
