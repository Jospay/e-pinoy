<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import {
  Clock,
  RotateCcw,
  Bus,
  ReceiptText,
  Undo2,
  AlertTriangle,
  Users,
  Calendar,
  CarFront, // Added for taxi icon
  ChevronRight,
} from 'lucide-vue-next';
import { computed, watch, ref } from 'vue';
import { toast } from 'vue-sonner';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  transactions: any[];
  initialFilter: string;
}>();

const page = usePage();
const isConfirmOpen = ref(false);
const selectedTx = ref<any>(null);

watch(
  () => page.props.flash,
  (flash: any) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
  },
  { deep: true },
);

const filteredTransactions = computed(() => {
  if (props.initialFilter === 'completed')
    return props.transactions.filter((t) => t.is_completed);
  if (props.initialFilter === 'paid')
    return props.transactions.filter((t) => t.is_paid);
  if (props.initialFilter === 'refund')
    return props.transactions.filter((t) => t.is_refunded);
  return props.transactions.filter((t) => t.is_pending);
});

const updateFilter = (status: string) => {
  router.get(
    `/passenger/transaction-history`,
    { status },
    { preserveState: true, replace: true },
  );
};

const goToTicket = (qrName: string) =>
  router.get(`/passenger/reservation/success/${qrName}`);

const goToTaxiTicket = (id: number) => {
  router.get(`/passenger/reservation/taxi/success/${id}`);
};

const bookAgain = (tx: any) => {
  router.get(`/passenger/dashboard/Reserve`, {
    station_reservation_id: tx.id,
    from_id: tx.from_bus_station_id,
  });
};

const openRefundModal = (tx: any) => {
  selectedTx.value = tx;
  isConfirmOpen.value = true;
};

const confirmRefund = () => {
  if (!selectedTx.value) return;
  router.post(
    `/passenger/transaction-history/refund/${selectedTx.value.id}`,
    {},
    {
      onBefore: () => {
        isConfirmOpen.value = false;
        toast.loading('Processing your refund...', { id: 'refund-toast' });
      },
      onSuccess: () => {
        updateFilter('refund');
        toast.dismiss('refund-toast');
        toast.success('Refund successful!');
        selectedTx.value = null;
      },
      onError: () => {
        toast.dismiss('refund-toast');
        toast.error('Refund failed');
      },
    },
  );
};

const breadcrumbs = [{ title: 'Activity', href: '#' }];
</script>

