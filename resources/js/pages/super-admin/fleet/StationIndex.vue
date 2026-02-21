<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import InputError from '@/components/InputError.vue';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { MapPin, MoreHorizontal, ShieldCheck } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
  stations: { data: StationRow[] };
  franchises: { id: number; name: string }[];
  branches: { id: number; name: string }[];
  vehicleTypes: { id: number; name: string }[];
  filters: {
    type: 'franchise' | 'branch';
    franchises: string[];
    branches: string[];
  };
}>();

interface StationRow {
  id: number;
  franchise_name: string;
  branch_name?: string;
  stations: StationEntry[];
}

interface StationEntry {
  id: number;
  name: string;
  code: string;
  status: string;
}

interface StationDetail {
  id: number;
  name: string;
  code_no: string;
  status: string;
  latitude: number | null;
  longitude: number | null;
}

interface StationModalData {
  franchise_name: string;
  stations: StationDetail[];
  fares: {
    from_id: number;
    from_code: string;
    to_id: number;
    to_code: string;
    amount: string;
  }[];
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Bus Station Management', href: '#' },
];

// --- Filter State ---
const selectedType = ref(props.filters.type || 'franchise');
const selectedBranches = ref<string[]>(props.filters.branches || []);
const selectedFranchise = ref<string[]>(props.filters.franchises || []);

const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);
const branchOptions = computed(() =>
  props.branches.map((b) => ({ id: b.id, label: b.name })),
);

// --- View Station Modal ---
const stationModal = useDetailsModal<StationModalData>({
  baseUrl: '/super-admin/station',
});

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

const statusForm = useForm({
  station_id: null as number | null,
  status: null as 'active' | 'inactive' | null,
});
const statusModal = ref<{
  isOpen: boolean;
  parentId: number | null;
  parentName: string;
  row: StationRow | null;
  pendingStatus: Record<string, 'active' | 'inactive' | null>;
}>({
  isOpen: false,
  parentId: null,
  parentName: '',
  row: null,
  pendingStatus: {},
});

const openStatusModal = (row: StationRow) => {
  statusModal.value = {
    isOpen: true,
    parentId: row.id,
    parentName: row.franchise_name,
    row,
    pendingStatus: Object.fromEntries(row.stations.map((s) => [s.code, null])),
  };
};

const closeStatusModal = () => {
  statusModal.value.isOpen = false;
  statusForm.clearErrors('status');
};

const statusOptions = (current: string): ('active' | 'inactive')[] => {
  if (current === 'active') return ['inactive'];
  if (current === 'inactive') return ['active'];
  return ['active', 'inactive'];
};

const submittingCode = ref<string | null>(null);

const applyStatusChange = (station: StationEntry) => {
  const newStatus = statusModal.value.pendingStatus[station.code];
  if (!newStatus) return;

  statusForm.clearErrors('status');
  statusForm.station_id = station.id;
  statusForm.status = newStatus;
  submittingCode.value = station.code;

  statusForm.patch(
    `${superAdmin.station.updateStatus(statusModal.value.parentId!).url}?type=${selectedType.value}`,
    {
      preserveScroll: true,
      onSuccess: () => {
        station.status = newStatus;
        statusModal.value.pendingStatus[station.code] = null;
        submittingCode.value = null;
        statusForm.reset();
        statusModal.value.isOpen = false;
        toast.success('Station status updated successfully!');
      },
    },
  );
};

// --- Table Columns ---
const stationColumns = computed<ColumnDef<StationRow>[]>(() => {
  const baseColumns: ColumnDef<StationRow>[] = [
    { accessorKey: 'franchise_name', header: 'Franchise' },
  ];

  if (selectedType.value === 'branch') {
    baseColumns.push({ accessorKey: 'branch_name', header: 'Branch' });
  }

  baseColumns.push(
    {
      accessorKey: 'stations',
      header: 'Station Codes',
      cell: ({ row }) => {
        const stations = row.original.stations;
        if (!stations?.length)
          return h('span', { class: 'text-muted-foreground' }, 'N/A');
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
        if (!stations?.length)
          return h('div', { class: 'text-muted-foreground' }, 'N/A');
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
        const station = row.original;
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
                  onclick: () =>
                    stationModal.open(station.id, {
                      params: { type: selectedType.value },
                    }),
                },
                () => 'View Station Details',
              ),
              h(DropdownMenuSeparator),
              h(
                DropdownMenuItem,
                {
                  class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                  onclick: () => openStatusModal(station),
                },
                () => 'Change Status',
              ),
            ]),
          ]),
        ]);
      },
    },
  );

  return baseColumns;
});

// --- URL Filter Watchers ---
const updateFilters = () => {
  router.get(
    superAdmin.station.index().url,
    {
      type: selectedType.value,
      franchises: selectedFranchise.value || [],
      branches: selectedBranches.value || [],
    },
    { preserveScroll: true, replace: true },
  );
};

