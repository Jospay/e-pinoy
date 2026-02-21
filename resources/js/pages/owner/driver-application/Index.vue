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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';

import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash-es';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// 🔵 ADDED
interface Branch {
  id: number;
  name: string;
}

interface Assignment {
  type: 'branch' | 'franchise';
  name: string;
}

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

interface VehicleType {
  id: number;
  name: string;
}

interface Driver {
  id: number;
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
  assignment?: Assignment | null;
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
  // 🔵 ADDED
  branches: Branch[];
  filters?: {
    search?: string;
    status?: string;
    vehicle_type?: string;
  };
}

const props = defineProps<Props>();

const confirmDialogOpen = ref(false);
const driverToToggle = ref<Driver | null>(null);
const updatingId = ref<number | null>(null);

// 🔵 ADDED
const selectedTarget = ref<'franchise' | number>('franchise');

// 🔵 ADDED
watch(confirmDialogOpen, (open) => {
  if (open) selectedTarget.value = 'franchise';
});

// Initialize filters from props or defaults
const globalFilter = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'available');

// Initialize activeTab
const activeTab = ref(props.filters?.vehicle_type || '');

// Unified update function to fetch data from server
const updateFilters = debounce(() => {
  router.get(
    window.location.pathname,
    {
      status: statusFilter.value,
      search: globalFilter.value || undefined,
      vehicle_type: activeTab.value,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}, 300);

/**
 * WATCHERS: This is what was missing!
 * These watch the local refs and trigger the router.get() automatically.
 */
watch([statusFilter, activeTab], () => {
  updateFilters();
});

watch(globalFilter, () => {
  updateFilters();
});

// Sync local state if the user navigates (Back/Forward browser buttons)
watch(
  () => props.filters,
  (newFilters) => {
    if (
      newFilters?.vehicle_type &&
      newFilters.vehicle_type !== activeTab.value
    ) {
      activeTab.value = newFilters.vehicle_type;
    }
    if (newFilters?.status && newFilters.status !== statusFilter.value) {
      statusFilter.value = newFilters.status;
    }
    if (
      newFilters?.search !== undefined &&
      newFilters.search !== globalFilter.value
    ) {
      globalFilter.value = newFilters.search;
    }
  },
  { deep: true },
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Driver Applications', href: owner.drivers.index().url },
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
      status: statusFilter.value,
      search: globalFilter.value || undefined,
      vehicle_type: activeTab.value,
    },
    { preserveState: true, preserveScroll: true },
  );
};

const getStatusVariant = (status: string) => {
  const s = status?.toLowerCase() || '';
  if (s === 'available') return 'default';
  if (s === 'for approval' || s === 'pending') return 'secondary';
  if (s === 'retired') return 'destructive';
  return 'outline';
};

const handleAction = (id: number, action: 'request' | 'cancel') => {
  updatingId.value = id;
  const loadingMsg =
    action === 'request' ? 'Sending request...' : 'Cancelling request...';
  const toastId = toast.loading(loadingMsg);

  router.put(
    `/owner/drivers-application/${id}`,
    {
      action: action,
      // 🔵 ADDED
      target: selectedTarget.value,
    },
    {
      onSuccess: () => {
        toast.success(`Action successful!`, { id: toastId });
        confirmDialogOpen.value = false;
      },
      onError: () => toast.error(`Action failed.`, { id: toastId }),
      onFinish: () => (updatingId.value = null),
    },
  );
};
</script>

