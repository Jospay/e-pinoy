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
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import technician from '@/routes/technician';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Eye } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface MaintenanceJob {
  id: number;
  maintenance_type: string;
  description: string;
  maintenance_date: string;
  next_maintenance_date: string | null;

  vehicle: Vehicle | null;

  technician: Technician | null;

  franchise_id: number | null;
  branch_id: number | null;

  status: string;
  status_id: number;

  created_at: string;

  inventory: Inventory | null;
}

interface Vehicle {
  id: number;
  plate_number: string;
  vin: string;
  brand: string;
  model: string;
  color: string;
  year: number;
}

interface Inventory {
  id: number;
  code_no: string;
  name: string;
  category: string;
  specification: string;
}

interface Technician {
  id: number;
  name: string;
  email: string;
  phone: string;
}

interface Status {
  id: number;
  name: string;
}

interface MaintenancePaginator {
  current_page: number;
  data: MaintenanceJob[];
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

const props = defineProps<{
  franchiseExists: boolean;

  maintenance: MaintenancePaginator;
  statuses: Status[];
}>();

const paginator = ref(props.maintenance);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tickets / Jobs',
    href: technician.ticket().url,
  },
];

// -------------------------
// Watch for prop updates
// -------------------------
watch(
  () => props.maintenance,
  (newVal) => {
    paginator.value = newVal;
  },
  { deep: true },
);

// Filters
const globalFilter = ref('');
const statusFilter = ref('all');

// Filtered Maintenances
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

// Status badge style
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'active':
      return 'secondary';
    case 'completed':
      return 'default';
    default:
      return 'secondary';
  }
};

// Pagination Helpers
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

const selectedMaintenance = ref<MaintenanceJob | null>(null);
const dialogOpen = ref(false);
const viewMaintenance = (maintenance: MaintenanceJob) => {
  selectedMaintenance.value = maintenance;
  dialogOpen.value = true;
};

// -------------------------
// Toggle jobs status
// -------------------------
const updatingId = ref<number | null>(null);

const updateJobStatus = (ticketId: number, statusId: number) => {
  updatingId.value = ticketId;
  const toastId = toast.loading('Updating job status...');

  router.put(
    `/technician/ticket/${ticketId}`,
    { status_id: statusId },
    {
      onSuccess: () => toast.success('Job status updated!', { id: toastId }),
      onError: () =>
        toast.error('Failed to update job status.', { id: toastId }),
      onFinish: () => (updatingId.value = null),
    },
  );
};

const completedStatusId = 16;
</script>

