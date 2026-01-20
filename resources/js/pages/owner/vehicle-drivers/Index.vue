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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface Driver {
  id: number;
  name: string;
  email: string;
  phone: string;
}

interface Vehicle {
  id: number;
  plate_number: string;
  vin: string;
  brand: string;
  model: string;
  color: string;
  year: number;
  status_id: number;
  status_name: string;
  driver: Driver | null;
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

const props = defineProps<{ vehicles: VehiclesPaginator; drivers: Driver[] }>();
const paginator = ref(props.vehicles);

// -------------------------
// Watch for prop updates
// -------------------------
watch(
  () => props.vehicles,
  (newVal) => {
    paginator.value = newVal;
  },
  { deep: true },
);

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Assign Drivers', href: owner.vehicleDrivers.index().url },
];

// Filters
const globalFilter = ref('');
const statusFilter = ref('all');

// Filtered Vehicles
const filteredVehicles = computed(() => {
  let filtered = paginator.value.data;

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter(
      (v) => v.status_name.toLowerCase() === statusFilter.value,
    );
  }

  if (globalFilter.value) {
    const term = globalFilter.value.toLowerCase();
    filtered = filtered.filter((v) =>
      [
        v.plate_number,
        v.vin,
        v.brand,
        v.model,
        v.color,
        v.year,
        v.driver?.name,
        v.driver?.email,
      ].some((f) => String(f).toLowerCase().includes(term)),
    );
  }

  return filtered;
});

// Status badge style
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'retired':
      return 'destructive';
    case 'suspended':
      return 'outline';
    default:
      return 'secondary';
  }
};

// State
const showDialog = ref(false);
const editingVehicle = ref<Vehicle | null>(null);
const driverId = ref<number | null>(null);
const loading = ref(false);
const confirmVehicle = ref<Vehicle | null>(null);

// Dialogs
const openAssignDialog = (vehicle: Vehicle) => {
  editingVehicle.value = vehicle;
  driverId.value = vehicle.driver?.id ?? null;
  showDialog.value = true;
};

// Assign / update driver
const assignDriver = async () => {
  if (!editingVehicle.value) return;
  loading.value = true;
  const toastId = toast.loading('Assigning driver...');
  try {
    await router.put(
      `/owner/vehicle-drivers/${editingVehicle.value.id}`,
      { driver_id: driverId.value },
      {
        onSuccess: () => {
          toast.success('Driver assigned successfully!', { id: toastId });
          showDialog.value = false;
        },
        onError: () => toast.error('Failed to assign driver', { id: toastId }),
      },
    );
  } catch (e) {
    console.error(e);
    toast.error('Something went wrong', { id: toastId });
  } finally {
    loading.value = false;
  }
};

// Confirm unassign
const confirmUnassign = (vehicle: Vehicle) => {
  confirmVehicle.value = vehicle;
};

// Remove driver
const removeDriver = async () => {
  if (!confirmVehicle.value) return;
  loading.value = true;
  const toastId = toast.loading('Unassigning driver...');
  try {
    await router.put(
      `/owner/vehicle-drivers/${confirmVehicle.value.id}`,
      { driver_id: null },
      {
        onSuccess: () =>
          toast.success('Driver unassigned successfully!', { id: toastId }),
        onError: () =>
          toast.error('Failed to unassign driver', { id: toastId }),
      },
    );
  } catch (e) {
    console.error(e);
    toast.error('Something went wrong', { id: toastId });
  } finally {
    loading.value = false;
    confirmVehicle.value = null;
  }
};

// Pagination Helpers
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
  <Head title="Assign Drivers" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="mb-1 text-3xl font-bold">Assign Drivers</h1>
          <p class="text-muted-foreground">
            Manage assigning of drivers to vehicles
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <input
          v-model="globalFilter"
          placeholder="Search vehicles..."
          class="w-full rounded-md border px-3 py-2"
        />
        <select
          v-model="statusFilter"
          class="w-full rounded-md border px-3 py-2 md:w-48"
        >
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
          <option value="suspended">Suspended</option>
          <option value="retired">Retired</option>
        </select>
      </div>

      <!-- Vehicles Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Plate</TableHead>
              <TableHead>VIN</TableHead>
              <TableHead>Brand</TableHead>
              <TableHead>Model</TableHead>
              <TableHead>Color</TableHead>
              <TableHead>Year</TableHead>
              <TableHead>Driver</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="v in filteredVehicles"
              :key="v.id"
              class="hover:bg-muted/50"
            >
              <TableCell>{{ v.plate_number }}</TableCell>
              <TableCell>{{ v.vin }}</TableCell>
              <TableCell>{{ v.brand }}</TableCell>
              <TableCell>{{ v.model }}</TableCell>
              <TableCell>{{ v.color }}</TableCell>
              <TableCell>{{ v.year }}</TableCell>
              <TableCell>{{ v.driver?.name ?? '-' }}</TableCell>
              <TableCell>{{ v.driver?.email ?? '-' }}</TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(v.status_name)">
                  {{ v.status_name }}
                </Badge>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  @click="openAssignDialog(v)"
                  :disabled="loading"
                >
                  <Spinner
                    v-if="loading && editingVehicle?.id === v.id"
                    class="mr-2 h-4 w-4"
                  />
                  Assign / Edit
                </Button>

                <!-- Unassign -->
                <AlertDialog>
                  <AlertDialogTrigger as-child>
                    <Button
                      size="sm"
                      variant="destructive"
                      :disabled="!v.driver || loading"
                      @click="confirmUnassign(v)"
                    >
                      <Spinner
                        v-if="loading && confirmVehicle?.id === v.id"
                        class="mr-2 h-4 w-4"
                      />
                      Unassign
                    </Button>
                  </AlertDialogTrigger>

                  <AlertDialogContent v-if="confirmVehicle?.id === v.id">
                    <AlertDialogHeader>
                      <AlertDialogTitle>
                        Are you sure you want to unassign this driver?
                      </AlertDialogTitle>
                      <AlertDialogDescription>
                        This will remove the driver from
                        <strong>{{ confirmVehicle.plate_number }}</strong
                        >.
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel @click="confirmVehicle = null">
                        Cancel
                      </AlertDialogCancel>
                      <AlertDialogAction @click="removeDriver">
                        Continue
                      </AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              </TableCell>
            </TableRow>

            <TableRow v-if="filteredVehicles.length === 0">
              <TableCell
                colspan="10"
                class="py-6 text-center text-muted-foreground"
              >
                No results found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
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

            <template v-for="link in paginationLinks" :key="link.label">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{ 'bg-gray-100': link.active }"
                  :disabled="!link.url"
                  @click="goToPage(link.url)"
                >
                  {{ link.label }}
                </Button>
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

    <!-- Assign Driver Dialog -->
    <Dialog v-model:open="showDialog">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Assign Driver</DialogTitle>
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <Select v-model="driverId">
            <SelectTrigger>
              <SelectValue placeholder="Select Driver" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="d in props.drivers" :key="d.id" :value="d.id">
                {{ d.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <DialogFooter class="flex justify-end gap-2">
          <Button variant="outline" @click="showDialog = false">Cancel</Button>
          <Button @click="assignDriver" :disabled="loading">
            <Spinner v-if="loading" class="mr-2 h-4 w-4" />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
