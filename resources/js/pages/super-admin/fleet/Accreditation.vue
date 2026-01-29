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
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps<{
  vehicles: { data: any[] };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: number[];
    status: string;
    vehicle_type?: string | null;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accreditation Management', href: '#' },
];

// --------------------
// Inertia Form
// --------------------
const form = useForm({
  franchise_id: null as number | null,
  vehicle_type_id: null as number | null,
});

// --------------------
// State
// --------------------
const selectedFranchise = ref<string[]>(
  (props.filters.franchise || []).map(String),
);

const selectedStatus = ref(props.filters.status || 'active');

const activeTab = ref<string>(props.filters.vehicle_type ?? 'Taxi Car');

const tableData = ref([...props.vehicles.data]);

watch(
  () => props.vehicles.data,
  (val) => {
    tableData.value = [...val];
  },
  { deep: true },
);

const selectedContext = computed<string[]>({
  get: () => selectedFranchise.value,
  set: (val) => (selectedFranchise.value = val),
});

const contextOptions = computed(() =>
  props.franchises.map((f) => ({
    id: String(f.id),
    label: f.name,
  })),
);

// --------------------
// Filters
// --------------------
const updateFilters = () => {
  router.get(
    superAdmin.accreditation.index().url,
    {
      status: selectedStatus.value,
      franchise: selectedFranchise.value,
      vehicle_type: activeTab.value,
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

watch(
  [selectedStatus, selectedFranchise, activeTab],
  debounce(updateFilters, 300),
);

// --------------------
// Actions
// --------------------
const approveAccreditation = (franchiseId: number, vehicleTypeId: number) => {
  form.franchise_id = franchiseId;
  form.vehicle_type_id = vehicleTypeId;

  form.post(superAdmin.accreditation.approve().url, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['vehicles'] });
      toast.success('Approved successfully!');
    },
  });
};

const declineAccreditation = (franchiseId: number, vehicleTypeId: number) => {
  form.franchise_id = franchiseId;
  form.vehicle_type_id = vehicleTypeId;

  form.post(superAdmin.accreditation.decline().url, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ only: ['vehicles'] });
      toast.success('Declined successfully!');
    },
  });
};

// --------------------
// Columns
// --------------------
const vehicleColumns = computed<ColumnDef<any>[]>(() => [
  { accessorKey: 'franchise_name', header: 'Franchise' },
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
      const status = row.original.status_id;

      return h(
        Badge,
        {
          variant: 'outline',
          class:
            status === 1
              ? 'bg-green-500 text-white hover:bg-green-600'
              : status === 6
                ? 'border-amber-500 text-amber-600'
                : 'border-red-500 text-red-600',
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
            h(
              Button,
              {
                variant: 'ghost',
                class: 'h-8 w-8 p-0',
                disabled: form.processing,
              },
              () => h(MoreHorizontal, { class: 'h-4 w-4' }),
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
              {
                class: 'text-red-600 cursor-pointer',
                onClick: () =>
                  declineAccreditation(
                    row.original.franchise_id,
                    row.original.vehicle_type_id,
                  ),
              },
              () => 'Deny Request',
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
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <!-- Vehicle Type Tabs -->
      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="w-full justify-start p-1.5">
          <TabsTrigger value="Taxi Car" class="cursor-pointer font-semibold">
            Taxi Car
          </TabsTrigger>
          <TabsTrigger value="Tricycle" class="cursor-pointer font-semibold">
            Tricycle
          </TabsTrigger>
          <TabsTrigger value="Bus" class="cursor-pointer font-semibold">
            Bus
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <div class="rounded-xl border p-4">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Accreditations
          </h2>

          <div class="flex gap-4">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="deny">Deny</SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect
              v-model="selectedContext"
              :options="contextOptions"
              placeholder="Select Franchises"
              all-label="All Franchises"
            />
          </div>
        </div>

        <DataTable
          :columns="vehicleColumns"
          :data="tableData"
          search-placeholder="Search franchises..."
        />
      </div>
    </div>
  </AppLayout>
</template>
