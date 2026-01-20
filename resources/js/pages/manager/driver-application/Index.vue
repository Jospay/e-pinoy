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
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
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
}

const { drivers } = defineProps<Props>();
const paginator = ref(drivers);

// Dialog
const selectedDriver = ref<Driver | null>(null);
const dialogOpen = ref(false);
const confirmDialogOpen = ref(false);
const driverToToggle = ref<Driver | null>(null);

const viewDriver = (driver: Driver) => {
  selectedDriver.value = driver;
  dialogOpen.value = true;
};

// -------------------------
// Watcher: update paginator when props change
// -------------------------
watch(
  () => drivers,
  (newDrivers) => {
    paginator.value = newDrivers;
  },
  { deep: true },
);

// -------------------------
// Breadcrumbs
// -------------------------
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Driver Applications', href: manager.drivers.index().url },
];

// -------------------------
// Filters / Search (server-side)
// -------------------------
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

// -------------------------
// Pagination helper
// -------------------------
const paginationLinks = computed(() => {
  return paginator.value.links || [];
});

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

// -------------------------
// Helpers
// -------------------------
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'retired':
      return 'destructive';
    default:
      return 'secondary';
  }
};

// -------------------------
// Toggle driver status
// -------------------------
const updatingId = ref<number | null>(null);

const toggleStatus = (id: number) => {
  updatingId.value = id;
  const toastId = toast.loading('Updating driver status...');
  router.put(
    `/manager/drivers-application/${id}`,
    {},
    {
      onSuccess: () => toast.success('Driver status updated!', { id: toastId }),
      onError: () =>
        toast.error('Failed to update driver status.', { id: toastId }),
      onFinish: () => (updatingId.value = null),
    },
  );
};
</script>

<template>
  <Head title="Driver Applications" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="mb-1 text-3xl font-bold">Driver Applications</h1>
        <p class="text-gray-600">
          Accept the applications of drivers to your branch
        </p>
      </div>

      <!-- Filters -->
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

        <!-- <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="suspended">Suspended</SelectItem>
            <SelectItem value="retired">Retired</SelectItem>
          </SelectContent>
        </Select> -->
      </div>

      <!-- Table -->
      <div class="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <!-- <TableHead>Name</TableHead> -->
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
              <!-- <TableCell>{{ driver.name }}</TableCell> -->
              <TableCell>{{ driver.username }}</TableCell>
              <TableCell>{{ driver.email }}</TableCell>
              <TableCell>{{ driver.phone }}</TableCell>
              <TableCell>{{ driver.region }}</TableCell>
              <TableCell>{{ driver.province }}</TableCell>
              <TableCell>{{ driver.city }}</TableCell>
              <TableCell>{{ driver.barangay }}</TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(driver.status)">
                  {{ driver.status }}
                </Badge>
              </TableCell>
              <TableCell class="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  :disabled="updatingId === driver.id"
                  @click="
                    driverToToggle = driver;
                    confirmDialogOpen = true;
                  "
                >
                  <Spinner
                    v-if="updatingId === driver.id"
                    class="mr-2 h-4 w-4"
                  />
                  <span v-else>Toggle Status</span>
                </Button>

                <Button size="sm" variant="default" @click="viewDriver(driver)">
                  View
                </Button>
              </TableCell>
            </TableRow>

            <TableRow v-if="paginator.data.length === 0">
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

    <Dialog v-model:open="dialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Driver's Information</DialogTitle>
          <DialogDescription>
            Detailed information for driver
            <strong>{{ selectedDriver?.name }}</strong
            >.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-2 space-y-2">
          <p><strong>ID:</strong> {{ selectedDriver?.details.code_number }}</p>
          <p><strong>Username:</strong> {{ selectedDriver?.username }}</p>
          <p><strong>Email:</strong> {{ selectedDriver?.email }}</p>
          <p><strong>Phone:</strong> {{ selectedDriver?.phone }}</p>
          <p><strong>Region:</strong> {{ selectedDriver?.region }}</p>
          <p><strong>Province:</strong> {{ selectedDriver?.province }}</p>
          <p><strong>City:</strong> {{ selectedDriver?.city }}</p>
          <p><strong>Barangay:</strong> {{ selectedDriver?.barangay }}</p>
          <p><strong>Status:</strong> {{ selectedDriver?.status || '—' }}</p>
        </div>

        <DialogFooter>
          <Button @click="dialogOpen = false">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="confirmDialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle class="text-xl font-semibold">
            Confirm Status Change
          </DialogTitle>

          <DialogDescription class="text-gray-600">
            You are about to toggle the status of
            <span class="font-semibold text-gray-900">
              {{ driverToToggle?.name }} </span
            >.
          </DialogDescription>
        </DialogHeader>

        <!-- Driver Information -->
        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
          <p><strong>ID:</strong> {{ driverToToggle?.details.code_number }}</p>
          <p><strong>Email:</strong> {{ driverToToggle?.email }}</p>

          <p><strong>Phone:</strong> {{ driverToToggle?.phone }}</p>
          <p><strong>Status:</strong> {{ driverToToggle?.status }}</p>

          <p><strong>Region:</strong> {{ driverToToggle?.region }}</p>
          <p><strong>Province:</strong> {{ driverToToggle?.province }}</p>
          <p><strong>City:</strong> {{ driverToToggle?.city }}</p>
          <p><strong>Barangay:</strong> {{ driverToToggle?.barangay }}</p>
          <p><strong>Address:</strong> {{ driverToToggle?.address }}</p>
        </div>

        <!-- Driver License Information -->
        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
          <p>
            <strong>License Number:</strong>
            {{ driverToToggle?.details.license_number }}
          </p>
          <p>
            <strong>License Expiry:</strong>
            {{ driverToToggle?.details.license_expiry }}
          </p>
        </div>

        <!-- Optional Images -->
        <div v-if="driverToToggle?.details" class="mt-4">
          <h3 class="mb-2 text-sm font-semibold">Driver Documents</h3>

          <div class="grid grid-cols-2 gap-4">
            <div v-if="driverToToggle.details.front_license_picture">
              <p class="mb-1 text-xs text-gray-500">Front License</p>
              <img
                :src="driverToToggle.details.front_license_picture"
                class="h-28 w-full rounded border object-cover"
              />
            </div>

            <div v-if="driverToToggle.details.back_license_picture">
              <p class="mb-1 text-xs text-gray-500">Back License</p>
              <img
                :src="driverToToggle.details.back_license_picture"
                class="h-28 w-full rounded border object-cover"
              />
            </div>

            <div v-if="driverToToggle.details.nbi_clearance">
              <p class="mb-1 text-xs text-gray-500">NBI Clearance</p>
              <img
                :src="driverToToggle.details.nbi_clearance"
                class="h-28 w-full rounded border object-cover"
              />
            </div>

            <div v-if="driverToToggle.details.selfie_picture">
              <p class="mb-1 text-xs text-gray-500">Selfie</p>
              <img
                :src="driverToToggle.details.selfie_picture"
                class="h-28 w-full rounded border object-cover"
              />
            </div>
          </div>
        </div>

        <DialogFooter class="mt-6 flex justify-end gap-2">
          <Button variant="outline" @click="confirmDialogOpen = false">
            Cancel
          </Button>

          <Button
            variant="default"
            :disabled="updatingId === driverToToggle?.id"
            @click="
              toggleStatus(driverToToggle!.id);
              confirmDialogOpen = false;
            "
          >
            <Spinner
              v-if="updatingId === driverToToggle?.id"
              class="mr-2 h-4 w-4"
            />
            <span v-else>Confirm</span>
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
