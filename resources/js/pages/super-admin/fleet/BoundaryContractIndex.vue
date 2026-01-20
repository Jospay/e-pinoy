<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import MultiSelect from '@/components/MultiSelect.vue';
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
import { debounce } from 'lodash-es';
import { AlertCircleIcon, MoreHorizontal, PlusIcon } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  contracts: {
    data: ContractRow[];
  };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: string[];
    status: 'active' | 'retired' | 'suspended';
  };
}>();

// --- Define ContractRow Interface ---
interface ContractRow {
  id: number;
  name: string;
  amount: number;
  coverage_area: string;
  start_date: string;
  end_date: string;
  franchise_name?: string;
  driver_username: string;
  status_name: string;
}

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Boundary Contract',
    href: superAdmin.boundaryContract.index().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchise || []);
const selectedStatus = ref(props.filters.status || 'active');

const selectedContext = computed({
  get: () =>
    selectedFranchise.value,
  set: (val: string[]) => {
    selectedFranchise.value = val;
  },
});

// Mapping options for the MultiSelect
const contextOptions = computed(() => {
  const data =
    props.franchises;
  return data.map((item) => ({ id: item.id, label: item.name }));
});

interface ContractModal {
  id: number;
  name: string;
  amount: number;
  coverage_area: string;
  contract_terms: string;
  renewal_terms: string;
  start_date: string;
  end_date: string;
  status_name: string;
  driver_name: string;
  driver_username: string;
  driver_email: string;
  driver_phone: string;
  franchise_name?: string;
  franchise_email?: string;
  franchise_phone?: string;
}
const contractDetails = computed(() => {
  const data = contractModal.data.value;
  if (!data) return [];

  return [
    { label: 'Contract', value: data.name, type: 'text' },
    { label: 'Status', value: data.status_name, type: 'text' },
    { label: 'Amount', value: formatCurrency(data.amount), type: 'text' },
    { label: 'Coverage Area', value: data.coverage_area, type: 'text' },
    { label: 'Contract Terms', value: data.contract_terms, type: 'text' },
    { label: 'Renewal Terms', value: data.renewal_terms, type: 'text' },
    { label: 'Start Date', value: data.start_date, type: 'text' },
    { label: 'End Date', value: data.end_date, type: 'text' },
    { label: 'Driver Name', value: data.driver_name, type: 'text' },
    { label: 'Driver Username', value: data.driver_username, type: 'text' },
    { label: 'Driver Email', value: data.driver_email, type: 'text' },
    { label: 'Driver Phone', value: data.driver_phone, type: 'text' },
    { label: 'Franchise Name', value: data.franchise_name, type: 'text' },
    { label: 'Franchise Email', value: data.franchise_email, type: 'text' },
    { label: 'Franchise Phone', value: data.franchise_phone, type: 'text' },
  ].filter((item) => item.value);
});

// --- Modal State ---
const contractModal = useDetailsModal<ContractModal>({
  baseUrl: '/super-admin/boundary-contract',
});

const createContract = () => {
  router.get(superAdmin.boundaryContract.create().url);
};

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Computed columns for the data table
const contractColumns = computed<ColumnDef<ContractRow>[]>(() => {
  const baseColumns: ColumnDef<ContractRow>[] = [
    {
      accessorKey: 'name',
      header: 'Contract',
    },
    {
      accessorKey: 'driver_username',
      header: 'Driver',
    },
    {
      accessorKey: 'franchise_name',
      header: 'Franchise'
    },
    {
      accessorKey: 'amount',
      header: 'Amount',
      cell: (info) => formatCurrency(info.getValue() as number),
    },
    {
      accessorKey: 'coverage_area',
      header: 'Coverage Area',
    },
    {
      accessorKey: 'start_date',
      header: 'Start Date',
    },
    {
      accessorKey: 'end_date',
      header: 'End Date',
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-blue-500 hover:bg-blue-600': status === 'active',
          'bg-rose-500 hover:bg-rose-600':
            status === 'terminated' || status === 'expired',
          'bg-amber-500 hover:bg-amber-600': status === 'pending',
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
        const contract = row.original;

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
                  onClick: () => contractModal.open(contract.id),
                },
                () => 'View Contract Details',
              ),
            ]),
          ]),
        ]);
      },
    },
  ];
  return baseColumns;
});

// --- Watchers to Update URL ---
const updateFilters = () => {
  router.get(
    superAdmin.boundaryContract.index().url,
    {
      status: selectedStatus.value,
      franchise: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true, // Doesn't pollute browser history
    },
  );
};

// Watch for select filter changes (debounced)
watch(
  [selectedStatus],
  debounce(() => {
    updateFilters();
  }, 300), // Debounce to avoid firing on every keystroke/click
);
</script>

<template>

  <Head title="Boundary Contract" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Contracts
          </h2>

          <div class="flex gap-4">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active"> Active </SelectItem>
                <SelectItem value="pending"> Pending </SelectItem>
                <SelectItem value="expired"> Expired </SelectItem>
                <SelectItem value="terminated"> Terminated </SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect v-model="selectedContext" :options="contextOptions" placeholder="
                Select Franchises
              " all-label="All Franchises" @change="
                (val) => {
                  selectedFranchise = val;
                  updateFilters();
                }
              " />
          </div>
        </div>

        <DataTable :columns="contractColumns" :data="contracts.data" search-placeholder="Search contracts...">
          <template #custom-actions>
            <Button class="me-5" @click="createContract">
              <PlusIcon />Add Contract
            </Button>
          </template>
        </DataTable>
      </div>
    </div>

    <Dialog v-model:open="contractModal.isOpen.value">
      <DialogContent class="max-w-3xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Contract Details</DialogTitle>
        </DialogHeader>
        <DialogDescription>
          <div v-if="contractModal.isLoading.value" class="grid grid-cols-2 gap-4">
            <template v-for="item in 10" :key="item">
              <Skeleton class="h-5 w-24" />
              <Skeleton class="h-5 w-3/4" />
            </template>
          </div>

          <div v-else-if="contractDetails.length > 0" class="grid grid-cols-2 gap-4">
            <template v-for="item in contractDetails" :key="item.label">
              <div class="font-medium">{{ item.label }}:</div>
              <div>
                {{ item.value }}
              </div>
            </template>
          </div>

          <div v-else-if="contractModal.isError.value">
            <Alert variant="destructive" class="border-2 border-red-500 shadow-lg">
              <AlertCircleIcon class="h-4 w-4" />
              <AlertTitle class="font-bold">Error</AlertTitle>
              <AlertDescription class="font-semibold">
                Failed to load contract details.
              </AlertDescription>
            </Alert>
          </div>
        </DialogDescription>

        <DialogFooter class="mt-5">
          <Button variant="outline" @click="contractModal.close">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
