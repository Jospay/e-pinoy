<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { AlertCircleIcon, MoreHorizontal } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  drivers: {
    data: DriverRow[];
  };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: string[];
    status: 'active' | 'retired' | 'suspended';
  };
}>();

// --- Define DriverRow Interface ---
interface DriverRow {
  id: number;
  franchise_name?: string;
  username: string;
  email: string;
  phone: string;
  status_name: string;
}

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Driver Management',
    href: superAdmin.driver.index().url,
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

interface DriverModal {
  id: number;
  status: string;
  code_number: string;
  username: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  region: string;
  city: string;
  barangay: string;
  province?: string;
  postal_code: string;
  created_at: string;
  license_number: string;
  license_expiry: string;
  front_license_picture: string;
  back_license_picture: string;
  nbi_clearance: string;
  selfie_picture: string;
  shift: string;
  hire_date?: string;
}
const driverDetails = computed(() => {
  const data = driverModal.data.value;
  if (!data) return [];

  return [
    { label: 'Username', value: data.username, type: 'text' },
    { label: 'Code Number', value: data.code_number, type: 'text' },
    { label: 'Name', value: data.name, type: 'text' },
    { label: 'Email', value: data.email, type: 'text' },
    { label: 'Phone', value: data.phone, type: 'text' },
    { label: 'Status', value: data.status, type: 'text' },
    { label: 'Region', value: data.region, type: 'text' },
    { label: 'Province', value: data.province, type: 'text' },
    { label: 'City', value: data.city, type: 'text' },
    { label: 'Barangay', value: data.barangay, type: 'text' },
    { label: 'Postal Code', value: data.postal_code, type: 'text' },
    { label: 'Address', value: data.address, type: 'text' },
    { label: 'Registered At', value: data.created_at, type: 'text' },
    { label: 'Hire Date', value: data.hire_date, type: 'text' },
    { label: 'Shift', value: data.shift, type: 'text' },
    { label: 'License Number', value: data.license_number, type: 'text' },
    { label: 'License Expiry', value: data.license_expiry, type: 'text' },
    {
      label: 'Front License Picture',
      value: data.front_license_picture,
      type: 'link',
    },
    {
      label: 'Back License Picture',
      value: data.back_license_picture,
      type: 'link',
    },
    { label: 'NBI Clearance', value: data.nbi_clearance, type: 'link' },
    { label: 'Selfie Picture', value: data.selfie_picture, type: 'link' },
  ].filter((item) => item.value);
});

// --- Modal State ---
const driverModal = useDetailsModal<DriverModal>({
  baseUrl: '/super-admin/driver',
});

// Computed columns for the data table
const driverColumns = computed<ColumnDef<DriverRow>[]>(() => {
  const baseColumns: ColumnDef<DriverRow>[] = [
    {
      accessorKey: 'username',
      header: 'Driver',
    },
    {
      accessorKey: 'franchise_name',
      header: 'Franchise'
    },
    {
      accessorKey: 'email',
      header: 'Email',
    },
    {
      accessorKey: 'phone',
      header: 'Phone',
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-blue-500 hover:bg-blue-600': status === 'active',
          'bg-rose-500 hover:bg-rose-600':
            status === 'suspended' || status === 'retired',
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
        const driver = row.original;

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
                  onClick: () => driverModal.open(driver.id),
                },
                () => 'View Driver Details',
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
    superAdmin.driver.index().url,
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

  <Head title="Driver Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Drivers
          </h2>

          <div class="flex gap-4">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px] cursor-pointer">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active" class="cursor-pointer">
                  Active
                </SelectItem>
                <SelectItem value="retired" class="cursor-pointer">
                  Retired
                </SelectItem>
                <SelectItem value="suspended" class="cursor-pointer">
                  Suspended
                </SelectItem>
              </SelectContent>
            </Select>

            <MultiSelect v-model="selectedContext" :options="contextOptions" placeholder="Select Franchises"
              all-label="All Franchises" @change="
                (val) => {
                  selectedFranchise = val;

                  updateFilters();
                }
              " />
          </div>
        </div>

        <DataTable :columns="driverColumns" :data="drivers.data" search-placeholder="Search drivers..." />
      </div>
    </div>
  </AppLayout>

  <Dialog v-model:open="driverModal.isOpen.value">
    <DialogContent class="max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Driver Details</DialogTitle>
      </DialogHeader>
      <DialogDescription>
        <div v-if="driverModal.isLoading.value" class="grid grid-cols-2 gap-4">
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div v-else-if="driverDetails.length > 0" class="grid grid-cols-2 gap-4">
          <template v-for="item in driverDetails" :key="item.label">
            <div class="font-medium">{{ item.label }}:</div>

            <div v-if="item.type === 'link'">
              <a :href="item.value" target="_blank" class="text-blue-500 hover:underline">View</a>
            </div>

            <div v-else>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="driverModal.isError.value">
          <Alert variant="destructive" class="border-2 border-red-500 shadow-lg">
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load driver details.
            </AlertDescription>
          </Alert>
        </div>
      </DialogDescription>

      <DialogFooter class="mt-5">
        <Button variant="outline" @click="driverModal.close">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
