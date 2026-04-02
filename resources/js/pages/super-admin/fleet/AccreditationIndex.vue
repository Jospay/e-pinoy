<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// --- Define Props ---
const props = defineProps<{
  accreditations: {
    data: accreditationRow[];
  };
  franchises: { id: number; name: string }[];
  vehicleTypes: { id: number; name: string }[];
  filters: {
    franchises: string[];
  };
}>();

// --- Define accreditationRow Interface ---
interface accreditationRow {
  id: number;
  franchise_name: string;
  vehicle_types: vehicleTypeEntry[];
}

interface vehicleTypeEntry {
  id: number;
  name: string;
  status: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accreditation Management', href: '#' },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchises || []);

// Options for MultiSelect
const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);

const statusForm = useForm({
  vehicle_type: '' as string,
  status: '' as 'active' | 'inactive',
});
const statusModal = ref<{
  isOpen: boolean;
  parentId: number | null;
  parentName: string;
  row: accreditationRow | null;
  pendingStatus: Record<string, 'active' | 'inactive' | null>;
}>({
  isOpen: false,
  parentId: null,
  parentName: '',
  row: null,
  pendingStatus: {},
});

const openStatusModal = (row: accreditationRow) => {
  statusModal.value = {
    isOpen: true,
    parentId: row.id,
    parentName: row.franchise_name,
    row,
    pendingStatus: Object.fromEntries(
      row.vehicle_types.map((s) => [s.name, null]),
    ),
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

const applyStatusChange = (vehicleType: vehicleTypeEntry) => {
  const newStatus = statusModal.value.pendingStatus[vehicleType.name];
  if (!newStatus) return;

  statusForm.clearErrors('status');
  statusForm.vehicle_type = vehicleType.name;
  statusForm.status = newStatus;
  submittingCode.value = vehicleType.name;

  statusForm.patch(
    `${superAdmin.accreditation.change(statusModal.value.parentId!).url}`,
    {
      preserveScroll: true,
      onSuccess: () => {
        vehicleType.status = newStatus;
        statusModal.value.pendingStatus[vehicleType.name] = null;
        submittingCode.value = null;
        statusForm.reset();
        statusModal.value.isOpen = false;
        toast.success('Accreditation status updated successfully!');
      },
    },
  );
};

// Computed columns for the data table
const accreditationColumns = computed<ColumnDef<accreditationRow>[]>(() => {
  const baseColumns: ColumnDef<accreditationRow>[] = [
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'vehicle_type',
      header: 'Vehicle Category',
      cell: ({ row }) => {
        const vehicleTypes = row.original.vehicle_types;
        if (!vehicleTypes?.length)
          return h('span', { class: 'text-muted-foreground' }, 'N/A');
        return h(
          'div',
          { class: 'flex flex-wrap gap-1' },
          vehicleTypes.map((s) =>
            h(Badge, { variant: 'secondary', key: s.name }, () => s.name),
          ),
        );
      },
    },
    {
      id: 'statuses',
      header: 'Statuses',
      cell: ({ row }) => {
        const vehicleTypes = row.original.vehicle_types;
        if (!vehicleTypes?.length)
          return h('div', { class: 'text-muted-foreground' }, 'N/A');
        return h(
          'div',
          { class: 'flex flex-wrap gap-1' },
          vehicleTypes.map((v) => {
            const badgeClass = {
              'bg-blue-500 hover:bg-blue-600': v.status === 'active',
              'bg-amber-500 hover:bg-amber-600': v.status === 'pending',
              'bg-rose-500 hover:bg-rose-600': v.status === 'inactive',
            };
            return h(
              Badge,
              { class: [badgeClass, 'text-white'], key: v.name },
              () => v.status,
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
        const hasVehicles = franchise.vehicle_types?.length > 0;

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
              hasVehicles
                ? h(
                    DropdownMenuItem,
                    {
                      class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                      onClick: () => openStatusModal(franchise),
                    },
                    () => 'Change Status',
                  )
                : h(
                    DropdownMenuItem,
                    { disabled: true, class: 'text-muted-foreground' },
                    () => 'No Vehicle Types',
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
    superAdmin.accreditation.index().url,
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
  <Head title="Accreditation Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
      <div class="rounded-xl border p-4">
  <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <h2 class="font-mono text-xl font-semibold">
      Franchise Accreditations
    </h2>

    <div class="flex w-full gap-4 md:w-auto">
      <MultiSelect
        v-model="selectedFranchise"
        :options="franchiseOptions"
        placeholder="Select Franchises"
        all-label="All Franchises"
        class="w-full md:w-[250px]"
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
    :columns="accreditationColumns"
    :data="accreditations.data"
    search-placeholder="Search franchises..."
  />
</div>
    </div>

    <!-- Change Status Modal -->
    <Dialog v-model:open="statusModal.isOpen">
      <DialogContent class="overflow-hidden p-0 sm:max-w-2xl">
        <div class="flex max-h-[90vh] flex-col">
          <DialogHeader class="ps-3 pt-6 pb-2">
            <DialogTitle class="flex items-center gap-2">
              <ShieldCheck class="h-5 w-5 text-blue-600" />
              Change Accreditation Status
            </DialogTitle>
            <DialogDescription class="ps-7">
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
              <div v-for="v in statusModal.row?.vehicle_types" :key="v.name">
                <div
                  class="flex flex-wrap items-center justify-between rounded-lg border-2 px-4 py-3"
                >
                  <!-- Vehicle Type info -->
                  <div class="flex items-center gap-3">
                    <div>
                      <p class="text-sm font-semibold uppercase">
                        {{ v.name }}
                      </p>
                    </div>
                    <span
                      class="inline-block rounded px-2 py-0.5 text-[10px] font-bold text-white"
                      :class="{
                        'bg-blue-500': v.status === 'active',
                        'bg-amber-500': v.status === 'pending',
                        'bg-rose-500': v.status === 'inactive',
                      }"
                      >{{ v.status }}</span
                    >
                  </div>

                  <!-- Status selector + apply -->
                  <div class="flex items-center gap-2">
                    <Select
                      :model-value="statusModal.pendingStatus[v.name] ?? ''"
                      @update:model-value="
                        (val) =>
                          (statusModal.pendingStatus[v.name] = val as
                            | 'active'
                            | 'inactive')
                      "
                    >
                      <SelectTrigger class="w-[120px] cursor-pointer text-xs">
                        <SelectValue placeholder="Change to..." />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="opt in statusOptions(v.status)"
                          :key="opt"
                          :value="opt"
                          class="cursor-pointer text-xs"
                        >
                          {{ opt.charAt(0).toUpperCase() + opt.slice(1) }}
                        </SelectItem>
                      </SelectContent>
                    </Select>

                    <Button
                      variant="default"
                      :disabled="
                        !statusModal.pendingStatus[v.name] ||
                        statusForm.processing
                      "
                      @click="applyStatusChange(v)"
                    >
                      {{
                        statusForm.processing &&
                        statusForm.vehicle_type === v.name
                          ? 'Saving...'
                          : 'Apply'
                      }}
                    </Button>
                  </div>
                </div>
                <InputError
                  v-if="submittingCode === v.name"
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
