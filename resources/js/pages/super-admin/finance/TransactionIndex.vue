<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Skeleton } from '@/components/ui/skeleton';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { AlertCircleIcon, MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  transactions: {
    data: TransactionRow[];
  };
  franchises: { id: number; name: string }[];
  drivers: { id: number; username: string }[];
  filters: {
    type: 'revenue' | 'expense';
    franchise: string[];
    driver: string[];
    service: 'Trips' | 'Boundary';
  };
}>();

// --- Define TransactionRow Interface ---
interface TransactionRow {
  id: number;
  franchise_name?: string;
  type: string;
  invoice_no: string;
  amount: number;
  date: string;
  service_type: string;
  driver_username?: string;
  status_name: string;
}

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Transaction History',
    href: superAdmin.transaction.index().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedType = ref(props.filters.type);
const selectedFranchise = ref<string[]>(props.filters.franchise || []);
const selectedDriver = ref<string[]>(props.filters.driver || []);
const selectedService = ref(props.filters.service);

const selectedContext = computed({
  get: () => selectedFranchise.value,
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
  const data = props.franchises;
  return data.map((item) => ({ id: item.id, label: item.name }));
});

interface TransactionModal {
  id: number;
  franchise_name?: string;
  service_type: string;
  payment_option: string;
  invoice_no: string;
  amount: number;
  driver_username?: string;
  vehicle_plate?: string;
  description?: string;
  maintenance_date?: string;
  inventory_name?: string;
  inventory_category?: string;
  status_name: string;
  payment_date: string | null;
  created_at: string | null;
  notes: string | null;
}
const transactionDetails = computed(() => {
  const data = transactionModal.data.value;
  if (!data) return [];

  const amount = formatCurrency(data.amount);
  const nameValue = data.franchise_name;
  const nameLabel = 'Franchise';

  const details = [
    { label: nameLabel, value: nameValue, type: 'text' },
    { label: 'Service Type', value: data.service_type, type: 'text' },
    { label: 'Invoice #', value: data.invoice_no, type: 'text' },
    { label: 'Amount', value: amount, type: 'text' },
    { label: 'Payment Option', value: data.payment_option, type: 'text' },
    { label: 'Status', value: data.status_name, type: 'text' },
    { label: 'Transaction Date', value: data.created_at, type: 'text' },
  ];

  if (props.filters.type === 'revenue') {
    details.push({
      label: 'Driver',
      value: data.driver_username,
      type: 'text',
    });
  } else {
    details.push(
      { label: 'Vehicle', value: data.vehicle_plate, type: 'text' },
      { label: 'Inventory', value: data.inventory_name, type: 'text' },
      { label: 'Category', value: data.inventory_category, type: 'text' },
      { label: 'Description', value: data.description, type: 'text' },
      {
        label: 'Maintenance Date',
        value: data.maintenance_date,
        type: 'text',
      },
    );
  }

  if (data.payment_date) {
    details.push({
      label: 'Paid Date',
      value: data.payment_date,
      type: 'text',
    });
  }
  if (data.notes) {
    details.push({ label: 'Notes', value: data.notes, type: 'text' });
  }

  return details;
});

const openDetails = (id: number) => {
  transactionModal.open(id, {
    params: { type: props.filters.type },
  });
};

// --- Modal State ---
const transactionModal = useDetailsModal<TransactionModal>({
  baseUrl: '/super-admin/transaction',
});

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Computed columns for the data table
const transactionColumns = computed<ColumnDef<TransactionRow>[]>(() => {
  const isRevenue = selectedType.value === 'revenue';

  const columns: ColumnDef<TransactionRow>[] = [
    {
      accessorKey: 'invoice_no',
      header: 'Invoice #',
    },
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    ...(isRevenue
      ? [
          {
            accessorKey: 'driver_username',
            header: 'Driver',
          },
        ]
      : []),
    {
      accessorKey: 'date',
      header: 'Date',
    },
    {
      accessorKey: 'service_type',
      header: 'Service Type',
    },
    {
      accessorKey: 'amount',
      header: 'Amount',
      cell: (info) => formatCurrency(info.getValue() as number),
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-green-500 hover:bg-green-600': status === 'paid',
          'bg-amber-500 hover:bg-amber-600': status === 'pending',
          'bg-rose-500 hover:bg-rose-600':
            status === 'cancelled' || status === 'overdue',
        };
        return h('div', { class: 'text-center' }, [
          h(
            Badge,
            { class: [badgeClass, 'text-white'] },
            () => status || 'N/A',
          ),
        ]);
      },
    },
    {
      id: 'actions',
      header: () => h('div', { class: 'text-center' }, 'Actions'),
      cell: ({ row }) => {
        const transaction = row.original;

        return h('div', { class: 'relative text-center' }, [
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
                  onClick: () => openDetails(transaction.id),
                },
                () => 'View Transaction Details',
              ),
            ]),
          ]),
        ]);
      },
    },
  ];

  return columns;
});

// --- Watchers to Update URL ---
const updateFilters = () => {
  router.get(
    superAdmin.transaction.index().url,
    {
      type: selectedType.value,
      service:
        selectedType.value === 'revenue' ? selectedService.value : undefined,
      driver: selectedType.value === 'revenue' ? selectedDriver.value : [],
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
  [selectedService, selectedType],
  debounce(() => {
    updateFilters();
  }, 300),
);
</script>

<template>
  <Head title=" Transaction History" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Transactions
          </h2>
          <div class="flex gap-4">
            <Select v-model="selectedType">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="revenue"> Revenue </SelectItem>
                <SelectItem value="expense"> Expense </SelectItem>
              </SelectContent>
            </Select>

            <Select v-if="selectedType === 'revenue'" v-model="selectedService">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Trips"> Trips </SelectItem>
                <SelectItem value="Boundary"> Boundary </SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect
              v-model="selectedDriver"
              :options="driverOptions"
              placeholder="Select Drivers"
              all-label="All Drivers"
              @change="updateFilters"
            />

            <MultiSelect
              v-model="selectedContext"
              :options="contextOptions"
              placeholder="Select Franchises"
              all-label="All Franchises"
              @change="
                (val) => {
                  selectedFranchise = val;
                  updateFilters();
                }
              "
            />
          </div>
        </div>

        <DataTable
          :columns="transactionColumns"
          :data="transactions.data"
          search-placeholder="Search transactions..."
        />
      </div>
    </div>
  </AppLayout>

  <Dialog v-model:open="transactionModal.isOpen.value">
    <DialogContent class="max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Transaction Details</DialogTitle>
      </DialogHeader>
      <DialogDescription>
        <div
          v-if="transactionModal.isLoading.value"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div
          v-else-if="transactionDetails.length > 0"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in transactionDetails" :key="item.label">
            <div class="font-medium">{{ item.label }}:</div>
            <div>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="transactionModal.isError.value">
          <Alert
            variant="destructive"
            class="border-2 border-red-500 shadow-lg"
          >
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load transaction details.
            </AlertDescription>
          </Alert>
        </div>
      </DialogDescription>

      <DialogFooter class="mt-5">
        <Button variant="outline" @click="transactionModal.close">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
