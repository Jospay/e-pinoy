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
  CarFront,
  CheckCircle2,
} from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';
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
  initialType: string;
}>();

const page = usePage();
const isConfirmOpen = ref(false);
const selectedTx = ref<any>(null);
const currentType = ref(props.initialType || 'all');
const amountError = ref<string | null>(null);

/**
 * HELPER: PROCESS FLASH MESSAGES
 * This centralizes the logic so both onMounted and router.on use the same rules.
 */
const handleFlashMessages = () => {
  const flash = page.props.flash as any;
  if (!flash) return;

  // Handle Success
  if (flash.success) {
    toast.success(flash.success, { id: 'transaction-toast', duration: 5000 });
    amountError.value = null; // Clear error on success
  }

  // Handle Error
  if (flash.error) {
    toast.error(flash.error, { id: 'transaction-toast' });

    const errorMsg = String(flash.error).toLowerCase();
    // Check if the error belongs in the "Security Alert" box
    if (
      errorMsg.includes('security') ||
      errorMsg.includes('seal') ||
      errorMsg.includes('tamper') ||
      errorMsg.includes('integrity')
    ) {
      amountError.value = flash.error;
    }
  }
};

/**
 * Listen for Inertia visits finishing (like redirects from OTP)
 */
router.on('finish', () => {
  handleFlashMessages();
});

/**
 * Check on initial load (in case of a direct redirect/refresh)
 */
onMounted(() => {
  handleFlashMessages();
});

// Filter logic
const filteredTransactions = computed(() => {
  return props.transactions.filter((t) => {
    const matchesStatus =
      (props.initialFilter === 'completed' && t.is_completed) ||
      (props.initialFilter === 'paid' &&
        t.is_paid &&
        !t.is_completed &&
        !t.is_refunded) ||
      (props.initialFilter === 'refund' && t.is_refunded) ||
      (props.initialFilter === 'pending' && t.is_pending);

    const matchesType =
      currentType.value === 'all' || t.type === currentType.value;
    return matchesStatus && matchesType;
  });
});

const updateFilter = (status: string) => {
  router.get(
    `/passenger/transaction-history`,
    { status, type: currentType.value },
    { preserveState: true, replace: true },
  );
};

const updateType = (type: string) => {
  currentType.value = type;
  router.get(
    `/passenger/transaction-history`,
    { status: props.initialFilter, type },
    { preserveState: true, replace: true },
  );
};

// Navigations
const goToTicket = (qrcode: string) =>
  router.get(`/passenger/bus/ticket/${qrcode}`);
const goToTaxiTicket = (id: number) =>
  router.get(`/passenger/taxi/ticket/${id}`);

const openRefundModal = (tx: any) => {
  selectedTx.value = tx;
  isConfirmOpen.value = true;
};

const confirmRefund = () => {
  if (!selectedTx.value) return;

  if (!navigator.geolocation) {
    toast.error('Geolocation is not supported by your browser.');
    return;
  }

  toast.loading('Accessing GPS location...', { id: 'transaction-toast' });

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords;

      router.post(
        `/passenger/transaction-history/refund/${selectedTx.value.id}`,
        {
          type: selectedTx.value.type,
          latitude: latitude,
          longitude: longitude,
        },
        {
          onBefore: () => {
            isConfirmOpen.value = false;
            toast.loading('Processing refund...', { id: 'transaction-toast' });
          },
          onSuccess: () => {
            selectedTx.value = null;
          },
          onError: () => {
            // This catches validation errors or 500s
            handleFlashMessages();
          },
          preserveScroll: true,
        },
      );
    },
    (error) => {
      let msg = 'Please enable location services to process a refund.';
      if (error.code === 1)
        msg = 'Location access denied. We need your GPS to verify the refund.';

      toast.error(msg, { id: 'transaction-toast' });
    },
    { enableHighAccuracy: true, timeout: 5000 },
  );
};

const breadcrumbs = [{ title: 'Transaction History', href: '#' }];
</script>