<template>
  <Head title="Driver Applications" />
  <AppLayout :breadcrumbs="breadcrumbs">
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
        <h1 class="mb-1 text-3xl font-bold">Driver Applications</h1>
        <p class="text-gray-600">
          Accept the applications of drivers for your
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
            class="w-full rounded-md border border-input bg-background px-10 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
          />
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-56">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="available">Available Driver</SelectItem>
            <SelectItem value="for approval">Request Sent (Pending)</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="overflow-x-auto rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Username</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Vehicle Type</TableHead>
              <TableHead v-if="statusFilter === 'for approval'"
                >Assigned To</TableHead
              >
              <TableHead>Status</TableHead>
              <TableHead class="text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow
              v-for="driver in drivers.data"
              :key="driver.id"
              class="transition-colors hover:bg-muted/50"
            >
              <TableCell class="font-medium">{{ driver.username }}</TableCell>
              <TableCell>{{ driver.email }}</TableCell>
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

              <TableCell v-if="statusFilter === 'for approval'">
                <Badge variant="outline">
                  {{ driver.assignment?.name || 'Franchise' }}
                  <span class="text-xs text-gray-400">
                    ({{
                      driver.assignment?.type === 'branch'
                        ? 'Branch'
                        : 'Franchise'
                    }})
                  </span>
                </Badge>
              </TableCell>

              <TableCell>
                <Badge
                  :variant="getStatusVariant(driver.status)"
                  class="capitalize"
                >
                  {{ driver.status }}
                </Badge>
              </TableCell>

              <TableCell class="text-center">
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
                  <span v-else>View Details</span>
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
        <span class="text-sm text-muted-foreground">
          Showing {{ drivers.from || 0 }} to {{ drivers.to || 0 }} of
          {{ drivers.total }} entries
        </span>

        <Pagination
          :items-per-page="drivers.per_page"
          :total="drivers.total"
          :default-page="drivers.current_page"
          class="w-auto"
        >
          <PaginationContent>
            <PaginationPrevious
              class="cursor-pointer"
              :class="{
                'pointer-events-none opacity-50': !drivers.prev_page_url,
              }"
              @click="goToPage(drivers.prev_page_url)"
            />

            <template v-for="(link, index) in paginationLinks" :key="index">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{ 'bg-gray-100 font-bold': link.active }"
                  :disabled="!link.url"
                  @click="goToPage(link.url)"
                >
                  {{ link.label }}
                </Button>
              </PaginationItem>
              <PaginationEllipsis v-else-if="link.label.includes('...')" />
            </template>

            <PaginationNext
              class="cursor-pointer"
              :class="{
                'pointer-events-none opacity-50': !drivers.next_page_url,
              }"
              @click="goToPage(drivers.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>
    </div>

    <Dialog v-model:open="confirmDialogOpen">
      <DialogContent class="overflow-hidden sm:max-w-2xl">
        <div class="flex max-h-[90vh] flex-col">
          <DialogHeader class="pb-1">
            <DialogTitle class="text-xl font-semibold">
              Confirm Status Change
            </DialogTitle>

            <DialogDescription class="text-gray-600">
              You are about to toggle the status of
              <span class="font-semibold text-gray-900">
                {{ driverToToggle?.username }} </span
              >.
            </DialogDescription>
          </DialogHeader>

          <div
            class="flex-1 overflow-y-auto pe-1 pb-6"
            style="scrollbar-gutter: stable both-edges"
          >
            <div class="mt-4 text-sm">
              <div class="grid grid-cols-2 gap-x-6 gap-y-2">
                <p><strong>Email:</strong> {{ driverToToggle?.email }}</p>
                <p><strong>Phone:</strong> {{ driverToToggle?.phone }}</p>
                <p>
                  <strong class="pe-1">Vehicle Type:</strong>
                  <span
                    v-for="type in driverToToggle?.vehicle_types"
                    :key="type.id"
                  >
                    {{ type.name }}
                  </span>
                </p>
                <p><strong>Status:</strong> {{ driverToToggle?.status }}</p>

                <p v-if="statusFilter === 'for approval'">
                  <strong>Assigned To:</strong>
                  {{ driverToToggle?.assignment?.name || 'Franchise' }}
                  <span class="text-xs text-gray-400">
                    ({{
                      driverToToggle?.assignment?.type === 'branch'
                        ? 'Branch'
                        : 'Franchise'
                    }})
                  </span>
                </p>

                <p><strong>Region:</strong> {{ driverToToggle?.region }}</p>
                <p><strong>Province:</strong> {{ driverToToggle?.province }}</p>
                <p><strong>City:</strong> {{ driverToToggle?.city }}</p>
                <p><strong>Barangay:</strong> {{ driverToToggle?.barangay }}</p>
              </div>
              <p class="mt-2 text-sm">
                <strong>Address:</strong> {{ driverToToggle?.address }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-2 pt-2 text-sm">
              <p>
                <strong>License Number:</strong>
                {{ driverToToggle?.details?.license_number }}
              </p>
              <p>
                <strong>License Expiry:</strong>
                {{ driverToToggle?.details?.license_expiry }}
              </p>
            </div>

            <div v-if="driverToToggle?.details" class="mt-4 pb-2">
              <h3 class="mb-2 text-sm font-semibold">Driver Documents</h3>

              <div class="grid grid-cols-2 gap-4">
                <div v-if="driverToToggle.details.front_license_picture">
                  <div class="mb-1 flex justify-between">
                    <p class="text-xs text-gray-500">Front License</p>
                    <a
                      :href="driverToToggle.details.front_license_picture"
                      class="text-xs text-blue-500"
                      target="_blank"
                      >View</a
                    >
                  </div>
                  <img
                    :src="driverToToggle.details.front_license_picture"
                    class="h-28 w-full rounded border object-cover"
                  />
                </div>

                <div v-if="driverToToggle.details.back_license_picture">
                  <div class="mb-1 flex justify-between">
                    <p class="text-xs text-gray-500">Back License</p>
                    <a
                      :href="driverToToggle.details.back_license_picture"
                      class="text-xs text-blue-500"
                      target="_blank"
                      >View</a
                    >
                  </div>
                  <img
                    :src="driverToToggle.details.back_license_picture"
                    class="h-28 w-full rounded border object-cover"
                  />
                </div>

                <div v-if="driverToToggle.details.nbi_clearance">
                  <div class="mb-1 flex justify-between">
                    <p class="text-xs text-gray-500">NBI Clearance</p>
                    <a
                      :href="driverToToggle.details.nbi_clearance"
                      class="text-xs text-blue-500"
                      target="_blank"
                      >View</a
                    >
                  </div>
                  <img
                    :src="driverToToggle.details.nbi_clearance"
                    class="h-28 w-full rounded border object-cover"
                  />
                </div>

                <div v-if="driverToToggle.details.selfie_picture">
                  <div class="mb-1 flex justify-between">
                    <p class="text-xs text-gray-500">Selfie</p>
                    <a
                      :href="driverToToggle.details.selfie_picture"
                      class="text-xs text-blue-500"
                      target="_blank"
                      >View</a
                    >
                  </div>
                  <img
                    :src="driverToToggle.details.selfie_picture"
                    class="h-28 w-full rounded border object-cover"
                  />
                </div>
              </div>
            </div>

            <!-- ✅ ADDED: Branch selector -->
            <div
              v-if="
                driverToToggle?.status === 'available' &&
                props.branches.length > 0
              "
            >
              <p class="p-1 text-sm font-medium">Assign Driver To:</p>

              <Select v-model="selectedTarget">
                <SelectTrigger>
                  <SelectValue placeholder="Choose destination" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem value="franchise">Franchise</SelectItem>

                  <SelectItem
                    v-for="branch in props.branches"
                    :key="branch.id"
                    :value="branch.id"
                  >
                    {{ branch.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <DialogFooter class="border-t pt-4">
            <div class="flex w-full justify-end gap-2">
              <Button variant="outline" @click="confirmDialogOpen = false">
                Cancel
              </Button>

              <Button
                v-if="driverToToggle?.status === 'available'"
                size="sm"
                variant="default"
                :disabled="updatingId === driverToToggle?.id"
                @click="handleAction(driverToToggle!.id, 'request')"
              >
                <Spinner
                  v-if="updatingId === driverToToggle?.id"
                  class="mr-2 h-4 w-4"
                />
                Request
              </Button>

              <Button
                v-else-if="driverToToggle?.status === 'for approval'"
                size="sm"
                variant="destructive"
                :disabled="updatingId === driverToToggle?.id"
                @click="handleAction(driverToToggle!.id, 'cancel')"
              >
                <Spinner
                  v-if="updatingId === driverToToggle?.id"
                  class="mr-2 h-4 w-4"
                />
                Cancel Request
              </Button>
            </div>
          </DialogFooter>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
