<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';

const props = defineProps<{
  driver: { id: number; username: string };
  periodLabel: string;
  breakdownTypes: string[];
  details: DetailedRevenueRow[];
  filters: {
    tab: 'franchise' | 'branch';
    franchise: string | null;
    branch: string | null;
    driver_id: string;
    period: 'daily' | 'weekly' | 'monthly';
    vehicle_type: string; // Ensure this is passed from Controller
  };
}>();

interface DetailedRevenueRow {
  id: number;
  invoice_no: string;
  amount: number | string;
  payment_date: string;
  revenue_breakdowns: Array<{
    total_earning: number | string;
    percentage_type: { name: string };
  }>;
}

const isExporting = ref<null | 'pdf' | 'excel' | 'csv'>(null);

// --- Dynamic Title ---
const pageTitle = computed(() => {
  return `${props.filters.vehicle_type} Transaction Breakdown`;
});

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Earning Report', href: owner.driverownerreport().url },
  { title: 'Details', href: '#' },
];

function handleExport(type: 'pdf' | 'excel' | 'csv') {
  if (isExporting.value) return;
  isExporting.value = type;

  const queryParams: Record<string, string> = {
    ...props.filters,
    driver_id: props.driver.id.toString(),
    payment_date: props.periodLabel,
    export_type: type,
  };

  const baseUrl = owner.driverownerreport_details.export().url;
  const url = new URL(baseUrl, window.location.origin);
  Object.keys(queryParams).forEach((key) => {
    if (queryParams[key]) url.searchParams.append(key, queryParams[key]);
  });

  window.location.href = url.toString();
  setTimeout(() => {
    isExporting.value = null;
  }, 3000);
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount || 0);
};

const getBreakdownAmount = (
  row: DetailedRevenueRow,
  typeName: string,
): number => {
  const dbKey = typeName.toLowerCase().replace(/\s/g, '_');
  const breakdown = row.revenue_breakdowns.find(
    (b) => b.percentage_type.name.toLowerCase() === dbKey,
  );
  return breakdown ? parseFloat(String(breakdown.total_earning)) : 0;
};

const calculateDriverEarning = (row: DetailedRevenueRow): number => {
  const totalBreakdowns = (row.revenue_breakdowns || []).reduce(
    (sum, b) => sum + parseFloat(String(b.total_earning)),
    0,
  );
  return Math.max(0, parseFloat(String(row.amount)) - totalBreakdowns);
};

const grandTotals = computed(() => {
  let totalAmount = 0,
    totalDriverEarning = 0;
  let totalBreakdowns = {} as Record<string, number>;
  props.breakdownTypes.forEach((t) => (totalBreakdowns[t] = 0));

  props.details.forEach((row) => {
    totalAmount += parseFloat(String(row.amount));
    props.breakdownTypes.forEach(
      (t) => (totalBreakdowns[t] += getBreakdownAmount(row, t)),
    );
    totalDriverEarning += calculateDriverEarning(row);
  });

  return {
    totalAmount: formatCurrency(totalAmount),
    breakdowns: Object.keys(totalBreakdowns).map((key) => ({
      name: key,
      value: formatCurrency(totalBreakdowns[key]),
    })),
    totalDriverEarning: formatCurrency(totalDriverEarning),
  };
});

const detailColumns = computed<ColumnDef<DetailedRevenueRow>[]>(() => {
  const cols: ColumnDef<DetailedRevenueRow>[] = [
    {
      accessorKey: 'invoice_no',
      header: 'Invoice No.',
      cell: (i) => h(Badge, { variant: 'outline' }, () => i.getValue()),
    },
    {
      accessorKey: 'payment_date',
      header: 'Date/Time',
      cell: (i) => new Date(i.getValue() as string).toLocaleString(),
    },
    {
      accessorKey: 'amount',
      header: 'Trip Amount',
      cell: (i) => formatCurrency(parseFloat(String(i.getValue()))),
    },
  ];
  props.breakdownTypes.forEach((type) => {
    cols.push({
      accessorKey: type,
      header: type,
      cell: ({ row }) => formatCurrency(getBreakdownAmount(row.original, type)),
    });
  });
  cols.push({
    accessorKey: 'driver_earning',
    header: 'Driver Net',
    cell: ({ row }) => formatCurrency(calculateDriverEarning(row.original)),
  });
  return cols;
});

const goBack = () => {
  router.get(owner.driverownerreport().url, {
    tab: props.filters.tab,
    period: props.filters.period,
    driver: props.filters.driver_id || 'all',
    vehicle_type: props.filters.vehicle_type, // Maintain the tab
    service: 'Trips',
  });
};
</script>

<template>
  <Head :title="pageTitle" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
      >
        <div
          class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <Button variant="outline" class="mb-4 sm:mb-0" @click="goBack"
              >← Back</Button
            >
            <h2
              class="mt-2 font-mono text-2xl font-bold tracking-tight uppercase"
            >
              {{ pageTitle }}
            </h2>
          </div>
          <div
            class="rounded-lg border border-primary/10 bg-primary/5 p-4 text-right"
          >
            <p class="text-lg font-semibold text-primary">
              {{ props.driver.username }}
            </p>
            <p class="font-mono text-sm text-muted-foreground">
              {{ props.periodLabel }}
            </p>
          </div>
        </div>

        <div
          class="mb-8 grid grid-cols-2 gap-4 rounded-lg bg-muted/50 p-4 shadow-inner sm:grid-cols-4 md:grid-cols-6"
        >
          <div class="col-span-2 border-r pr-4 sm:col-span-1">
            <p class="text-xs font-medium text-muted-foreground uppercase">
              Total Trips
            </p>
            <p class="text-xl font-bold">{{ grandTotals.totalAmount }}</p>
          </div>
          <div
            v-for="(item, index) in grandTotals.breakdowns"
            :key="item.name"
            class="col-span-2 border-r px-2 sm:col-span-1"
          >
            <p class="text-xs font-medium text-muted-foreground uppercase">
              {{ item.name }}
            </p>
            <p class="text-xl font-bold">{{ item.value }}</p>
          </div>
          <div class="col-span-2 px-2 sm:col-span-1">
            <p class="text-xs font-medium text-muted-foreground uppercase">
              Driver Net
            </p>
            <p class="text-xl font-bold text-green-600">
              {{ grandTotals.totalDriverEarning }}
            </p>
          </div>
        </div>

        <DataTable
          :columns="detailColumns"
          :data="props.details"
          search-placeholder="Search by Invoice No."
        >
          <template #custom-actions>
            <Button
              @click="handleExport('pdf')"
              :disabled="!!isExporting"
              variant="outline"
            >
              {{ isExporting === 'pdf' ? 'Exporting...' : 'PDF' }}
            </Button>
            <Button
              @click="handleExport('excel')"
              :disabled="!!isExporting"
              variant="outline"
            >
              {{ isExporting === 'excel' ? 'Exporting...' : 'Excel' }}
            </Button>
          </template>
        </DataTable>
      </div>
    </div>
  </AppLayout>
</template>
