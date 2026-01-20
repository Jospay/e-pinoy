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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { debounce } from 'lodash-es';
import { AlertCircleIcon, MoreHorizontal, PlusIcon } from 'lucide-vue-next';
import { computed, h, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// --- Define Props ---
const props = defineProps<{
  vehicles: {
    data: VehicleRow[];
  };
  franchises: { id: number; name: string }[];
  filters: {
    franchise: string[];
    status: 'active' | 'available' | 'maintenance';
  };
}>();

// --- Define VehicleRow Interface ---
interface VehicleRow {
  id: number;
  franchise_name?: string;
  plate_number: string;
  vin: string;
  status_name: string;
}

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Vehicle Management',
    href: superAdmin.vehicle.index().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchise || []);
const selectedStatus = ref(props.filters.status || 'active');

const selectedContext = computed({
  get: () => selectedFranchise.value,
  set: (val: string[]) => {
    selectedFranchise.value = val;
  },
});

// Mapping options for the MultiSelect
const contextOptions = computed(() => {
  const data = props.franchises;
  return data.map((item) => ({ id: item.id, label: item.name }));
});

// for vehicle details
interface VehicleModal {
  id: number;
  status: string;
  plate_number: string;
  vin: string;
  brand: string;
  model: string;
  year: string;
  color: string;
  or_cr: string;
  franchise_id: number;
}

const vehicleDetails = computed(() => {
  const data = vehicleModal.data.value;
  if (!data) return [];

  return [
    { label: 'Status', value: data.status, type: 'text' },
    { label: 'Plate Number', value: data.plate_number, type: 'text' },
    { label: 'Vehicle Identification Number', value: data.vin, type: 'text' },
    { label: 'Brand', value: data.brand, type: 'text' },
    { label: 'Model', value: data.model, type: 'text' },
    { label: 'Year', value: data.year, type: 'text' },
    { label: 'Color', value: data.color, type: 'text' },
    { label: 'OR-CR Document', value: data.or_cr, type: 'link' },
  ].filter((item) => item.value);
});

// --- Modal State ---
const vehicleModal = useDetailsModal<VehicleModal>({
  baseUrl: '/super-admin/vehicle',
});

// --- Edit Mode Logic ---
const isEditMode = ref(false);
const editForm = useForm({
  franchise_id: null as number | null,
  plate_number: '',
  vin: '',
  brand: '',
  model: '',
  year: '',
  color: '',
  or_cr: null as File | null,
});

const startEditing = () => {
  const data = vehicleModal.data.value;
  if (!data) return;

  editForm.franchise_id = data.franchise_id;
  editForm.plate_number = data.plate_number;
  editForm.vin = data.vin;
  editForm.brand = data.brand;
  editForm.model = data.model;
  editForm.year = data.year;
  editForm.color = data.color;
  editForm.or_cr = null; // Reset file input
  isEditMode.value = true;
};

const handleUpdateVehicle = () => {
  if (!vehicleModal.data.value?.id) return;

  // 1. Transform the data to include the method spoofing key
  editForm
    .transform((data) => ({
      ...data,
      _method: 'patch', // This tells Laravel to treat the POST as a PATCH
    }))
    .post(superAdmin.vehicle.update(vehicleModal.data.value.id).url, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        isEditMode.value = false;
        toast.success('Vehicle updated successfully!');

        // 2. Refresh the modal data to show the newly saved information
        if (vehicleModal.data.value?.id) {
          vehicleModal.open(vehicleModal.data.value.id);
        }
      },
      onError: (errors) => {
        // Logic for handling validation errors is already handled by
        // the :class bindings in your template, but we can add a toast here.
        toast.error('Please check the form for errors.');
      },
    });
};

// Reset Edit Mode when modal closes
watch(vehicleModal.isOpen, (isOpen) => {
  if (!isOpen) {
    isEditMode.value = false;
    editForm.reset();
    editForm.clearErrors();
  }
});

