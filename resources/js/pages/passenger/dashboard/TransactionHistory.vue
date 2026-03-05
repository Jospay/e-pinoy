<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Clock, RotateCcw, Bus, ReceiptText, Undo2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  transactions: any[];
  initialFilter: string;
}>();

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
const bookAgain = (fromId: number) =>
  router.get(`/passenger/dashboard/Reserve?from_id=${fromId}`);

const handleRefund = (id: number) => {
  if (confirm('Refund this ticket to your E-Wallet?')) {
    router.post(`/passenger/transaction-history/refund/${id}`);
  }
};

const breadcrumbs = [{ title: 'Activity', href: '#' }];
</script>

<template>
  <Head title="Trip Activity" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-[calc(100vh-64px)] bg-slate-50/50 px-4 py-8">
      <div class="mx-auto max-w-2xl">
        <div class="mb-8">
          <h1 class="text-3xl font-extrabold text-slate-900">Activity</h1>
          <div class="mt-6 flex w-fit rounded-2xl bg-slate-200/50 p-1">
            <button
              v-for="s in ['completed', 'paid', 'refund', 'pending']"
              :key="s"
              @click="updateFilter(s)"
              :class="[
                initialFilter === s
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500',
              ]"
              class="rounded-xl px-6 py-2 text-xs font-bold capitalize transition-all"
            >
              {{ s }}
            </button>
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
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl transition-all"
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
                tx.booked_at
              }}</span>
            </div>

            <div class="p-6">
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
                  <div class="space-y-4 text-sm">
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Origin
                      </p>
                      <p class="font-bold">{{ tx.origin }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Destination
                      </p>
                      <p class="font-bold">{{ tx.destination }}</p>
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <p class="text-2xl font-black">₱{{ tx.amount }}</p>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    Total Paid
                  </p>
                </div>
              </div>

              <div class="mb-6 grid grid-cols-2 gap-3">
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Bus class="h-4 w-4 text-slate-400" />
                  <div>
                    <p class="text-[9px] font-bold text-slate-400">VEHICLE</p>
                    <p class="max-w-[100px] truncate text-xs font-bold">
                      {{ tx.vehicle_name }}
                    </p>
                  </div>
                </div>
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <Clock class="h-4 w-4 text-slate-400" />
                  <div>
                    <p class="text-[9px] font-bold text-slate-400">SCHEDULE</p>
                    <p class="text-xs font-bold">{{ tx.time_window }}</p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-3">
                <button
                  v-if="tx.can_refund"
                  @click="handleRefund(tx.id)"
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 py-3.5 text-xs font-bold text-red-600 transition-all hover:bg-red-100"
                >
                  <Undo2 class="h-4 w-4" /> Refund to E-Wallet
                </button>

                <div
                  v-else-if="tx.is_too_early"
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-amber-100 bg-amber-50 py-3 text-[10px] font-bold text-amber-600"
                >
                  <Clock class="h-4 w-4" /> Refund available 10m after departure
                </div>

                <div
                  v-else-if="tx.is_expired"
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 py-3 text-[10px] font-bold text-slate-400"
                >
                  <Clock class="h-4 w-4" /> Refund window expired
                </div>

                <div class="flex gap-3">
                  <button
                    v-if="tx.is_completed"
                    @click="bookAgain(tx.from_bus_station_id)"
                    class="flex-1 rounded-2xl bg-slate-900 py-3.5 text-xs font-bold text-white hover:bg-slate-800"
                  >
                    <RotateCcw class="mr-1 inline h-4 w-4" /> Book Again
                  </button>

                  <button
                    v-if="(tx.is_paid || tx.is_completed) && !tx.is_expired"
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
  </AppLayout>
</template>
