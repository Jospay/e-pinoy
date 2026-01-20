<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Skeleton } from '@/components/ui/skeleton';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { AlertCircleIcon, MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

// --- Define Props ---
const props = defineProps<{
  inventories: {
    data: InventoryRow[];
  };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: string[];
  };
}>();

// --- Define InventoryRow Interface ---
interface InventoryRow {
  id: number;
  franchise_name?: string;
  code_no: string;
  name: string;
  category: string;
  specification: string;
  quantity: string;
  unit_price: string;
}

// --- Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Inventory Management',
    href: superAdmin.inventory.index().url,
  },
];

// --- Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchise || []);

// Mapping options for the MultiSelect
const contextOptions = computed(() =>
  props.franchises.map((item) => ({ id: item.id, label: item.name })),
);

interface InventoryModal {
  id: number;
  franchise_name?: string;
  code_no: string;
  name: string;
  category: string;
  specification: string;
  quantity: string;
  unit_price: string;
  notes: string;
}
const inventoryDetails = computed(() => {
  const data = inventoryModal.data.value;
  if (!data) return [];

  return [
    { label: 'Franchise', value: data.franchise_name, type: 'text' },
    { label: 'Code No', value: data.code_no, type: 'text' },
    { label: 'Item', value: data.name, type: 'text' },
    { label: 'Category', value: data.category, type: 'text' },
    { label: 'Specification', value: data.specification, type: 'text' },
    { label: 'Quantity', value: data.quantity, type: 'text' },
    { label: 'Unit Price', value: data.unit_price, type: 'text' },
    { label: 'Notes', value: data.notes, type: 'text' },
  ].filter((item) => item.value);
});

// --- Modal State ---
const inventoryModal = useDetailsModal<InventoryModal>({
  baseUrl: '/super-admin/inventory',
});

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

// Computed columns for the data table
const inventoryColumns = computed<ColumnDef<InventoryRow>[]>(() => {
  const baseColumns: ColumnDef<InventoryRow>[] = [
    // Conditionally add the correct column
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'code_no',
      header: 'Code No.',
    },
    {
      accessorKey: 'name',
      header: 'Item',
    },
    {
      accessorKey: 'category',
      header: 'Category',
    },
    {
      accessorKey: 'specification',
      header: 'Specification',
    },
    {
      accessorKey: 'quantity',
      header: 'Quantity',
    },
    {
      accessorKey: 'unit_price',
      header: 'Unit Price',
      cell: (info) => formatCurrency(info.getValue() as number),
    },
    {
      id: 'actions',
      header: () => h('div', { class: 'text-center' }, 'Actions'),
      cell: ({ row }) => {
        const inventory = row.original;

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
                  onClick: () => inventoryModal.open(inventory.id),
                },
                () => 'View Inventory Details',
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
    superAdmin.inventory.index().url,
    {
      franchise: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};
</script>

<template>
  <Head title="Inventory Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">Franchise Inventory</h2>

          <div class="flex gap-4">
            <MultiSelect
              v-model="selectedFranchise"
              :options="contextOptions"
              placeholder="
               Select Franchises
                 
              "
              all-label="
                All Franchises
              "
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
          :columns="inventoryColumns"
          :data="inventories.data"
          search-placeholder="Search inventories..."
        />
      </div>
    </div>
  </AppLayout>
  <Dialog v-model:open="inventoryModal.isOpen.value">
    <DialogContent class="max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Inventory Details</DialogTitle>
      </DialogHeader>
      <DialogDescription>
        <div
          v-if="inventoryModal.isLoading.value"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div
          v-else-if="inventoryDetails.length > 0"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in inventoryDetails" :key="item.label">
            <div class="font-medium">{{ item.label }}:</div>

            <div v-if="item.type === 'link'">
              <a
                :href="item.value"
                target="_blank"
                class="text-blue-500 hover:underline"
                >View</a
              >
            </div>

            <div v-else>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="inventoryModal.isError.value">
          <Alert
            variant="destructive"
            class="border-2 border-red-500 shadow-lg"
          >
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load inventorys details.
            </AlertDescription>
          </Alert>
        </div>
      </DialogDescription>

      <DialogFooter class="mt-5">
        <Button variant="outline" @click="inventoryModal.close">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
