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
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import type { Branch, VehicleType } from '@/types';

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
const paginator = ref(props.vehicles);

// Filter allowed statuses (15: Available, 5: Maintenance)
const allowedStatuses = computed(() =>
  props.statuses.filter((s) => [15, 5].includes(s.id)),
);

watch(
  () => props.vehicles,
  (newVehicles) => {
    paginator.value = newVehicles;
  },
  { deep: true },
);

const breadcrumbs = [{ title: 'Vehicle Management', href: '/owner/vehicles' }];

// --- FILTERS LOGIC ---
const globalFilter = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const branchFilter = ref(props.filters?.branch_id?.toString() || 'all');

// Use props.filters.vehicle_type as the primary source of truth for the active tab
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

// Sync filters when inputs change
watch(
  [statusFilter, activeTab, globalFilter, branchFilter],
  (newVals, oldVals) => {
    // Only trigger router if values actually changed to avoid double-loading on mount
    const hasChanged = newVals.some((val, i) => val !== oldVals[i]);
    if (hasChanged) {
      updateFilters();
    }
  },
);

// --- STATE & DIALOGS ---
const isSaving = ref(false);
const deletingId = ref<number | null>(null);
const selectedVehicleToDelete = ref<Vehicle | null>(null);
const or_cr_file = ref<File | null>(null);

const filteredVehicles = computed(() => paginator.value.data);

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

const openCreateDialog = () => {
  dialogMode.value = 'create';
  editingVehicle.value = null;
  plate_number.value = vin.value = brand.value = model.value = color.value = '';
  year.value = undefined;
  capacity.value = undefined;

  statusId.value = '15'; // Default to ID 15 (Available)
  branchId.value = 'franchise';

  // Set vehicle type based on current active tab
  if (props.franchiseVehicleTypes.length === 1) {
    vehicleTypeId.value = props.franchiseVehicleTypes[0].id.toString();
  } else {
    const currentTabType = props.franchiseVehicleTypes.find(
      (t) => t.name === activeTab.value,
    );
    vehicleTypeId.value = currentTabType?.id.toString() || '';
  }

  or_cr_file.value = null;
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
  showDialog.value = true;
};

const getStatusVariant = (status: string) => {
  const s = status.toLowerCase();
  if (s === 'available' || s === 'active') return 'default';
  if (s === 'maintenance') return 'outline';
  return 'secondary';
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
        or_cr_file.value = null;
      },
    },
  );
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    or_cr_file.value = target.files[0];
  }
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

const paginationLinks = computed(() =>
  paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  ),
);

const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(url, {}, { preserveState: true, preserveScroll: true });
};
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

      <div class="flex items-center justify-between">
        <div>
          <h1 class="mb-1 text-3xl font-bold">Vehicle Management</h1>
          <p class="text-gray-600">Manage all vehicles in the system</p>
        </div>
        <Button @click="openCreateDialog">+ Add Vehicle</Button>
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

        <select
          v-model="statusFilter"
          class="w-full rounded-md border px-3 py-2 text-sm md:w-48"
        >
          <option value="all">All Status</option>
          <option v-for="s in allowedStatuses" :key="s.id" :value="s.name">
            {{ s.name }}
          </option>
        </select>
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
                  <span class="text-sm font-medium">
                    {{ v.branch_name || 'Main Franchise' }}
                  </span>
                  <span class="text-[10px] text-muted-foreground uppercase">
                    {{ v.branch_id ? 'Branch' : 'Franchise' }}
                  </span>
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
                <Badge :variant="getStatusVariant(v.status_name)">
                  {{ v.status_name }}
                </Badge>
              </TableCell>
              <TableCell>
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
                        <AlertDialogDescription>
                          Are you sure? This deletes
                          <b>{{ v.plate_number }}</b> permanently.
                        </AlertDialogDescription>
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
              </TableCell>
            </TableRow>
            <TableRow v-if="filteredVehicles.length === 0">
              <TableCell
                colspan="8"
                class="py-10 text-center text-muted-foreground"
              >
                No vehicles found matching your filters.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between pt-4">
        <span class="text-sm text-gray-600">
          Showing {{ paginator.from || 0 }} to {{ paginator.to || 0 }} of
          {{ paginator.total }} entries
        </span>
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
          <DialogDescription> Enter vehicle details. </DialogDescription>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div v-if="franchiseVehicleTypes.length > 1" class="grid gap-2">
            <Label>Vehicle Type</Label>
            <Select v-model="vehicleTypeId">
              <SelectTrigger>
                <SelectValue placeholder="Select Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="type in franchiseVehicleTypes"
                  :key="type.id"
                  :value="type.id.toString()"
                >
                  {{ type.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="grid gap-2">
            <Label>Assign To</Label>
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
                  >
                    {{ b.name }}
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Plate Number</Label>
              <Input v-model="plate_number" placeholder="ABC 1234" />
            </div>
            <div class="grid gap-2">
              <Label>VIN</Label>
              <Input v-model="vin" placeholder="Serial Number" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Brand</Label>
              <Input v-model="brand" placeholder="Toyota" />
            </div>
            <div class="grid gap-2">
              <Label>Model</Label>
              <Input v-model="model" placeholder="Innova" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label>Color</Label>
              <Input v-model="color" />
            </div>
            <div class="grid gap-2">
              <Label>Year</Label>
              <Input v-model="year" type="number" />
            </div>

            <div class="grid gap-2">
              <Label>Seating Capacity</Label>
              <Input
                v-model="capacity"
                type="number"
                placeholder="Enter capacity"
                required
              />
            </div>

            <div class="grid gap-2">
              <Label>Status</Label>
              <Select v-model="statusId">
                <SelectTrigger>
                  <SelectValue placeholder="Select Status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="s in allowedStatuses"
                    :key="s.id"
                    :value="s.id.toString()"
                  >
                    {{ s.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="grid gap-2 border-t pt-4">
            <Label class="font-bold">OR/CR Document (PDF/Image)</Label>

            <Input
              type="file"
              @change="handleFileUpload"
              accept=".jpg,.jpeg,.png,.pdf"
              class="cursor-pointer"
            />

            <div
              v-if="dialogMode === 'edit' && editingVehicle?.or_cr"
              class="mt-2 flex items-center gap-3 rounded-md border bg-muted/30 p-3"
            >
              <div
                v-if="
                  editingVehicle.or_cr.match(/\.(jpg|jpeg|png)$/i) ||
                  editingVehicle.or_cr.includes('placeholder')
                "
                class="h-12 w-12 overflow-hidden rounded border bg-white"
              >
                <img
                  :src="editingVehicle.or_cr"
                  class="h-full w-full object-cover"
                  alt="OR/CR Preview"
                />
              </div>

              <div
                v-else
                class="flex h-12 w-12 items-center justify-center rounded border bg-white text-red-500"
              >
                <span class="text-[10px] font-bold">PDF</span>
              </div>

              <div class="flex flex-col">
                <span class="text-xs font-medium">Existing Document</span>
                <a
                  :href="editingVehicle.or_cr"
                  target="_blank"
                  class="text-xs text-primary underline hover:text-primary/80"
                >
                  Click to view full document
                </a>
              </div>
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
  </AppLayout>
</template>
