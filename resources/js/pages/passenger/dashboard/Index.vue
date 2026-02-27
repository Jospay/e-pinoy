<script setup lang="ts">
import LocationMap from '@/components/ReservedMap.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import {
  ArrowRight,
  MapPin,
  MapPinOff,
  Navigation,
  Search,
  XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
  stations: Array<{
    id: number;
    name: string;
    code: string;
    lat: number;
    lng: number;
    address: string;
  }>;
}>();

const breadcrumbs = [{ title: 'Select Terminal', href: '#' }];

// Search State
const searchQuery = ref('');

// Filtered Stations Logic
const filteredStations = computed(() => {
  return props.stations.filter((station) => {
    const term = searchQuery.value.toLowerCase();
    return (
      station.name.toLowerCase().includes(term) ||
      station.code.toLowerCase().includes(term) ||
      station.address.toLowerCase().includes(term)
    );
  });
});

const goToReservation = (stationId: number) => {
  router.get(`/passenger/dashboard/Reserve?from_id=${stationId}`);
};
</script>

<template>
  <Head title="Available Terminals" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="mx-auto max-w-5xl p-3 sm:p-6">
      <div
        class="mb-10 flex flex-col gap-6 md:flex-row md:items-end md:justify-between"
      >
        <div class="flex-1">
          <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
            Available Terminals
          </h1>
          <p class="mt-1 text-slate-500">
            Select your starting point to view available destinations and fares.
          </p>
        </div>

        <div class="relative w-full md:w-80">
          <div
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
          >
            <Search class="h-4 w-4 text-slate-400" />
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search terminal or address..."
            class="block w-full rounded-xl border border-slate-200 bg-white py-2.5 pr-4 pl-10 text-sm transition-all focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 focus:outline-none"
          />
        </div>
      </div>

      <div v-if="filteredStations.length > 0" class="flex flex-col gap-6">
        <div
          v-for="station in filteredStations"
          :key="station.id"
          class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:border-slate-300 hover:shadow-md md:flex-row"
        >
          <div
            class="relative z-0 h-48 w-full shrink-0 border-b border-slate-100 bg-slate-50 md:h-auto md:w-72 md:border-r md:border-b-0 lg:w-80"
          >
            <LocationMap
              :locations="[
                {
                  id: station.id,
                  latitude: station.lat,
                  longitude: station.lng,
                  type: 'Pin',
                  name: station.name,
                },
              ]"
              :zoom="15"
              :center="[station.lat, station.lng]"
              :selectable="false"
            />
            <div class="absolute bottom-3 left-3 z-[1000]">
              <span
                class="rounded-lg border border-slate-200 bg-white/90 px-2 py-1 text-[10px] font-bold text-slate-900 uppercase shadow-sm backdrop-blur-sm"
              >
                GPS: {{ station.lat.toFixed(3) }}, {{ station.lng.toFixed(3) }}
              </span>
            </div>
          </div>

          <div class="flex flex-1 flex-col p-6">
            <div class="flex items-start justify-between">
              <div>
                <div
                  class="mb-2 inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold tracking-wide text-blue-700 uppercase"
                >
                  {{ station.code }}
                </div>
                <h3
                  class="text-xl font-bold text-slate-900 transition-colors group-hover:text-blue-600"
                >
                  {{ station.name }}
                </h3>
              </div>
              <div class="hidden sm:block">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-2">
                  <MapPin class="h-5 w-5 text-slate-400" />
                </div>
              </div>
            </div>

            <div class="mt-3 flex items-start gap-2 text-slate-600">
              <Navigation class="mt-1 h-4 w-4 shrink-0 text-slate-400" />
              <p class="max-w-md text-sm leading-relaxed">
                {{ station.address }}
              </p>
            </div>

            <div class="mt-auto flex items-center justify-between pt-6">
              <div class="flex items-center gap-1 text-xs text-slate-400">
                <span class="h-2 w-2 rounded-full bg-green-500"></span>
                Active
              </div>
              <Button
                @click="goToReservation(station.id)"
                class="group/btn flex items-center gap-2 rounded-xl bg-slate-900 px-6 text-white transition-all hover:bg-blue-700"
              >
                <span>Book From Terminal</span>
                <ArrowRight
                  class="h-4 w-4 transition-transform group-hover/btn:translate-x-1"
                />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else
        class="flex flex-col items-center justify-center rounded-[2rem] border-2 border-dashed border-slate-200 bg-slate-50/50 py-24 text-center"
      >
        <div
          class="mb-4 rounded-full border border-slate-100 bg-white p-5 shadow-sm"
        >
          <component
            :is="searchQuery ? XCircle : MapPinOff"
            class="h-10 w-10 text-slate-300"
          />
        </div>
        <h2 class="text-xl font-bold text-slate-900">
          {{
            searchQuery
              ? 'No terminals match your search'
              : 'No Terminals Available'
          }}
        </h2>
        <p class="mt-2 max-w-xs text-sm text-slate-500">
          {{
            searchQuery
              ? 'Try searching for a different terminal name, station code, or address.'
              : "We couldn't find enough active terminals. Please check back later."
          }}
        </p>
        <Button
          v-if="searchQuery"
          variant="outline"
          @click="searchQuery = ''"
          class="mt-6 rounded-xl"
        >
          Clear Search
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
