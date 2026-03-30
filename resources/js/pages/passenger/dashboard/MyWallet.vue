<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
  Wallet,
  PlusCircle,
  Loader2,
  Clock,
  Check,
  AlertTriangle,
  MapPin,
  ArrowRight,
  ChevronLeft,
  Undo2,
} from 'lucide-vue-next';
import LocationMap from '@/components/ReservedMap.vue';
import { Button } from '@/components/ui/button';
import { debounce } from 'lodash';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import Input from '@/components/ui/input/Input.vue';

interface Transaction {
  id: number;
  date: string;
  time: string;
  amount: string;
  balance: string;
  symbol: string;
  description: string;
  status: string;
  latitude?: string | number;
  longitude?: string | number;
}

const props = defineProps<{
  walletBalance: string;
  transactions: {
    data: Transaction[];
    last_page: number;
    current_page: number;
  };
}>();

const pageObj = usePage();
const items = ref<Transaction[]>([...props.transactions.data]);
const page = ref(props.transactions.current_page);
const lastPage = ref(props.transactions.last_page);
const loading = ref(false);
const amountError = ref<string | null>(null);

/**
 * HELPER: PROCESS MESSAGES & VALIDATION ERRORS
 * Checks both flash messages and Inertia validation errors
 */
const handleFlashMessages = () => {
  const flash = pageObj.props.flash as any;
  const errors = pageObj.props.errors as any;
  const toastId = 'wallet-toast';

  // 1. Handle Validation Errors (from withErrors in Controller)
  if (errors && Object.keys(errors).length > 0) {
    const firstError = Object.values(errors)[0] as string;
    toast.error(firstError, { id: toastId });
    amountError.value = firstError;
    return; // Stop if there's a specific validation error
  }

  // 2. Handle Flash Messages
  if (!flash) return;

  if (flash.success) {
    toast.success(flash.success, { id: toastId, duration: 5000 });
    amountError.value = null;
  }

  if (flash.error) {
    toast.error(flash.error, { id: toastId });
    const errorMsg = String(flash.error).toLowerCase();
    if (
      errorMsg.includes('security') ||
      errorMsg.includes('seal') ||
      errorMsg.includes('tamper') ||
      errorMsg.includes('integrity')
    ) {
      amountError.value = flash.error;
    }
  }

  if (flash.info) {
    toast.info(flash.info, { id: toastId });
  }
};

// Watch for changes in page props (especially errors) to trigger alerts
watch(
  () => pageObj.props,
  () => {
    handleFlashMessages();
  },
  { deep: true },
);

router.on('finish', () => {
  handleFlashMessages();
});

onMounted(() => {
  handleFlashMessages();
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});

// --- Buy Load Logic ---
const isLoadOpen = ref(false);
const loadAmount = ref(100);
const isProcessingLoad = ref(false);

const buyLoad = () => {
  amountError.value = null;
  if (!navigator.geolocation) {
    toast.error('Geolocation is not supported by your browser.');
    return;
  }

  isProcessingLoad.value = true;
  const toastId = 'wallet-toast';
  toast.loading('Accessing GPS location...', { id: toastId });

  navigator.geolocation.getCurrentPosition(
    (position) => {
      const { latitude, longitude } = position.coords;

      router.post(
        '/passenger/my-wallet/load',
        {
          amount: loadAmount.value,
          latitude: latitude,
          longitude: longitude,
        },
        {
          onStart: () => {
            toast.loading('Creating payment session...', { id: toastId });
          },
          // SUCCESS: Close modal and stop loading
          onSuccess: () => {
            isLoadOpen.value = false;
            isProcessingLoad.value = false;
          },
          // ERROR: (This is where the Security Alert comes from)
          onError: (errors) => {
            isLoadOpen.value = false; // Add this line
            isProcessingLoad.value = false;
            handleFlashMessages();
          },
          // FINISH: Backup cleanup
          onFinish: () => {
            isProcessingLoad.value = false;
          },
        },
      );
    },
    (error) => {
      isProcessingLoad.value = false;
      toast.error('Location access is required for secure top-ups.', {
        id: toastId,
      });
    },
    { enableHighAccuracy: true, timeout: 10000 },
  );
};

