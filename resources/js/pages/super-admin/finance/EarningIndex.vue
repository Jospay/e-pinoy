<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Button } from '@/components/ui/button';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  earnings: {
    data: any[];
  };
  franchises: { id: number; name: string }[];
  drivers: { id: number; username: string }[];
  filters: {
    franchise: string[];
    driver: string[];
    period: 'daily' | 'weekly' | 'monthly';
  };
  feeTypes: {
    id: number;
    db_name: string;
    slug: string;
    display: string;
  }[];
}>();

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Earning Report',
    href: superAdmin.earning.index().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchise || []);
const selectedDriver = ref<string[]>(props.filters.driver || []);
const selectedPeriod = ref(props.filters.period);

const selectedContext = computed({
  get: () =>
    selectedFranchise.value,
  set: (val: string[]) => {
    selectedFranchise.value = val;
    selectedDriver.value = [];
  },
});

// Mapping options for the MultiSelect
const driverOptions = computed(() =>
  props.drivers.map((d) => ({ id: d.id, label: d.username })),
);
const contextOptions = computed(() => {
  const data =
    props.franchises;
  return data.map((item) => ({ id: item.id, label: item.name }));
});

const showExportModal = ref(false);
const exportType = ref<'pdf' | 'excel' | 'csv'>('pdf');
const exportYear = ref(String(new Date().getFullYear()));
const exportMonths = ref<number[]>([]);

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
    period: selectedPeriod.value,
    export: exportType.value,
    year: exportYear.value,
  });

  // 2. Add franchise filter
  if (selectedFranchise.value.length > 0) {
    selectedFranchise.value.forEach((f) => params.append('franchise[]', f));
  }

  // 3. Add drivers
  if (selectedDriver.value.length > 0) {
    selectedDriver.value.forEach((d) => params.append('driver[]', d));
  }

  // 4. Add months
  exportMonths.value.forEach((month) => {
    params.append('months[]', String(month));
  });

  // 5. Build URL and open in new tab (triggers download)
  const url = `${superAdmin.earning.export.index().url}?${params.toString()}`;
  window.open(url, '_blank');

  // 6. Close modal and reset
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
const earningColumns = computed<ColumnDef<any>[]>(() => {

  const columns: ColumnDef<any>[] = [
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'driver_username',
      header: 'Driver',
    },
    {
      accessorKey: 'payment_date',
      header: 'Date',
    },
    {
      accessorKey: 'total_amount',
      header: 'Total Amount',
      cell: (info) => formatCurrency(info.getValue() as number),
    },
  ];

  // Loop through the fee_types passed from backend
  props.feeTypes.forEach((type) => {
    columns.push({
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

  // Static End Column (Driver Earning)
  columns.push({
    accessorKey: 'driver_earning',
    header: 'Driver Earning',
    cell: (info) => formatCurrency(info.getValue() as number),
  });

  columns.push({
    id: 'actions',
    header: () => h('div', 'Actions'),
    cell: ({ row }) => {
      const rowData = row.original as any;

      return h('div', { class: 'relative' }, [
        h(DropdownMenu, null, () => [
          h(
            DropdownMenuTrigger,
            { asChild: true, class: 'cursor-pointer' },
            () =>
              h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () => [
                h('span', { class: 'sr-only' }, 'Open menu'),
                h(MoreHorizontal, { class: 'h-4 w-4' }),
              ]),
          ),
          h(DropdownMenuContent, { align: 'end', class: 'border-2' }, () => [
            h(DropdownMenuLabel, null, () => 'Actions'),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer',
                onClick: () => {
                  const queryParams: Record<string, string> = {
                    driver: String(rowData.driver_id),
                    start: rowData.query_params.start,
                    end: rowData.query_params.end,
                    label: rowData.payment_date,
                  };

                  router.get(superAdmin.earning.show().url, queryParams, {
                    preserveScroll: true,
                    replace: false,
                  });
                },
              },
              () => 'View Computation Details',
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
  router.get(
    superAdmin.earning.index().url,
    {
      period: selectedPeriod.value,
      driver: selectedDriver.value,
      franchise: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

// Watch all filters for changes (debounced)
watch(
  [selectedPeriod],
  debounce(() => {
    updateFilters();
  }, 300),
);
</script>

<template>

  <Head title="Earning Report" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Earnings
          </h2>
          <div class="flex gap-4">
            <Select v-model="selectedPeriod">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="daily"> Daily </SelectItem>
                <SelectItem value="weekly"> Weekly </SelectItem>
                <SelectItem value="monthly"> Monthly </SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect v-model="selectedDriver" :options="driverOptions" placeholder="Select Drivers"
              all-label="All Drivers" @change="updateFilters" />

            <MultiSelect v-model="selectedContext" :options="contextOptions" placeholder="Select Franchises"
              all-label="All Franchises" @change="
                (val) => {
                  selectedFranchise = val;
                  updateFilters();
                }
              " />
          </div>
        </div>

        <DataTable :columns="earningColumns" :data="earnings.data" search-placeholder="Search earnings...">
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
              active filters.
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
                  <SelectItem v-for="year in yearOptions" :key="year" :value="year">
                    {{ year }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="grid grid-cols-4 items-start gap-4">
              <label class="pt-2 text-right">Months</label>
              <div class="col-span-3 grid grid-cols-2 gap-2">
                <div v-for="month in monthOptions" :key="month.id" class="flex items-center gap-2">
                  <Checkbox :id="`month-${month.id}`" :model-value="exportMonths.includes(month.id)"
                    @update:model-value="() => toggleMonth(month.id)" />

                  <label :for="`month-${month.id}`" class="cursor-pointer">
                    {{ month.label }}
                  </label>
                </div>
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button @click="handleExport" :disabled="exportMonths.length === 0">
              Confirm Export
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
