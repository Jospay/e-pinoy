<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps<{
  vehicles: { data: any[] };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: number[];
    status: string;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accreditation Management', href: '#' },
];

// -------------------------------------
// Reactive State
// -------------------------------------
const selectedFranchise = ref<string[]>(
  (props.filters.franchise || []).map(String),
);

const selectedStatus = ref(props.filters.status || 'active');

// v-model wrapper for MultiSelect
const selectedContext = computed<string[]>({
  get: () => selectedFranchise.value,
  set: (val) => {
    selectedFranchise.value = val;
  },
});

// MultiSelect options
const contextOptions = computed(() =>
  props.franchises.map((item) => ({
    id: String(item.id),
    label: item.name,
  })),
);

// -------------------------------------
// URL / Filter Update
// -------------------------------------
const updateFilters = () => {
  router.get(
    superAdmin.accreditation.index().url,
    {
      status: selectedStatus.value,
      franchise: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

// Watch filters (debounced)
watch(
  [selectedStatus],
  debounce(() => {
    updateFilters();
  }, 300),
);

// -------------------------------------
// Approve Accreditation Function
// -------------------------------------
const approveAccreditation = async (
  franchiseId: number,
  vehicleTypeId: number,
) => {
  try {
    await axios.post(route('accreditation.approve'), {
      franchise_id: franchiseId,
      vehicle_type_id: vehicleTypeId,
    });

    // Update the status_id in local table immediately
    const vehicle = vehicles.data.find(
      (v) =>
        v.franchise_id === franchiseId && v.vehicle_type_id === vehicleTypeId,
    );
    if (vehicle) {
      vehicle.status_id = 1;
      vehicle.status_label = 'Active';
    }
  } catch (error) {
    console.error('Failed to approve accreditation:', error);
  }
};

// -------------------------------------
// DataTable Columns
// -------------------------------------
const vehicleColumns = computed<ColumnDef<any>[]>(() => [
  {
    accessorKey: 'franchise_name',
    header: 'Franchise',
  },
  {
    accessorKey: 'vehicle_type',
    header: 'Vehicle Category',
    cell: ({ row }) =>
      h(Badge, { variant: 'secondary' }, () => row.original.vehicle_type),
  },
  {
    accessorKey: 'status_label',
    header: 'Status',
    cell: ({ row }) => {
      const isActive = row.original.status_id === 1;
      return h(
        Badge,
        {
          variant: isActive ? 'default' : 'outline',
          class: isActive
            ? 'bg-green-500 hover:bg-green-600'
            : 'border-amber-500 text-amber-600',
        },
        () => row.original.status_label,
      );
    },
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-center' }, 'Actions'),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, [
        h(DropdownMenu, null, () => [
          h(DropdownMenuTrigger, { asChild: true }, () =>
            h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () =>
              h(MoreHorizontal, { class: 'h-4 w-4' }),
            ),
          ),
          h(DropdownMenuContent, { align: 'end' }, () => [
            h(DropdownMenuLabel, () => 'Decide'),
            h(
              DropdownMenuItem,
              {
                class: 'text-green-600 cursor-pointer',
                onClick: () =>
                  approveAccreditation(
                    row.original.franchise_id,
                    row.original.vehicle_type_id,
                  ),
              },
              () => 'Approve Request',
            ),
            h(
              DropdownMenuItem,
              { class: 'text-red-600 cursor-pointer' },
              () => 'Reject Request',
            ),
          ]),
        ]),
      ]),
  },
]);
</script>

<template>
  <Head title="Accreditation Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Accreditations
          </h2>

          <div class="flex gap-4">
            <!-- Status Filter -->
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px] cursor-pointer">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
              </SelectContent>
            </Select>

            <!-- Franchise Filter -->
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
          :columns="vehicleColumns"
          :data="vehicles.data"
          search-placeholder="Search franchises..."
        />
      </div>
    </div>
  </AppLayout>
</template>