// --- Pagination/Infinite Scroll Logic ---
async function loadMore() {
  if (loading.value || page.value >= lastPage.value) return;
  loading.value = true;

  try {
    // Use the next page based on our current state
    const nextPage = page.value + 1;
    const response = await fetch(
      `/passenger/my-wallet/infinite?page=${nextPage}`,
    );
    const data = await response.json();

    // Prevent duplicates just in case
    const currentIds = new Set(items.value.map((i) => i.id));
    const filteredNewData = data.data.filter(
      (item: Transaction) => !currentIds.has(item.id),
    );

    items.value = [...items.value, ...filteredNewData];
    page.value = data.current_page;
    lastPage.value = data.last_page;
  } catch (e) {
    console.error('Failed to load more transactions', e);
  } finally {
    loading.value = false;
  }
}

function handleScroll() {
  const isAtBottom =
    window.innerHeight + window.scrollY >= document.body.offsetHeight - 150;
  if (isAtBottom && !loading.value) loadMore();
}

// --- Map Logic ---
const isMapOpen = ref(false);
const showMapContent = ref(false);
const selectedLocationTx = ref<Transaction | null>(null);

const openLocationMap = async (tx: Transaction) => {
  if (tx.latitude && tx.longitude) {
    selectedLocationTx.value = tx;
    isMapOpen.value = true;
    showMapContent.value = false;
    await nextTick();
    showMapContent.value = true;
  }
};

// --- Bus Payment Logic ---
const isPayBusOpen = ref(false);
const busSearchQuery = ref('');
const busData = ref<any>(null); // Stores search results
const isSearchingBus = ref(false);

const searchBusCode = async () => {
  if (!busSearchQuery.value) return;
  isSearchingBus.value = true;
  try {
    const response = await fetch(
      `/passenger/wallet/search-bus?unique_name=${busSearchQuery.value}`,
      {
        headers: {
          Accept: 'application/json', // <--- Add this
          'X-Requested-With': 'XMLHttpRequest', // <--- And this
        },
      },
    );

    const data = await response.json();

    if (response.ok) {
      busData.value = data;
    } else {
      // Now 'data' will be the Laravel error JSON instead of an HTML string
      toast.error(data.message || 'Bus code not found.');
    }
  } catch (error) {
    console.error('Search failed:', error);
    toast.error('A server error occurred.');
  } finally {
    isSearchingBus.value = false;
  }
};

const isProcessingPayment = ref(false);
const passengerCount = ref(1);
const balanceError = ref<string | null>(null);

const confirmBusPayment = () => {
  // 1. Quick local check to save a server request
  const totalCost = busData.value.total_amount * passengerCount.value;
  if (parseFloat(props.walletBalance) < totalCost) {
    balanceError.value = 'Insufficient wallet balance.';
    toast.error('Insufficient balance. Please top up your wallet.');
    return;
  }

  if (!navigator.geolocation) {
    toast.error('Geolocation is required for secure payments.');
    return;
  }

  isProcessingPayment.value = true;
  balanceError.value = null;

  navigator.geolocation.getCurrentPosition(
    (position) => {
      router.post(
        '/passenger/wallet/pay-bus',
        {
          amount: totalCost,
          unique_name: busSearchQuery.value,
          reservation_id: busData.value.destination.station_reservation_id,
          passengers: passengerCount.value,
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        },
        {
          onSuccess: () => {
            isPayBusOpen.value = false;
            toast.success('Payment Successful!');
          },
          onError: (errors) => {
            // This catches the 'back()->withErrors' from Laravel
            balanceError.value = Object.values(errors)[0] as string;
            toast.error(balanceError.value);
          },
          onFinish: () => {
            isProcessingPayment.value = false;
          },
        },
      );
    },
    () => {
      isProcessingPayment.value = false;
      toast.error('Location access denied.');
    },
  );
};

watch(busSearchQuery, (newVal) => {
  if (newVal.length < 3) {
    busData.value = null; // Hide the route/price until they type enough
  }
  // Optional: Trigger search automatically after typing stops
  debouncedSearch();
});

