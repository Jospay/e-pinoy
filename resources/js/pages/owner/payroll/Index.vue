<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import Button from '@/components/ui/button/Button.vue';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
// Removed Tabs import as it's no longer needed
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  revenues: {
    data: RevenueRow[];
  };
  drivers: { id: number; username: string }[];
  filters: {
    driver: string | null;
    service: 'Trips';
    period: 'daily' | 'weekly' | 'monthly'; // RESTORED: Period filter definition
  };
}>();

// --- Define RevenueRow Interface ---
interface RevenueRow {
  id: number | null;
  franchise_name?: string;
  invoice_no?: string;
  driver_id: number;
  amount: number;
  payment_date: string; // This now holds the formatted period
  service_type: string;
  driver_name: string;
  driver?: {
    id: number;
    username: string;
  };
  driver_username?: string;
  week_sort?: string; // RESTORED for Weekly/Monthly sorting
  month_sort?: string; // RESTORED for Monthly sorting
  total_deduction?: number; // << ADDED: New field for combined deduction
  // Dynamic breakdown fields (e.g., tax, bank, driver_earning)
  [key: string]: any;
}

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Payroll Management',
    href: owner.driverownerpayroll().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedDriver = ref(props.filters.driver || 'all');
const selectedService = ref(props.filters.service);
const selectedPeriod = ref(props.filters.period); // RESTORED: Period state

// --- 5. Computed Properties for UI ---
const title = computed(() => {
  // Simplified title since it's only for the owner's franchise now
  return 'Driver Payroll Summary';
});

const showExportModal = ref(false);
const exportType = ref<'pdf' | 'excel' | 'csv'>('pdf');
const exportYear = ref(String(new Date().getFullYear()));
const exportMonths = ref<number[]>([]); // Initial state is an empty array (unchecked)

// Data for modal selects 2025 is minimum up to latest year
const yearOptions = computed(() => {
  const current = new Date().getFullYear();
  const start = 2025;
  return Array.from({ length: current - start + 1 }, (_, i) =>
    String(start + i),
  );
});

const monthOptions = [
  { id: 1, label: 'January' },
  { id: 2, label: 'February' },
  { id: 3, label: 'March' },
  { id: 4, label: 'April' },
  { id: 5, label: 'May' },
  { id: 6, label: 'June' },
  { id: 7, label: 'July' },
  { id: 8, label: 'August' },
  { id: 9, label: 'September' },
  { id: 10, label: 'October' },
  { id: 11, label: 'November' },
  { id: 12, label: 'December' },
];

// Open modal and set the export type
function openExportModal(type: 'pdf' | 'excel' | 'csv') {
  exportType.value = type;
  exportMonths.value = [];
  showExportModal.value = true;
}

// Handle checkbox-style "multi-select" for months
function toggleMonth(monthId: number) {
  const index = exportMonths.value.indexOf(monthId);
  if (index > -1) {
    exportMonths.value.splice(index, 1);
  } else {
    exportMonths.value.push(monthId);
  }
}

// Build and trigger the download URL
function handleExport() {
  if (!exportYear.value || exportMonths.value.length === 0) {
    return;
  }

  // 1. Get all *current* page filters
  const params = new URLSearchParams({
    service: selectedService.value,
    period: selectedPeriod.value, // RESTORED: Add period to export params
    export_type: exportType.value,
    year: exportYear.value,
  });

  if (selectedDriver.value && selectedDriver.value !== 'all') {
    params.append('driver', selectedDriver.value);
  }

  // 2. Add months
  exportMonths.value.forEach((month) => {
    params.append('months[]', String(month));
  });

  // 3. Build URL and open in new tab (triggers download)
  const url = `${owner.driverownerpayroll.export().url}?${params.toString()}`;
  window.open(url, '_blank');

  // 4. Close modal and reset
  showExportModal.value = false;
  exportMonths.value = [];
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Computed columns for the data table
const revenueColumns = computed<ColumnDef<RevenueRow>[]>(() => {
  const isDaily = selectedPeriod.value === 'daily'; // RESTORED: Period check

  // Base columns
  const columns: ColumnDef<RevenueRow>[] = [
    {
      accessorKey: 'driver_username',
      header: 'Driver Username',
    },
    {
      // Now explicitly show Franchise Name since the data is tied to one
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'payment_date',
      header: isDaily ? 'Date' : 'Period', // RESTORED: Dynamic header
    },
    {
      accessorKey: 'amount',
      header: 'Total Amount',
      cell: (info) => formatCurrency(info.getValue() as number),
    },
  ];

  // --- START: Deduction and Driver Earning columns logic ---

  // 1. Add the new Deduction column using the aggregated value from the backend
  columns.push({
    accessorKey: 'total_deduction',
    header: 'Deduction',
    cell: (info) => formatCurrency(info.getValue() as number),
  });

  // 2. Add the Driver Earning column, calculating the value using total_deduction
  columns.push({
    accessorKey: 'driver_earning',
    header: 'Driver Earning',
    cell: (info) => {
      const rowData = info.row.original as RevenueRow;
      // Use the 'total_deduction' field which is now provided by the backend query
      const totalDeduction = parseFloat(rowData.total_deduction as any) || 0;

      const driverEarning = rowData.amount - totalDeduction;

      return formatCurrency(Math.max(0, driverEarning));
    },
  });
  // --- END: Deduction and Driver Earning columns logic ---

  // 3. Add the action button column
  // columns.push({
  //   accessorKey: 'action',
  //   header: 'Action',
  //   cell: (info) => {
  //     const rowData = info.row.original as RevenueRow;

  //     return h(
  //       Button,
  //       {
  //         class: 'py-1 px-2 text-xs',
  //         onClick: () => {
  //           const queryParams: Record<string, string> = {
  //             driver_id: String(rowData.driver_id),
  //             payment_date: rowData.payment_date,
  //             period: selectedPeriod.value,
  //           };

  //           router.get(owner.driverownerpayroll.details().url, queryParams, {
  //             preserveScroll: true,
  //             replace: false,
  //           });
  //         },
  //       },
  //       () => 'View Payroll',
  //     );
  //   },
  // });

  columns.push({
    id: 'actions',
    header: () => h('div', { class: 'text-center' }, 'Action'),
    cell: ({ row }) => {
      const rowData = row.original as RevenueRow;

      return h('div', { class: 'flex justify-center' }, [
        h(DropdownMenu, null, () => [
          // The Trigger (The 3-dots button)
          h(DropdownMenuTrigger, { asChild: true }, () =>
            h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () => [
              h(MoreHorizontal, { class: 'h-4 w-4' }),
            ]),
          ),

          // The Content (The menu that pops up)
          h(DropdownMenuContent, { align: 'end', class: 'w-48' }, () => [
            h(DropdownMenuLabel, null, () => 'Actions'),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer',
                // Use onSelect for DropdownMenuItem to ensure it triggers correctly
                onSelect: () => {
                  const queryParams: Record<string, string> = {
                    driver_id: String(rowData.driver_id),
                    payment_date: rowData.payment_date,
                    period: selectedPeriod.value,
                  };

                  router.get(
                    owner.driverownerpayroll.details().url,
                    queryParams,
                    {
                      preserveScroll: true,
                      replace: false,
                    },
                  );
                },
              },
              () => 'View Payroll Details',
            ),
          ]),
        ]),
      ]);
    },
  });

  return columns;
});