<template>
  <Head title="Trip Activity" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-[calc(100vh-64px)] bg-slate-50/50 px-3 py-8">
      <div class="mx-auto max-w-2xl">
        <div class="mb-8 sm:px-4">
          <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">
            Activity
          </h1>
          <div class="mt-6 overflow-x-auto pb-2 sm:overflow-visible">
            <div
              class="flex w-max min-w-full rounded-2xl bg-slate-200/50 p-1 sm:w-fit"
            >
              <button
                v-for="s in ['completed', 'paid', 'refund', 'pending']"
                :key="s"
                @click="updateFilter(s)"
                :class="[
                  initialFilter === s
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700',
                ]"
                class="flex-1 rounded-xl px-4 py-2.5 text-[11px] font-bold capitalize transition-all sm:px-6 sm:py-2 sm:text-xs"
              >
                {{ s }}
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="filteredTransactions.length === 0"
          class="rounded-3xl border-2 border-dashed border-slate-200 bg-white py-20 text-center"
        >
          <p class="text-sm text-slate-400 italic">
            No {{ initialFilter }} reservations found.
          </p>
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="tx in filteredTransactions"
            :key="tx.id"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl"
          >
            <div
              class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-3"
            >
              <div class="flex items-center gap-2">
                <span
                  :class="[
                    'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase',
                    tx.is_completed
                      ? 'bg-blue-100 text-blue-700'
                      : tx.is_paid
                        ? 'bg-green-100 text-green-700'
                        : tx.is_refunded
                          ? 'bg-red-100 text-red-700'
                          : 'bg-amber-100 text-amber-700',
                  ]"
                >
                  {{ tx.status_text }}
                </span>
                <span class="font-mono text-[10px] text-slate-400"
                  >#{{ tx.qrcode_name }}</span
                >
              </div>
              <span class="text-[10px] font-medium text-slate-400 uppercase">{{
                tx.date_at
              }}</span>
            </div>

            <div class="p-4 sm:p-6">
              <div class="mb-6 flex justify-between">
                <div class="flex gap-4">
                  <div class="flex flex-col items-center py-1">
                    <div
                      class="h-2.5 w-2.5 rounded-full border-2 border-blue-500"
                    ></div>
                    <div
                      class="my-1 h-12 w-0.5 border-l-2 border-dotted border-slate-200"
                    ></div>
                    <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                  </div>
                  <div class="space-y-4 text-sm text-slate-600">
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Origin
                      </p>
                      <p class="font-bold text-slate-900">{{ tx.origin }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Destination
                      </p>
                      <p class="font-bold text-slate-900">
                        {{ tx.destination }}
                      </p>
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-black">₱{{ tx.bus_amount }}</p>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    Bus Fare
                  </p>
                </div>
              </div>

              <div
                v-if="tx.has_taxi"
                class="mb-6 rounded-2xl border border-blue-100 bg-blue-50/50 p-4"
              >
                <div class="mb-2 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <CarFront class="h-4 w-4 text-blue-600" />
                    <span
                      class="text-[11px] font-black tracking-wider text-blue-900 uppercase"
                    >
                      Connected Taxi Booking
                    </span>
                  </div>

                  <div class="text-right">
                    <p class="text-sm font-black text-blue-700">
                      ₱{{ tx.taxi_details.amount }}
                    </p>
                    <p class="text-[8px] font-bold text-blue-400 uppercase">
                      Taxi Fare
                    </p>
                  </div>
                </div>

                <div
                  class="flex items-center justify-between border-t border-blue-100 pt-3"
                >
                  <div class="text-[11px] text-slate-600">
                    <div class="flex items-center gap-2">
                      <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-[9px] font-bold text-blue-700 uppercase"
                      >
                        {{ tx.taxi_details.status }}
                      </span>
                      <p class="font-mono text-[9px] text-slate-400">
                        Ref: {{ tx.taxi_details.qrcode_name }}
                      </p>
                    </div>
                    <p class="mt-1">
                      Drop-off:
                      <span class="font-bold text-slate-900">{{
                        tx.taxi_details.destination
                      }}</span>
                    </p>
                  </div>

                  <button
                    @click="goToTaxiTicket(tx.taxi_details.id)"
                    class="flex items-center gap-1 text-[10px] font-bold text-blue-600 hover:underline"
                  >
                    View Taxi Ticket <ChevronRight class="h-3 w-3" />
                  </button>
                </div>
              </div>

              <div class="mb-6 grid grid-cols-2 gap-3">
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Bus class="h-4 w-4 text-slate-400" />
                  <div>
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      Vehicle
                    </p>
                    <p class="max-w-[100px] truncate text-xs font-bold">
                      {{ tx.vehicle_name }}
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Users class="h-4 w-4 text-slate-400" />
                  <div>
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      Passengers
                    </p>
                    <p class="max-w-[100px] truncate text-xs font-bold">
                      {{ tx.passenger_count }}
                      {{ tx.passenger_count > 1 ? 'Seats' : 'Seat' }}
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Calendar class="h-4 w-4 text-slate-400" />
                  <div>
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      Travel Date
                    </p>
                    <p class="text-xs font-bold">{{ tx.book_at }}</p>
                  </div>
                </div>
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Clock class="h-4 w-4 text-slate-400" />
                  <div>
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      Departure
                    </p>
                    <p class="text-xs font-bold">{{ tx.time_window }}</p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-3">
                <button
                  v-if="tx.can_refund"
                  @click="openRefundModal(tx)"
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 py-3.5 text-xs font-bold text-red-600 transition-all hover:bg-red-100"
                >
                  <Undo2 class="h-4 w-4" /> Refund to E-Wallet
                </button>
                <div class="flex gap-3">
                  <button
                    v-if="tx.is_completed"
                    @click="bookAgain(tx)"
                    class="flex-1 rounded-2xl bg-slate-900 py-3.5 text-xs font-bold text-white hover:bg-slate-800"
                  >
                    <RotateCcw class="mr-1 inline h-4 w-4" /> Book Again
                  </button>
                  <button
                    v-if="tx.is_paid && !tx.can_refund"
                    @click="goToTicket(tx.qrcode_name)"
                    class="flex-1 rounded-2xl border-2 border-slate-900 py-3 text-xs font-bold text-slate-900 hover:bg-slate-50"
                  >
                    <ReceiptText class="mr-1 inline h-4 w-4" /> View Ticket
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog v-model:open="isConfirmOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader class="flex flex-col items-center text-center">
          <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600"
          >
            <AlertTriangle class="h-7 w-7" />
          </div>
          <DialogTitle class="text-xl font-bold">Confirm Refund</DialogTitle>
          <DialogDescription class="pt-2 text-slate-500">
            Are you sure you want to refund this ticket? <br />
            <span class="text-lg font-bold text-slate-900"
              >₱{{ selectedTx?.amount }}</span
            >
            will be added back to your E-Wallet.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="flex flex-col gap-2">
          <Button
            variant="destructive"
            class="h-12 w-full rounded-xl font-bold"
            @click="confirmRefund"
            >Yes, Refund Now</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
