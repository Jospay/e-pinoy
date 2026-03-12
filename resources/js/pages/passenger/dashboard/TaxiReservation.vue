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
  Users,
  Info,
  AlertTriangle,
  AlertCircle,
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

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
  });
};

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

const isSubmitting = ref(false);

const canSubmit = computed(() => {
  if (isSubmitting.value || form.processing) return false;

  const hasRoute = !!form.start_lat && !!form.end_lat;
  const timeValid = props.bookingType === 'before' ? !!form.time_pickup : true;
  const validFare = form.amount >= 50;
  const passengerCountValid =
    form.passenger_count > 0 && form.passenger_count <= 4;

  const balanceValid =
    form.payment_options === 'Wallet'
      ? props.walletBalance >= form.amount
      : true;

  return (
    hasRoute && timeValid && validFare && balanceValid && passengerCountValid
  );
});

const submit = () => {
  if (isSubmitting.value || form.processing) return;

  isSubmitting.value = true;

  form.post('/passenger/reservation/taxi/reservation', {
    onSuccess: () => {
      console.log('Success! Staying locked for redirect...');
    },
    onError: () => {
      isSubmitting.value = false;
    },
    onFinish: () => {
      if (Object.keys(form.errors).length > 0) {
        isSubmitting.value = false;
      }
    },
  });
};
</script>

<template>
  <Head title="Book a Taxi" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50/50 px-4 py-10">
      <div class="mx-auto max-w-5xl">
        <div
          v-if="form.errors.amount"
          class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"
            >
              <AlertTriangle class="h-5 w-5" />
            </div>
            <div class="text-start">
              <h3 class="text-sm font-bold text-red-900">Security Alert</h3>
              <p class="text-[11px] leading-tight font-medium text-red-600">
                {{ form.errors.amount }}
              </p>
            </div>
          </div>

          <div class="mt-3 border-t border-red-100 pt-3">
            <p class="text-[10px] font-bold text-red-800 uppercase">
              Status: Incident Reported
            </p>
            <p class="mt-1 text-[11px] leading-normal text-red-700">
              A report has been sent to our team for verification. If you are in
              a hurry, please
              <strong>change your payment method</strong> to
              <strong>Online Payment</strong> to continue.
            </p>
          </div>
        </div>

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
                      {{ formatDate(busReservation.reserve_date) }}
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

                <div class="mb-6 space-y-2">
                  <Label
                    class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                    :class="{ 'text-red-500': form.passenger_count > 4 }"
                  >
                    Passengers
                  </Label>

                  <div class="relative">
                    <Users
                      class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2"
                      :class="
                        form.passenger_count > 4
                          ? 'text-red-500'
                          : 'text-slate-400'
                      "
                    />
                    <input
                      v-model="form.passenger_count"
                      type="number"
                      min="1"
                      max="4"
                      class="h-12 w-full rounded-xl border bg-white pr-4 pl-12 text-sm font-bold shadow-sm transition-colors outline-none"
                      :class="[
                        form.passenger_count > 4 || form.errors.passenger_count
                          ? 'border-red-500 text-red-600 ring-1 ring-red-500'
                          : 'border-slate-200 focus:border-black focus:ring-1 focus:ring-black',
                      ]"
                    />
                  </div>

                  <div
                    v-if="form.passenger_count > 4"
                    class="mt-1 flex items-start gap-2 text-red-500"
                  >
                    <AlertCircle class="mt-0.5 h-3 w-3" />
                    <p class="text-[11px] font-bold">
                      Error: You have {{ form.passenger_count }} passengers.
                      Taxis only support up to 4.
                    </p>
                  </div>

                  <div class="mt-1 flex items-start gap-2" v-else>
                    <Info class="mt-0.5 h-3 w-3 text-slate-400" />
                    <p class="text-[11px] leading-relaxed text-slate-500">
                      <strong>Note:</strong> Maximum taxi capacity is 4
                      passengers. For groups larger than 4, please book an
                      additional taxi.
                    </p>
                  </div>

                  <p
                    v-if="form.errors.passenger_count"
                    class="text-xs font-medium text-red-500"
                  >
                    {{ form.errors.passenger_count }}
                  </p>
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
                class="h-14 w-full rounded-xl bg-brand-blue font-bold text-white hover:bg-blue-800 disabled:opacity-50"
              >
                <Loader2
                  v-if="isSubmitting || form.processing"
                  class="mr-2 h-4 w-4 animate-spin"
                />
                <span class="flex items-center gap-2">
                  <template v-if="isSubmitting || form.processing">
                    {{
                      form.payment_options === 'Wallet'
                        ? 'Submitting...'
                        : 'Processing Payment...'
                    }}
                  </template>
                  <template v-else>
                    Confirm Booking <CarFront class="h-5 w-5" />
                  </template>
                </span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
