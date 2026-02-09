<script setup lang="ts">
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
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, Eye, PlusIcon, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Contract {
  id: number;
  name: string;
  amount: string;
  coverage_area: string;
  contract_terms: string;
  start_date: string;
  end_date: string;
  status: 'pending' | 'active' | 'expired' | 'terminated' | string;
  driver_username: string;
  driver_email: string;
  driver_phone: string;
  franchise: string | null;
  franchise_email?: string;
  franchise_phone?: string;
  branch: string | null;
}

// Updated fields to match the new database schema for the details view
const visibleContractFields = [
  'name',
  'amount',
  'coverage_area',
  'contract_terms',
  'start_date',
  'end_date',
  'status',
  'driver_username',
  'driver_email',
  'driver_phone',
  'franchise',
  'franchise_email',
  'franchise_phone',
] as const;

interface ContractsPaginator {
  current_page: number;
  data: Contract[];
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
  contracts: ContractsPaginator;
}>();

const paginator = ref(props.contracts);

// Update local ref if server sends new data (Inertia partial reloads)
watch(
  () => props.contracts,
  (newContracts) => {
    paginator.value = newContracts;
  },
  { deep: true },
);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Boundary Contracts', href: owner.boundaryContracts.index().url },
];

// Filters
const globalFilter = ref('');
const statusFilter = ref('all');

// Dialog State
const selectedContract = ref<Contract | null>(null);
const showDialog = ref(false);

const openDialog = (contract: Contract) => {
  selectedContract.value = contract;
  showDialog.value = true;
};

// Helper for Status Badge Colors
const getStatusVariant = (status: string | undefined) => {
  switch (status?.toLowerCase()) {
    case 'active':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'expired':
      return 'outline';
    case 'terminated':
      return 'destructive';
    default:
      return 'secondary';
  }
};

// Client-side search for the current page data
const filteredData = computed(() => {
  if (!globalFilter.value) return paginator.value.data;
  const search = globalFilter.value.toLowerCase();
  return paginator.value.data.filter((item) =>
    Object.values(item)
      .filter((v) => v !== null && v !== undefined)
      .some((v) => v.toString().toLowerCase().includes(search)),
  );
});

// Navigation logic
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

// Watchers to trigger server-side filtering
watch([statusFilter, globalFilter], () => {
  router.get(
    paginator.value.path,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      search: globalFilter.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
});

const createContract = () => {
  router.get(owner.boundaryContracts.create().url);
};
</script>

<template>
  <Head title="Boundary Contracts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="mb-1 text-3xl font-bold">Boundary Contracts</h1>
        <p class="text-gray-600">
          Manage and monitor driver lease agreements and coverage areas.
        </p>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="globalFilter"
            placeholder="Search contracts..."
            class="w-full rounded-md border px-10 py-2 focus:ring-2 focus:ring-primary focus:outline-none"
          />
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="expired">Expired</SelectItem>
            <SelectItem value="terminated">Terminated</SelectItem>
          </SelectContent>
        </Select>

        <Button @click="createContract">
          <PlusIcon class="mr-2 h-4 w-4" /> Add Contract
        </Button>
      </div>

      <div class="rounded-lg border bg-white">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Contract Name</TableHead>
              <TableHead>Driver</TableHead>
              <TableHead>Franchise</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Area</TableHead>
              <TableHead>Duration</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-if="filteredData.length === 0">
              <TableCell colspan="8" class="py-12 text-center text-gray-500">
                No contracts found matching your criteria.
              </TableCell>
            </TableRow>

            <TableRow v-for="contract in filteredData" :key="contract.id">
              <TableCell class="font-medium">{{ contract.name }}</TableCell>
              <TableCell>{{ contract.driver_username || '—' }}</TableCell>
              <TableCell>{{ contract.franchise || '—' }}</TableCell>
              <TableCell class="font-medium text-green-700"
                >₱{{ contract.amount }}</TableCell
              >
              <TableCell class="max-w-[150px] truncate text-gray-600">
                {{ contract.coverage_area }}
              </TableCell>
              <TableCell>
                <div class="flex items-center gap-2 text-xs">
                  <Calendar class="h-3.5 w-3.5 text-gray-400" />
                  {{ new Date(contract.start_date).toLocaleDateString() }} -
                  {{ new Date(contract.end_date).toLocaleDateString() }}
                </div>
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(contract.status)">
                  {{ contract.status }}
                </Badge>
              </TableCell>
              <TableCell class="text-right">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button
                        size="icon"
                        variant="ghost"
                        @click="openDialog(contract)"
                      >
                        <Eye class="h-4 w-4" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent>View Details</TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between border-t pt-4">
        <p class="text-sm text-gray-500">
          Showing <span class="font-medium">{{ paginator.from || 0 }}</span> to
          <span class="font-medium">{{ paginator.to || 0 }}</span> of
          <span class="font-medium">{{ paginator.total }}</span> results
        </p>

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

            <template
              v-for="link in paginator.links.filter(
                (l) => !['&laquo; Previous', 'Next &raquo;'].includes(l.label),
              )"
              :key="link.label"
            >
              <PaginationItem v-if="link.url || link.label === '...'">
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{
                    'bg-primary text-white hover:bg-primary/90': link.active,
                  }"
                  :disabled="!link.url"
                  @click="goToPage(link.url)"
                >
                  {{ link.label }}
                </Button>
              </PaginationItem>
            </template>

            <PaginationNext
              :disabled="!paginator.next_page_url"
              @click="goToPage(paginator.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>

      <Dialog v-model:open="showDialog">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Contract Details</DialogTitle>
          </DialogHeader>

          <div
            v-if="selectedContract"
            class="grid grid-cols-2 gap-x-8 gap-y-4 border-t pt-4 text-sm"
          >
            <template v-for="key in visibleContractFields" :key="key">
              <div class="text-[10px] font-semibold uppercase ...">
                {{ key.replace(/_/g, ' ') }}
              </div>
              <div class="text-gray-900">
                {{ selectedContract[key] || 'Not provided' }}
              </div>
            </template>
          </div>

          <DialogFooter class="mt-6 border-t pt-4">
            <Button variant="secondary" @click="showDialog = false"
              >Close</Button
            >
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
