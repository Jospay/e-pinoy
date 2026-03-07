<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, Link, router } from '@inertiajs/vue3';
import { toPng } from 'html-to-image'; // FIXED: Removed the extra characters
import { Download, ArrowRight, CarFront, Home } from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
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
      link.download = `Ticket-${props.reservation.qrcode_name}.png`;
      link.href = dataUrl;
      link.click();
    } catch (err) {
      console.error('Download failed', err);
    }
  }
};

const formatTime = (time: string) => {
  if (!time) return '--:--';
  return new Date(`2000-01-01T${time}`).toLocaleTimeString([], {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
};

const goToTaxiBooking = () => {
  router.get(`/passenger/reservation/taxi/Reserve/${props.reservation.id}`);
};
</script>

<template>
  <Head title="Booking Receipt" />
  <AppLayout>
    <div class="min-h-screen bg-white px-4 py-12">
      <div class="mx-auto max-w-sm">
        <div
          v-if="reservation"
          ref="receiptRef"
          class="border-2 border-black bg-white p-6 text-black"
        >
          <div class="border-b-2 border-black pb-4 text-center">
            <h1 class="text-2xl font-black tracking-tighter uppercase">
              Receipt
            </h1>
            <p
              class="text-xs font-bold tracking-widest text-gray-500 uppercase"
            >
              Official Boarding Pass
            </p>
          </div>

          <div class="py-4 text-center">
            <p class="text-[10px] font-bold uppercase">Reference Number</p>
            <p class="font-mono text-lg font-bold">
              {{ reservation.qrcode_name }}
            </p>
          </div>

          <div class="space-y-4 border-t-2 border-black pt-4">
            <div>
              <p class="text-[10px] font-bold text-gray-500 uppercase">Route</p>
              <div class="flex items-center justify-between font-black">
                <span>{{ reservation.from_station?.name }}</span>
                <ArrowRight class="h-4 w-4" />
                <span class="ps-2">{{ reservation.to_station?.name }}</span>
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
                  Departure
                </p>
                <p class="text-sm font-bold">
                  {{ formatTime(reservation.reserve_from_time) }}
                </p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-gray-500 uppercase">
                  Name
                </p>
                <p class="text-sm font-bold">
                  {{ reservation.passenger?.name || 'Guest' }}
                </p>
              </div>
              <div>
                <p class="text-[10px] font-bold text-gray-500 uppercase">
                  Passengers
                </p>
                <p class="text-sm font-bold"></p>

                <p class="max-w-[100px] truncate text-xs font-bold">
                  {{ reservation.passenger_count }}
                  {{ reservation.passenger_count > 1 ? 'Seats' : 'Seat' }}
                </p>
              </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
              <p class="text-[10px] font-bold text-gray-500 uppercase">
                Vehicle
              </p>
              <p class="text-sm font-bold">
                {{ reservation.vehicle?.model }} ({{
                  reservation.vehicle?.plate_number
                }})
              </p>
            </div>
          </div>

          <div class="mt-6 border-t-2 border-black pt-6 text-center">
            <div class="inline-block border-2 border-black p-2">
              <qrcode-vue
                :value="reservation.qrcode_name"
                :size="140"
                level="H"
                render-as="svg"
              />
            </div>
            <p class="mt-4 text-[10px] font-black tracking-[0.2em] uppercase">
              Scan to Verify
            </p>
          </div>

          <div
            class="mt-6 border-t-2 border-dashed border-black pt-4 text-center"
          >
            <p class="text-[10px] font-bold uppercase">Total Amount Paid</p>
            <p class="text-2xl font-black">
              ₱{{ Number(reservation.amount).toFixed(2) }}
            </p>
          </div>
        </div>

        <div class="mt-8 space-y-3">
          <Button
            @click="downloadTicket"
            class="h-12 w-full rounded-none bg-black text-xs font-bold tracking-widest text-white uppercase hover:bg-gray-800"
          >
            <Download class="mr-2 h-4 w-4" /> Save as Image
          </Button>

          <Button
            @click="goToTaxiBooking"
            class="h-12 w-full rounded-none bg-black ..."
          >
            <CarFront class="mr-2 h-4 w-4" />
            Book Taxi for your Last Route
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
