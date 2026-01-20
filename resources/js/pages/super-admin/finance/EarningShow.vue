<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { computed, h } from 'vue';

// Define Props
const props = defineProps<{
  details: {
    data: any[];
  };
  driver: { id: number; username: string };
  periodLabel: string;
  feeTypes: {
    id: number;
    db_name: string;
    slug: string;
    display: string;
  }[];
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Earning Report', href: superAdmin.earning.index().url },
  { title: 'Details', href: '#' },
];

// Helper to get params from URL if not in props
const getUrlParam = (name: string) => {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
};

const handleExport = (type: 'pdf' | 'excel' | 'csv') => {
  // 1. Get Filters
  const startDate = getUrlParam('start');
  const endDate = getUrlParam('end');

  const params = new URLSearchParams({
    driver: String(props.driver.id),
    start: startDate || '',
    end: endDate || '',
    label: props.periodLabel,
    export: type,
  });

  // 2. Open URL
  const url = `${superAdmin.earning.export.show().url}?${params.toString()}`;
  window.open(url, '_blank');
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

// --- Dynamic Column Definitions ---
const detailColumns = computed<ColumnDef<any>[]>(() => {
  const cols: ColumnDef<any>[] = [
    {
      accessorKey: 'invoice_no',
      header: 'Invoice No.',
    },
    {
      accessorKey: 'payment_date',
      header: 'Date',
    },
    {
      accessorKey: 'total_amount',
      header: 'Trip Amount',
      cell: (info) => formatCurrency(Number(info.getValue())),
    },
  ];

  // Dynamic Fees Columns
  props.feeTypes.forEach((type) => {
    cols.push({
      id: type.slug, // Use slug for ID
      accessorFn: (row) => row.fees?.[type.slug], // Access the 'fees' object created in Resource
      header: type.display, // Display Name (e.g., "Markup Fee")
      cell: ({ getValue }) => {
        const val = getValue() as number;
        // Optional: Grey out 0 values for cleaner UI
        return val > 0 ? formatCurrency(val) : '-';
        // return formatCurrency(val || 0);
      },
    });
  });

  // Driver Earning
  cols.push({
    accessorKey: 'driver_earning',
    header: 'Driver Net',
    cell: (info) => h('span', formatCurrency(Number(info.getValue()))),
  });

  return cols;
});

// --- Grand Total Calculations (Client Side) ---
const grandTotals = computed(() => {
  const totals = {
    totalAmount: 0,
    totalDriverEarning: 0,
    breakdowns: props.feeTypes.map((type) => ({
      name: type.display,
      slug: type.slug,
      value: 0,
    })),
  };

  props.details.data.forEach((row) => {
    totals.totalAmount += Number(row.total_amount);
    totals.totalDriverEarning += Number(row.driver_earning);

    // Sum up specific fees
    totals.breakdowns.forEach((breakdown) => {
      breakdown.value += Number(row.fees?.[breakdown.slug] || 0);
    });
  });

  return {
    totalAmount: formatCurrency(totals.totalAmount),
    totalDriverEarning: formatCurrency(totals.totalDriverEarning),
    breakdowns: totals.breakdowns.map((b) => ({
      name: b.name,
      value: formatCurrency(b.value),
    })),
  };
});

const goBack = () => {
  window.history.back();
};
</script>

<template>

  <Head title="Earning Computation Details" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <Button variant="outline" class="mb-4 gap-2 sm:mb-0" @click="goBack">
              <span>&larr;</span> Back
            </Button>
            <h2 class="mt-4 font-mono text-2xl font-bold">
              Transaction Breakdown
            </h2>
          </div>
          <div class="rounded-lg border border-primary/10 bg-primary/5 p-4 text-right">
            <p class="text-lg font-semibold text-primary">
              {{ props.driver.username }}
            </p>
            <p class="font-mono text-sm text-muted-foreground">
              {{ props.periodLabel }}
            </p>
          </div>
        </div>

        <div
          class="mb-8 grid grid-cols-2 gap-4 rounded-lg border bg-gray-50 p-4 sm:grid-cols-3 lg:grid-cols-6 dark:bg-zinc-900/50">
          <div class="col-span-2 border-r border-dashed border-gray-300 pr-4 sm:col-span-1 dark:border-gray-700">
            <p class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">
              Total Trips
            </p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ grandTotals.totalAmount }}
            </p>
          </div>

          <div v-for="item in grandTotals.breakdowns" :key="item.name" class="col-span-1">
            <p class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
              {{ item.name }}
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ item.value }}
            </p>
          </div>

          <div class="col-span-2 -m-2 flex flex-col justify-center p-2 sm:col-span-1">
            <p class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
              Driver Earning
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ grandTotals.totalDriverEarning }}
            </p>
          </div>
        </div>

        <DataTable :columns="detailColumns" :data="props.details.data" search-placeholder="Search Invoice No...">
          <template #custom-actions>
            <Button @click="handleExport('pdf')"> Export PDF </Button>
            <Button @click="handleExport('excel')"> Export Excel </Button>
            <Button @click="handleExport('csv')"> Export CSV </Button>
          </template>
        </DataTable>
      </div>
    </div>
  </AppLayout>
</template>
