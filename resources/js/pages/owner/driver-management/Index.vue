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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useAddress } from '@/composables/useAddress';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { BreadcrumbItem } from '@/types';
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
  details: DriverDetails;
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
  statuses: Status[];
}

const { drivers, statuses } = defineProps<Props>();
const paginator = ref(drivers);

// --- Address Composable ---
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

// Dialog & Edit States
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
});

const viewDriver = (driver: Driver) => {
  selectedDriver.value = driver;
  isEditing.value = false;

  // Prep form values
  editForm.email = driver.email || '';
  editForm.phone = driver.phone || '';
  editForm.code_number = driver.details.code_number || '';
  editForm.license_number = driver.details.license_number || '';
  editForm.license_expiry = driver.details.license_expiry || '';

  // Set address defaults in composable to trigger chained fetching
  selectedRegion.value = driver.region || '';
  // Note: Provinces/Cities/Barangays will load via the composable's watchers
  // based on the name matching logic in your useAddress.ts
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

  // Sync address selections to form
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
      // Handle uniqueness errors from Laravel validation
      const firstErr = Object.values(errors)[0] as string;
      toast.error(
        firstErr ||
          'Failed to update. Check if Email/Phone/ID is already taken.',
      );
    },
  });
};

const removeDialogOpen = ref(false);
const driverToRemove = ref<Driver | null>(null);

const confirmRemoveDriver = (driver: Driver) => {
  driverToRemove.value = driver;
  removeDialogOpen.value = true;
};

// -------------------------
// File Upload Logic
// -------------------------
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
  const toastId = toast.loading(
    `Uploading new ${currentFieldToEdit.value.replace(/_/g, ' ')}...`,
  );

  router.post(
    `/owner/drivers/${selectedDriver.value.id}`,
    {
      _method: 'PUT',
      [currentFieldToEdit.value]: file,
    },
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

// -------------------------
// Watchers & Breadcrumbs
// -------------------------
watch(
  () => drivers,
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

const globalFilter = ref('');
const statusFilter = ref('all');

watch([statusFilter, globalFilter], () => {
  router.get(
    paginator.value.path,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: globalFilter.value || undefined,
      per_page: paginator.value.per_page,
    },
    { preserveState: true, preserveScroll: true },
  );
});

const paginationLinks = computed(() => paginator.value.links || []);

const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(
    url,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: globalFilter.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
};

const getStatusVariant = (status: string) => {
  switch (status) {
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
      <div>
        <h1 class="mb-1 text-3xl font-bold">Driver Management</h1>
        <p class="text-gray-600">Driver Management on your franchise</p>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="globalFilter"
            placeholder="Search drivers..."
            class="w-full rounded-md border px-10 py-2"
          />
        </div>
      </div>

      <div class="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Username</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Phone</TableHead>
              <TableHead>Region</TableHead>
              <TableHead>Province</TableHead>
              <TableHead>City</TableHead>
              <TableHead>Barangay</TableHead>
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
              <TableCell>{{ driver.phone }}</TableCell>
              <TableCell>{{ driver.region }}</TableCell>
              <TableCell>{{ driver.province }}</TableCell>
              <TableCell>{{ driver.city }}</TableCell>
              <TableCell>{{ driver.barangay }}</TableCell>
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
                  ><Eye
                /></Button>
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button size="sm" variant="outline" class="cursor-pointer"
                      ><Edit
                    /></Button>
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
                  ><Trash
                /></Button>
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
                  :class="{ 'bg-gray-100': link.active }"
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
          <div class="flex items-center justify-between pr-6">
            <div>
              <DialogTitle>Driver's Information</DialogTitle>
              <DialogDescription
                >Detailed information for driver
                <strong>{{ selectedDriver?.username }}</strong
                >.</DialogDescription
              >
            </div>
          </div>
        </DialogHeader>

        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
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
                  >City / Municipality</label
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
        </div>

        <div
          class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 border-t pt-4 text-sm"
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

        <div class="flex justify-start gap-2 pt-2">
          <template v-if="!isEditing">
            <Button
              variant="outline"
              size="sm"
              @click="isEditing = true"
              class="h-7 text-xs"
              ><Edit class="mr-1 h-3 w-3" /> Edit Profile</Button
            >
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
              ><X class="mr-1 h-3 w-3" /> Cancel</Button
            >
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
              <div
                v-if="selectedDriver.details[field as keyof DriverDetails]"
                class="space-y-1"
              >
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
                      :href="
                        String(
                          selectedDriver.details[field as keyof DriverDetails],
                        )
                      "
                      class="text-[10px] font-bold text-gray-600 hover:underline"
                      target="_blank"
                      >View</a
                    >
                  </div>
                </div>
                <img
                  :src="
                    String(selectedDriver.details[field as keyof DriverDetails])
                  "
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
          <DialogDescription
            >Are you sure you want to remove
            <strong>{{ driverToRemove?.username }}</strong> from your
            franchise?</DialogDescription
          >
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
