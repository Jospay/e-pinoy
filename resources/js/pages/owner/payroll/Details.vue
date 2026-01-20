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

// --- DIALOG IMPORTS ---
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
// --- END DIALOG IMPORTS ---

// --- MAP IMPORTS ---
import LeafletMap, { type MarkerData } from '@/components/LocationMap.vue'; // ASSUMED PATH
// --- END MAP IMPORTS ---

// --- Define Full Route Detail Interface ---
interface FullRouteDetail {
  start_trip: string;
  end_trip: string;
  start_lat: number | string;
  start_lng: number | string;
  end_lat: number | string;
  end_lng: number | string;
  distance_km: number | string;
  average_speed_kmh: number | string;
  max_speed_kmh: number | string;
}
// --- END Full Route Detail Interface ---

// --- Define DetailedRevenueRow Interface ---
interface DetailedRevenueRow {
  id: number;
  invoice_no: string;
  amount: number | string;
  payment_date: string;
  driver_id: number;
  revenue_breakdowns: Array<{
    total_earning: number | string;
    percentage_type: {
      name: string;
    };
  }>;
  route_data: FullRouteDetail | null;
}

// --- Define Props ---
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
  };
}>();

// --- STATE FOR MODAL ---
const showRouteModal = ref(false);
const selectedRouteData = ref<{
  route: FullRouteDetail;
  invoiceNo: string;
} | null>(null);
// --- END STATE FOR MODAL ---

const initializedDetails = computed(() => props.details);
const isExporting = ref<null | 'pdf' | 'excel' | 'csv'>(null);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Driver Payroll Summary',
    href: owner.driverownerreport().url,
  },
  {
    title: 'Details',
    href: '#',
  },
];

// --- Helper Functions ---

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

const calculateTotalDeduction = (row: DetailedRevenueRow): number => {
  return (row.revenue_breakdowns || []).reduce(
    (sum, b) => sum + parseFloat(String(b.total_earning)),
    0,
  );
};

const calculateDriverEarning = (row: DetailedRevenueRow): number => {
  const totalDeduction = calculateTotalDeduction(row);
  const rowAmount = parseFloat(String(row.amount));
  return Math.max(0, rowAmount - totalDeduction);
};

/**
 * Handles the click on "View Details" button.
 */
function handleViewDetails(row: DetailedRevenueRow) {
  if (row.route_data) {
    selectedRouteData.value = {
      route: row.route_data,
      invoiceNo: row.invoice_no,
    };
    showRouteModal.value = true;
  }
}

// --- Utility function for safe number parsing in template ---
const parseToFloat = (value: number | string | undefined | null): number => {
  const num = parseFloat(String(value));
  return isNaN(num) ? 0 : num;
};

const parseAndFix = (
  value: number | string | undefined | null,
  decimals: number = 2,
): string => {
  return parseToFloat(value).toFixed(decimals);
};
// --- End Helper Functions ---

// 🚀 NEW: Computed property to prepare data for LeafletMap component
const mapLocations = computed<MarkerData[]>(() => {
  if (!selectedRouteData.value) {
    return [];
  }

  const route = selectedRouteData.value.route;
  const startLat = parseToFloat(route.start_lat);
  const startLng = parseToFloat(route.start_lng);
  const endLat = parseToFloat(route.end_lat);
  const endLng = parseToFloat(route.end_lng);

  // Return an array of MarkerData objects for the map
  return [
    {
      id: 1,
      latitude: startLat,
      longitude: startLng,
      type: 'Start',
      isOnline: true, // Use green icon for start
    },
    {
      id: 2,
      latitude: endLat,
      longitude: endLng,
      type: 'End',
      isOnline: false, // Use red icon for end
    },
  ].filter((location) => location.latitude !== 0 && location.longitude !== 0);
});
// --- End Map Data Preparation ---

