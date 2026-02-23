<script setup lang="ts">
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { TabsList, TabsTrigger } from '@/components/ui/tabs';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import { useAddress } from '@/composables/useAddress';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { Branch, BreadcrumbItem, VehicleType } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, Edit, Eye, Loader2, Search, Trash, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface DriverDetails {
  code_number: string | null;
  license_number: string | null;
  license_expiry: string | null;
  is_verified: number | boolean | null;
  shift: string | null;
  hire_date: string | null;
  front_license_picture: string | null;
  back_license_picture: string | null;
  nbi_clearance: string | null;
  selfie_picture: string | null;
}

interface Driver {
  id: number;
  name: string;
  username: string;
  email: string;
  phone: string;
  status: string;
  region: string;
  province: string;
  city: string;
  barangay: string;
  address: string;
  vehicle_types: VehicleType[];
  details: DriverDetails;
  assignment: {
    type: 'branch' | 'franchise';
    name: string;
    id: number | null;
  };
}

interface Status {
  id: number;
  name: string;
}

interface DriversPaginator {
  current_page: number;
  data: Driver[];
  first_page_url: string | null;
  from: number | null;
  last_page: number;
  last_page_url: string | null;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

interface Props {
  drivers: DriversPaginator;
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
const paginator = ref(props.drivers);

function debounce(fn: Function, delay: number) {
  let timeoutId: ReturnType<typeof setTimeout>;
  return (...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
}

const {
  regions,
  provinces,
  cities,
  barangays,
  selectedRegion,
  selectedProvince,
  selectedCity,
  selectedBarangay,
  isLoadingProvinces,
  isLoadingCities,
  isLoadingBarangays,
  isNcr,
} = useAddress();

const selectedDriver = ref<Driver | null>(null);
const dialogOpen = ref(false);
const isEditing = ref(false);

const editForm = useForm({
  email: '',
  phone: '',
  code_number: '',
  license_number: '',
  license_expiry: '',
  region: '',
  province: '',
  city: '',
  barangay: '',
  branch_id: '' as string | number,
});

const viewDriver = (driver: Driver) => {
  selectedDriver.value = driver;
  isEditing.value = false;

  editForm.email = driver.email || '';
  editForm.phone = driver.phone || '';
  editForm.code_number = driver.details.code_number || '';
  editForm.license_number = driver.details.license_number || '';
  editForm.license_expiry = driver.details.license_expiry || '';

  if (driver.assignment.type === 'branch' && driver.assignment.id) {
    editForm.branch_id = driver.assignment.id.toString();
  } else {
    editForm.branch_id = 'franchise';
  }

  selectedRegion.value = driver.region || '';
  setTimeout(() => {
    if (driver.province) selectedProvince.value = driver.province;
  }, 500);
  setTimeout(() => {
    if (driver.city) selectedCity.value = driver.city;
  }, 1000);
  setTimeout(() => {
    if (driver.barangay) selectedBarangay.value = driver.barangay;
  }, 1500);

  dialogOpen.value = true;
};

const saveDriverDetails = () => {
  if (!selectedDriver.value) return;

  editForm.region = selectedRegion.value;
  editForm.province = selectedProvince.value;
  editForm.city = selectedCity.value;
  editForm.barangay = selectedBarangay.value;

  editForm.put(`/owner/drivers/${selectedDriver.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Driver profile updated successfully');
      isEditing.value = false;
    },
    onError: (errors) => {
      const firstErr = Object.values(errors)[0] as string;
      toast.error(firstErr || 'Failed to update.');
    },
  });
};

const removeDialogOpen = ref(false);
const driverToRemove = ref<Driver | null>(null);

const confirmRemoveDriver = (driver: Driver) => {
  driverToRemove.value = driver;
  removeDialogOpen.value = true;
};

const fileInput = ref<HTMLInputElement | null>(null);
const currentFieldToEdit = ref<string | null>(null);

const triggerFileEdit = (fieldName: string) => {
  currentFieldToEdit.value = fieldName;
  fileInput.value?.click();
};

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (
    !target.files?.length ||
    !selectedDriver.value ||
    !currentFieldToEdit.value
  )
    return;

  const file = target.files[0];
  const toastId = toast.loading(`Uploading document...`);

  router.post(
    `/owner/drivers/${selectedDriver.value.id}`,
    { _method: 'PUT', [currentFieldToEdit.value]: file },
    {
      forceFormData: true,
      onSuccess: () => toast.success('Document updated!', { id: toastId }),
      onError: () => toast.error('Upload failed.', { id: toastId }),
      onFinish: () => {
        currentFieldToEdit.value = null;
        target.value = '';
      },
    },
  );
};

const globalFilter = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'active');
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

watch([statusFilter, activeTab, globalFilter, branchFilter], () =>
  updateFilters(),
);

watch(
  () => props.drivers,
  (newDrivers) => {
    paginator.value = newDrivers;
    if (selectedDriver.value) {
      selectedDriver.value =
        newDrivers.data.find((d) => d.id === selectedDriver.value?.id) ||
        selectedDriver.value;
    }
  },
  { deep: true },
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Driver Management', href: owner.drivers.index().url },
];

const paginationLinks = computed(() => {
  return props.drivers.links.filter(
    (link) => !link.label.includes('Previous') && !link.label.includes('Next'),
  );
});

const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(
    url,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: globalFilter.value || undefined,
      vehicle_type: activeTab.value || undefined,
      branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
};

const getStatusVariant = (status: string) => {
  switch (status.toLowerCase()) {
    case 'active':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'retired':
    case 'suspended':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const updateDriverStatus = (driverId: number, statusId: number) => {
  const toastId = toast.loading('Updating driver status...');
  router.put(
    `/owner/drivers/${driverId}`,
    { status_id: statusId },
    {
      onSuccess: () => toast.success('Driver status updated!', { id: toastId }),
      onError: () =>
        toast.error('Failed to update driver status.', { id: toastId }),
    },
  );
};

const removeDriverFromFranchise = () => {
  if (!driverToRemove.value) return;
  const toastId = toast.loading('Removing driver...');
  router.delete(`/owner/drivers/${driverToRemove.value.id}`, {
    onSuccess: () =>
      toast.success('Driver removed successfully!', { id: toastId }),
    onError: () => toast.error('Failed to remove driver.', { id: toastId }),
    onFinish: () => {
      driverToRemove.value = null;
      removeDialogOpen.value = false;
    },
  });
};
</script>

<template>
  <Head title="Driver Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <input
      type="file"
      ref="fileInput"
      class="hidden"
      accept="image/*"
      @change="handleFileUpload"
    />

    <div class="space-y-6 p-6">
      <Tabs
        v-if="franchiseVehicleTypes.length > 1"
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

      <div>
        <h1 class="mb-1 text-3xl font-bold">Driver Management</h1>
        <p class="text-gray-600">
          Managing your
          <span class="font-bold text-primary">{{ activeTab }}</span> fleet.
        </p>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="globalFilter"
            placeholder="Search drivers..."
            class="w-full rounded-md border border-input bg-background px-10 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
          />
        </div>

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
                >
                  Franchise
                </SelectLabel>
                <SelectItem value="franchise"
                  >Main Franchise (Unassigned)</SelectItem
                >
              </SelectGroup>

              <SelectGroup>
                <SelectLabel
                  class="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase"
                >
                  Branches
                </SelectLabel>
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
          <SelectTrigger class="w-full md:w-40">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="status in statuses"
              :key="status.id"
              :value="status.name"
            >
              {{ status.name.charAt(0).toUpperCase() + status.name.slice(1) }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Username</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Assignment</TableHead>
              <TableHead>Vehicle Type</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="driver in paginator.data"
              :key="driver.id"
              class="hover:bg-muted/50"
            >
              <TableCell>{{ driver.username }}</TableCell>
              <TableCell>{{ driver.email }}</TableCell>
              <TableCell>
                <div class="flex flex-col">
                  <span class="text-sm font-medium">
                    {{
                      driver.assignment?.name?.trim()
                        ? driver.assignment.name
                        : 'Main Franchise'
                    }}
                  </span>
                  <span class="text-[10px] text-muted-foreground uppercase">
                    {{ driver.assignment?.type || 'franchise' }}
                  </span>
                </div>
              </TableCell>
              <TableCell>
                <div class="flex flex-wrap gap-1">
                  <Badge
                    v-for="vType in driver.vehicle_types"
                    :key="vType.id"
                    variant="outline"
                    class="text-[10px] font-bold uppercase"
                  >
                    {{ vType.name }}
                  </Badge>
                  <span
                    v-if="driver.vehicle_types.length === 0"
                    class="text-xs text-gray-400"
                    >None</span
                  >
                </div>
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(driver.status)">{{
                  driver.status
                }}</Badge>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  @click="viewDriver(driver)"
                  class="cursor-pointer"
                >
                  <Eye class="h-4 w-4" />
                </Button>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button size="sm" variant="outline" class="cursor-pointer">
                      <Edit class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    <DropdownMenuLabel>Change Status</DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      v-for="status in statuses"
                      :key="status.id"
                      :disabled="driver.status === status.name"
                      @click="updateDriverStatus(driver.id, status.id)"
                    >
                      {{
                        status.name.charAt(0).toUpperCase() +
                        status.name.slice(1)
                      }}
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
                <Button
                  size="sm"
                  variant="destructive"
                  @click="confirmRemoveDriver(driver)"
                >
                  <Trash class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>

            <TableRow v-if="drivers.data.length === 0">
              <TableCell
                colspan="6"
                class="py-12 text-center text-muted-foreground"
              >
                No <span class="font-bold">{{ statusFilter }}</span> drivers
                found for <span class="font-bold">{{ activeTab }}</span
                >.
              </TableCell>
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

    <Dialog v-model:open="dialogOpen">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Driver's Information</DialogTitle>
          <DialogDescription>
            Detailed information for driver
            <strong>{{ selectedDriver?.username }}</strong
            >.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
          <div class="col-span-2 mb-2">
            <p class="text-xs font-bold text-gray-500 uppercase">
              Assigned Branch:
            </p>
            <Select v-if="isEditing" v-model="editForm.branch_id">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Choose branch" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise"
                  >Main Franchise (Unassigned)</SelectItem
                >
                <SelectItem
                  v-for="branch in branches"
                  :key="branch.id"
                  :value="branch.id.toString()"
                >
                  {{ branch.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-else class="font-medium text-primary">
              {{ selectedDriver?.assignment?.name || 'Main Franchise' }}
            </p>
          </div>

          <div>
            <p class="text-xs font-bold text-gray-500 uppercase">ID / Code:</p>
            <input
              v-if="isEditing"
              v-model="editForm.code_number"
              class="w-full rounded border px-2 py-1"
            />
            <p v-else>{{ selectedDriver?.details.code_number }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase">Email:</p>
            <input
              v-if="isEditing"
              v-model="editForm.email"
              class="w-full rounded border px-2 py-1"
            />
            <p v-else>{{ selectedDriver?.email }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase">Phone:</p>
            <input
              v-if="isEditing"
              v-model="editForm.phone"
              class="w-full rounded border px-2 py-1"
            />
            <p v-else>{{ selectedDriver?.phone }}</p>
          </div>
          <div>
            <p class="text-xs font-bold text-gray-500 uppercase">Status:</p>
            <p>{{ selectedDriver?.status }}</p>
          </div>

          <div class="col-span-2 space-y-3 border-t pt-4">
            <p class="text-xs font-bold text-gray-500 uppercase">
              Address Information:
            </p>
            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase"
                  >Region</label
                >
                <select
                  v-if="isEditing"
                  v-model="selectedRegion"
                  class="w-full rounded border bg-white px-2 py-1 text-xs"
                >
                  <option v-for="r in regions" :key="r.code" :value="r.name">
                    {{ r.name }}
                  </option>
                </select>
                <p v-else class="text-xs">{{ selectedDriver?.region }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase"
                  >Province</label
                >
                <select
                  v-if="isEditing"
                  v-model="selectedProvince"
                  :disabled="isNcr || isLoadingProvinces"
                  class="w-full rounded border bg-white px-2 py-1 text-xs"
                >
                  <option v-for="p in provinces" :key="p.code" :value="p.name">
                    {{ p.name }}
                  </option>
                </select>
                <p v-else class="text-xs">{{ selectedDriver?.province }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase"
                  >City</label
                >
                <select
                  v-if="isEditing"
                  v-model="selectedCity"
                  :disabled="isLoadingCities"
                  class="w-full rounded border bg-white px-2 py-1 text-xs"
                >
                  <option v-for="c in cities" :key="c.code" :value="c.name">
                    {{ c.name }}
                  </option>
                </select>
                <p v-else class="text-xs">{{ selectedDriver?.city }}</p>
              </div>
              <div class="space-y-1">
                <label class="text-[10px] font-bold text-gray-400 uppercase"
                  >Barangay</label
                >
                <select
                  v-if="isEditing"
                  v-model="selectedBarangay"
                  :disabled="isLoadingBarangays"
                  class="w-full rounded border bg-white px-2 py-1 text-xs"
                >
                  <option v-for="b in barangays" :key="b.code" :value="b.name">
                    {{ b.name }}
                  </option>
                </select>
                <p v-else class="text-xs">{{ selectedDriver?.barangay }}</p>
              </div>
            </div>
          </div>

          <div
            class="col-span-2 mt-2 grid grid-cols-2 gap-x-6 gap-y-3 border-t pt-4"
          >
            <div>
              <p class="text-xs font-bold text-gray-500 uppercase">
                License Number:
              </p>
              <input
                v-if="isEditing"
                v-model="editForm.license_number"
                class="w-full rounded border px-2 py-1"
              />
              <p v-else>{{ selectedDriver?.details.license_number }}</p>
            </div>
            <div>
              <p class="text-xs font-bold text-gray-500 uppercase">
                License Expiry:
              </p>
              <input
                v-if="isEditing"
                type="date"
                v-model="editForm.license_expiry"
                class="w-full rounded border px-2 py-1"
              />
              <p v-else>{{ selectedDriver?.details.license_expiry }}</p>
            </div>
          </div>
        </div>

        <div class="flex justify-start gap-2 pt-4">
          <template v-if="!isEditing">
            <Button
              variant="outline"
              size="sm"
              @click="isEditing = true"
              class="h-7 text-xs"
            >
              <Edit class="mr-1 h-3 w-3" /> Edit Profile
            </Button>
          </template>
          <template v-else>
            <Button
              variant="default"
              size="sm"
              @click="saveDriverDetails"
              :disabled="editForm.processing"
              class="h-7 text-xs"
            >
              <Loader2
                v-if="editForm.processing"
                class="mr-1 h-3 w-3 animate-spin"
              />
              <Check v-else class="mr-1 h-3 w-3" /> Save Changes
            </Button>
            <Button
              variant="ghost"
              size="sm"
              @click="isEditing = false"
              class="h-7 text-xs"
            >
              <X class="mr-1 h-3 w-3" /> Cancel
            </Button>
          </template>
        </div>

        <div v-if="selectedDriver?.details" class="mt-4 border-t pt-4">
          <h3 class="mb-2 text-sm font-semibold">Driver Documents</h3>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="field in [
                'front_license_picture',
                'back_license_picture',
                'nbi_clearance',
                'selfie_picture',
              ] as const"
              :key="field"
            >
              <div v-if="selectedDriver.details[field]" class="space-y-1">
                <div class="flex items-center justify-between">
                  <p class="text-[10px] font-bold text-gray-400 uppercase">
                    {{ field.replace(/_/g, ' ') }}
                  </p>
                  <div class="flex gap-2">
                    <button
                      @click="triggerFileEdit(field)"
                      class="text-[10px] font-bold text-blue-600 hover:underline"
                    >
                      Edit
                    </button>
                    <a
                      :href="String(selectedDriver.details[field])"
                      class="text-[10px] font-bold text-gray-600 hover:underline"
                      target="_blank"
                      >View</a
                    >
                  </div>
                </div>
                <img
                  :src="String(selectedDriver.details[field])"
                  class="h-24 w-full rounded border bg-gray-50 object-cover"
                />
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="ghost" @click="dialogOpen = false">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="removeDialogOpen">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Confirm Removal</DialogTitle>
          <DialogDescription>
            Are you sure you want to remove
            <strong>{{ driverToRemove?.username }}</strong> from your franchise?
          </DialogDescription>
        </DialogHeader>
        <DialogFooter class="flex justify-end gap-2">
          <Button variant="outline" @click="removeDialogOpen = false"
            >Cancel</Button
          >
          <Button variant="destructive" @click="removeDriverFromFranchise"
            >Confirm</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
