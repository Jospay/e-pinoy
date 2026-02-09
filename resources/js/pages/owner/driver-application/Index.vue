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
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
} from '@/components/ui/select';

import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
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
  filters?: {
    search?: string;
    status?: string;
  };
}

const { drivers, filters } = defineProps<Props>();
const paginator = ref(drivers);

const confirmDialogOpen = ref(false);
const driverToToggle = ref<Driver | null>(null);

watch(
  () => drivers,
  (newDrivers) => {
    paginator.value = newDrivers;
  },
  { deep: true },
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Driver Applications', href: owner.drivers.index().url },
];

const globalFilter = ref(filters?.search || '');
const statusFilter = ref(filters?.status || 'available');

watch([statusFilter, globalFilter], ([newStatus, newSearch]) => {
  router.get(
    window.location.pathname,
    {
      status: newStatus,
      search: newSearch || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
});

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

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'available':
      return 'default';
    case 'for approval':
      return 'secondary';
    case 'retired':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const updatingId = ref<number | null>(null);

const handleAction = (id: number, action: 'request' | 'cancel') => {
  updatingId.value = id;
  const loadingMsg =
    action === 'request' ? 'Sending request...' : 'Cancelling request...';
  const toastId = toast.loading(loadingMsg);

  router.put(
    `/owner/drivers-application/${id}`,
    { action: action },
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
      <div>
        <h1 class="mb-1 text-3xl font-bold">Driver Applications</h1>
        <p class="text-gray-600">
          Accept the applications of drivers to your franchise
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
            class="w-full rounded-md border px-10 py-2"
          />
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="available">Available Driver</SelectItem>
            <SelectItem value="for approval">Request Driver</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="overflow-x-auto rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Username</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Phone</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="text-center">Actions</TableHead>
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
              <TableCell>
                <Badge :variant="getStatusVariant(driver.status)">
                  {{ driver.status }}
                </Badge>
              </TableCell>
              <TableCell class="flex justify-center gap-2">
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
            <TableRow v-if="paginator.data.length === 0">
              <TableCell
                colspan="5"
                class="py-6 text-center text-muted-foreground"
              >
                No results found.
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
              :disabled="!paginator.next_page_url"
              @click="goToPage(paginator.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>
    </div>

    <Dialog v-model:open="confirmDialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
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

        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
          <p><strong>Email:</strong> {{ driverToToggle?.email }}</p>
          <p><strong>Phone:</strong> {{ driverToToggle?.phone }}</p>
          <p><strong>Status:</strong> {{ driverToToggle?.status }}</p>
          <p><strong>Region:</strong> {{ driverToToggle?.region }}</p>
          <p><strong>Province:</strong> {{ driverToToggle?.province }}</p>
          <p><strong>City:</strong> {{ driverToToggle?.city }}</p>
          <p><strong>Barangay:</strong> {{ driverToToggle?.barangay }}</p>
          <p><strong>Address:</strong> {{ driverToToggle?.address }}</p>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
          <p>
            <strong>License Number:</strong>
            {{ driverToToggle?.details?.license_number }}
          </p>
          <p>
            <strong>License Expiry:</strong>
            {{ driverToToggle?.details?.license_expiry }}
          </p>
        </div>

        <div v-if="driverToToggle?.details" class="mt-4">
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

        <DialogFooter class="mt-6">
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
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
