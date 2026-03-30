<script setup lang="ts">
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import axios from 'axios';
import { AlertCircle, MoreHorizontal } from 'lucide-vue-next';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { Spinner } from '@/components/ui/spinner';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Branch, VehicleType } from '@/types';

// Status Constants
const STATUS_ACTIVE = 1;
const STATUS_MAINTENANCE = 5;
const STATUS_AVAILABLE = 15;

interface Vehicle {
  id: number;
  plate_number: string;
  vin: string;
  brand: string;
  model: string;
  color: string;
  year: number;
  capacity: number;
  status_id: number;
  status_name: string;
  branch_id: number | null;
  branch_name?: string;
  or_cr: string;
  vehicle_type_id: number;
  vehicle_type_name?: string;
  driver_id: number | null;
}

interface VehiclesPaginator {
  current_page: number;
  data: Vehicle[];
  first_page_url: string | null;
  from: number | null;
  last_page: number;
  last_page_url: string | null;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

interface Status {
  id: number;
  name: string;
}

interface Props {
  vehicles: VehiclesPaginator;
  inventories: any[];
  franchiseVehicleTypes: VehicleType[];
  branches: Branch[];
  statuses: Status[];
  filters?: {
    search?: string;
    status?: string;
    vehicle_type?: string;
    branch_id?: string | number;
  };
}

const props = defineProps<Props>();
const page = usePage();
const errors = computed(() => page.props.errors as any);

// State
const paginator = ref(props.vehicles);
const isSaving = ref(false);
const deletingId = ref<number | null>(null);
const selectedVehicleToDelete = ref<Vehicle | null>(null);
const or_cr_file = ref<File | null>(null);
const existing_or_cr = ref<string | null>(null);

const showDialog = ref(false);
const dialogMode = ref<'create' | 'edit'>('create');
const editingVehicle = ref<Vehicle | null>(null);

// Form refs
const plate_number = ref('');
const vin = ref('');
const brand = ref('');
const model = ref('');
const color = ref('');
const year = ref<number>();
const statusId = ref<string>('');
const capacity = ref<number | undefined>();
const branchId = ref<string>('franchise');
const vehicleTypeId = ref<string>('');

// Maintenance Form State
const showMaintenanceDialog = ref(false);
const isSubmittingMaintenance = ref(false);

const maintenanceForm = ref({
  vehicle_id: null as number | null,
  inventory_id: null as number | null,
  quantity: 1,
  description: '',
  maintenance_date: new Date().toISOString().substr(0, 10),
  next_maintenance_date: '',
});

const resetMaintenanceForm = () => {
  maintenanceForm.value = {
    vehicle_id: null,
    inventory_id: null,
    quantity: 1,
    description: '',
    maintenance_date: new Date().toISOString().substr(0, 10),
    next_maintenance_date: '',
  };
};

// Logic
const availableStatuses = computed(() => {
  if (dialogMode.value === 'create') {
    return [{ id: STATUS_AVAILABLE, name: 'Available' }];
  }

  if (dialogMode.value === 'edit' && editingVehicle.value) {
    const currentStatus = editingVehicle.value.status_id;
    const hasDriver = !!editingVehicle.value.driver_id;

    if (currentStatus === STATUS_MAINTENANCE) {
      return [
        { id: STATUS_MAINTENANCE, name: 'Maintenance' },
        hasDriver
          ? { id: STATUS_ACTIVE, name: 'Active' }
          : { id: STATUS_AVAILABLE, name: 'Available' },
      ];
    }

    if (currentStatus === STATUS_ACTIVE) {
      return [{ id: STATUS_ACTIVE, name: 'Active' }];
    }

    if (currentStatus === STATUS_AVAILABLE) {
      return [{ id: STATUS_AVAILABLE, name: 'Available' }];
    }
  }

  return props.statuses;
});

const isStatusDisabled = computed(() => {
  if (dialogMode.value === 'create') return true;
  if (editingVehicle.value) {
    if (editingVehicle.value.status_id === STATUS_AVAILABLE) return true;
    if (editingVehicle.value.status_id === STATUS_ACTIVE) return true;
  }
  return false;
});

const clearError = (field: string) => {
  if (page.props.errors[field]) {
    delete (page.props.errors as any)[field];
  }
};

watch(
  () => props.vehicles,
  (newVehicles) => {
    paginator.value = newVehicles;
  },
  { deep: true },
);

const breadcrumbs = [{ title: 'Vehicle Management', href: '/owner/vehicles' }];

// Filters
const globalFilter = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const branchFilter = ref(props.filters?.branch_id?.toString() || 'all');
const activeTab = ref(
  props.filters?.vehicle_type || props.franchiseVehicleTypes[0]?.name || '',
);

const updateFilters = debounce(() => {
  router.get(
    window.location.pathname,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: globalFilter.value || undefined,
      vehicle_type: activeTab.value || undefined,
      branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  );
}, 300);

watch(
  [statusFilter, activeTab, globalFilter, branchFilter],
  (newVals, oldVals) => {
    const hasChanged = newVals.some((val, i) => val !== oldVals[i]);
    if (hasChanged) updateFilters();
  },
);

const openCreateDialog = () => {
  dialogMode.value = 'create';
  editingVehicle.value = null;
  plate_number.value = vin.value = brand.value = model.value = color.value = '';
  year.value = capacity.value = undefined;
  statusId.value = STATUS_AVAILABLE.toString();
  branchId.value = 'franchise';

  if (props.franchiseVehicleTypes.length === 1) {
    vehicleTypeId.value = props.franchiseVehicleTypes[0].id.toString();
  } else {
    const currentTabType = props.franchiseVehicleTypes.find(
      (t) => t.name === activeTab.value,
    );
    vehicleTypeId.value = currentTabType?.id.toString() || '';
  }

  or_cr_file.value = null;
  existing_or_cr.value = null;
  showDialog.value = true;
};

const openEditDialog = (vehicle: Vehicle) => {
  dialogMode.value = 'edit';
  editingVehicle.value = vehicle;
  plate_number.value = vehicle.plate_number;
  vin.value = vehicle.vin;
  brand.value = vehicle.brand;
  model.value = vehicle.model;
  color.value = vehicle.color;
  year.value = vehicle.year;
  capacity.value = vehicle.capacity;
  statusId.value = vehicle.status_id.toString();
  branchId.value = vehicle.branch_id
    ? vehicle.branch_id.toString()
    : 'franchise';
  vehicleTypeId.value = vehicle.vehicle_type_id.toString();
  existing_or_cr.value = vehicle.or_cr;
  or_cr_file.value = null;
  showDialog.value = true;
};

const getStatusVariant = (status: string) => {
  const s = status.toLowerCase();
  if (s === 'active') return 'bg-blue-500 hover:bg-blue-600';
  if (s === 'available') return 'bg-green-500 hover:bg-green-600';
  if (s === 'maintenance') return 'bg-rose-500 hover:bg-rose-600';
  return 'bg-gray-500 hover:bg-gray-600';
};

const saveVehicle = () => {
  const formData = new FormData();
  formData.append('plate_number', plate_number.value || '');
  formData.append('vin', vin.value || '');
  formData.append('brand', brand.value || '');
  formData.append('model', model.value || '');
  formData.append('color', color.value || '');
  formData.append('year', year.value ? String(year.value) : '');
  formData.append('capacity', capacity.value ? String(capacity.value) : '');
  formData.append('status_id', statusId.value);
  formData.append('vehicle_type_id', vehicleTypeId.value);

  if (branchId.value !== 'franchise') {
    formData.append('branch_id', branchId.value);
  }

  if (or_cr_file.value) {
    formData.append('or_cr', or_cr_file.value);
  }

  if (dialogMode.value === 'edit') {
    formData.append('_method', 'PUT');
  }

  router.post(
    dialogMode.value === 'create'
      ? '/owner/vehicles'
      : `/owner/vehicles/${editingVehicle.value?.id}`,
    formData,
    {
      forceFormData: true,
      onStart: () => (isSaving.value = true),
      onFinish: () => (isSaving.value = false),
      onSuccess: () => {
        toast.success(
          dialogMode.value === 'create'
            ? 'Vehicle Created!'
            : 'Vehicle Updated!',
        );
        showDialog.value = false;
      },
    },
  );
};

const maintenanceModal = ref({
  isOpen: false,
  isLoading: false,
  isError: false,
  data: [] as any[],
});

const openMaintenanceHistory = async (vehicle: Vehicle) => {
  maintenanceModal.value.isOpen = true;
  maintenanceModal.value.isLoading = true;
  maintenanceModal.value.isError = false;
  maintenanceModal.value.data = [];

  try {
    const response = await axios.get(
      `/owner/vehicles/${vehicle.id}/maintenance-history`,
    );
    maintenanceModal.value.data = response.data;
  } catch (error) {
    console.error('Failed to load maintenance history:', error);
    maintenanceModal.value.isError = true;
  } finally {
    maintenanceModal.value.isLoading = false;
  }
};

const closeMaintenanceHistory = () => {
  maintenanceModal.value.isOpen = false;
};

const selectedInventory = computed(() =>
  props.inventories?.find((i) => i.id === maintenanceForm.value.inventory_id),
);

const submitMaintenance = () => {
  // Prevent submission if frontend detects low stock
  if (
    selectedInventory.value &&
    maintenanceForm.value.quantity > selectedInventory.value.quantity
  ) {
    toast.error('Cannot exceed available stock');
    return;
  }

  router.post('/owner/vehicles/maintenance', maintenanceForm.value, {
    onStart: () => (isSubmittingMaintenance.value = true),
    onFinish: () => (isSubmittingMaintenance.value = false),
    onSuccess: () => {
      toast.success('Maintenance record added and stock updated!');
      showMaintenanceDialog.value = false;
      resetMaintenanceForm();
    },
  });
};

const handlePlateInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  let val = target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (val.length > 3 && isNaN(Number(val[2]))) {
    val = val.slice(0, 3) + ' ' + val.slice(3, 7);
  } else if (val.length > 2) {
    val = val.slice(0, 2) + ' ' + val.slice(2, 7);
  }
  plate_number.value = val;
  clearError('plate_number');
};

const handleVinInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  vin.value = target.value
    .toUpperCase()
    .replace(/[^A-Z0-9]/g, '')
    .replace(/[IOQ]/g, '')
    .slice(0, 17);
  clearError('vin');
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    or_cr_file.value = target.files[0];
    clearError('or_cr');
  }
};

const handleYearInput = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.value.length > 4) target.value = target.value.slice(0, 4);
  year.value = Number(target.value);
  clearError('year');
};

const deleteVehicle = () => {
  if (!selectedVehicleToDelete.value) return;
  const id = selectedVehicleToDelete.value.id;
  router.delete(`/owner/vehicles/${id}`, {
    onStart: () => (deletingId.value = id),
    onFinish: () => {
      deletingId.value = null;
      selectedVehicleToDelete.value = null;
    },
    onSuccess: () => toast.success('Vehicle Deleted!'),
  });
};

const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(url, {}, { preserveState: true, preserveScroll: true });
};

const filteredVehicles = computed(() => paginator.value.data);
const paginationLinks = computed(() =>
  paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  ),
);

const vehicleColors = [
  'White',
  'Black',
  'Silver',
  'Gray',
  'Red',
  'Blue',
  'Brown',
  'Green',
  'Yellow',
  'Orange',
  'Gold',
  'Beige',
  'Magenta',
  'Purple',
];

