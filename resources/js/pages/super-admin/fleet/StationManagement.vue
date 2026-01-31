<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
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
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { MoreHorizontal, MapPin } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import LocationBusStation from '@/components/LocationBusStation.vue';
import MultiSelect from '@/components/MultiSelect.vue';

const props = defineProps<{
  stations: { data: any[] };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: number[];
    status: string;
    vehicle_type?: string | null;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Station Management', href: '#' },
];

// --------------------
// State & Forms
// --------------------
const form = useForm({ id: null as number | null });
const selectedFranchise = ref<string[]>(
  (props.filters.franchise || []).map(String),
);
const selectedStatus = ref(props.filters.status || 'pending');

// activeTab is now a constant since we only deal with Bus
const activeTab = 'Bus';

const isDetailModalOpen = ref(false);
const selectedStation = ref<any>(null);

// --------------------
// Map Data Preparation
// --------------------
const detailMarkers = computed(() => {
  if (!selectedStation.value) return [];
  return [
    {
      id: selectedStation.value.id,
      latitude: Number(selectedStation.value.latitude),
      longitude: Number(selectedStation.value.longitude),
      type: 'Pin',
      name: selectedStation.value.station_name,
    },
  ];
});

const mapCenter = computed((): [number, number] => {
  if (selectedStation.value?.latitude) {
    return [
      Number(selectedStation.value.latitude),
      Number(selectedStation.value.longitude),
    ];
  }
  return [14.5995, 120.9842];
});

// --------------------
// Actions
// --------------------
const updateFilters = () => {
  router.get(
    superAdmin.stationManagement.index().url,
    {
      status: selectedStatus.value,
      franchise: selectedFranchise.value,
      vehicle_type: activeTab, // Always 'Bus'
    },
    { preserveScroll: true, replace: true },
  );
};

// Removed activeTab from watch list
watch([selectedStatus, selectedFranchise], debounce(updateFilters, 300));

const openDetailModal = (station: any) => {
  selectedStation.value = station;
  isDetailModalOpen.value = true;
};

const approveStation = (stationId: number) => {
  form.id = stationId;
  form.post(superAdmin.stationManagement.approve().url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Station approved successfully!');
      isDetailModalOpen.value = false;
    },
  });
};

const declineStation = (stationId: number) => {
  form.id = stationId;
  form.post(superAdmin.stationManagement.decline().url, {
    preserveScroll: true,
    onSuccess: () => {
      toast.error('Station has been denied.');
      isDetailModalOpen.value = false;
    },
  });
};

// --------------------
// Table Columns
// --------------------
const stationColumns = computed<ColumnDef<any>[]>(() => [
  {
    accessorKey: 'station_name',
    header: 'Station Name',
    cell: ({ row }) =>
      h('div', { class: 'flex flex-col' }, [
        h(
          'span',
          { class: 'font-bold text-slate-900' },
          row.original.station_name,
        ),
      ]),
  },
  {
    accessorKey: 'code_no',
    header: 'Code',
    cell: ({ row }) =>
      h(Badge, { variant: 'secondary' }, () => row.original.code_no),
  },
  {
    accessorKey: 'franchise_name',
    header: 'Franchise',
  },
  {
    accessorKey: 'status_label',
    header: 'Status',
    cell: ({ row }) => {
      const status = row.original.status_id;
      const statusClasses =
        status === 1
          ? 'bg-green-100 text-green-700 border-green-200'
          : status === 6
            ? 'bg-amber-100 text-amber-700 border-amber-200'
            : 'bg-red-100 text-red-700 border-red-200';
      return h(
        Badge,
        { variant: 'outline', class: statusClasses },
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
          h(DropdownMenuContent, { align: 'end', class: 'w-48' }, () =>
            [
              h(DropdownMenuLabel, () => 'Options'),
              h(
                DropdownMenuItem,
                {
                  class: 'cursor-pointer',
                  onClick: () => openDetailModal(row.original),
                },
                () => 'View Details',
              ),
              h('div', { class: 'h-px bg-slate-100 my-1' }),
              row.original.status_id !== 1
                ? h(
                    DropdownMenuItem,
                    {
                      class: 'text-green-600 cursor-pointer',
                      onClick: () => approveStation(row.original.id),
                    },
                    () => 'Approve Station',
                  )
                : null,
              row.original.status_id !== 18
                ? h(
                    DropdownMenuItem,
                    {
                      class: 'text-red-600 cursor-pointer',
                      onClick: () => declineStation(row.original.id),
                    },
                    () => 'Deny Station',
                  )
                : null,
            ].filter(Boolean),
          ),
        ]),
      ]),
  },
]);
</script>

