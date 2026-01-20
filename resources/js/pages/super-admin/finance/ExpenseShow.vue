<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { computed } from 'vue';

// Define Props
const props = defineProps<{
  details: {
    data: any[];
  };
  periodLabel: string;
  targetName: string;
  totalSum: number;
  filters: {
    franchise?: string[] | string;
    period: 'daily' | 'weekly' | 'monthly';
  };
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Expense Report', href: superAdmin.expense.index().url },
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

  // Helper to safely get first value
  const getFirstValue = (value?: string[] | string) => {
    if (Array.isArray(value)) {
      return value[0] || '';
    }
    return value || '';
  };

  const params = new URLSearchParams({
    start: startDate || '',
    end: endDate || '',
    label: props.periodLabel,
    export: type,
    period: props.filters.period,
    franchise: getFirstValue(props.filters.franchise),
  });

  // 2. Open URL
  const url = `${superAdmin.expense.export.show().url}?${params.toString()}`;
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
      accessorKey: 'description',
      header: 'Description',
    },
    {
      accessorKey: 'inventory_name',
      header: 'Inventory',
    },
    {
      accessorKey: 'plate_number',
      header: 'Plate Number',
    },
    {
      accessorKey: 'payment_date',
      header: 'Date',
    },
    {
      accessorKey: 'amount',
      header: 'Amount',
      cell: (info) => formatCurrency(Number(info.getValue())),
    },
  ];

  return cols;
});

const goBack = () => {
  window.history.back();
};
</script>

<template>

  <Head title="Expense Details" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <Button variant="outline" class="mb-4 gap-2 sm:mb-0" @click="goBack">
              <span>&larr;</span> Back
            </Button>
            <h2 class="mt-4 font-mono text-2xl font-bold">Expense Details</h2>
          </div>
        </div>

        <div
          class="mb-8 grid grid-cols-2 gap-4 rounded-lg border bg-gray-50 p-4 sm:grid-cols-3 lg:grid-cols-4 dark:bg-zinc-900/50">
          <div class="col-span-1 border-r border-dashed border-gray-300 pr-4 dark:border-gray-700">
            <p class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">
              Total Transactions
            </p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ props.details.data.length }}
            </p>
          </div>

          <div class="col-span-1 border-r border-dashed border-gray-300 pr-4 dark:border-gray-700">
            <p class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">
              Total Expense
            </p>
            <p class="text-xl font-bold text-green-600">
              {{ formatCurrency(props.totalSum) }}
            </p>
          </div>

          <div class="col-span-1">
            <p class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
              {{ targetName }}
            </p>
            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
              {{ periodLabel }}
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