watch([selectedType], () => {
  selectedFranchise.value = [];
  selectedBranches.value = [];
  updateFilters();
});

watch(selectedFranchise, () => {
  selectedBranches.value = [];
  updateFilters();
});
</script>

<template>
  <Head title="Bus Station Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div class="rounded-xl border p-4">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold capitalize">
            {{ selectedType }} Bus Station
          </h2>

          <div class="flex gap-4">
            <Select v-model="selectedType">
              <SelectTrigger class="w-[150px] cursor-pointer">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise" class="cursor-pointer"
                  >Franchise</SelectItem
                >
                <SelectItem value="branch" class="cursor-pointer"
                  >Branch</SelectItem
                >
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
          :columns="stationColumns"
          :data="stations.data"
          search-placeholder="Search franchises..."
        />
      </div>
    </div>

    <!-- View Station Details Modal -->
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
              <span class="font-bold text-blue-500">{{
                modalData?.franchise_name ?? '...'
              }}</span>
            </DialogDescription>
          </DialogHeader>

          <div
            v-if="stationModal.isLoading.value"
            class="flex items-center justify-center py-16"
          >
            <span class="text-sm text-slate-500">Loading...</span>
          </div>
          <div
            v-else-if="stationModal.isError.value"
            class="p-6 text-center text-sm text-rose-500"
          >
            Failed to load station details.
          </div>

          <div
            v-else-if="modalData"
            class="flex-1 overflow-y-auto"
            style="scrollbar-gutter: stable both-edges"
          >
            <div class="space-y-4 p-4">
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

    <!-- Change Status Modal -->
    <Dialog v-model:open="statusModal.isOpen">
      <DialogContent class="overflow-hidden p-0 sm:max-w-2xl">
        <div class="flex max-h-[90vh] flex-col">
          <DialogHeader class="p-6 pb-2">
            <DialogTitle class="flex items-center gap-2">
              <ShieldCheck class="h-5 w-5 text-blue-600" />
              Change Station Status
            </DialogTitle>
            <DialogDescription>
              {{ selectedType === 'branch' ? 'Branch' : 'Franchise' }}:
              <span class="font-bold text-blue-500">{{
                statusModal.parentName
              }}</span>
            </DialogDescription>
          </DialogHeader>

          <div
            class="flex-1 overflow-y-auto p-4"
            style="scrollbar-gutter: stable both-edges"
          >
            <div class="space-y-2">
              <div v-for="s in statusModal.row?.stations" :key="s.code">
                <div
                  class="flex flex-wrap items-center justify-between rounded-lg border bg-slate-50 px-4 py-3"
                >
                  <!-- Station info -->
                  <div class="flex items-center gap-3">
                    <div>
                      <p class="text-xs font-black text-slate-400 uppercase">
                        {{ s.code }}
                      </p>
                      <p class="text-sm font-semibold text-slate-700">
                        {{ s.name }}
                      </p>
                    </div>
                    <span
                      class="inline-block rounded px-2 py-0.5 text-[10px] font-bold text-white"
                      :class="{
                        'bg-blue-500': s.status === 'active',
                        'bg-amber-500': s.status === 'pending',
                        'bg-rose-500': s.status === 'inactive',
                      }"
                      >{{ s.status }}</span
                    >
                  </div>

                  <!-- Status selector + apply -->
                  <div class="flex items-center gap-2">
                    <Select
                      :model-value="statusModal.pendingStatus[s.code] ?? ''"
                      @update:model-value="
                        (val) =>
                          (statusModal.pendingStatus[s.code] = val as
                            | 'active'
                            | 'inactive')
                      "
                    >
                      <SelectTrigger class="w-[120px] cursor-pointer text-xs">
                        <SelectValue placeholder="Change to..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="opt in statusOptions(s.status)"
                          :key="opt"
                          :value="opt"
                          class="cursor-pointer text-xs"
                        >
                          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
                        </SelectItem>
                      </SelectContent>
                    </Select>

                    <Button
                      :disabled="
                        !statusModal.pendingStatus[s.code] ||
                        statusForm.processing
                      "
                      @click="applyStatusChange(s)"
                    >
                      {{
                        statusForm.processing && statusForm.station_id === s.id
                          ? 'Saving...'
                          : 'Apply'
                      }}
                    </Button>
                  </div>
                </div>
                <InputError
                  v-if="submittingCode === s.code"
                  :message="statusForm.errors.status"
                  class="mt-.5 ms-1 w-full"
                />
              </div>
            </div>
          </div>

          <DialogFooter class="flex items-center justify-end border-t p-4">
            <Button variant="outline" @click="closeStatusModal">Close</Button>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
