<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { ChevronLeft, ArrowRight, Wallet, MapPin } from 'lucide-vue-next';
import LocationMap from '@/components/ReservedMap.vue';
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

const items = ref<Transaction[]>([...props.transactions.data]);
const page = ref(props.transactions.current_page);
const lastPage = ref(props.transactions.last_page);
const loading = ref(false);

// Map Dialog State
const isMapOpen = ref(false);
const selectedLocationTx = ref<Transaction | null>(null);

async function loadMore() {
  if (loading.value || page.value >= lastPage.value) return;

  loading.value = true;
  page.value++;

  try {
    const response = await fetch(
      `/passenger/my-wallet/infinite?page=${page.value}`,
    );
    const data = await response.json();

    items.value.push(...data.data);
    lastPage.value = data.last_page;
  } catch (error) {
    console.error('Error loading transactions:', error);
  } finally {
    loading.value = false;
  }
}

function handleScroll() {
  const bottom =
    window.innerHeight + window.scrollY >= document.body.offsetHeight - 50;
  if (bottom) loadMore();
}

const openLocationMap = (tx: Transaction) => {
  if (tx.latitude && tx.longitude) {
    selectedLocationTx.value = tx;
    isMapOpen.value = true;
  }
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});
</script>

<template>
  <Head title="My Wallet" />

  <AppLayout>
    <div class="space-y-6 px-3 py-12">
      <div class="mx-auto max-w-2xl">
        <div
          class="flex items-center justify-center space-x-4 rounded-xl bg-white p-6 text-center shadow-md"
        >
          <Wallet class="h-10 w-10 text-brand-blue" />

          <div class="text-left">
            <p class="text-sm text-gray-500">Wallet Balance</p>
            <h1 class="text-3xl font-bold text-brand-blue">
              ₱ {{ walletBalance }}
            </h1>
          </div>
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
                  item.symbol === '+'
                    ? 'h-5 w-5 text-brand-blue'
                    : 'h-5 w-5 text-red-600'
                "
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

          <div v-if="loading" class="py-4 text-center text-gray-500">
            Loading more transactions...
          </div>

          <p
            v-if="items.length === 0 && !loading"
            class="text-center text-gray-500"
          >
            No transactions yet
          </p>
        </div>
      </div>
    </div>

    <Dialog :open="isMapOpen" @update:open="isMapOpen = $event">
      <DialogContent class="overflow-hidden p-0 sm:max-w-[450px]">
        <div v-if="selectedLocationTx" class="p-0">
          <DialogHeader>
            <DialogTitle></DialogTitle>
            <DialogDescription class="sr-only"></DialogDescription>
          </DialogHeader>

          <div class="bg-gray-50 p-4">
            <p class="text-sm font-bold text-gray-900">
              {{ selectedLocationTx.description }}
            </p>
            <p class="text-xs text-gray-500">
              {{ selectedLocationTx.date }} at {{ selectedLocationTx.time }}
            </p>
          </div>

          <div class="relative h-[200px] w-full">
            <LocationMap
              v-if="selectedLocationTx.latitude && selectedLocationTx.longitude"
              :locations="[
                {
                  id: selectedLocationTx.id,
                  latitude: Number(selectedLocationTx.latitude),
                  longitude: Number(selectedLocationTx.longitude),
                  type: 'Pin',
                  // name: selectedLocationTx.description || 'Transaction Point',
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

          <div class="px-4 py-2 text-center">
            <p class="text-[11px] text-gray-400 italic">
              This location represents where the transaction was authorized.
            </p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
