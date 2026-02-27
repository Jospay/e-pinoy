<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ArrowRight, Clock, MapPin, RotateCcw } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  transactions: any[];
  initialFilter: string;
}>();

// Filter logic based on the URL status
const filteredTransactions = computed(() => {
  if (props.initialFilter === 'completed') {
    return props.transactions.filter((t) => t.is_completed);
  } else if (props.initialFilter === 'paid') {
    return props.transactions.filter((t) => t.is_paid);
  }
  return props.transactions.filter((t) => t.is_pending);
});

// Update URL and Filter
const updateFilter = (status: 'completed' | 'paid' | 'pending') => {
  const url = `/passenger/transaction-history`;
  router.get(
    url,
    { status: status },
    {
      preserveState: true,
      replace: true,
    },
  );
};

// Navigate to specific ticket
const goToTicket = (qrName: string) => {
  const urlSuccess = `/passenger/reservation/success/${qrName}`;
  router.get(urlSuccess);
};

// Book Again redirect with from_id
const bookAgain = (fromId: number) => {
  const urlReserve = `/passenger/dashboard/Reserve?from_id=${fromId}`;
  router.get(urlReserve);
};

const breadcrumbs = [{ title: 'Activity', href: '#' }];
</script>

<template>
  <Head title="Trip Activity" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="min-h-[calc(100vh-64px)] bg-slate-50/50 px-4 py-8 sm:px-6 lg:px-8"
    >
      <div class="mx-auto max-w-2xl sm:p-6">
        <div class="mb-8">
          <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
            Activity
          </h1>

          <div
            class="mt-6 flex w-fit items-center rounded-2xl bg-slate-200/50 p-1"
          >
            <button
              @click="updateFilter('completed')"
              :class="[
                initialFilter === 'completed'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700',
              ]"
              class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
            >
              Completed
            </button>
            <button
              @click="updateFilter('paid')"
              :class="[
                initialFilter === 'paid'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700',
              ]"
              class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
            >
              Paid Trips
            </button>
            <button
              @click="updateFilter('pending')"
              :class="[
                initialFilter === 'pending'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700',
              ]"
              class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
            >
              Pending
            </button>
          </div>
        </div>

        <div
          v-if="filteredTransactions.length === 0"
          class="rounded-3xl border-2 border-dashed border-slate-200 bg-white py-20 text-center"
        >
          <p class="text-sm font-medium text-slate-400 italic">
            No {{ initialFilter }} reservations found.
          </p>
        </div>

        <div v-else class="space-y-6">
          <div v-for="tx in filteredTransactions" :key="tx.id">
            <div
              class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/40"
            >
              <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-3"
              >
                <span
                  :class="[
                    'rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                    tx.is_completed
                      ? 'bg-blue-100 text-blue-700'
                      : tx.is_paid
                        ? 'bg-green-100 text-green-700'
                        : 'bg-amber-100 text-amber-700',
                  ]"
                >
                  {{ tx.status_text }}
                </span>
                <span
                  class="text-[10px] font-medium tracking-widest text-slate-400 uppercase"
                  >{{ tx.booked_at }}</span
                >
              </div>

              <div class="p-6">
                <div class="mb-6 flex items-start justify-between">
                  <div class="flex gap-4">
                    <div class="flex flex-col items-center py-1">
                      <div
                        class="h-2.5 w-2.5 rounded-full border-2 border-blue-500 bg-white"
                      ></div>
                      <div
                        class="my-1 h-8 w-0.5 border-l-2 border-dotted border-slate-200"
                      ></div>
                      <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                    </div>
                    <div class="space-y-4">
                      <div>
                        <p
                          class="mb-1 text-[10px] leading-none font-bold text-slate-400 uppercase"
                        >
                          From
                        </p>
                        <p class="leading-tight font-bold text-slate-800">
                          {{ tx.origin }}
                        </p>
                      </div>
                      <div>
                        <p
                          class="mb-1 text-[10px] leading-none font-bold text-slate-400 uppercase"
                        >
                          To
                        </p>
                        <p class="leading-tight font-bold text-slate-800">
                          {{ tx.destination }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-2xl font-black text-slate-900">
                      ₱{{ tx.amount }}
                    </p>
                    <p class="text-[10px] font-bold text-slate-400 uppercase">
                      Fare Amount
                    </p>
                  </div>
                </div>

                <div
                  class="mb-4 flex items-center gap-6 rounded-2xl bg-slate-50 p-4"
                >
                  <div class="flex items-center gap-2">
                    <Clock class="h-4 w-4 text-slate-400" />
                    <div>
                      <p class="text-[9px] font-bold text-slate-400 uppercase">
                        Time
                      </p>
                      <p class="text-xs font-bold text-slate-700">
                        {{ tx.time_window }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <MapPin class="h-4 w-4 text-slate-400" />
                    <div>
                      <p class="text-[9px] font-bold text-slate-400 uppercase">
                        Date
                      </p>
                      <p class="text-xs font-bold text-slate-700">
                        {{ tx.date }}
                      </p>
                    </div>
                  </div>
                </div>

                <div v-if="tx.is_completed">
                  <button
                    @click="bookAgain(tx.from_bus_station_id)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 py-4 text-sm font-bold text-white shadow-lg shadow-blue-200 transition-all hover:bg-blue-700"
                  >
                    <RotateCcw class="h-4 w-4" />
                    Book Trip Again
                  </button>
                </div>

                <button
                  v-else-if="tx.is_paid"
                  @click="goToTicket(tx.qr_name)"
                  class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 py-4 text-sm font-bold text-white transition-all hover:bg-slate-700"
                >
                  View Digital Ticket
                  <ArrowRight class="h-4 w-4" />
                </button>

                <div
                  v-else
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-200 bg-slate-100 py-4 text-xs font-bold tracking-widest text-slate-400 uppercase"
                >
                  Ticket Not Issued (Pending)
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
