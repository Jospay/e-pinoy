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
  Clock,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
  busReservation: any;
  bookingType: 'before' | 'after';
  defaultPickup: string;
  defaultDest: string;
  passengerCount: number;
  walletBalance: number;
}>();

const routeDetails = ref({
  distance: '0 km',
  duration: '0 mins',
  meters: 0,
  seconds: 0,
});

const stationData = computed(() =>
  props.bookingType === 'before'
    ? props.busReservation?.from_station
    : props.busReservation?.to_station,
);

const isMapReady = computed(
  () => !!(stationData.value?.latitude && stationData.value?.longitude),
);

const stationCoords = computed(() => ({
  lat: Number(stationData.value?.latitude || 14.5995),
  lng: Number(stationData.value?.longitude || 120.9842),
}));

const form = useForm({
  reservation_id: props.busReservation.id,
  booking_type: props.bookingType,
  time_pickup: '',
  passenger_count: props.passengerCount,
  amount: 0,
  pickup_loc_name: props.defaultPickup,
  destination_loc_name: props.defaultDest,
  start_lat: props.bookingType === 'after' ? stationCoords.value.lat : 0,
  start_lng: props.bookingType === 'after' ? stationCoords.value.lng : 0,
  end_lat:
    props.bookingType === 'before'
      ? stationCoords.value.lat
      : (null as number | null),
  end_lng:
    props.bookingType === 'before'
      ? stationCoords.value.lng
      : (null as number | null),
  distance_km: 0,
  payment_options: 'Wallet',
});

const handleLocationSelected = (data: any) => {
  if (props.bookingType === 'before') {
    form.start_lat = data.lat;
    form.start_lng = data.lng;
    form.pickup_loc_name = data.address;
  } else {
    form.end_lat = data.lat;
    form.end_lng = data.lng;
    form.destination_loc_name = data.address;
  }
};

const handleRouteFound = (data: any) => {
  const meters = Number(data.distanceValue) || 0;
  const seconds = Number(data.durationValue) || 0;
  const minutes = Math.round(seconds / 60);

  routeDetails.value = {
    distance: data.distanceText || '0 km',
    duration: minutes + ' mins', // This matches the template variable
    meters: meters,
    seconds: seconds,
  };

  // Fare Calculation
  const farePerMinute = 2;
  const farePerKm = 13.5;
  const flagDown = 50;
  const distKm = meters / 1000;

  let distFare = distKm > 1 ? (distKm - 1) * farePerKm : 0;
  const timeFare = (seconds / 60) * farePerMinute;
  const total = flagDown + distFare + timeFare;

  form.amount = isNaN(total) ? flagDown : total < 50 ? 50 : total;
  form.distance_km = isNaN(distKm) ? 0 : distKm;
};

const canSubmit = computed(() => {
  const hasRoute = !!form.start_lat && !!form.end_lat;
  const timeValid = props.bookingType === 'before' ? !!form.time_pickup : true;
  const isNotProcessing = !form.processing;
  const validFare = form.amount >= 50;
  const balanceValid =
    form.payment_options === 'Wallet'
      ? props.walletBalance >= form.amount
      : true;

  return hasRoute && timeValid && isNotProcessing && validFare && balanceValid;
});

const submit = () => {
  form.post('/passenger/reservation/taxi/reservation');
};
</script>

<template>
  <Head title="Book a Taxi" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50/50 px-4 py-10">
      <div class="mx-auto max-w-5xl">
        <div class="overflow-hidden rounded-2xl border bg-white shadow-xl">
          <div class="grid grid-cols-1 lg:grid-cols-12">
            <div class="relative h-[400px] lg:col-span-7 lg:h-auto">
              <LocationMap
                v-if="isMapReady"
                :center="stationCoords"
                :station-name="stationData?.name || 'Bus Station'"
                :mode="bookingType"
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
                <h2
                  class="mb-1 text-2xl font-black tracking-tighter uppercase italic"
                >
                  Taxi Booking
                </h2>
                <p class="mb-4 text-xs font-bold text-slate-500 uppercase">
                  {{
                    bookingType === 'before'
                      ? 'Select pickup point from your location'
                      : 'Select drop-off point on the map'
                  }}
                </p>

                <div class="mb-5 flex items-center gap-3">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-blue text-white"
                  >
                    <CalendarDays class="h-5 w-5" />
                  </div>
                  <div>
                    <p
                      class="text-[10px] font-bold tracking-wider text-brand-blue/80 uppercase"
                    >
                      Travel Date
                    </p>
                    <p class="text-sm font-black text-brand-blue">
                      {{ busReservation.reserve_date }}
                    </p>
                  </div>
                </div>

                <div class="mb-6 space-y-4 rounded-xl border bg-slate-50 p-4">
                  <div class="flex items-start gap-3">
                    <div>
                      <div
                        class="mt-1.5 h-2 w-2 rounded-full bg-brand-blue"
                      ></div>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Pickup
                      </p>
                      <p
                        class="text-sm font-bold"
                        :class="
                          !form.pickup_loc_name
                            ? 'animate-pulse text-brand-blue'
                            : ''
                        "
                      >
                        {{ form.pickup_loc_name || 'Select on map' }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div>
                      <div class="mt-1.5 h-2 w-2 rounded-full bg-red-500"></div>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Drop-off
                      </p>
                      <p
                        class="text-sm font-bold"
                        :class="
                          !form.destination_loc_name
                            ? 'animate-pulse text-red-500'
                            : ''
                        "
                      >
                        {{ form.destination_loc_name || 'Select on map' }}
                      </p>
                    </div>
                  </div>
                </div>

                <div
                  class="mb-6 flex justify-between rounded-xl border bg-white p-4 shadow-sm"
                >
                  <div class="flex gap-4">
                    <div>
                      <p class="text-[8px] font-bold text-slate-400 uppercase">
                        Distance
                      </p>
                      <p class="text-[14px] font-bold text-slate-700">
                        {{ routeDetails.distance }}
                      </p>
                    </div>
                    <div>
                      <p class="text-[8px] font-bold text-slate-400 uppercase">
                        Duration
                      </p>
                      <p class="text-[14px] font-bold text-slate-700">
                        {{ routeDetails.duration }}
                      </p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      Taxi Fare
                    </p>
                    <p class="text-2xl font-black text-brand-blue">
                      ₱{{ form.amount.toFixed(2) }}
                    </p>
                  </div>
                </div>

                <div v-if="bookingType === 'before'" class="mb-6 space-y-2">
                  <Label
                    class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    >Preferred Pickup Time</Label
                  >

                  <div class="relative">
                    <Clock
                      class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2"
                    />
                    <input
                      type="time"
                      v-model="form.time_pickup"
                      class="h-12 w-full rounded-xl border px-4 pl-12 text-sm font-bold shadow-sm transition-all focus:ring-4"
                      required
                    />
                  </div>
                </div>

                <div class="mb-6 space-y-3">
                  <Label
                    class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
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
                      Balance: ₱{{ props.walletBalance.toFixed(2) }}
                    </p>
                  </div>
                </div>
              </div>

              <Button
                @click="submit"
                :disabled="!canSubmit"
                class="h-14 w-full rounded-xl bg-brand-blue font-bold text-white hover:bg-blue-700 disabled:opacity-50"
              >
                <Loader2
                  v-if="form.processing"
                  class="mr-2 h-4 w-4 animate-spin"
                />
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