// for maintenance history
interface MaintenanceRow {
  id: number;
  description: string;
  maintenance_date: string;
  next_maintenance_date: string;
  inventory_name: string;
  category: string;
  specification: string;
}
const maintenanceModal = useDetailsModal<MaintenanceRow[]>({
  baseUrl: '/super-admin/vehicle',
});

// --- Change Status Modal State ---
const isChangeModalOpen = ref(false);
const selectedVehicle = ref<Partial<VehicleRow>>({});

const changeForm = useForm({
  status: '' as string,
});

const openChangeModal = (vehicle: VehicleRow) => {
  selectedVehicle.value = vehicle;
  isChangeModalOpen.value = true;
};

const handleChangeVehicle = () => {
  if (!selectedVehicle.value?.id) return;

  changeForm.patch(superAdmin.vehicle.change(selectedVehicle.value.id).url, {
    onSuccess: () => {
      changeForm.reset();
      isChangeModalOpen.value = false;
      toast.success('Vehicle change status successfully!');
    },
  });
};

// --- Delete Vehicle Modal State ---
const isDeleteModalOpen = ref(false);
const isDeletingVehicle = ref(false);

const openDeleteModal = (vehicle: VehicleRow) => {
  selectedVehicle.value = vehicle;
  isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
  if (!selectedVehicle.value) return
  isDeletingVehicle.value = true;

  router.delete(superAdmin.vehicle.destroy(selectedVehicle.value.id).url, {
    preserveScroll: true,

    onSuccess: () => {
      isDeleteModalOpen.value = false;
      selectedVehicle.value = {};
      toast.success('Vehicle deleted successfully!');
    },

    onFinish: () => {
      isDeletingVehicle.value = false;
    },
  });
}

const statuses = [
  { value: 'active', label: 'Active' },
  { value: 'available', label: 'Available' },
  { value: 'maintenance', label: 'Maintenance' },
];

const createVehicle = () => {
  router.get(superAdmin.vehicle.create().url);
};

