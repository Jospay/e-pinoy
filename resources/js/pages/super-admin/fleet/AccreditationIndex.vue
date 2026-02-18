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
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
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
    tab: string;
    franchises: string[];
    status: 'active' | 'pending' | 'inactive';
  };
}>();

// --- Define accreditationRow Interface ---
interface accreditationRow {
  id: number;
  franchise_name?: string;
  vehicle_type: string;
  status_name: string;
}

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Accreditation Management', href: '#' },
];

// --- 4. Setup Reactive State for Filters ---
const activeTab = ref(props.filters.tab);
const selectedFranchise = ref<string[]>(props.filters.franchises || []);
const selectedStatus = ref(props.filters.status || 'active');

// Options for MultiSelect
const franchiseOptions = computed(() =>
  props.franchises.map((f) => ({ id: f.id, label: f.name })),
);

// --- Change Status Modal State ---
const isChangeModalOpen = ref(false);
const selectedAccreditation = ref<Partial<accreditationRow>>({});

const changeForm = useForm({
  vehicle_type: '' as string,
  status: '' as string,
});

const openChangeModal = (franchise: accreditationRow) => {
  selectedAccreditation.value = franchise;
  isChangeModalOpen.value = true;
};

const handleChangeAccreditation = () => {
  if (!selectedAccreditation.value?.id) return;
  changeForm.vehicle_type = selectedAccreditation.value.vehicle_type as string;

  changeForm.patch(
    superAdmin.accreditation.change(selectedAccreditation.value.id).url,
    {
      onSuccess: () => {
        changeForm.reset();
        isChangeModalOpen.value = false;
        toast.success('Accreditation change status successfully!');
      },
    },
  );
};

const statuses = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

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
      cell: ({ row }) =>
        h(Badge, { variant: 'secondary' }, () => row.original.vehicle_type),
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-blue-500 hover:bg-blue-600': status === 'active',
          'bg-amber-500 hover:bg-amber-600': status === 'pending',
          'bg-rose-500 hover:bg-rose-600': status === 'inactive',
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
                  onClick: () => openChangeModal(franchise),
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
    superAdmin.accreditation.index().url,
    {
      tab: activeTab.value,
      status: selectedStatus.value,
      franchises: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true,
    },
  );
};

watch([activeTab], () => {
  selectedFranchise.value = [];
  updateFilters();
});

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
  <Head title="Accreditation Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
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
          :columns="accreditationColumns"
          :data="accreditations.data"
          search-placeholder="Search franchises..."
        />
      </div>
    </div>

    <Dialog v-model:open="isChangeModalOpen">
      <DialogContent class="max-w-md font-mono">
        <DialogHeader>
          <DialogTitle class="text-xl">Change Accreditation Status</DialogTitle>
          <DialogDescription>
            Change the status of this accreditation for
            <strong class="text-blue-500">{{
              selectedAccreditation?.franchise_name
            }}</strong
            >. From {{ selectedAccreditation?.status_name }} to
            <em>"{{ changeForm.status }}"</em>.
          </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>Status</Label>
            <Select v-model="changeForm.status">
              <SelectTrigger>
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <template v-for="s in statuses" :key="s.value">
                  <SelectItem
                    v-if="selectedAccreditation?.status_name !== s.value"
                    :value="s.value"
                  >
                    {{ s.label }}
                  </SelectItem>
                </template>
              </SelectContent>
            </Select>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isChangeModalOpen = false"
            >Cancel</Button
          >
          <Button
            @click="handleChangeAccreditation"
            :disabled="changeForm.processing || !changeForm.status"
          >
            {{ changeForm.processing ? 'Changing...' : 'Confirm Change' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