<template>
  <Head title="Transaction History" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="min-h-[calc(100vh-64px)] bg-slate-50/50 px-3 py-8">
      <div class="mx-auto max-w-2xl">
        <div class="mb-8">
          <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">
            Transaction History
          </h1>

          <div class="mt-6 flex gap-2">
            <button
              v-for="type in ['all', 'bus', 'taxi']"
              :key="type"
              @click="updateType(type)"
              :class="[
                currentType === type
                  ? 'bg-brand-blue text-white'
                  : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50',
              ]"
              class="rounded-full px-5 py-1.5 text-xs font-bold capitalize transition-all"
            >
              {{ type }}
            </button>
          </div>

          <div class="mt-4 overflow-x-auto pb-2 sm:overflow-visible">
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

          <div
            v-if="amountError"
            class="mt-5.5 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm"
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"
              >
                <AlertTriangle class="h-5 w-5" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-red-900">Security Alert</h3>
                <p class="text-[11px] leading-tight font-medium text-red-600">
                  {{ amountError }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div
          v-if="filteredTransactions.length === 0"
          class="rounded-3xl border-2 border-dashed border-slate-200 bg-white py-20 text-center"
        >
          <p class="text-sm text-slate-400 italic">
            No {{ initialFilter }}
            {{ currentType !== 'all' ? currentType : '' }} reservations found.
          </p>
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="tx in filteredTransactions"
            :key="tx.type + tx.id"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl"
          >
            <div
              class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-3"
            >
              <div class="flex items-center gap-2">
                <component
                  :is="tx.type === 'bus' ? Bus : CarFront"
                  class="h-3.5 w-3.5 text-slate-400"
                />
                <span
                  :class="[
                    'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase',
                    tx.is_expired
                      ? 'bg-slate-200 text-slate-500'
                      : tx.is_completed
                        ? 'bg-blue-100 text-brand-blue'
                        : tx.is_paid
                          ? 'bg-green-100 text-green-700'
                          : tx.is_refunded
                            ? 'bg-red-100 text-red-700'
                            : 'bg-amber-100 text-amber-700',
                  ]"
                >
                  {{ tx.is_expired ? 'Expired' : tx.status_text }}
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
                      class="h-2.5 w-2.5 rounded-full border-2 border-brand-blue"
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
                  <p class="text-2xl font-black">₱{{ tx.amount }}</p>
                  <p class="text-[10px] font-bold text-slate-400 uppercase">
                    {{ tx.type === 'bus' ? 'Bus Fare' : 'Taxi Fare' }}
                  </p>
                </div>
              </div>

              <div class="mb-6 grid grid-cols-2 gap-3">
                <div
                  class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                >
                  <component
                    :is="tx.type === 'bus' ? Bus : CarFront"
                    class="h-4 w-4 text-slate-400"
                  />
                  <div>
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      Vehicle
                    </p>
                    <p
                      class="max-w-[150px] text-xs leading-tight font-bold"
                      :class="{
                        'text-amber-600 italic':
                          !tx.vehicle_name && tx.type === 'taxi',
                        'text-slate-900': tx.vehicle_name,
                      }"
                    >
                      <template v-if="tx.vehicle_name">
                        {{ tx.vehicle_name }}
                      </template>
                      <template v-else-if="tx.type === 'taxi'">
                        Waiting for Driver Acceptance
                      </template>
                      <template v-else> N/A </template>
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
                    <p class="text-xs font-bold">
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
                  <Clock class="h-4 w-4 shrink-0 text-slate-400" />
                  <div class="min-w-0 flex-1">
                    <p
                      class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                    >
                      {{ tx.type === 'bus' ? 'Departure' : 'Pickup Schedule' }}
                    </p>
                    <p
                      class="leading-tight font-bold"
                      :class="
                        tx.time_window.length > 10 ? 'text-[10px]' : 'text-xs'
                      "
                    >
                      {{ tx.time_window }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-3">
                <template
                  v-if="
                    tx.type === 'taxi' &&
                    tx.is_paid &&
                    !tx.is_expired &&
                    !tx.is_completed
                  "
                >
                  <button
                    @click="goToTaxiTicket(tx.id)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-slate-900 py-3 text-xs font-bold text-slate-900 hover:bg-slate-50"
                  >
                    <ReceiptText class="h-4 w-4" /> View Taxi Ticket
                  </button>

                  <button
                    @click="openRefundModal(tx)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 py-3.5 text-xs font-bold text-red-600 transition-all hover:bg-red-100"
                  >
                    <Undo2 class="h-4 w-4" /> Refund to E-Wallet
                  </button>
                </template>

                <template v-else>
                  <button
                    v-if="tx.can_refund"
                    @click="openRefundModal(tx)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-red-100 bg-red-50 py-3.5 text-xs font-bold text-red-600 transition-all hover:bg-red-100"
                  >
                    <Undo2 class="h-4 w-4" /> Refund to E-Wallet
                  </button>

                  <div
                    v-if="tx.is_expired && (tx.is_paid || tx.is_pending)"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 py-3.5 text-xs font-bold text-slate-400"
                  >
                    <Clock class="h-4 w-4" /> Expired
                  </div>

                  <div class="flex gap-3">
                    <button
                      v-if="tx.type === 'bus' && tx.is_completed"
                      class="flex-1 rounded-2xl bg-slate-900 py-3.5 text-xs font-bold text-white hover:bg-slate-800"
                    >
                      <RotateCcw class="mr-1 inline h-4 w-4" /> Book Again
                    </button>

                    <button
                      v-if="tx.type === 'taxi' && tx.is_completed"
                      class="flex-1 rounded-2xl bg-brand-blue py-3.5 text-xs font-bold text-white"
                    >
                      <CheckCircle2 class="mr-1 inline h-4 w-4" /> Taxi Trip
                      Completed
                    </button>

                    <button
                      v-if="
                        tx.type === 'bus' &&
                        tx.is_paid &&
                        !tx.can_refund &&
                        !tx.is_expired
                      "
                      @click="goToTicket(tx.qrcode_name)"
                      class="flex-1 rounded-2xl border-2 border-slate-900 py-3 text-xs font-bold text-slate-900 hover:bg-slate-50"
                    >
                      <ReceiptText class="mr-1 inline h-4 w-4" /> View Bus
                      Ticket
                    </button>
                  </div>
                </template>
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
            Are you sure you want to refund this {{ selectedTx?.type }} ticket?
            <br />
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
          >
            Yes, Refund Now
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