// Computed columns for the data table
const vehicleColumns = computed<ColumnDef<VehicleRow>[]>(() => {
  const baseColumns: ColumnDef<VehicleRow>[] = [
    {
      accessorKey: 'franchise_name',
      header: 'Franchise',
    },
    {
      accessorKey: 'vin',
      header: 'Vehicle Identification Number',
    },
    {
      accessorKey: 'plate_number',
      header: 'Plate Number',
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-blue-500 hover:bg-blue-600': status === 'active',
          'bg-rose-500 hover:bg-rose-600': status === 'maintenance',
          'bg-green-500 hover:bg-green-600': status === 'available',
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
        const vehicle = row.original;

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
                  onClick: () => vehicleModal.open(vehicle.id),
                },
                () => 'View Vehicle Details',
              ),
              h(
                DropdownMenuItem,
                {
                  class: 'cursor-pointer',
                  onClick: () =>
                    maintenanceModal.open(vehicle.id, 'maintenances'),
                },
                () => 'View Maintenance History',
              ),
              h(DropdownMenuSeparator),
              [
                h(
                  DropdownMenuItem,
                  {
                    class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                    onClick: () => openChangeModal(vehicle),
                  },
                  () => 'Change Status',
                ),
              ],
              h(DropdownMenuSeparator),
              [
                h(
                  DropdownMenuItem,
                  {
                    class: 'cursor-pointer text-red-500 focus:text-red-600',
                    onClick: () => openDeleteModal(vehicle),
                  },
                  () => 'Delete Vehicle',
                ),
              ],
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
    superAdmin.vehicle.index().url,
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

// Watch for select filter changes (debounced)
watch(
  [selectedStatus],
  debounce(() => {
    updateFilters();
  }, 300),
);
</script>

<template>
  <Head title="Vehicle Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">Franchise Vehicles</h2>

          <div class="flex gap-4">
            <Select v-model="selectedStatus">
              <SelectTrigger class="w-[150px]">
                <SelectValue placeholder="Filter by..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="active"> Active </SelectItem>
                <SelectItem value="available"> Available </SelectItem>
                <SelectItem value="maintenance"> Maintenance </SelectItem>
              </SelectContent>
            </Select>

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
          search-placeholder="Search vehicles..."
        >
          <template #custom-actions>
            <Button class="me-5" @click="createVehicle">
              <PlusIcon />Add Vehicle
            </Button>
          </template>
        </DataTable>
      </div>
    </div>
  </AppLayout>

  <Dialog v-model:open="vehicleModal.isOpen.value">
    <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>{{
          isEditMode ? 'Edit Vehicle' : 'Vehicle Details'
        }}</DialogTitle>
      </DialogHeader>

      <div class="py-4">
        <div v-if="vehicleModal.isLoading.value" class="grid grid-cols-2 gap-4">
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div v-else-if="isEditMode" class="grid grid-cols-2 gap-x-6 gap-y-4">
          <div class="flex flex-col gap-1.5">
            <Label>Franchise</Label>
            <Select v-model="editForm.franchise_id">
              <SelectTrigger
                :class="{ 'border-destructive': editForm.errors.franchise_id }"
              >
                <SelectValue placeholder="Select Franchise" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="f in franchises" :key="f.id" :value="f.id">
                  {{ f.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <span
              v-if="editForm.errors.franchise_id"
              class="text-xs text-destructive"
              >{{ editForm.errors.franchise_id }}</span
            >
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>Plate Number</Label>
            <Input
              v-model="editForm.plate_number"
              :class="{ 'border-destructive': editForm.errors.plate_number }"
            />
            <span
              v-if="editForm.errors.plate_number"
              class="text-xs text-destructive"
              >{{ editForm.errors.plate_number }}</span
            >
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>VIN</Label>
            <Input
              v-model="editForm.vin"
              :class="{ 'border-destructive': editForm.errors.vin }"
            />
            <span v-if="editForm.errors.vin" class="text-xs text-destructive">{{
              editForm.errors.vin
            }}</span>
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>Brand</Label>
            <Input
              v-model="editForm.brand"
              :class="{ 'border-destructive': editForm.errors.brand }"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>Model</Label>
            <Input
              v-model="editForm.model"
              :class="{ 'border-destructive': editForm.errors.model }"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>Year</Label>
            <Input
              v-model="editForm.year"
              type="number"
              :class="{ 'border-destructive': editForm.errors.year }"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <Label>Color</Label>
            <Input
              v-model="editForm.color"
              :class="{ 'border-destructive': editForm.errors.color }"
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <Label
              >OR-CR
              <span class="text-[11px]"
                >(Leave empty to keep current)</span
              ></Label
            >
            <Input
              type="file"
              @input="editForm.or_cr = $event.target.files[0]"
              class="cursor-pointer"
            />
            <span
              v-if="editForm.errors.or_cr"
              class="text-xs text-destructive"
              >{{ editForm.errors.or_cr }}</span
            >
          </div>
        </div>

        <div
          v-else-if="vehicleDetails.length > 0"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in vehicleDetails" :key="item.label">
            <div class="font-medium text-muted-foreground">
              {{ item.label }}:
            </div>

            <div v-if="item.type === 'link'">
              <a
                :href="item.value"
                target="_blank"
                class="flex items-center gap-1 text-blue-500 hover:underline"
                >View Document</a
              >
            </div>

            <div v-else>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="vehicleModal.isError.value">
          <Alert
            variant="destructive"
            class="border-2 border-red-500 shadow-lg"
          >
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load vehicle details.
            </AlertDescription>
          </Alert>
        </div>
      </div>

      <DialogFooter class="mt-5 border-t pt-4">
        <div class="flex w-full justify-between gap-2">
          <div>
            <Button
              v-if="!isEditMode && vehicleModal.data.value"
              @click="startEditing"
              variant="default"
            >
              Edit
            </Button>
            <Button
              v-if="isEditMode"
              @click="handleUpdateVehicle"
              :disabled="editForm.processing"
            >
              {{ editForm.processing ? 'Saving Changes...' : 'Save Changes' }}
            </Button>
          </div>
          <div class="flex gap-2">
            <Button
              v-if="isEditMode"
              variant="ghost"
              @click="isEditMode = false"
              >Cancel</Button
            >
            <Button variant="outline" @click="vehicleModal.close">Close</Button>
          </div>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="isChangeModalOpen">
    <DialogContent class="max-w-md font-mono">
      <DialogHeader>
        <DialogTitle class="text-xl">Change Driver Status</DialogTitle>
        <DialogDescription>
          Change the status of this vehicle plate number
          <strong class="text-blue-500">{{
            selectedVehicle?.plate_number
          }}</strong
          >. From {{ selectedVehicle?.status_name }} to
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
                  v-if="selectedVehicle?.status_name !== s.value"
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
          @click="handleChangeVehicle"
          :disabled="changeForm.processing || !changeForm.status"
        >
          {{ changeForm.processing ? 'Changing...' : 'Confirm Change' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="isDeleteModalOpen">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Delete Vehicle</DialogTitle>
        <DialogDescription>
          Are you sure you want to delete vehicle with plate no.
          <span class="font-semibold">
            {{ selectedVehicle?.plate_number }}
          </span>?
          <br />
          This action cannot be undone.
        </DialogDescription>
      </DialogHeader>

      <DialogFooter class="gap-2">
        <Button variant="outline" @click="isDeleteModalOpen = false"
          >Cancel</Button
        >

        <Button
          variant="destructive"
          @click="confirmDelete"
          :disabled="isDeletingVehicle"
        >
          {{ isDeletingVehicle ? 'Deleting...' : 'Yes, Delete' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="maintenanceModal.isOpen.value">
    <DialogContent class="flex max-h-[80vh] max-w-4xl flex-col">
      <DialogHeader>
        <DialogTitle>Maintenance History</DialogTitle>
        <DialogDescription>
          Showing all maintenance records for this vehicle.
        </DialogDescription>
      </DialogHeader>

      <div class="flex-1 overflow-y-auto py-4 pe-1.5">
        <div v-if="maintenanceModal.isLoading.value" class="space-y-4">
          <Skeleton v-for="i in 3" :key="i" class="h-20 w-full" />
        </div>

        <Alert v-else-if="maintenanceModal.isError.value" variant="destructive">
          <AlertCircleIcon class="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription
            >Failed to load maintenance history.</AlertDescription
          >
        </Alert>

        <div v-else-if="maintenanceModal.data.value?.length" class="space-y-4">
          <div
            v-for="item in maintenanceModal.data.value"
            :key="item.id"
            class="rounded-lg border p-4 transition-colors hover:bg-muted/50"
          >
            <div class="mb-2 flex items-start justify-between">
              <div>
                <h4 class="text-lg font-bold text-primary">
                  {{ item.inventory_name }}
                </h4>
                <Badge variant="outline" class="mt-1">{{
                  item.category
                }}</Badge>
              </div>
              <div class="text-right text-sm">
                <p class="font-medium">Date: {{ item.maintenance_date }}</p>
                <p class="text-muted-foreground italic">
                  Next: {{ item.next_maintenance_date }}
                </p>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
              <div>
                <span class="block font-semibold">Specification:</span>
                <p class="text-muted-foreground">{{ item.specification }}</p>
              </div>
              <div>
                <span class="block font-semibold">Work Done:</span>
                <p class="text-muted-foreground">{{ item.description }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="py-10 text-center text-muted-foreground">
          No maintenance records found for this vehicle.
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="maintenanceModal.close">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
