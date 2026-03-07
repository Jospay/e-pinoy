<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { toPng } from 'html-to-image';
import {
  Download,
  MapPin,
  Navigation,
  Home,
  Clock,
  Info,
} from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
  reservation: any;
}>();

const receiptRef = ref<HTMLElement | null>(null);

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
            class="my-4 border border-yellow-200 bg-yellow-50 p-3 text-center"
          >
            <div class="mb-1 flex items-center justify-center gap-2">
              <Clock class="h-4 w-4 text-yellow-700" />
              <p class="text-[10px] font-black text-yellow-700 uppercase">
                Driver Status
              </p>
            </div>
            <p class="text-sm font-bold text-yellow-800">Pending Assignment</p>
            <p class="mt-1 text-[9px] leading-tight text-yellow-600 italic">
              Note: A driver will be dispatched 30 minutes before your arrival
              at the final station.
            </p>
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
                <MapPin class="mt-0.5 h-4 w-4 text-blue-600" />
                <div>
                  <p class="text-[9px] font-bold text-gray-500 uppercase">
                    Pickup
                  </p>
                  <p class="text-xs font-bold">
                    {{ reservation.pickup_loc_name }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <Navigation class="mt-0.5 h-4 w-4 text-red-600" />
                <div>
                  <p class="text-[9px] font-bold text-gray-500 uppercase">
                    Drop-off
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
                  Date
                </p>
                <p class="text-sm font-bold">{{ reservation.reserve_date }}</p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-gray-500 uppercase">
                  Distance
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
                  <strong>DISPATCH POLICY:</strong> To ensure your driver is
                  ready upon arrival, the system computes your ETA and assigns
                  the nearest available vehicle 30 minutes before you reach the
                  station.
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
              {{ reservation.payment_options }}
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
