<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import Button from '@/components/ui/button/Button.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/manager';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { computed, h, ref } from 'vue';

// --- Define Props (UPDATED: Removed 'franchise' from filters) ---
const props = defineProps<{
  // Driver Details (for header context)
  driver: { id: number; username: string };
  periodLabel: string; // The formatted date string (e.g., "November 20, 2025")
  breakdownTypes: string[]; // e.g., ['Tax', 'Bank Fee', 'Markup Fee'] (clean, capitalized names)

  // Individual Revenue Records (The detailed data)
  details: DetailedRevenueRow[];

  // Filters used to generate the data (for context/back button)
  filters: {
    tab: 'branch';
    franchise?: string | null;
    branch: string | null;
    driver_id: string;
    period: 'daily' | 'weekly' | 'monthly';
  };
}>();

// --- Define DetailedRevenueRow Interface (UPDATED: Removed 'franchise' from optional fields) ---
interface DetailedRevenueRow {
  id: number;
  invoice_no: string;
  amount: number | string; // Total trip amount
  payment_date: string;
  // franchise?: { name: string } | null; // Removed
  branch?: { name: string } | null;
  driver?: { name: string } | null;
  revenue_breakdowns: Array<{
    total_earning: number | string;
    percentage_type: {
      name: string; // e.g., 'tax', 'bank_fee' (lowercase_snake_case)
    };
  }>;
}

// --- 1. Export State (Remains the same) ---
const isExporting = ref<null | 'pdf' | 'excel' | 'csv'>(null);

// --- 2. Setup Breadcrumbs (Remains the same) ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Earning Report',
    href: owner.driverownerreport().url,
  },
  {
    title: 'Details',
    href: '#',
  },
];

// --- 3. Export Logic: UPDATED to only check 'branch' filter ---
function handleExport(type: 'pdf' | 'excel' | 'csv') {
  // Prevent starting a new export while one is already in progress
  if (isExporting.value) {
    return;
  }

  // 1. Set loading state
  isExporting.value = type;

  // 2. Prepare base filters, ensuring no nulls are spread directly
  const baseFilters: Record<string, string> = {};

  // Iterate over relevant keys only (excluding franchise)
  const filterKeys: Array<keyof typeof props.filters> = [
    'tab',
    'branch',
    'driver_id',
    'period',
  ];

  filterKeys.forEach((key) => {
    const value = props.filters[key];
    if (value !== null && value !== undefined) {
      baseFilters[key] = String(value);
    }
  });

  // 3. Construct final query parameters
  const queryParams: Record<string, string> = {
    ...baseFilters,
    driver_id: props.driver.id.toString(),
    payment_date: props.periodLabel,
    export_type: type,
  };

  // 4. Construct the full URL (Remaining logic unchanged)
  const baseUrl = owner.driverownerreport_details.export().url;
  const url = new URL(baseUrl, window.location.origin);

  Object.keys(queryParams).forEach((key) => {
    url.searchParams.append(key, encodeURIComponent(queryParams[key]));
  });

  // 5. Force a direct browser download
  window.location.href = url.toString();

  // 6. Clear loading state after a brief delay
  setTimeout(() => {
    isExporting.value = null;
  }, 3000); // 3-second timeout for the file download to start
}
// --- End Export Logic ---

