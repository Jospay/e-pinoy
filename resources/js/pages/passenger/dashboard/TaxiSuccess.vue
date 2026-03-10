<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, Link, router } from '@inertiajs/vue3';
import { toPng } from 'html-to-image';
import {
  Download,
  MapPin,
  Navigation,
  Home,
  Clock,
  Info,
  AlertCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps<{
  reservation: any;
}>();

const formatTime = (time: string) => {
  if (!time) return '';
  const [hours, minutes] = time.split(':');
  const date = new Date();
  date.setHours(parseInt(hours), parseInt(minutes));
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
};

const receiptRef = ref<HTMLElement | null>(null);

// Check if this is a booking TO the station or FROM the station
const isBeforeStation = computed(
  () => props.reservation.booking_type === 'before',
);

const downloadTicket = async () => {
  if (receiptRef.value) {
    try {
      const dataUrl = await toPng(receiptRef.value, {
        cacheBust: true,
        backgroundColor: '#ffffff',
        pixelRatio: 2,
      });
      const link = document.createElement('a');
      link.download = `Taxi-Receipt-${props.reservation.qrcode_name}.png`;
      link.href = dataUrl;
      link.click();
    } catch (err) {
      console.error('Download failed', err);
    }
  }
};

const beforeTaxiBooking = () => {
  router.get(
    `/passenger/reservation/taxi/Reserve/${props.reservation.id}?type=before`,
  );
};

const afterTaxiBooking = () => {
  router.get(
    `/passenger/reservation/taxi/Reserve/${props.reservation.id}?type=after`,
  );
};
</script>

<template>
  <Head title="Taxi Booking Confirmed" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50/50 px-4 py-12">
      <div class="mx-auto max-w-sm">
        <div
          v-if="reservation"
          ref="receiptRef"
          class="border-2 border-black bg-white p-6 text-black shadow-xl"
        >
          <div class="border-b-2 border-black pb-4 text-center">
            <h1 class="text-2xl font-black tracking-tighter uppercase">
              Taxi Receipt
            </h1>
            <p
              class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
            >
              Reservation Confirmed
            </p>
          </div>

          <div
            :class="[
              'my-4 border p-4 text-center',
              isBeforeStation
                ? 'border-amber-200 bg-amber-50'
                : 'border-yellow-200 bg-yellow-50',
            ]"
          >
            <div class="mb-1 flex items-center justify-center gap-2">
              <Clock v-if="!isBeforeStation" class="h-4 w-4 text-yellow-700" />
              <AlertCircle v-else class="h-4 w-4 text-amber-700" />
              <p
                :class="[
                  'text-[10px] font-black tracking-wider uppercase',
                  isBeforeStation ? 'text-amber-700' : 'text-yellow-700',
                ]"
              >
                {{ isBeforeStation ? 'Pickup Schedule' : 'Station Arrival' }}
              </p>
            </div>

            <p
              :class="[
                'text-sm font-bold',
                isBeforeStation ? 'text-amber-800' : 'text-yellow-800',
              ]"
            >
              {{
                isBeforeStation
                  ? 'Target Pickup: ' + formatTime(reservation.time_pickup)
                  : 'Arrival: ' + reservation.pickup_loc_name
              }}
            </p>

            <p
              :class="[
                'mt-3 text-[9px] leading-tight font-medium',
                isBeforeStation ? 'text-amber-600' : 'text-yellow-600',
              ]"
            >
              <span v-if="isBeforeStation">
                <strong>Take Note:</strong> We are looking for a driver to
                accept your schedule. Please be ready
                <strong>5 minutes before</strong> ({{
                  formatTime(reservation.time_pickup)
                }}) and ensure you are waiting at the location at least
                <strong>5 minutes early</strong>.
              </span>
              <span v-else>
                <strong>Take Note:</strong> Your request is broadcasted to
                nearby drivers. The system will notify you once a driver
                accepts. A driver is typically dispatched 30 minutes before your
                bus reaches the station.
              </span>
            </p>

            <div class="mt-4">
              <p
                :class="[
                  'text-[9px] font-black uppercase italic',
                  isBeforeStation ? 'text-amber-700' : 'text-yellow-700',
                ]"
              >
                Status: Waiting for Driver Acceptance
              </p>
            </div>
          </div>

          <div class="py-2 text-center">
            <p class="text-[10px] font-bold uppercase">Reference Number</p>
            <p class="font-mono text-lg font-bold">
              {{ reservation.qrcode_name }}
            </p>
          </div>

          <div class="space-y-4 border-t-2 border-black pt-4">
            <div class="space-y-3">
              <div class="flex items-start gap-3">
                <MapPin class="mt-0.5 h-4 w-4 text-emerald-600" />
                <div>
                  <p class="text-[9px] font-bold text-gray-500 uppercase">
                    Pickup Location
                  </p>
                  <p class="text-xs font-bold">
                    {{ reservation.pickup_loc_name }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <Navigation class="mt-0.5 h-4 w-4 text-pink-600" />
                <div>
                  <p class="text-[9px] font-bold text-gray-500 uppercase">
                    Drop-off Point
                  </p>
                  <p class="text-xs font-bold">
                    {{ reservation.destination_loc_name }}
                  </p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-4">
              <div>
                <p class="text-[10px] font-bold text-gray-500 uppercase">
                  Travel Date
                </p>
                <p class="text-sm font-bold">{{ reservation.reserve_date }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-gray-500 uppercase">
                  Est. Distance
                </p>
                <p class="text-sm font-bold">
                  {{ reservation.distance_km }} KM
                </p>
              </div>
            </div>

            <div class="border-t border-black pt-4">
              <div
                class="flex items-start gap-2 border border-slate-200 bg-slate-50 p-2"
              >
                <Info class="h-4 w-4 text-slate-500" />
                <p class="text-[9px] leading-normal text-slate-600">
                  <strong class="uppercase">{{
                    isBeforeStation
                      ? 'Pre-Departure Policy:'
                      : 'Dispatch Policy:'
                  }}</strong>
                  <span v-if="isBeforeStation">
                    To maintain the schedule, ensure you are ready at the pickup
                    point. The system monitors available drivers to guarantee
                    your arrival at the bus station before departure.
                  </span>
                  <span v-else>
                    The system computes your bus ETA and assigns the nearest
                    available vehicle 30 minutes before you reach the station to
                    ensure zero waiting time.
                  </span>
                </p>
              </div>
            </div>
          </div>

          <div
            class="mt-6 border-t-2 border-dashed border-black pt-4 text-center"
          >
            <p class="text-[10px] font-bold uppercase">Total Fare Paid</p>
            <p class="text-2xl font-black">
              ₱{{ Number(reservation.amount).toFixed(2) }}
            </p>
            <p class="text-[9px] font-bold text-emerald-600 uppercase">
              Paid via {{ reservation.payment_options }}
            </p>
          </div>
        </div>

        <div class="mt-8 space-y-3">
          <Button
            @click="downloadTicket"
            class="h-12 w-full rounded-none bg-black text-xs font-bold tracking-widest text-white uppercase hover:bg-gray-800"
          >
            <Download class="mr-2 h-4 w-4" /> Save Receipt
          </Button>

          <Button
            v-if="!isBeforeStation"
            @click="beforeTaxiBooking"
            class="text-warp w-full rounded-none bg-black px-5 py-8 font-bold whitespace-normal text-white uppercase hover:bg-gray-800"
          >
            Book Taxi from Your Location to
            {{ reservation.pickup_loc_name }}
          </Button>

          <Button
            v-else
            @click="afterTaxiBooking"
            class="text-warp w-full rounded-none bg-black px-5 py-8 font-bold whitespace-normal text-white uppercase hover:bg-gray-800"
          >
            Book Taxi from {{ reservation.destination_loc_name }} to Your
            Destination
          </Button>

          <Link
            :href="dashboard()"
            class="flex items-center justify-center text-xs font-bold tracking-widest text-gray-400 uppercase transition-colors hover:text-black"
          >
            <Home class="mr-2 h-3 w-3" /> Back to Dashboard
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
