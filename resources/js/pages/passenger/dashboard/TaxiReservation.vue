<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import LocationMap from '@/components/taxiMap.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  CarFront,
  Wallet,
  CreditCard,
  Loader2,
  CalendarDays,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
  busReservation: any;
  pickupStation: string;
  passengerCount: number;
  walletBalance: number;
}>();

const routeDetails = ref({
  distance: '0 km',
  duration: '0 mins',
  meters: 0,
  seconds: 0,
});

const stationData = computed(
  () => props.busReservation?.to_station || props.busReservation?.toStation,
);
const isMapReady = computed(
  () => !!(stationData.value?.latitude && stationData.value?.longitude),
);

const mapCenter = computed(() => ({
  lat: Number(stationData.value?.latitude || 14.5995),
  lng: Number(stationData.value?.longitude || 120.9842),
}));

const form = useForm({
  reservation_id: props.busReservation.id,
  passenger_count: props.passengerCount,
  amount: 0,
  pickup_loc_name: props.pickupStation,
  destination_loc_name: '',
  start_lat: 0,
  start_lng: 0,
  end_lat: null as number | null,
  end_lng: null as number | null,
  distance_km: 0,
  payment_options: 'Wallet',
});

const handleLocationSelected = (data: any) => {
  form.end_lat = data.lat;
  form.end_lng = data.lng;
  form.destination_loc_name = data.address;
  form.start_lat = mapCenter.value.lat;
  form.start_lng = mapCenter.value.lng;
};

const handleRouteFound = (data: any) => {
  const meters = Number(data.distanceValue) || 0;
  const seconds = Number(data.durationValue) || 0;

  routeDetails.value = {
    distance: data.distanceText || '0 km',
    duration: data.durationText || '0 mins',
    meters: meters,
    seconds: seconds,
  };

  const farePerMinute = 2;
  const farePerKm = 13.5;
  const flagDown = 50;

  const distKm = meters / 1000;
  const minutes = seconds / 60;

  let distFare = 0;
  if (distKm > 1) {
    distFare = (distKm - 1) * farePerKm;
  }

  const timeFare = minutes * farePerMinute;
  const total = flagDown + distFare + timeFare;

  form.amount = isNaN(total) ? flagDown : total;
  form.distance_km = isNaN(distKm) ? 0 : distKm;
};

const canSubmit = computed(() => {
  const hasLocation = !!form.end_lat;
  const isNotProcessing = !form.processing;
  const validFare = form.amount >= 50;

  if (form.payment_options === 'Wallet') {
    return (
      hasLocation &&
      isNotProcessing &&
      validFare &&
      props.walletBalance >= form.amount
    );
  }
  return hasLocation && isNotProcessing && validFare;
});

const submit = () => {
  form.post('/passenger/reservation/taxi/reservation', {
    onError: (errors) => {
      console.log('Validation errors from server:', errors);
    },
  });
};
</script>

<template>
  <Head title="Book a Taxi" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50/50 px-4 py-10">
      <div class="mx-auto max-w-5xl">
        <div class="overflow-hidden rounded-2xl border bg-white shadow-xl">
          <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="relative h-[400px] lg:col-span-7 lg:h-[600px]">
              <LocationMap
                v-if="isMapReady"
                :center="mapCenter"
                :pickup="{
                  lat: mapCenter.lat,
                  lng: mapCenter.lng,
                  name: pickupStation,
                }"
                @location-selected="handleLocationSelected"
                @route-found="handleRouteFound"
              />
              <div
                v-else
                class="flex h-full items-center justify-center bg-slate-100"
              >
                <Loader2 class="animate-spin" />
              </div>
            </div>

            <div class="flex flex-col justify-between p-6 lg:col-span-5">
              <div>
                <h2 class="mb-1 text-2xl font-black">Taxi Booking</h2>
                <p class="mb-4 text-sm text-slate-500">
                  Select drop-off point on the map
                </p>

                <div class="mb-5 flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-blue text-white"
                  >
                    <CalendarDays class="h-5 w-5" />
                  </div>
                  <div>
                    <p
                      class="text-[10px] font-bold tracking-wider text-blue-500/80 uppercase"
                    >
                      Scheduled Travel Date
                    </p>
                    <p class="text-sm font-black text-blue-900">
                      {{ busReservation.reserve_date }}
                    </p>
                  </div>
                </div>

                <div class="mb-6 space-y-4 rounded-xl border bg-slate-50 p-4">
                  <div class="flex items-start gap-3">
                    <div class="mt-1.5 h-2 w-2 rounded-full bg-blue-500"></div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Pickup
                      </p>
                      <p class="text-sm font-bold">{{ pickupStation }}</p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div class="mt-1.5 h-2 w-2 rounded-full bg-red-500"></div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Drop-off
                      </p>
                      <p
                        class="text-sm font-bold"
                        v-if="form.destination_loc_name"
                      >
                        {{ form.destination_loc_name }}
                      </p>
                      <p
                        class="animate-pulse text-sm font-bold text-red-500"
                        v-else
                      >
                        Please select on map
                      </p>
                    </div>
                  </div>
                </div>

                <div
                  class="mb-6 flex justify-between rounded-xl border bg-white p-4 shadow-sm"
                >
                  <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      Taxi Fare
                    </p>
                    <p class="text-2xl font-black text-brand-blue">
                      ₱{{ form.amount.toFixed(2) }}
                    </p>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      Distance
                    </p>
                    <p class="font-bold text-slate-700">
                      {{ routeDetails.distance }}
                    </p>
                  </div>
                </div>

                <div class="mb-6 space-y-3">
                  <Label class="text-[10px] font-bold text-slate-400 uppercase"
                    >Payment Method</Label
                  >
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      type="button"
                      @click="form.payment_options = 'Wallet'"
                      :class="
                        form.payment_options === 'Wallet'
                          ? 'border-blue-600 bg-blue-50'
                          : 'border-slate-200'
                      "
                      class="flex flex-col items-center rounded-xl border p-3 transition-all"
                    >
                      <Wallet class="mb-1 h-5 w-5" />
                      <span class="text-xs font-bold">Wallet</span>
                    </button>
                    <button
                      type="button"
                      @click="form.payment_options = 'Online Payment'"
                      :class="
                        form.payment_options === 'Online Payment'
                          ? 'border-blue-600 bg-blue-50'
                          : 'border-slate-200'
                      "
                      class="flex flex-col items-center rounded-xl border p-3 transition-all"
                    >
                      <CreditCard class="mb-1 h-5 w-5" />
                      <span class="text-xs font-bold">Online</span>
                    </button>
                  </div>

                  <div
                    v-if="form.payment_options === 'Wallet'"
                    class="text-center"
                  >
                    <p
                      class="text-xs"
                      :class="
                        props.walletBalance < form.amount
                          ? 'font-bold text-red-500'
                          : 'text-slate-500'
                      "
                    >
                      Wallet Balance: ₱{{ props.walletBalance.toFixed(2) }}
                    </p>
                  </div>
                </div>
              </div>

              <Button
                @click="submit"
                :disabled="!canSubmit"
                class="h-14 w-full rounded-xl bg-blue-600 font-bold text-white hover:bg-blue-700 disabled:opacity-50"
              >
                <span v-if="form.processing" class="flex items-center gap-2">
                  <Loader2 class="h-4 w-4 animate-spin" /> Processing...
                </span>
                <span v-else class="flex items-center gap-2">
                  Confirm Booking <CarFront class="h-5 w-5" />
                </span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