<template>
  <Head title="Tickets / Jobs" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="mb-1 text-3xl font-bold">Tickets / Jobs</h1>
          <p class="text-gray-600">Update the status of tickets / jobs here.</p>
        </div>
      </div>

      <!-- No Franchise -->
      <template v-if="!franchiseExists">
        <p>No franchise assigned. Dashboard data is not available.</p>
      </template>

      <template v-else>
        <!-- Filters -->
        <div class="flex flex-col gap-4 md:flex-row md:items-center">
          <input
            v-model="globalFilter"
            placeholder="Search..."
            class="w-full rounded-md border px-3 py-2"
          />
          <select
            v-model="statusFilter"
            class="w-full rounded-md border px-3 py-2 md:w-48"
          >
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <!-- Maintenance Table -->
        <div class="rounded-lg border">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Vehicle</TableHead>
                <TableHead>Plate Number</TableHead>
                <TableHead>VIN</TableHead>
                <TableHead>Technician's Name</TableHead>
                <TableHead>Inventory</TableHead>
                <TableHead>Description</TableHead>
                <TableHead>Maintenance Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow
                v-for="maintenance in paginator.data"
                :key="maintenance.id"
                class="hover:bg-muted/50"
              >
                <TableCell
                  >{{ maintenance.vehicle?.brand }}
                  {{ maintenance.vehicle?.model }}</TableCell
                >
                <TableCell>{{ maintenance.vehicle?.plate_number }}</TableCell>
                <TableCell>{{ maintenance.vehicle?.vin }}</TableCell>
                <TableCell>{{ maintenance.technician.name }}</TableCell>
                <TableCell>{{ maintenance.inventory?.name }}</TableCell>
                <TableCell>{{ maintenance.description }}</TableCell>
                <TableCell>{{ maintenance.maintenance_date }}</TableCell>
                <TableCell>
                  <Badge
                    :variant="getStatusVariant(maintenance.status)"
                    class="capitalize"
                  >
                    {{ maintenance.status }}
                  </Badge>
                </TableCell>
                <TableCell class="flex gap-2">
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <Button
                          size="sm"
                          variant="outline"
                          @click="viewMaintenance(maintenance)"
                          class="cursor-pointer"
                        >
                          <Eye />
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>View</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>

                  <DropdownMenu>
                    <DropdownMenuTrigger as-child
                      ><Button
                        size="sm"
                        variant="outline"
                        class="cursor-pointer"
                      >
                        <Edit /> </Button
                    ></DropdownMenuTrigger>
                    <DropdownMenuContent>
                      <DropdownMenuLabel>Action</DropdownMenuLabel>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        v-for="status in statuses"
                        :key="status.id"
                        @click="updateJobStatus(maintenance.id, status.id)"
                        :disabled="maintenance.status_id === completedStatusId"
                      >
                        {{
                          status.name.charAt(0).toUpperCase() +
                          status.name.slice(1)
                        }}
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
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
      </template>

      <Dialog v-model:open="dialogOpen">
        <DialogContent class="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>Maintenance Request Information</DialogTitle>
            <DialogDescription>
              Detailed information for request
              <strong>{{ selectedMaintenance?.id }}</strong
              >.
            </DialogDescription>
          </DialogHeader>

          <!-- Request Information -->
          <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <p><strong>ID:</strong> {{ selectedMaintenance?.id }}</p>
            <p><strong>Status:</strong> {{ selectedMaintenance?.status }}</p>

            <p>
              <strong>Description:</strong>
              {{ selectedMaintenance?.description }}
            </p>
            <p>
              <strong>Maintenance Date:</strong>
              {{ selectedMaintenance?.maintenance_date }}
            </p>
            <p>
              <strong>Next Maintenance Date:</strong>
              {{ selectedMaintenance?.next_maintenance_date }}
            </p>
          </div>

          <!-- Vehicle Information -->
          <div
            v-if="selectedMaintenance?.vehicle"
            class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm"
          >
            <p>
              <strong>Plate Number:</strong>
              {{ selectedMaintenance.vehicle.plate_number }}
            </p>
            <p><strong>VIN:</strong> {{ selectedMaintenance.vehicle.vin }}</p>
            <p>
              <strong>Brand:</strong> {{ selectedMaintenance.vehicle.brand }}
            </p>
            <p>
              <strong>Model:</strong> {{ selectedMaintenance.vehicle.model }}
            </p>
            <p>
              <strong>Color:</strong> {{ selectedMaintenance.vehicle.color }}
            </p>
            <p><strong>Year:</strong> {{ selectedMaintenance.vehicle.year }}</p>
          </div>

          <!-- Inventory Information -->
          <div
            v-if="selectedMaintenance?.inventory"
            class="mt-4 grid grid-cols-2 gap-x-6 gap-y-2 text-sm"
          >
            <p>
              <strong>Code No:</strong>
              {{ selectedMaintenance.inventory.code_no }}
            </p>
            <p>
              <strong>Name:</strong> {{ selectedMaintenance.inventory.name }}
            </p>
            <p>
              <strong>Category:</strong>
              {{ selectedMaintenance.inventory.category }}
            </p>
            <p>
              <strong>Specification:</strong>
              {{ selectedMaintenance.inventory.specification }}
            </p>
          </div>

          <DialogFooter>
            <Button @click="dialogOpen = false">Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