// 2. The Real-time Search Logic
const debouncedSearch = debounce(async () => {
  if (busSearchQuery.value.length < 3) return;

  isSearchingBus.value = true;
  try {
    const response = await fetch(
      `/passenger/wallet/search-bus?unique_name=${busSearchQuery.value.toUpperCase()}`,
      {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    );

    const data = await response.json();

    if (response.ok) {
      busData.value = data;
    } else {
      busData.value = null; // Clear if not found
    }
  } catch (error) {
    console.error('Search failed:', error);
  } finally {
    isSearchingBus.value = false;
  }
}, 500);

watch(
  () => props.transactions.data,
  (newData) => {
    // When Inertia refreshes the props after a successful post (payment),
    // we want to update our local 'items' list.

    // Logic: Find items in newData that aren't in our current items list yet
    const currentIds = new Set(items.value.map((i) => i.id));
    const newItems = newData.filter((item) => !currentIds.has(item.id));

    if (newItems.length > 0) {
      // Prepend new transactions to the top of the list
      items.value = [...newItems, ...items.value];
    }
  },
  { deep: true },
);
</script>

<template>
  <Head title="My Wallet" />

  <AppLayout>
    <div class="space-y-6 px-3 py-12">
      <div class="mx-auto max-w-2xl">
        <div
          v-if="amountError"
          class="mb-5.5 rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm"
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

        <div
          class="flex flex-col items-center justify-between gap-4 rounded-xl border border-gray-100 bg-white p-6 shadow-md md:flex-row"
        >
          <div class="flex items-center space-x-4">
            <div class="rounded-full bg-brand-blue/10 p-3">
              <Wallet class="h-10 w-10 text-brand-blue" />
            </div>
            <div class="text-left">
              <p class="text-sm text-gray-500">Wallet Balance</p>
              <h1 class="text-3xl font-bold text-brand-blue">
                ₱ {{ walletBalance }}
              </h1>
            </div>
          </div>
          <div class="flex gap-5">
            <Button
              @click="isLoadOpen = true"
              class="w-full rounded-xl bg-brand-blue px-6 py-6 font-bold hover:bg-brand-blue/90 md:w-auto"
            >
              <PlusCircle class="mr-2 h-5 w-5" />
              Buy Load
            </Button>
            <Button
              variant="outline"
              @click="isPayBusOpen = true"
              class="w-full rounded-xl border-2 border-brand-blue px-6 py-5.5 font-bold text-brand-blue md:w-auto"
            >
              <Wallet class="mr-2 h-5 w-5" />
              Pay with Wallet
            </Button>
          </div>
        </div>

        <div class="space-y-4 pt-8">
          <h2 class="text-lg font-bold">Transaction History</h2>

          <div
            v-for="item in items"
            :key="item.id"
            class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-all active:scale-[0.98]"
            @click="item.latitude ? openLocationMap(item) : null"
            :class="{
              'cursor-pointer hover:bg-gray-50': item.latitude,
              '': item.status === 'pending',
            }"
          >
            <div class="flex flex-1 flex-col">
              <div class="flex items-center gap-2">
                <p
                  class="font-semibold"
                  :class="
                    item.status === 'pending'
                      ? 'text-gray-400'
                      : 'text-gray-900'
                  "
                >
                  {{ item.date }}
                </p>

                <MapPin v-if="item.latitude" class="h-3 w-3 text-brand-blue" />
              </div>
              <p class="text-xs text-gray-500">
                {{ item.description || item.time }}
              </p>
              <p
                v-if="item.status === 'paid' || item.status === 'refund'"
                class="text-[10px] text-gray-400"
              >
                Balance: ₱ {{ item.balance }}
              </p>
            </div>

            <div class="grid items-center space-x-2">
              <div class="flex items-center space-x-2">
                <component
                  :is="item.symbol === '+' ? ArrowRight : ChevronLeft"
                  :class="[
                    item.status === 'pending'
                      ? 'text-gray-300'
                      : item.symbol === '+'
                        ? 'text-brand-blue'
                        : 'text-red-600',
                  ]"
                  class="h-5 w-5"
                />
                <span
                  class="text-lg font-bold"
                  :class="[
                    item.status === 'pending'
                      ? 'text-gray-300 line-through'
                      : item.symbol === '+'
                        ? 'text-brand-blue'
                        : 'text-red-600',
                  ]"
                >
                  {{ item.symbol }} ₱{{ item.amount }}
                </span>
              </div>
              <div class="flex justify-end">
                <span
                  v-if="item.status === 'pending'"
                  class="flex w-fit items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[8px] font-black text-amber-600"
                >
                  <Clock class="h-2 w-2" /> Failed
                </span>

                <span
                  v-if="item.status === 'refund'"
                  class="flex w-fit items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[8px] font-black text-blue-600"
                >
                  <Undo2 class="h-2 w-2" /> REFUNDED
                </span>

                <span
                  v-if="item.status === 'paid'"
                  class="flex w-fit items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[8px] font-black text-blue-600"
                >
                  <Check class="h-2 w-2" /> Paid
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="loading"
            class="flex items-center justify-center gap-2 py-4 text-gray-500"
          >
            <Loader2 class="h-5 w-5 animate-spin" />
            Loading more...
          </div>

          <p
            v-if="!loading && page >= lastPage && items.length > 0"
            class="py-4 text-center text-xs text-gray-400"
          >
            End of transaction history.
          </p>
        </div>
      </div>
    </div>

    <Dialog :open="isLoadOpen" @update:open="isLoadOpen = $event">
      <DialogContent class="rounded-2xl sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold">Top-up Wallet</DialogTitle>
          <DialogDescription>Enter amount (Min. ₱100)</DialogDescription>
        </DialogHeader>
        <div class="py-4">
          <label class="mb-1 block text-sm font-medium">Amount</label>
          <div class="relative">
            <span
              class="absolute top-1/2 left-3 -translate-y-1/2 font-bold text-gray-400"
              >₱</span
            >
            <Input
              type="number"
              v-model="loadAmount"
              class="h-12 pl-8 text-xl font-bold"
              min="100"
            />
          </div>
        </div>
        <div class="grid gap-3">
          <Button
            @click="buyLoad"
            :disabled="loadAmount < 100 || isProcessingLoad"
            class="w-full bg-brand-blue hover:bg-blue-900"
          >
            <Loader2
              v-if="isProcessingLoad"
              class="mr-2 h-4 w-4 animate-spin"
            />
            Pay Now
          </Button>
          <Button variant="outline" @click="isLoadOpen = false" class="w-full"
            >Cancel</Button
          >
        </div>
      </DialogContent>
    </Dialog>

    <Dialog :open="isMapOpen" @update:open="isMapOpen = $event">
      <DialogContent class="overflow-hidden p-0 sm:max-w-[450px]">
        <div v-if="selectedLocationTx" class="p-0">
          <div class="border-b bg-gray-50 p-4">
            <p class="text-sm font-bold text-gray-900">
              {{ selectedLocationTx.description }}
            </p>
            <p class="text-xs text-gray-500">
              {{ selectedLocationTx.date }} • {{ selectedLocationTx.time }}
            </p>
          </div>
          <div class="relative h-[300px] w-full bg-gray-100">
            <LocationMap
              v-if="showMapContent"
              :locations="[
                {
                  id: selectedLocationTx.id,
                  latitude: Number(selectedLocationTx.latitude),
                  longitude: Number(selectedLocationTx.longitude),
                  type: 'Pin',
                  name: 'Transaction Location',
                },
              ]"
              :zoom="18"
              :center="[
                Number(selectedLocationTx.latitude),
                Number(selectedLocationTx.longitude),
              ]"
              :selectable="false"
            />
            <div v-else class="flex h-full items-center justify-center">
              <Loader2 class="h-8 w-8 animate-spin text-gray-400" />
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <Dialog :open="isPayBusOpen" @update:open="isPayBusOpen = $event">
      <DialogContent class="max-w-xl overflow-hidden rounded-2xl">
        <DialogHeader>
          <DialogTitle>Quick Bus Payment</DialogTitle>
          <DialogDescription>
            Enter the Bus Unique Code (e.g., EPINOY-00001)
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-4">
          <div class="relative">
            <Input
              v-model="busSearchQuery"
              placeholder="TYPE BUS CODE..."
              class="pr-10 font-bold uppercase transition-all duration-300 focus:ring-2"
              :class="busData ? 'border-green-500 ring-green-50' : ''"
              @input="busSearchQuery = $event.target.value.toUpperCase()"
            />
            <div class="absolute top-2.5 right-3 flex items-center">
              <Transition mode="out-in">
                <Loader2
                  v-if="isSearchingBus"
                  key="loading"
                  class="h-5 w-5 animate-spin text-slate-400"
                />
                <Check
                  v-else-if="busData"
                  key="success"
                  class="h-5 w-5 text-green-500"
                />
              </Transition>
            </div>
          </div>

          <Transition
            mode="out-in"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-2 opacity-0"
          >
            <div
              v-if="busData"
              key="results"
              class="rounded-xl border border-slate-100 bg-slate-50 p-4 shadow-inner"
            >
              <div class="mb-4 flex items-center justify-between border-b pb-2">
                <span
                  class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                  >Route Found</span
                >
                <span
                  class="rounded bg-brand-blue/10 px-2 py-0.5 text-[10px] font-bold text-brand-blue"
                >
                  {{ busData.route_stations.length }} Stops
                </span>
              </div>

              <div class="relative mb-6 ml-2">
                <div
                  class="absolute top-2 left-[11px] h-[calc(100%-24px)] w-0.5 border-l-2 border-dashed border-slate-200"
                ></div>
                <div
                  v-for="(stop, index) in busData.route_stations"
                  :key="index"
                  class="relative mb-6 pl-10 last:mb-0"
                >
                  <div
                    :class="[
                      'absolute top-0 left-0 z-10 flex h-6 w-6 items-center justify-center rounded-full border-2 border-white shadow-sm transition-transform duration-500',
                      stop.route_step === 1
                        ? 'bg-brand-blue'
                        : stop.unique_name?.toUpperCase() ===
                            busSearchQuery.toUpperCase()
                          ? 'scale-125 bg-red-500 shadow-lg shadow-red-200'
                          : 'bg-slate-200',
                    ]"
                  >
                    <div class="h-1.5 w-1.5 rounded-full bg-white"></div>
                  </div>
                  <p
                    :class="[
                      'text-xs font-bold transition-colors duration-300',
                      stop.unique_name?.toUpperCase() ===
                      busSearchQuery.toUpperCase()
                        ? 'text-red-600'
                        : 'text-slate-900',
                    ]"
                  >
                    {{ stop.name }}
                  </p>
                  <p class="text-[10px] leading-tight text-slate-500">
                    {{ stop.address || 'Terminal Station' }}
                  </p>
                </div>
              </div>

              <div
                class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 transition-all hover:shadow-md"
              >
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase">
                      Total Amount
                    </p>
                    <h2
                      class="text-3xl font-black text-brand-blue tabular-nums"
                    >
                      ₱{{ (busData.total_amount * passengerCount).toFixed(2) }}
                    </h2>
                  </div>
                  <div class="text-right">
                    <p
                      class="mb-1 text-[10px] font-black text-slate-400 uppercase"
                    >
                      Passengers
                    </p>
                    <div
                      class="flex items-center gap-3 rounded-lg border bg-slate-50 p-1"
                    >
                      <button
                        @click="passengerCount > 1 ? passengerCount-- : null"
                        class="flex h-7 w-7 items-center justify-center rounded bg-white shadow-sm transition-colors hover:bg-slate-100"
                      >
                        -
                      </button>
                      <span
                        class="w-4 text-center text-sm font-bold tabular-nums"
                        >{{ passengerCount }}</span
                      >
                      <button
                        @click="passengerCount++"
                        class="flex h-7 w-7 items-center justify-center rounded bg-white shadow-sm transition-colors hover:bg-slate-100"
                      >
                        +
                      </button>
                    </div>
                  </div>
                </div>

                <Transition
                  enter-active-class="transition duration-300 ease-out"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition duration-200 ease-in"
                >
                  <div
                    v-if="balanceError"
                    class="mt-4 flex flex-col gap-2 rounded-lg border border-red-200 bg-red-50 p-3"
                  >
                    <div class="flex items-center gap-2 text-red-700">
                      <AlertTriangle class="h-4 w-4 shrink-0" />
                      <p class="text-[11px] font-bold">{{ balanceError }}</p>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      class="h-8 w-full border-red-200 text-red-700 hover:bg-red-100"
                      @click="
                        isPayBusOpen = false;
                        isLoadOpen = true;
                      "
                    >
                      Top Up Wallet Now
                    </Button>
                  </div>
                </Transition>

                <Button
                  @click="confirmBusPayment"
                  :disabled="isProcessingPayment"
                  class="mt-4 w-full bg-brand-blue py-6 text-lg font-bold transition-all hover:bg-blue-900 active:scale-[0.98]"
                >
                  <Loader2
                    v-if="isProcessingPayment"
                    class="mr-2 h-5 w-5 animate-spin"
                  />
                  {{ isProcessingPayment ? 'Processing...' : 'Pay Now' }}
                </Button>
              </div>
            </div>

            <div
              v-else-if="busSearchQuery.length >= 3 && !isSearchingBus"
              key="no-results"
              class="flex flex-col items-center py-12 text-center"
            >
              <div class="mb-4 rounded-full bg-amber-50 p-4">
                <AlertTriangle class="h-10 w-10 animate-pulse text-amber-500" />
              </div>
              <p class="text-sm font-bold text-slate-600">Bus Code Not Found</p>
              <p class="mx-auto max-w-[200px] text-xs text-slate-400">
                We couldn't find a bus matching "{{ busSearchQuery }}".
              </p>
            </div>
          </Transition>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
