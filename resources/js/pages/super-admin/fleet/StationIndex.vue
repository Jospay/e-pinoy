<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import LocationBusStation from '@/components/LocationBusStation.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import StationFareMatrix from '@/components/StationFareMatrix.vue';
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { MapPin, MoreHorizontal } from 'lucide-vue-next';
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

// Options for MultiSelect
const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);

interface StationModalData {
  franchise_name: string;
  stations: {
    id: number;
    name: string;
    code_no: string;
    status: string;
    latitude: number | null;
    longitude: number | null;
  }[];
  fares: {
    from_id: number;
    from_code: string;
    to_id: number;
    to_code: string;
    amount: string;
  }[];
}
// Convenient computed refs for the template
const modalData = computed(() => stationModal.data.value);
const mapMarkers = computed(() =>
  (modalData.value?.stations ?? [])
    .filter((s) => s.latitude && s.longitude)
    .map((s) => ({
      id: s.id,
      latitude: s.latitude!,
      longitude: s.longitude!,
      name: s.name,
      code_no: s.code_no,
      status: s.status,
    })),
);

// --- Modal State ---
const stationModal = useDetailsModal<StationModalData>({
  baseUrl: '/super-admin/station',
});

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
        if (!stations || stations.length === 0) {
          return h('span', { class: 'text-muted-foreground' }, 'N/A');
        }
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
      header: 'Statuses',
      cell: ({ row }) => {
        const stations = row.original.stations;
        if (!stations || stations.length === 0) {
          return h('div', { class: 'text-muted-foreground' }, 'N/A');
        }
        return h(
          'div',
          { class: 'flex flex-wrap gap-1' },
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
                  class: 'cursor-pointer',
                  onclick: () => stationModal.open(franchise.id),
                },
                () => 'View Station Details',
              ),
              h(DropdownMenuSeparator),
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
</script>

<template>
  <Head title="Bus Station Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div class="rounded-xl border p-4">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">Franchise Bus Station</h2>

          <div class="flex gap-4">
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

    <Dialog v-model:open="stationModal.isOpen.value">
      <DialogContent class="overflow-hidden p-0 sm:max-w-3xl">
        <div class="flex max-h-[90vh] flex-col">
          <DialogHeader class="p-6 pb-2">
            <DialogTitle class="flex items-center gap-2">
              <MapPin class="h-5 w-5 text-blue-600" />
              Station Locations
            </DialogTitle>
            <DialogDescription>
              Franchise:
              <span class="font-bold text-blue-500">
                {{ modalData?.franchise_name ?? '...' }}
              </span>
            </DialogDescription>
          </DialogHeader>

          <!-- Loading -->
          <div
            v-if="stationModal.isLoading.value"
            class="flex items-center justify-center py-16"
          >
            <span class="text-sm text-slate-500">Loading...</span>
          </div>

          <!-- Error -->
          <div
            v-else-if="stationModal.isError.value"
            class="p-6 text-center text-sm text-rose-500"
          >
            Failed to load station details.
          </div>

          <!-- Content -->
          <div
            v-else-if="modalData"
            class="flex-1 overflow-y-auto"
            style="scrollbar-gutter: stable both-edges"
          >
            <div class="space-y-4 p-4">
              <!-- Station list -->
              <div class="grid grid-cols-2 gap-2">
                <div
                  v-for="s in modalData.stations"
                  :key="s.id"
                  class="rounded-lg border bg-slate-50 p-3 text-xs"
                >
                  <p class="font-black text-slate-400 uppercase">
                    {{ s.code_no }}
                  </p>
                  <p class="font-bold text-slate-700">{{ s.name }}</p>
                  <span
                    class="mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-bold text-white"
                    :class="{
                      'bg-blue-500': s.status === 'active',
                      'bg-amber-500': s.status === 'pending',
                      'bg-rose-500': s.status === 'inactive',
                    }"
                    >{{ s.status }}</span
                  >
                </div>
              </div>

              <!-- Map -->
              <div
                class="relative h-72 overflow-hidden rounded-xl border-2 border-slate-100"
              >
                <LocationBusStation
                  v-if="mapMarkers.length"
                  :locations="mapMarkers"
                />
                <div
                  v-else
                  class="flex h-full items-center justify-center text-sm text-slate-400"
                >
                  No location data available.
                </div>
              </div>

              <!-- Legend -->
              <div class="flex gap-3 text-[10px] text-slate-500">
                <span class="flex items-center gap-1">
                  <span
                    class="inline-block h-2.5 w-2.5 rounded-full bg-blue-500"
                  ></span>
                  Active
                </span>
                <span class="flex items-center gap-1">
                  <span
                    class="inline-block h-2.5 w-2.5 rounded-full bg-amber-500"
                  ></span>
                  Pending
                </span>
                <span class="flex items-center gap-1">
                  <span
                    class="inline-block h-2.5 w-2.5 rounded-full bg-rose-500"
                  ></span>
                  Inactive
                </span>
              </div>
              <div class="space-y-2">
                <p class="text-[10px] font-black text-slate-400 uppercase">
                  Point-to-Point Fare Rates
                </p>
                <StationFareMatrix :fares="modalData.fares" />
              </div>
            </div>
          </div>

          <DialogFooter class="flex items-center justify-end border-t p-4">
            <Button variant="outline" @click="stationModal.close">Close</Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
