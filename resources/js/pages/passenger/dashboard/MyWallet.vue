<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { ChevronLeft, ArrowRight, Wallet } from 'lucide-vue-next'; // add Wallet/CreditCard icons

interface Transaction {
  id: number;
  date: string;
  time: string;
  amount: string;
  balance: string;
  symbol: string;
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

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
});
</script>

<template>
  <Head title="My Wallet" />

  <AppLayout>
    <div class="space-y-6 px-3 py-12">
      <div class="mx-auto max-w-2xl">
        <!-- Wallet Balance -->
        <div
          class="flex items-center justify-center space-x-4 rounded-xl bg-white p-6 text-center shadow-md"
        >
          <!-- Wallet Icon -->
          <Wallet class="h-10 w-10 text-green-600" />

          <div class="text-left">
            <p class="text-sm text-gray-500">Wallet Balance</p>
            <h1 class="text-3xl font-bold text-green-600">
              ₱ {{ walletBalance }}
            </h1>
          </div>
        </div>

        <!-- Transaction History -->
        <div class="space-y-4 pt-8">
          <h2 class="text-lg font-bold">Transaction History</h2>

          <div
            v-for="item in items"
            :key="item.id"
            class="flex items-center justify-between rounded-xl bg-white p-4 shadow"
          >
            <div class="flex flex-col">
              <p class="font-semibold">{{ item.date }}</p>
              <p class="text-xs text-gray-500">{{ item.time }}</p>
              <p class="text-sm text-gray-500">Balance: ₱ {{ item.balance }}</p>
            </div>

            <div class="flex items-center space-x-2">
              <!-- Lucide Icon -->
              <component
                :is="item.symbol === '+' ? ArrowRight : ChevronLeft"
                :class="
                  item.symbol === '+'
                    ? 'h-5 w-5 text-green-600'
                    : 'h-5 w-5 text-red-600'
                "
              />
              <span
                class="text-lg font-bold"
                :class="item.symbol === '+' ? 'text-green-600' : 'text-red-600'"
              >
                {{ item.symbol }} ₱{{ item.amount }}
              </span>
            </div>
          </div>

          <div v-if="loading" class="text-center text-gray-500">
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
  </AppLayout>
</template>