// Individual watchers for errors
watch(statusId, () => clearError('status_id'));
watch(brand, () => clearError('brand'));
watch(model, () => clearError('model'));
watch(color, () => clearError('color'));
watch(capacity, () => clearError('capacity'));
</script>

<template>
  <Head title="Vehicle Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <Tabs
        v-if="franchiseVehicleTypes.length > 0"
        v-model="activeTab"
        class="w-full"
      >
        <TabsList
          class="w-full justify-start overflow-x-auto bg-muted/50 p-1.5"
        >
          <TabsTrigger
            v-for="type in franchiseVehicleTypes"
            :key="type.id"
            :value="type.name"
            class="gap-2 px-4"
          >
            {{ type.name }}
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <div
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h1 class="mb-1 text-3xl font-bold">Vehicle Management</h1>
          <p class="text-sm text-gray-600 sm:text-base">Manage all vehicles</p>
        </div>
        <div
          class="grid w-full grid-cols-2 gap-3 sm:flex sm:w-auto sm:flex-row sm:gap-4"
        >
          <Button
            class="w-full sm:w-auto"
            @click="showMaintenanceDialog = true"
          >
            + Add Maintenance
          </Button>
          <Button class="w-full sm:w-auto" @click="openCreateDialog">
            + Add Vehicle
          </Button>
        </div>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <Input
          v-model="globalFilter"
          placeholder="Search by plate, vin, brand..."
          class="w-full md:flex-1"
        />

        <div v-if="branches.length > 0">
          <Select v-model="branchFilter">
            <SelectTrigger class="w-full md:w-48">
              <SelectValue placeholder="Select Branch" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Assignments</SelectItem>
              <SelectGroup>
                <SelectLabel
                  class="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase"
                  >Franchise</SelectLabel
                >
                <SelectItem value="franchise"
                  >Main Franchise (Unassigned)</SelectItem
                >
              </SelectGroup>
              <SelectGroup>
                <SelectLabel
                  class="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase"
                  >Branches</SelectLabel
                >
                <SelectItem v-if="branches.length > 1" value="only_branches"
                  >All Branches</SelectItem
                >
                <SelectItem
                  v-for="branch in branches"
                  :key="branch.id"
                  :value="branch.id.toString()"
                >
                  {{ branch.name }}
                </SelectItem>
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue>{{
              statusFilter === 'all' ? 'Filter by status' : statusFilter
            }}</SelectValue>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="available">Available</SelectItem>
            <SelectItem value="maintenance">Maintenance</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Plate</TableHead>
              <TableHead>VIN</TableHead>
              <TableHead>Brand</TableHead>
              <TableHead>Model</TableHead>
              <TableHead>Assignment</TableHead>
              <TableHead>Vehicle Type</TableHead>
              <TableHead>Year</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="v in filteredVehicles" :key="v.id">
              <TableCell class="font-medium">{{ v.plate_number }}</TableCell>
              <TableCell class="text-xs uppercase">{{ v.vin }}</TableCell>
              <TableCell>{{ v.brand }}</TableCell>
              <TableCell>{{ v.model }}</TableCell>
              <TableCell>
                <div class="flex flex-col">
                  <span class="text-sm font-medium">{{
                    v.branch_name || 'Main Franchise'
                  }}</span>
                  <span class="text-[10px] text-muted-foreground uppercase">{{
                    v.branch_id ? 'Branch' : 'Franchise'
                  }}</span>
                </div>
              </TableCell>
              <TableCell>
                <div class="flex flex-wrap gap-1">
                  <Badge
                    v-if="v.vehicle_type_name"
                    variant="outline"
                    class="border-primary/20 bg-primary/5 text-[10px] font-bold text-primary uppercase"
                  >
                    {{ v.vehicle_type_name }}
                  </Badge>
                  <span v-else class="text-xs text-gray-400">None</span>
                </div>
              </TableCell>
              <TableCell>{{ v.year }}</TableCell>
              <TableCell>
                <Badge
                  :class="getStatusVariant(v.status_name)"
                  class="text-white"
                >
                  {{ v.status_name }}
                </Badge>
              </TableCell>
              <!-- <TableCell>
                <div class="flex justify-center gap-2">
                  <Button size="sm" variant="outline" @click="openEditDialog(v)"
                    >Edit</Button
                  >
                  <AlertDialog>
                    <AlertDialogTrigger as-child>
                      <Button
                        size="sm"
                        variant="destructive"
                        :disabled="deletingId === v.id"
                      >
                        <Spinner
                          v-if="deletingId === v.id"
                          class="mr-2 h-4 w-4"
                        />
                        Delete
                      </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                      <AlertDialogHeader>
                        <AlertDialogTitle>Confirm Deletion</AlertDialogTitle>
                        <AlertDialogDescription
                          >Are you sure? This deletes
                          <b>{{ v.plate_number }}</b>
                          permanently.</AlertDialogDescription
                        >
                      </AlertDialogHeader>
                      <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                          @click="
                            selectedVehicleToDelete = v;
                            deleteVehicle();
                          "
                          >Confirm</AlertDialogAction
                        >
                      </AlertDialogFooter>
                    </AlertDialogContent>
                  </AlertDialog>
                </div>
              </TableCell> -->

              <TableCell class="text-center">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="h-8 w-8 p-0">
                      <span class="sr-only">Open menu</span>
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuLabel class="text-xs text-muted-foreground"
                      >Actions</DropdownMenuLabel
                    >

                    <DropdownMenuItem
                      @click="openEditDialog(v)"
                      class="cursor-pointer"
                    >
                      Edit Vehicle
                    </DropdownMenuItem>

                    <DropdownMenuItem
                      @click="openMaintenanceHistory(v)"
                      class="cursor-pointer"
                    >
                      Maintenance History
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <AlertDialog>
                      <AlertDialogTrigger as-child>
                        <div
                          class="relative flex cursor-pointer items-center rounded-sm px-2 py-1.5 text-sm text-red-600 transition-colors outline-none select-none hover:bg-destructive hover:text-destructive-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                        >
                          Delete Vehicle
                        </div>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                          <AlertDialogDescription>
                            This will permanently remove
                            <b>{{ v.plate_number }}</b> from the system.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Cancel</AlertDialogCancel>
                          <AlertDialogAction
                            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            @click="
                              selectedVehicleToDelete = v;
                              deleteVehicle();
                            "
                          >
                            Confirm Delete
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>
            <TableRow v-if="filteredVehicles.length === 0">
              <TableCell
                colspan="9"
                class="py-10 text-center text-muted-foreground"
                >No vehicles found matching your filters.</TableCell
              >
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between pt-4">
        <span class="text-sm text-gray-600"
          >Showing {{ paginator.from || 0 }} to {{ paginator.to || 0 }} of
          {{ paginator.total }} entries</span
        >
        <Pagination
          :items-per-page="paginator.per_page"
          :total="paginator.total"
          :default-page="paginator.current_page"
          class="w-auto"
        >
          <PaginationContent>
            <PaginationPrevious
              :disabled="!paginator.prev_page_url"
              @click="goToPage(paginator.prev_page_url)"
            />
            <template v-for="(link, index) in paginationLinks" :key="index">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{ 'bg-primary/10 text-primary': link.active }"
                  :disabled="!link.url"
                  @click="goToPage(link.url)"
                  >{{ link.label }}</Button
                >
              </PaginationItem>
              <PaginationEllipsis v-else-if="link.label.includes('...')" />
            </template>
            <PaginationNext
              :disabled="!paginator.next_page_url"
              @click="goToPage(paginator.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>
    </div>

    <Dialog v-model:open="showDialog">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{{
            dialogMode === 'create' ? 'Add New Vehicle' : 'Edit Vehicle'
          }}</DialogTitle>
          <DialogDescription>Enter vehicle details.</DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div v-if="franchiseVehicleTypes.length > 1" class="grid gap-2">
            <Label class="p-1">Vehicle Type</Label>
            <Select v-model="vehicleTypeId">
              <SelectTrigger>
                <SelectValue placeholder="Select Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="type in franchiseVehicleTypes"
                  :key="type.id"
                  :value="type.id.toString()"
                  >{{ type.name }}</SelectItem
                >
              </SelectContent>
            </Select>
          </div>

          <div class="grid gap-2">
            <Label class="p-1">Assign To</Label>
            <Select v-model="branchId">
              <SelectTrigger>
                <SelectValue placeholder="Select Assignment" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise"
                  >Main Franchise (Unassigned)</SelectItem
                >
                <SelectGroup v-if="branches.length > 0">
                  <SelectLabel>Branches</SelectLabel>
                  <SelectItem
                    v-for="b in branches"
                    :key="b.id"
                    :value="b.id.toString()"
                    >{{ b.name }}</SelectItem
                  >
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label class="p-1">Plate Number</Label>
              <Input
                v-model="plate_number"
                placeholder="Enter Plate Number"
                :class="{ 'border-red-500': errors.plate_number }"
                @input="handlePlateInput"
              />
              <p v-if="errors.plate_number" class="px-1 text-xs text-red-500">
                {{ errors.plate_number }}
              </p>
            </div>
            <div class="grid gap-2">
              <Label class="p-1">VIN (Chassis Number)</Label>
              <Input
                v-model="vin"
                placeholder="17-character VIN"
                maxlength="17"
                :class="{ 'border-red-500': errors.vin }"
                @input="handleVinInput"
                class="font-mono uppercase"
              />
              <p v-if="errors.vin" class="px-1 text-xs text-red-500">
                {{ errors.vin }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label class="p-1">Brand</Label>
              <Input
                v-model="brand"
                placeholder="Enter Brand"
                :class="{ 'border-red-500': errors.brand }"
              />
              <p v-if="errors.brand" class="px-1 text-xs text-red-500">
                {{ errors.brand }}
              </p>
            </div>
            <div class="grid gap-2">
              <Label class="p-1">Model</Label>
              <Input
                v-model="model"
                placeholder="Enter Model"
                :class="{ 'border-red-500': errors.model }"
              />
              <p v-if="errors.model" class="px-1 text-xs text-red-500">
                {{ errors.model }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label class="p-1">Color</Label>
              <Select v-model="color" @update:model-value="clearError('color')">
                <SelectTrigger :class="{ 'border-red-500': errors.color }">
                  <SelectValue placeholder="Select Color" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="c in vehicleColors" :key="c" :value="c">{{
                    c
                  }}</SelectItem>
                </SelectContent>
              </Select>
              <p v-if="errors.color" class="px-1 text-xs text-red-500">
                {{ errors.color }}
              </p>
            </div>
            <div class="grid gap-2">
              <Label class="p-1">Year</Label>
              <Input
                v-model="year"
                type="number"
                placeholder="Enter Year"
                :class="{ 'border-red-500': errors.year }"
                @input="handleYearInput"
              />
              <p v-if="errors.year" class="px-1 text-xs text-red-500">
                {{ errors.year }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 items-start gap-4">
            <div class="grid gap-2">
              <Label class="p-1">Seating Capacity</Label>
              <Input
                v-model="capacity"
                type="number"
                placeholder="Enter capacity"
                :class="{ 'border-red-500': errors.or_cr }"
              />
              <p v-if="errors.capacity" class="px-1 text-xs text-red-500">
                {{ errors.capacity }}
              </p>
            </div>
            <div class="grid gap-2">
              <Label class="p-1">Status</Label>
              <Select v-model="statusId" :disabled="isStatusDisabled">
                <SelectTrigger
                  :class="{
                    'border-red-500': errors.status_id,
                    'bg-slate-50 opacity-80': isStatusDisabled,
                  }"
                >
                  <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="s in availableStatuses"
                    :key="s.id"
                    :value="s.id.toString()"
                    >{{ s.name }}</SelectItem
                  >
                </SelectContent>
              </Select>
              <p
                v-if="isStatusDisabled && dialogMode === 'edit'"
                class="px-1 text-[10px] text-slate-500 italic"
              >
                Status is managed automatically or locked.
              </p>
              <p v-if="errors.status_id" class="px-1 text-xs text-red-500">
                {{ errors.status_id }}
              </p>
            </div>
          </div>

          <div class="grid gap-2 border-t pt-4">
            <Label class="p-1 font-semibold">OR CR Document</Label>
            <Input
              type="file"
              accept="image/*,.pdf"
              @change="handleFileUpload"
              :class="{ 'border-red-500': errors.or_cr }"
              class="cursor-pointer file:cursor-pointer"
            />
            <p v-if="errors.or_cr" class="px-1 text-xs text-red-500">
              {{ errors.or_cr }}
            </p>

            <div v-if="or_cr_file" class="mt-1 flex items-center gap-2 px-1">
              <Badge variant="secondary" class="bg-green-100 text-green-700"
                >New: {{ or_cr_file.name }}</Badge
              >
            </div>
            <div
              v-else-if="existing_or_cr"
              class="mt-1 flex items-center gap-2 px-1"
            >
              <span class="text-[11px] text-gray-500">Current file:</span>
              <a
                :href="existing_or_cr"
                target="_blank"
                class="text-[11px] text-blue-600 underline"
                >View Document</a
              >
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDialog = false">Cancel</Button>
          <Button @click="saveVehicle" :disabled="isSaving">
            <Spinner v-if="isSaving" class="mr-2 h-4 w-4" />
            {{ dialogMode === 'create' ? 'Create Vehicle' : 'Save Changes' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="maintenanceModal.isOpen">
      <DialogContent class="flex max-h-[80vh] max-w-4xl flex-col">
        <DialogHeader>
          <DialogTitle>Maintenance History</DialogTitle>
          <DialogDescription>
            Showing all maintenance records for this vehicle.
          </DialogDescription>
        </DialogHeader>

        <div class="flex-1 overflow-y-auto py-4 pe-1.5">
          <div v-if="maintenanceModal.isLoading" class="space-y-4">
            <div
              v-for="i in 3"
              :key="i"
              class="h-24 w-full animate-pulse rounded-lg bg-muted"
            />
          </div>

          <Alert v-else-if="maintenanceModal.isError" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription
              >Failed to load maintenance history.</AlertDescription
            >
          </Alert>

          <div v-else-if="maintenanceModal.data?.length" class="space-y-4">
            <div
              v-for="item in maintenanceModal.data"
              :key="item.id"
              class="rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
              <div class="mb-2 flex items-start justify-between">
                <div>
                  <h4 class="text-lg font-bold text-primary">
                    {{ item.inventory_name }}
                  </h4>
                  <Badge variant="outline" class="mt-1">
                    {{ item.category }}
                  </Badge>
                </div>
                <div class="text-right text-sm">
                  <p class="font-medium text-foreground">
                    Date: {{ item.maintenance_date }}
                  </p>
                  <p class="text-muted-foreground italic">
                    Next: {{ item.next_maintenance_date }}
                  </p>
                </div>
              </div>

              <div
                class="mt-3 grid grid-cols-1 gap-4 border-t pt-3 text-sm md:grid-cols-3"
              >
                <div>
                  <span class="block font-semibold">Specification:</span>
                  <p class="text-muted-foreground">{{ item.specification }}</p>
                </div>
                <div>
                  <span class="block font-semibold">Quantity</span>
                  <p class="text-muted-foreground">{{ item.quantity }}</p>
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
          <Button variant="outline" @click="closeMaintenanceHistory">
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="showMaintenanceDialog">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Add Maintenance Record</DialogTitle>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label>Select Vehicle</Label>
            <Select
              v-model="maintenanceForm.vehicle_id"
              @update:model-value="clearError('vehicle_id')"
            >
              <SelectTrigger :class="{ 'border-red-500': errors.vehicle_id }">
                <SelectValue placeholder="Choose Vehicle" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="v in vehicles.data"
                  :key="v.id"
                  :value="v.id"
                >
                  {{ v.plate_number }} - {{ v.model }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p
              v-if="errors.vehicle_id"
              class="text-xs font-medium text-red-500"
            >
              {{ errors.vehicle_id }}
            </p>
          </div>

          <div class="grid gap-2">
            <Label>Part / Inventory Item</Label>
            <Select
              v-model="maintenanceForm.inventory_id"
              @update:model-value="clearError('inventory_id')"
            >
              <SelectTrigger :class="{ 'border-red-500': errors.inventory_id }">
                <SelectValue
                  :placeholder="
                    inventories.length
                      ? 'Select Item'
                      : 'No inventory records found'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <div
                  v-if="inventories.length === 0"
                  class="p-2 text-center text-sm text-muted-foreground"
                >
                  No inventory available
                </div>
                <SelectItem
                  v-for="item in inventories"
                  :key="item.id"
                  :value="item.id.toString()"
                >
                  {{ item.name }} (Stock: {{ item.quantity }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p
              v-if="errors.inventory_id"
              class="text-xs font-medium text-red-500"
            >
              {{ errors.inventory_id }}
            </p>
          </div>

          <div
            v-if="selectedInventory"
            class="grid grid-cols-2 gap-4 rounded-md bg-muted p-3 text-sm"
          >
            <div>
              <span class="text-muted-foreground">Unit Price:</span>
              <p class="font-bold">₱{{ selectedInventory.unit_price }}</p>
            </div>
            <div>
              <span class="text-muted-foreground">Available Stock:</span>
              <p
                :class="
                  selectedInventory.quantity < maintenanceForm.quantity
                    ? 'text-red-500'
                    : 'text-green-600'
                "
              >
                {{ selectedInventory.quantity }} units
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 items-start gap-4">
            <div class="grid gap-2">
              <Label>Quantity Used</Label>
              <Input
                type="number"
                v-model="maintenanceForm.quantity"
                min="1"
                :class="{
                  'border-red-500':
                    errors.quantity ||
                    (selectedInventory &&
                      maintenanceForm.quantity > selectedInventory.quantity),
                }"
                @input="clearError('quantity')"
              />
              <p
                v-if="
                  selectedInventory &&
                  maintenanceForm.quantity > selectedInventory.quantity
                "
                class="text-xs font-medium text-red-500"
              >
                Warning: Exceeds available stock ({{
                  selectedInventory.quantity
                }})
              </p>
              <p
                v-else-if="errors.quantity"
                class="text-xs font-medium text-red-500"
              >
                {{ errors.quantity }}
              </p>
            </div>
            <div class="grid gap-2">
              <Label>Date</Label>
              <Input
                type="date"
                v-model="maintenanceForm.maintenance_date"
                :class="{ 'border-red-500': errors.maintenance_date }"
                @input="clearError('maintenance_date')"
              />
              <p
                v-if="errors.maintenance_date"
                class="text-xs font-medium text-red-500"
              >
                {{ errors.maintenance_date }}
              </p>
            </div>
          </div>

          <div class="grid gap-2">
            <Label>Next Maintenance Date</Label>
            <Input
              type="date"
              v-model="maintenanceForm.next_maintenance_date"
              :class="{ 'border-red-500': errors.next_maintenance_date }"
              @input="clearError('next_maintenance_date')"
            />
            <p
              v-if="errors.next_maintenance_date"
              class="text-xs font-medium text-red-500"
            >
              {{ errors.next_maintenance_date }}
            </p>
          </div>

          <div class="grid gap-2">
            <Label>Work Description</Label>
            <Textarea
              v-model="maintenanceForm.description"
              placeholder="What was fixed?"
              :class="{ 'border-red-500': errors.description }"
              @input="clearError('description')"
            />
            <p
              v-if="errors.description"
              class="text-xs font-medium text-red-500"
            >
              {{ errors.description }}
            </p>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showMaintenanceDialog = false"
            >Cancel</Button
          >
          <Button
            @click="submitMaintenance"
            :disabled="
              isSubmittingMaintenance ||
              (selectedInventory &&
                maintenanceForm.quantity > selectedInventory.quantity)
            "
          >
            <Spinner v-if="isSubmittingMaintenance" class="mr-2 h-4 w-4" />
            Save Maintenance
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