<template>
  <Head title="Station Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div
          class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
          <div>
            <h2 class="text-xl font-bold text-slate-800">
              Bus Station Verification
            </h2>
            <p class="text-sm text-slate-500">
              Review and manage bus station locations.
            </p>
          </div>
          <div class="flex flex-wrap gap-3">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[140px]"
                ><SelectValue placeholder="Status"
              /></SelectTrigger>
              <SelectContent>
                <SelectItem value="active">Active</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="deny">Denied</SelectItem>
              </SelectContent>
            </Select>
            <MultiSelect
              v-model="selectedFranchise"
              :options="
                props.franchises.map((f) => ({
                  id: String(f.id),
                  label: f.name,
                }))
              "
              placeholder="Franchise"
              class="min-w-[200px]"
            />
          </div>
        </div>

        <DataTable
          :columns="stationColumns"
          :data="props.stations.data"
          search-placeholder="Search station..."
        />
      </div>
    </div>

    <Dialog :open="isDetailModalOpen" @update:open="isDetailModalOpen = $event">
      <DialogContent class="max-w-md overflow-hidden p-0">
        <DialogHeader class="p-6 pb-2">
          <DialogTitle class="flex items-center gap-2">
            <MapPin class="h-5 w-5 text-blue-600" />
            Station Location
          </DialogTitle>
          <DialogDescription>
            Viewing
            <span class="font-bold text-slate-900">{{
              selectedStation?.station_name
            }}</span>
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 p-6 pt-2">
          <div
            class="grid grid-cols-2 gap-4 rounded-xl border bg-slate-50 p-4 text-xs"
          >
            <div>
              <p class="font-black text-slate-400 uppercase">Code</p>
              <p class="font-bold">{{ selectedStation?.code_no }}</p>
            </div>
            <div>
              <p class="font-black text-slate-400 uppercase">Franchise</p>
              <p class="font-bold">{{ selectedStation?.franchise_name }}</p>
            </div>
          </div>

          <div
            class="relative h-64 overflow-hidden rounded-xl border-2 border-slate-100"
          >
            <LocationBusStation
              v-if="selectedStation"
              :locations="detailMarkers"
              :selectable="false"
              :center="mapCenter"
              :zoom="15"
            />
          </div>

          <div
            class="flex items-center justify-between rounded bg-slate-100 p-2 font-mono text-[10px] text-slate-500"
          >
            <span>Lat: {{ selectedStation?.latitude }}</span>
            <span>Lng: {{ selectedStation?.longitude }}</span>
          </div>
        </div>

        <DialogFooter
          class="flex items-center justify-end border-t bg-slate-50/50 p-4"
        >
          <div class="flex gap-2">
            <Button
              v-if="selectedStation?.status_id !== 18"
              variant="destructive"
              size="sm"
              @click="declineStation(selectedStation.id)"
            >
              Deny
            </Button>
            <Button
              v-if="selectedStation?.status_id !== 1"
              class="bg-green-600 text-white hover:bg-green-700"
              size="sm"
              @click="approveStation(selectedStation.id)"
            >
              Approve
            </Button>
            <Button
              variant="outline"
              class="border border-gray-300"
              @click="isDetailModalOpen = false"
              >Close</Button
            >
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
