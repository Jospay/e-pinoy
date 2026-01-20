<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { payout } from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
  dailyEarnings: number;
  dailyPayout: {
    date: string;
    cutoffTime: string;
    totalGrossFare: number;
    totalNetPayout: number;
    computedDistribution: Record<string, number>;
  };
}

const { dailyEarnings, dailyPayout } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Payout',
    href: payout().url,
  },
];

// dynamic label & description mapping
const labels: Record<string, string> = {
  driverAmount: 'Driver Share',
  ownerAmount: 'Franchise Owner Share',
  platformAmount: 'Platform Fee',
  maintenanceAmount: 'Maintenance & Ops',
};

const descriptions: Record<string, string> = {
  driverAmount: '60% of total net earnings',
  ownerAmount: '25% of total net earnings',
  platformAmount: '10% platform charge',
  maintenanceAmount: '5% service allocation',
};

// Computed property for top 2 items
const topDistribution = computed(() => {
  return Object.entries(dailyPayout.computedDistribution)
    .slice(0, 2)
    .map(([key, amount]) => ({ key, amount }));
});

// Computed property for full distribution breakdown
const distributionArray = computed(() => {
  return Object.entries(dailyPayout.computedDistribution).map(
    ([key, amount]) => ({
      key,
      amount,
    }),
  );
});

const cleanLabel = (str: string) => {
  return str.replace(/_/g, ' ');
};
</script>

<template>
  <Head title="Payout" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <!-- Header -->
      <div class="mb-2">
        <h1 class="mb-2 text-3xl font-bold">Daily Payout Distribution</h1>
        <p class="text-gray-600">
          Automated earnings breakdown for bank disbursement
        </p>
      </div>

      <!-- Top Statistics -->
      <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <!-- Total Gross Fare -->
        <div class="rounded-xl border p-4 dark:border-sidebar-border">
          <p class="text-sm text-gray-500">Total Gross Fare</p>
          <h2 class="mt-2 text-3xl font-bold">
            ₱{{
              dailyEarnings.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
            }}
          </h2>
          <p class="mt-1 text-xs text-gray-400">
            Cutoff: {{ dailyPayout.date }} • {{ dailyPayout.cutoffTime }}
          </p>
        </div>

        <!-- Dynamic Top Shares (first two items) -->
        <div
          v-for="item in topDistribution"
          :key="item.key"
          class="rounded-xl border p-4 dark:border-sidebar-border"
        >
          <p class="text-sm text-gray-500 capitalize">
            {{ cleanLabel(labels[item.key] || item.key) }}
          </p>
          <h2 class="mt-2 text-3xl font-bold">
            ₱{{
              item.amount.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
            }}
          </h2>
          <p class="mt-1 text-xs text-gray-400">
            {{ descriptions[item.key] || '' }}
          </p>
        </div>
      </div>

      <!-- Distribution Breakdown Panel -->
      <div
        class="relative flex-1 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border"
      >
        <h2 class="mb-4 text-xl font-semibold">Distribution Breakdown</h2>

        <div class="space-y-4">
          <div
            v-for="item in distributionArray"
            :key="item.key"
            class="flex items-center justify-between"
          >
            <div>
              <p class="font-medium capitalize">
                {{ cleanLabel(labels[item.key] || item.key) }}
              </p>
              <p class="text-sm text-gray-500">
                {{ descriptions[item.key] || '' }}
              </p>
            </div>
            <p class="font-semibold">
              ₱{{
                item.amount.toLocaleString('en-US', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2,
                })
              }}
            </p>
          </div>
        </div>

        <hr class="my-6" />

        <div class="flex items-center justify-between">
          <p class="text-lg font-semibold">Total Net Distribution</p>
          <p class="text-lg font-bold">
            ₱{{
              dailyPayout.totalNetPayout.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })
            }}
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
