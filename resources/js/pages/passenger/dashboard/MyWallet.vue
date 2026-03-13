<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { toast } from 'vue-sonner';
import {
  ChevronLeft,
  ArrowRight,
  Wallet,
  MapPin,
  PlusCircle,
  Loader2,
} from 'lucide-vue-next';
import LocationMap from '@/components/ReservedMap.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';

interface Transaction {
  id: number;
  date: string;
  time: string;
  amount: string;
  balance: string;
  symbol: string;
  description: string;
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

// --- Buy Load Logic ---
const isLoadOpen = ref(false);
const loadAmount = ref(100);
const isProcessingLoad = ref(false);

const buyLoad = () => {
  isProcessingLoad.value = true;
  router.post(
    '/passenger/my-wallet/load',
    { amount: loadAmount.value },
    { onFinish: () => (isProcessingLoad.value = false) },
  );
};

watch(
  () => pageObj.props.flash,
  (flash: any) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
  },
  { deep: true, immediate: true },
);

// --- Map Logic ---
const isMapOpen = ref(false);
const selectedLocationTx = ref<Transaction | null>(null);

const openLocationMap = (tx: Transaction) => {
  if (tx.latitude && tx.longitude) {
    selectedLocationTx.value = tx;
    isMapOpen.value = true;
  }
};

// --- Infinite Scroll Logic ---
async function loadMore() {
  if (loading.value || page.value >= lastPage.value) return;

  loading.value = true;
  const nextPage = page.value + 1;

  try {
    const response = await fetch(
      `/passenger/my-wallet/infinite?page=${nextPage}`,
    );
    if (!response.ok) throw new Error('Network error');

    const data = await response.json();

    // Append unique items
    items.value = [...items.value, ...data.data];
    page.value = data.current_page;
    lastPage.value = data.last_page;
  } catch (error) {
    console.error('Error loading transactions:', error);
    toast.error('Failed to load more transactions.');
  } finally {
    loading.value = false;
  }
}

function handleScroll() {
  const scrollThreshold = 150; // Trigger before reaching the absolute bottom
  const isAtBottom =
    window.innerHeight + window.scrollY >=
    document.body.offsetHeight - scrollThreshold;

  if (isAtBottom && !loading.value) {
    loadMore();
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
  <Head title="My Wallet" />

  <AppLayout>
    <div class="space-y-6 px-3 py-12">
      <div class="mx-auto max-w-2xl">
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

          <Button
            @click="isLoadOpen = true"
            class="w-full rounded-xl bg-brand-blue px-6 py-6 font-bold hover:bg-brand-blue/90 md:w-auto"
          >
            <PlusCircle class="mr-2 h-5 w-5" />
            Buy Load
          </Button>
        </div>

        <div class="space-y-4 pt-8">
          <h2 class="text-lg font-bold">Transaction History</h2>

          <div
            v-for="item in items"
            :key="item.id"
            class="flex items-center justify-between rounded-xl bg-white p-4 shadow transition-all active:scale-[0.98]"
            @click="item.latitude ? openLocationMap(item) : null"
            :class="{ 'cursor-pointer hover:bg-gray-50': item.latitude }"
          >
            <div class="flex flex-1 flex-col">
              <div class="flex items-center gap-2">
                <p class="font-semibold">{{ item.date }}</p>
                <MapPin v-if="item.latitude" class="h-3 w-3 text-brand-blue" />
              </div>
              <p class="text-xs text-gray-500">
                {{ item.description || item.time }}
              </p>
              <p class="text-[10px] text-gray-400">
                Balance: ₱ {{ item.balance }}
              </p>
            </div>

            <div class="flex items-center space-x-2">
              <component
                :is="item.symbol === '+' ? ArrowRight : ChevronLeft"
                :class="
                  item.symbol === '+' ? 'text-brand-blue' : 'text-red-600'
                "
                class="h-5 w-5"
              />
              <span
                class="text-lg font-bold"
                :class="
                  item.symbol === '+' ? 'text-brand-blue' : 'text-red-600'
                "
              >
                {{ item.symbol }} ₱{{ item.amount }}
              </span>
            </div>
          </div>

          <div
            v-if="loading"
            class="flex items-center justify-center gap-2 py-4 text-center text-gray-500"
          >
            <Loader2 class="h-5 w-5 animate-spin" />
            Loading more...
          </div>

          <p
            v-if="!loading && page >= lastPage && items.length > 0"
            class="py-4 text-center text-xs text-gray-400"
          >
            No more transactions to show.
          </p>
        </div>
      </div>
    </div>

    <Dialog :open="isLoadOpen" @update:open="isLoadOpen = $event">
      <DialogContent class="rounded-2xl sm:max-w-[400px]">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold">Top-up Wallet</DialogTitle>
          <DialogDescription
            >Enter the amount to load via PayMongo. (Min.
            ₱100)</DialogDescription
          >
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
          <DialogHeader class="sr-only"
            ><DialogTitle>Transaction Map</DialogTitle></DialogHeader
          >
          <div class="bg-gray-50 p-4">
            <p class="text-sm font-bold text-gray-900">
              {{ selectedLocationTx.description }}
            </p>
            <p class="text-xs text-gray-500">
              {{ selectedLocationTx.date }} at {{ selectedLocationTx.time }}
            </p>
          </div>
          <div class="relative h-[250px] w-full">
            <LocationMap
              v-if="selectedLocationTx.latitude && selectedLocationTx.longitude"
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
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