// --- 4. Helper Functions (Remains the same) ---
const formatCurrency = (amount: number): string => {
  if (isNaN(amount) || amount === null) {
    return '₱0.00';
  }
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

const getBreakdownAmount = (
  row: DetailedRevenueRow,
  typeName: string,
): number => {
  if (!row.revenue_breakdowns || row.revenue_breakdowns.length === 0) {
    return 0;
  }

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

  const rowAmount = parseFloat(String(row.amount));

  return Math.max(0, rowAmount - totalBreakdowns);
};

// --- 5. Computed Properties for Grand Totals (Remains the same) ---
const grandTotals = computed(() => {
  let totalAmount = 0;
  let totalBreakdowns = {} as Record<string, number>;
  let totalDriverEarning = 0;

  props.breakdownTypes.forEach((type) => {
    totalBreakdowns[type] = 0;
  });

  props.details.forEach((row) => {
    totalAmount += parseFloat(String(row.amount));

    props.breakdownTypes.forEach((type) => {
      const breakdownValue = getBreakdownAmount(row, type);
      totalBreakdowns[type] += breakdownValue;
    });

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

// --- 6. Define Columns for DataTable (Remains the same) ---
const detailColumns = computed<ColumnDef<DetailedRevenueRow>[]>(() => {
  const columns: ColumnDef<DetailedRevenueRow>[] = [
    {
      accessorKey: 'invoice_no',
      header: 'Invoice No.',
      minSize: 100,
      cell: (info) => h(Badge, { variant: 'outline' }, () => info.getValue()),
    },
    {
      accessorKey: 'payment_date',
      header: 'Date/Time',
      minSize: 150,
      cell: (info) => {
        const date = info.getValue() as string;
        return new Date(date).toLocaleString('en-US', {
          month: 'short',
          day: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          hour12: true,
        });
      },
    },
    {
      accessorKey: 'amount',
      header: 'Total Trip Amount',
      minSize: 150,
      cell: (info) => formatCurrency(parseFloat(String(info.getValue()))),
    },
  ];

  // Dynamically add breakdown columns
  props.breakdownTypes.forEach((type) => {
    columns.push({
      accessorKey: type,
      header: type,
      minSize: 100,
      cell: ({ row }) => {
        const amount = getBreakdownAmount(row.original, type);
        return formatCurrency(amount);
      },
    });
  });

  // Add Driver Earning column
  columns.push({
    accessorKey: 'driver_earning',
    header: 'Driver Earning',
    minSize: 150,
    cell: ({ row }) => {
      const earning = calculateDriverEarning(row.original);
      return formatCurrency(earning);
    },
  });

  return columns;
});

// --- 7. Go Back Function (UPDATED: Removed franchise check) ---
const goBack = () => {
  const queryParams: Record<string, string> = {
    // Set tab to 'branch' explicitly (or remove if not required by index)
    tab: 'branch',
    period: props.filters.period,
    driver: props.filters.driver_id || 'all',
    service: 'Trips', // Assuming service is always 'Trips' for this report
  };

  // Only apply the branch filter if it exists
  if (props.filters.branch) {
    queryParams.branch = props.filters.branch;
  }
  // Removed franchise check

  // Navigates back to the aggregated report index
  router.get(owner.driverownerreport().url, queryParams);
};
</script>

<template>
  <Head title="Transaction Breakdown" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div
          class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <Button variant="outline" class="mb-4 sm:mb-0" @click="goBack">
              ← Back
            </Button>
            <h2 class="mt-2 font-mono text-2xl font-bold">
              Transaction Breakdown
            </h2>
          </div>
          <div class="text-right">
            <p class="text-lg font-semibold text-primary">
              Driver: {{ props.driver.username }}
            </p>
            <p class="text-sm text-muted-foreground">
              Period: {{ props.periodLabel }}
            </p>
          </div>
        </div>

        <div
          class="mb-8 grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 shadow-inner sm:grid-cols-4 md:grid-cols-6 dark:bg-gray-900"
        >
          <div class="col-span-2 border-r pr-4 sm:col-span-1 sm:pr-2">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              Total Trips Amount
            </p>
            <p class="text-xl font-bold text-green-600 dark:text-green-400">
              {{ grandTotals.totalAmount }}
            </p>
          </div>

          <div
            v-for="(item, index) in grandTotals.breakdowns"
            :key="item.name"
            class="col-span-2 sm:col-span-1"
            :class="{
              'border-r pr-4 sm:pr-2':
                index < grandTotals.breakdowns.length - 1,
            }"
          >
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              Total {{ item.name }}
            </p>
            <p class="text-xl font-bold">
              {{ item.value }}
            </p>
          </div>

          <div class="col-span-2 sm:col-span-1">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
              Total Driver Earning
            </p>
            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
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
            <Button @click="handleExport('pdf')" :disabled="!!isExporting">
              <span v-if="isExporting === 'pdf'"> PDF to Exporting... </span>
              <span v-else> Export PDF </span>
            </Button>
            <Button @click="handleExport('excel')" :disabled="!!isExporting">
              <span v-if="isExporting === 'excel'">
                Excel to Exporting...
              </span>
              <span v-else> Export Excel </span>
            </Button>
            <Button @click="handleExport('csv')" :disabled="!!isExporting">
              <span v-if="isExporting === 'csv'"> CSV to Exporting... </span>
              <span v-else> Export CSV </span>
            </Button>
          </template>
        </DataTable>
      </div>
    </div>
  </AppLayout>
</template>
