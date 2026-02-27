<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { toPng } from 'html-to-image'; // Import the downloader
import {
  Calendar,
  CheckCircle,
  Clock,
  Download,
  Home,
  MapPin,
} from 'lucide-vue-next';
import QrcodeVue from 'qrcode.vue';
import { ref } from 'vue'; // Import ref

const props = defineProps<{
  reservation: any;
}>();

// Reference to the receipt div
const receiptRef = ref<HTMLElement | null>(null);

const downloadTicket = async () => {
  if (receiptRef.value) {
    try {
      const dataUrl = await toPng(receiptRef.value, {
        cacheBust: true,
        backgroundColor: '#f8fafc',
      });
      const link = document.createElement('a');
      link.download = `Ticket-${props.reservation.qrcode_name}.png`;
      link.href = dataUrl;
      link.click();
    } catch (err) {
      console.error('oops, something went wrong!', err);
    }
  }
};

const formatTime = (time: string) => {
  if (!time) return '';
  return new Date(`2000-01-01T${time}`).toLocaleTimeString([], {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
};
</script>

<template>
  <Head title="Success" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50 px-4 py-12">
      <div class="mx-auto max-w-md">
        <div class="mb-6 text-center">
          <div
            class="mb-4 inline-flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-green-600"
          >
            <CheckCircle class="h-12 w-12" />
          </div>
          <h1 class="text-3xl font-black text-slate-900 italic">
            RESERVATION SAVED!
          </h1>
        </div>

        <div
          v-if="reservation"
          ref="receiptRef"
          class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
        >
          <div class="border-b border-dashed border-slate-200 p-6 text-center">
            <p
              class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
            >
              Official Receipt
            </p>
            <p class="font-mono text-lg font-bold text-slate-800">
              {{ reservation.qrcode_name }}
            </p>
          </div>

          <div class="space-y-4 p-6">
            <div class="flex items-start justify-between">
              <div class="flex gap-3">
                <MapPin class="h-5 w-5 text-slate-400" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    Route
                  </p>
                  <p class="text-sm font-bold text-slate-900">
                    {{ reservation.from_station?.name }} <br />
                    <span class="font-normal text-slate-400">to</span>
                    {{ reservation.to_station?.name }}
                  </p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4 border-t border-slate-50 pt-4">
              <div class="flex gap-3">
                <Calendar class="h-5 w-5 text-slate-400" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    Date
                  </p>
                  <p class="text-sm font-bold text-slate-900">
                    {{ reservation.reserve_date }}
                  </p>
                </div>
              </div>
              <div class="flex gap-3">
                <Clock class="h-5 w-5 text-slate-400" />
                <div>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    Departure
                  </p>
                  <p class="text-sm font-bold text-slate-900">
                    {{ formatTime(reservation.reserve_from_time) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div
            class="flex items-center justify-between border-y border-slate-200 bg-slate-50 px-6 py-4"
          >
            <span class="text-xs font-bold text-slate-500 uppercase"
              >Total Paid</span
            >
            <span class="text-xl font-black text-slate-900"
              >₱{{ Number(reservation.amount).toFixed(2) }}</span
            >
          </div>

          <div class="bg-white p-8 text-center">
            <div
              class="mb-4 inline-block rounded-xl border-2 border-slate-100 p-2"
            >
              <qrcode-vue
                :value="reservation.qrcode_name"
                :size="140"
                level="M"
                render-as="svg"
              />
            </div>
            <p class="text-[10px] tracking-tighter text-slate-400 uppercase">
              Scan at boarding terminal
            </p>
          </div>

          <div
            class="absolute top-1/2 -left-3 h-6 w-6 rounded-full border border-slate-200 bg-slate-100 shadow-inner"
          ></div>
          <div
            class="absolute top-1/2 -right-3 h-6 w-6 rounded-full border border-slate-200 bg-slate-100 shadow-inner"
          ></div>
        </div>

        <div class="mt-6 space-y-3">
          <Button
            @click="downloadTicket"
            variant="outline"
            class="h-12 w-full rounded-xl border-slate-200 text-slate-700 hover:bg-slate-100"
          >
            <Download class="mr-2 h-4 w-4" /> Save as Image
          </Button>

          <Link :href="dashboard()">
            <Button
              class="h-12 w-full rounded-xl bg-slate-900 text-white hover:bg-slate-800"
            >
              <Home class="mr-2 h-4 w-4" /> Back to Dashboard
            </Button>
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