// --- DataTable Columns and other existing functions (No changes needed) ---
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
    {
      accessorKey: 'total_deduction',
      header: 'Deduction',
      minSize: 100,
      cell: ({ row }) => {
        const amount = calculateTotalDeduction(row.original);
        return formatCurrency(amount);
      },
    },
    {
      accessorKey: 'driver_earning',
      header: 'Driver Earning',
      minSize: 150,
      cell: ({ row }) => {
        const earning = calculateDriverEarning(row.original);
        return formatCurrency(earning);
      },
    },
    {
      id: 'action',
      header: 'Action',
      cell: ({ row }) => {
        const hasRouteData = !!row.original.route_data;

        return h(
          Button,
          {
            class: 'py-1 px-2 text-xs',
            onClick: () => handleViewDetails(row.original),
            disabled: !hasRouteData,
          },
          () => (hasRouteData ? 'View Details' : 'No Route Data'),
        );
      },
    },
  ];

  return columns;
});

const grandTotals = computed(() => {
  let totalAmount = 0;
  let totalDeduction = 0;
  let totalDriverEarning = 0;

  props.details.forEach((row) => {
    totalAmount += parseToFloat(row.amount);
    const rowDeduction = calculateTotalDeduction(row);
    totalDeduction += rowDeduction;
    totalDriverEarning += calculateDriverEarning(row);
  });

  return {
    totalAmount: formatCurrency(totalAmount),
    totalDeduction: formatCurrency(totalDeduction),
    totalDriverEarning: formatCurrency(totalDriverEarning),
  };
});

// --- Export and Go Back functions (Unchanged) ---
function handleExport(type: 'pdf' | 'excel' | 'csv') {
  if (isExporting.value) return;
  isExporting.value = type;
  const baseFilters: Record<string, string> = {};
  for (const key in props.filters) {
    const value = props.filters[key as keyof typeof props.filters];
    if (value !== null && value !== undefined) {
      baseFilters[key] = String(value);
    }
  }
  const queryParams: Record<string, string> = {
    ...baseFilters,
    driver_id: props.driver.id.toString(),
    payment_date: props.periodLabel,
    export_type: type,
  };
  const baseUrl = owner.driverownerpayroll_details.export().url;
  const url = new URL(baseUrl, window.location.origin);
  Object.keys(queryParams).forEach((key) => {
    url.searchParams.append(key, encodeURIComponent(queryParams[key]));
  });
  window.location.href = url.toString();
  setTimeout(() => {
    isExporting.value = null;
  }, 3000);
}

const goBack = () => {
  const queryParams: Record<string, string> = {
    tab: props.filters.tab,
    period: props.filters.period,
    driver: props.filters.driver_id || 'all',
    service: 'Trips',
  };
  if (props.filters.franchise) {
    queryParams.franchise = props.filters.franchise;
  } else if (props.filters.branch) {
    queryParams.branch = props.filters.branch;
  }
  router.get(owner.driverownerpayroll().url, queryParams);
};
</script>

