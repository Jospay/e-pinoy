<script setup lang="ts">
import LocationMap from '@/components/ReservedMap.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Navigation } from 'lucide-vue-next';

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

const breadcrumbs = [{ title: 'Select Station', href: '#' }];

const goToReservation = (stationId: number) => {
  // Use the specific Reserve route
  router.get(`/passenger/dashboard/Reserve?from_id=${stationId}`);
};
</script>

<template>
  <Head title="Available Terminals" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Available Terminals</h1>
        <p class="text-sm text-muted-foreground">
          Select a starting point for your trip.
        </p>
      </div>

      <div
        v-if="stations.length >= 2"
        class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
      >
        <div
          v-for="station in stations"
          :key="station.id"
          class="group flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
          <div class="relative aspect-video w-full border-b bg-slate-100">
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
              :zoom="14"
              :center="[station.lat, station.lng]"
              :selectable="false"
            />
          </div>

          <div class="flex flex-1 flex-col p-5">
            <span
              class="mb-2 w-fit rounded bg-brand-blue/10 px-2 py-0.5 text-[10px] font-bold text-brand-blue uppercase"
            >
              {{ station.code }}
            </span>
            <h3 class="text-lg font-bold text-slate-900">{{ station.name }}</h3>

            <div class="mt-2 mb-6 flex items-start gap-2 text-slate-500">
              <Navigation class="mt-0.5 h-3.5 w-3.5 shrink-0" />
              <p class="line-clamp-2 text-xs leading-tight">
                {{ station.address }}
              </p>
            </div>

            <div class="mt-auto border-t pt-4">
              <Button
                @click="goToReservation(station.id)"
                class="group flex w-full justify-between bg-slate-900 text-white hover:bg-slate-800"
              >
                <span>Book from this terminal</span>
                <ArrowRight
                  class="h-4 w-4 transition-transform group-hover:translate-x-1"
                />
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else
        class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 py-24 text-center"
      >
        <div class="mb-4 rounded-full bg-white p-4 shadow-sm">
          <MapPinOff class="h-10 w-10 text-slate-300" />
        </div>
        <h2 class="text-xl font-bold text-slate-900">
          Routes Currently Unavailable
        </h2>
        <p class="mt-2 max-w-sm text-sm text-slate-500">
          A minimum of two active terminals are required to create a booking
          route. Please check back later.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
