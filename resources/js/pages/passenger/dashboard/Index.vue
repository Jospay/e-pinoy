<script setup lang="ts">
import LocationMap from '@/components/ReservedMap.vue'; // Using your existing map component
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
  Bus,
  MapPin,
  ArrowRight,
  Info,
  Navigation,
  Calendar,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';

interface RouteData {
  id: number;
  vehicle_info: any;
  origin: any;
  destination_name: string;
  start_time: string;
  end_time: string;
  days: string[];
  stops: any[];
}

const props = defineProps<{
  availableRoutes: RouteData[];
}>();

console.log('Routes loaded:', props.availableRoutes.length);

const selectedRoute = ref<any>(null);
const isRouteModalOpen = ref(false);

const viewRouteDetails = (route: any) => {
  selectedRoute.value = route;
  isRouteModalOpen.value = true;
};

const bookFromRoute = (route: any) => {
  router.get(`/passenger/dashboard/Reserve`, {
    station_reservation_id: route.id,
    from_id: route.origin.id,
  });
};
</script>

<template>
  <Head title="Available Bus Trips" />
  <AppLayout>
    <div class="mx-auto max-w-6xl px-3 py-8 sm:px-8">
      <header class="mb-10">
        <h1 class="text-4xl font-black tracking-tight text-slate-900">
          Where to next?
        </h1>
        <p class="text-lg text-slate-500">
          Explore active bus routes and real-time schedules.
        </p>
      </header>

      <div class="grid gap-8">
        <div
          v-for="route in availableRoutes"
          :key="route.id"
          class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:border-blue-200 hover:shadow-xl lg:flex-row"
        >
          <div
            class="relative z-0 h-48 shrink-0 border-b border-slate-100 bg-slate-100 lg:h-auto lg:w-80 lg:border-r lg:border-b-0"
          >
            <LocationMap
              :locations="[
                {
                  id: route.id,
                  latitude: route.origin.lat,
                  longitude: route.origin.lng,
                  type: 'Pin',
                  name: route.origin.name,
                },
              ]"
              :zoom="18"
              :center="[route.origin.lat, route.origin.lng]"
              :selectable="false"
            />
          </div>

          <div class="flex flex-1 flex-col p-3 sm:p-8">
            <div
              class="mb-6 flex flex-col justify-between gap-6 bg-white md:flex-row md:items-center"
            >
              <!-- LEFT CONTENT -->
              <div class="flex-1 space-y-3">
                <!-- Vehicle Info -->
                <div class="flex items-center gap-2 text-brand-blue">
                  <Bus class="h-5 w-5" />
                  <span class="text-sm font-semibold tracking-tight">
                    {{ route.vehicle_info.name }} •
                    {{ route.vehicle_info.plate }}
                  </span>
                </div>

                <!-- Route -->
                <div class="flex items-center gap-3">
                  <p class="text-xl font-bold text-slate-800">
                    {{ route.origin.name }}
                  </p>

                  <div
                    class="flex items-center justify-center rounded-full bg-slate-100 p-2"
                  >
                    <ArrowRight class="h-4 w-4 text-slate-500" />
                  </div>

                  <p class="text-xl font-bold text-slate-800">
                    {{ route.destination_name }}
                  </p>
                </div>

                <!-- Address -->
                <div class="flex items-start gap-2 text-sm text-slate-500">
                  <Navigation class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
                  <p class="italic">
                    {{ route.origin.address }}
                  </p>
                </div>
              </div>

              <!-- RIGHT TIME CARD -->

              <div
                class="grid min-w-[130px] items-center justify-evenly gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-3 shadow-md"
              >
                <div class="flex flex-col items-center">
                  <span
                    class="text-[12px] font-black tracking-tighter text-slate-400 uppercase"
                    >Arrive</span
                  >
                  <span class="font-mono text-sm font-bold text-slate-700">
                    {{ route.start_time }}
                  </span>
                </div>

                <div class="h-px w-full bg-slate-200"></div>

                <div class="flex flex-col items-center">
                  <span
                    class="text-[12px] font-black tracking-tighter text-brand-blue uppercase"
                    >Depart</span
                  >
                  <span class="font-mono text-sm font-bold text-brand-blue">
                    {{ route.end_time }}
                  </span>
                </div>
              </div>
            </div>

            <div
              class="mt-auto flex flex-wrap items-center justify-between gap-4 border-t border-slate-50 pt-6"
            >
              <div class="flex flex-wrap gap-1.5">
                <div
                  v-for="day in route.days"
                  :key="day"
                  class="flex items-center gap-1 rounded-lg bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700"
                >
                  <Calendar class="h-3 w-3" />
                  {{ day }}
                </div>
              </div>

              <div class="grid w-full gap-3 sm:flex sm:w-auto">
                <Button
                  variant="outline"
                  @click="viewRouteDetails(route)"
                  class="flex-1 rounded-xl border-slate-200 font-bold hover:bg-slate-50 sm:flex-none"
                >
                  <Info class="h-4 w-4" />
                  View Full Route
                </Button>
                <Button
                  @click="bookFromRoute(route)"
                  class="flex-1 rounded-xl bg-slate-900 px-8 font-bold shadow-lg shadow-slate-200 hover:bg-brand-blue sm:flex-none"
                >
                  Book Seat
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Dialog :open="isRouteModalOpen" @update:open="isRouteModalOpen = $event">
        <DialogContent class="max-w-lg overflow-hidden p-0">
          <DialogHeader class="border p-3 pb-4">
            <DialogTitle class="text-2xl font-bold text-slate-900">
              Bus Route
            </DialogTitle>
            <DialogDescription class="text-slate-500">
              This shows the complete route of the bus, including all stop
              locations.
            </DialogDescription>
          </DialogHeader>

          <div class="max-h-[60vh] overflow-y-auto bg-white p-4">
            <div class="relative space-y-0">
              <div
                v-for="(stop, index) in selectedRoute?.stops"
                :key="index"
                class="relative pb-10 pl-10 last:pb-0"
              >
                <div
                  v-if="index !== selectedRoute.stops.length - 1"
                  class="absolute top-8 left-[15px] h-full w-[3px] bg-slate-100"
                ></div>

                <div
                  class="absolute top-1.5 left-0 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white bg-brand-blue shadow-md"
                >
                  <MapPin
                    v-if="
                      index === 0 || index === selectedRoute.stops.length - 1
                    "
                    class="h-3 w-3 text-white"
                  />
                  <div v-else class="h-2 w-2 rounded-full bg-white"></div>
                </div>

                <div class="flex flex-col">
                  <p
                    class="mb-1 text-lg leading-none font-black text-slate-900"
                  >
                    {{ stop.station_name }}
                  </p>
                  <p
                    class="mb-3 flex items-center gap-1 text-xs text-slate-400 italic"
                  >
                    <Navigation class="h-3 w-3" /> {{ stop.address }}
                  </p>

                  <div v-if="stop.order === 1" class="flex gap-3">
                    <div
                      class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-1.5"
                    >
                      <p
                        class="text-[9px] font-bold tracking-tighter text-slate-400 uppercase"
                      >
                        Arrival
                      </p>
                      <p class="text-sm font-bold text-slate-700">
                        {{ stop.arrival }}
                      </p>
                    </div>
                    <div
                      class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5"
                    >
                      <p
                        class="text-[9px] font-bold tracking-tighter text-blue-400 uppercase"
                      >
                        Departure
                      </p>
                      <p class="text-sm font-bold text-blue-700">
                        {{ stop.departure }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <DialogFooter class="border-t bg-slate-50/80 p-6">
            <Button
              class="bg-slate-200 font-bold text-slate-500 hover:bg-slate-100"
              @click="isRouteModalOpen = false"
            >
              Cancel
            </Button>
            <Button
              class="min-w-[160px] rounded-xl bg-brand-blue px-8 font-bold shadow-lg shadow-brand-blue/20 hover:bg-brand-blue/90"
              @click="bookFromRoute(selectedRoute)"
            >
              Proceed to Booking
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
