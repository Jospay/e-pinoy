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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { AlertCircleIcon, MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  transactions: {
    data: TransactionRow[];
  };
  franchises: { id: number; name: string }[];
  branches: { id: number; name: string }[];
  vehicleTypes: { id: number; name: string }[];
  drivers: { id: number; username: string }[];
  filters: {
    tab: string;
    type: 'franchise' | 'branch';
    franchises: string[];
    branches: string[];
    driver: string[];
    category: 'revenue' | 'expense';
  };
}>();

// --- Define TransactionRow Interface ---
interface TransactionRow {
  id: number;
  franchise_name?: string;
  branch_name?: string;
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
const activeTab = ref(props.filters.tab);
const selectedType = ref(props.filters.type || 'franchise');
const selectedFranchises = ref<string[]>(props.filters.franchises || []);
const selectedBranches = ref<string[]>(props.filters.branches || []);
const selectedCategory = ref(props.filters.category || 'revenue');
const selectedDriver = ref<string[]>(props.filters.driver || []);

// Options for MultiSelect
const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);
const branchOptions = computed(() =>
  props.branches.map((b) => ({ id: b.id, label: b.name })),
);

// Mapping options for the MultiSelect
const driverOptions = computed(() =>
  props.drivers.map((d) => ({ id: d.id, label: d.username })),
);

interface TransactionModal {
  id: number;
  franchise_name?: string;
  branch_name?: string;
  vehicle_type?: string;
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
  const isFranchise = selectedType.value === 'franchise';

  const details = [
    {
      label: isFranchise ? 'Franchise' : 'Branch',
      value: isFranchise ? data.franchise_name : data.branch_name,
      type: 'text',
    },
    { label: 'Service Type', value: data.service_type, type: 'text' },
    { label: 'Invoice #', value: data.invoice_no, type: 'text' },
    { label: 'Amount', value: amount, type: 'text' },
    { label: 'Payment Option', value: data.payment_option, type: 'text' },
    { label: 'Status', value: data.status_name, type: 'text' },
    { label: 'Vehicle Type', value: data.vehicle_type, type: 'text' },
    { label: 'Transaction Date', value: data.created_at, type: 'text' },
  ];

  if (props.filters.category === 'revenue') {
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
    params: { category: props.filters.category },
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
  const isRevenue = selectedCategory.value === 'revenue';
  const isFranchise = selectedType.value === 'franchise';

  const columns: ColumnDef<TransactionRow>[] = [
    {
      accessorKey: 'invoice_no',
      header: 'Invoice #',
    },
    {
      accessorKey: isFranchise ? 'franchise_name' : 'branch_name',
      header: isFranchise ? 'Franchise' : 'Branch',
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
      tab: activeTab.value,
      type: selectedType.value,
      category: selectedCategory.value,
      franchises: selectedFranchises.value || [],
      branches: selectedBranches.value || [],
      driver: selectedCategory.value === 'revenue' ? selectedDriver.value : [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

watch([activeTab, selectedType, selectedCategory], () => {
  selectedFranchises.value = [];
  selectedBranches.value = [];
  selectedDriver.value = [];
  updateFilters();
});

watch(selectedFranchises, () => {
  selectedBranches.value = [];
  selectedDriver.value = [];
  updateFilters();
});
</script>

<template>
  <Head title=" Transaction History" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="h-auto w-full justify-start bg-sidebar p-1.5">
          <TabsTrigger
            v-for="type in vehicleTypes"
            :key="type.id"
            :value="type.name"
            class="cursor-pointer px-8 py-2 font-semibold capitalize"
            :class="{ 'pointer-events-none': activeTab === type.name }"
          >
            {{ type.name }}
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Transactions
          </h2>
          <div class="flex gap-4">
            <Select v-model="selectedType">
              <SelectTrigger class="w-[150px] cursor-pointer">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise" class="cursor-pointer">
                  Franchise
                </SelectItem>
                <SelectItem value="branch" class="cursor-pointer">
                  Branch
                </SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="selectedCategory">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="revenue"> Revenue </SelectItem>
                <SelectItem value="expense"> Expense </SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect
              v-if="selectedCategory === 'revenue'"
              v-model="selectedDriver"
              :options="driverOptions"
              placeholder="Select Drivers"
              all-label="All Drivers"
              @change="updateFilters"
            />

            <MultiSelect
              v-model="selectedFranchises"
              :options="franchiseOptions"
              placeholder="Select Franchises"
              all-label="All Franchises"
              @change="
                (val) => {
                  selectedFranchises = val;

                  updateFilters();
                }
              "
            />

            <MultiSelect
              v-if="selectedType === 'branch'"
              v-model="selectedBranches"
              :options="branchOptions"
              placeholder="Select Branches"
              all-label="All Branches"
              @change="
                (val) => {
                  selectedBranches = val;

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
