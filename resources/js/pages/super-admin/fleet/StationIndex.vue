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

// --- Define Props ---
const props = defineProps<{
  stations: {
    data: stationRow[];
  };
  franchises: { id: number; name: string }[];
  vehicleTypes: { id: number; name: string }[];
  filters: {
    franchises: string[];
    status: 'active' | 'pending' | 'inactive';
  };
}>();

interface stationRow {
  id: number;
  franchise_name: string;
  stations: StationEntry[];
}

interface StationEntry {
  code: string;
  status: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Bus Station Management', href: '#' },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchises || []);
const selectedStatus = ref(props.filters.status || 'active');

// Options for MultiSelect
const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);

// Computed columns for the data table
const stationColumns = computed<ColumnDef<stationRow>[]>(() => {
  const baseColumns: ColumnDef<stationRow>[] = [
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'stations',
      header: 'Station Codes',
      cell: ({ row }) => {
        const stations = row.original.stations;
        return h(
          'div',
          { class: 'flex flex-wrap gap-1' },
          stations.map((s) =>
            h(Badge, { variant: 'secondary', key: s.code }, () => s.code),
          ),
        );
      },
    },
    {
      id: 'statuses',
      header: () => h('div', { class: 'text-center' }, 'Statuses'),
      cell: ({ row }) => {
        const stations = row.original.stations;
        return h(
          'div',
          { class: 'flex flex-wrap justify-center gap-1' },
          stations.map((s) => {
            const badgeClass = {
              'bg-blue-500 hover:bg-blue-600': s.status === 'active',
              'bg-amber-500 hover:bg-amber-600': s.status === 'pending',
              'bg-rose-500 hover:bg-rose-600': s.status === 'inactive',
            };
            return h(
              Badge,
              { class: [badgeClass, 'text-white'], key: s.code },
              () => s.status,
            );
          }),
        );
      },
    },
    {
      id: 'actions',
      header: () => h('div', { class: 'text-center' }, 'Actions'),
      cell: ({ row }) => {
        const franchise = row.original;

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
                  class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                },
                () => 'Change Status',
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
    superAdmin.station.index().url,
    {
      status: selectedStatus.value,
      franchises: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

watch(selectedFranchise, () => {
  updateFilters();
});

// Watch for select filter changes (debounced)
watch(
  [selectedStatus],
  debounce(() => {
    updateFilters();
  }, 300), // Debounce to avoid firing on every keystroke/click
);
</script>

<template>
  <Head title="Bus Station Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div class="rounded-xl border p-4">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">Franchise Bus Station</h2>

          <div class="flex gap-4">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="inactive">Inactive</SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect
              v-model="selectedFranchise"
              :options="franchiseOptions"
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
          :columns="stationColumns"
          :data="stations.data"
          search-placeholder="Search franchises..."
        />
      </div>
    </div>
  </AppLayout>
</template>