// --- Watchers to Update URL ---
const updateFilters = () => {
  const queryParams: Record<string, string> = {
    service: selectedService.value,
    period: selectedPeriod.value, // RESTORED: Add period to URL
  };

  if (selectedDriver.value && selectedDriver.value !== 'all') {
    queryParams.driver = selectedDriver.value;
  }

  router.get(owner.driverownerpayroll().url, queryParams, {
    preserveScroll: true,
    replace: true,
  });
};

// Watch all filters for changes (debounced)
watch(
  [
    selectedPeriod, // RESTORED: Watch period changes
    selectedDriver,
  ],
  debounce(() => {
    updateFilters();
  }, 300),
);
</script>

<template>
  <Head :title="title" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            {{ title }}
          </h2>
          <div class="flex gap-4">
            <Select v-model="selectedPeriod">
              <SelectTrigger class="w-[150px] cursor-pointer">
                <SelectValue placeholder="Filter by Period" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="daily" class="cursor-pointer">
                  Daily
                </SelectItem>
                <SelectItem value="weekly" class="cursor-pointer">
                  Weekly
                </SelectItem>
                <SelectItem value="monthly" class="cursor-pointer">
                  Monthly
                </SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="selectedDriver">
              <SelectTrigger class="w-[200px] cursor-pointer">
                <SelectValue placeholder="Select Driver" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Drivers</SelectItem>
                <SelectItem
                  v-for="driver in drivers"
                  :key="driver.id"
                  :value="String(driver.id)"
                >
                  {{ driver.username }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <DataTable
          :columns="revenueColumns"
          :data="revenues.data"
          search-placeholder="Search drivers..."
        >
          <template #custom-actions>
            <Button @click="openExportModal('pdf')"> Export PDF </Button>
            <Button @click="openExportModal('excel')"> Export Excel </Button>
            <Button @click="openExportModal('csv')"> Export CSV </Button>
          </template>
        </DataTable>
      </div>

      <Dialog v-model:open="showExportModal">
        <DialogContent class="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle> Export {{ exportType.toUpperCase() }} </DialogTitle>
            <DialogDescription>
              Select the year and months to export. This will use your currently
              active filters (period and driver).
            </DialogDescription>
          </DialogHeader>
          <div class="grid gap-4 py-4">
            <div class="grid grid-cols-4 items-center gap-4">
              <label class="text-right">Year</label>
              <Select v-model="exportYear">
                <SelectTrigger class="col-span-3">
                  <SelectValue placeholder="Select year" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="year in yearOptions"
                    :key="year"
                    :value="year"
                  >
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid grid-cols-4 items-start gap-4">
              <label class="pt-2 text-right">Months</label>
              <div class="col-span-3 grid grid-cols-2 gap-2">
                <div
                  v-for="month in monthOptions"
                  :key="month.id"
                  class="flex items-center gap-2"
                >
                  <Checkbox
                    :id="`month-${month.id}`"
                    :model-value="exportMonths.includes(month.id)"
                    @update:model-value="() => toggleMonth(month.id)"
                  />

                  <label :for="`month-${month.id}`" class="cursor-pointer">
                    {{ month.label }}
                  </label>
                </div>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button @click="handleExport"> Confirm Export </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