<template>
  <Head title="Driver Payroll Report" />
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
              Driver Payroll Report
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
          class="mb-8 grid grid-cols-4 gap-4 rounded-lg bg-gray-50 p-4 shadow-inner sm:grid-cols-4 md:grid-cols-8 dark:bg-gray-900"
        >
          <div class="col-span-4 border-r pr-4 sm:col-span-2 sm:pr-2">
            <p
              class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
            >
              Total Trips
            </p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ grandTotals.totalAmount }}
            </p>
          </div>

          <div class="col-span-4 border-r pr-4 sm:col-span-2 sm:pr-2">
            <p
              class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
            >
              Deduction
            </p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ grandTotals.totalDeduction }}
            </p>
          </div>

          <div class="col-span-4 border-r sm:col-span-2">
            <p
              class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
            >
              Driver Earning
            </p>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100">
              {{ grandTotals.totalDriverEarning }}
            </p>
          </div>

          <div class="col-span-4 sm:col-span-2">
            <p
              class="text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
            >
              Export Payroll Driver
            </p>
            <Button @click="handleExport('pdf')" :disabled="!!isExporting">
              <span v-if="isExporting === 'pdf'"> PDF to Exporting... </span>
              <span v-else> Export PDF </span>
            </Button>
          </div>
        </div>

        <DataTable
          :columns="detailColumns"
          :data="initializedDetails"
          search-placeholder="Search by Invoice No."
        >
        </DataTable>
      </div>
    </div>
  </AppLayout>

  <Dialog :open="showRouteModal" @update:open="showRouteModal = $event">
    <DialogContent class="sm:max-w-[425px] md:max-w-2xl lg:max-w-4xl">
      <DialogHeader>
        <DialogTitle class="text-xl font-bold text-primary">
          Route Details: {{ props.driver.username }}
        </DialogTitle>
        <DialogDescription v-if="selectedRouteData">
          Data for Invoice No. **{{ selectedRouteData.invoiceNo }}**
        </DialogDescription>
        <DialogDescription v-else> Loading route data... </DialogDescription>
      </DialogHeader>

      <div v-if="selectedRouteData" class="space-y-6 py-4">
        <div class="h-[400px] w-full">
          <LeafletMap :locations="mapLocations" :fit-bounds="true" :zoom="12">
            <template #popup="{ item }">
              <div class="p-2">
                <span
                  :class="[
                    item.type === 'Start' ? 'text-green-600' : 'text-red-600',
                    'font-bold',
                  ]"
                >
                  {{ item.type }} Location
                </span>
                <p class="mt-1 text-xs">
                  Lat: {{ parseAndFix(item.latitude, 8) }}
                </p>
                <p class="text-xs">Lng: {{ parseAndFix(item.longitude, 8) }}</p>
              </div>
            </template>
          </LeafletMap>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <div class="flex flex-col space-y-1">
            <span class="text-sm font-medium text-muted-foreground">
              Total Distance
            </span>
            <span class="text-lg font-semibold">
              {{ parseAndFix(selectedRouteData.route.distance_km, 2) }} km
            </span>
          </div>
          <div class="flex flex-col space-y-1">
            <span class="text-sm font-medium text-muted-foreground">
              Average Speed
            </span>
            <span class="text-lg font-semibold">
              {{ parseAndFix(selectedRouteData.route.average_speed_kmh, 2) }}
              km/h
            </span>
          </div>
          <div class="flex flex-col space-y-1">
            <span class="text-sm font-medium text-muted-foreground">
              Max Speed
            </span>
            <span class="text-lg font-semibold">
              {{ parseAndFix(selectedRouteData.route.max_speed_kmh, 2) }} km/h
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div class="rounded-lg border p-3 shadow-sm">
            <p class="mb-1 text-sm font-medium text-green-600">Start Trip</p>
            <p class="font-mono text-base">
              {{
                new Date(selectedRouteData.route.start_trip).toLocaleString()
              }}
            </p>
            <div class="mt-2 space-y-1">
              <p class="text-xs text-muted-foreground">
                <span class="font-semibold text-green-700 dark:text-green-500"
                  >Latitude:</span
                >
                {{ parseAndFix(selectedRouteData.route.start_lat, 8) }}
              </p>
              <p class="text-xs text-muted-foreground">
                <span class="font-semibold text-green-700 dark:text-green-500"
                  >Longitude:</span
                >
                {{ parseAndFix(selectedRouteData.route.start_lng, 8) }}
              </p>
            </div>
          </div>

          <div class="rounded-lg border p-3 shadow-sm">
            <p class="mb-1 text-sm font-medium text-red-600">End Trip</p>
            <p class="font-mono text-base">
              {{ new Date(selectedRouteData.route.end_trip).toLocaleString() }}
            </p>
            <div class="mt-2 space-y-1">
              <p class="text-xs text-muted-foreground">
                <span class="font-semibold text-red-700 dark:text-red-500"
                  >Latitude:</span
                >
                {{ parseAndFix(selectedRouteData.route.end_lat, 8) }}
              </p>
              <p class="text-xs text-muted-foreground">
                <span class="font-semibold text-red-700 dark:text-red-500"
                  >Longitude:</span
                >
                {{ parseAndFix(selectedRouteData.route.end_lng, 8) }}
              </p>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="p-8 text-center text-red-500">
        Error: Route data is missing or not loading. Check console for errors.
      </div>

      <DialogFooter>
        <Button variant="outline" @click="showRouteModal = false">
          Close
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
