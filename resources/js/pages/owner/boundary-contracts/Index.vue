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
  status: 'pending' | 'paid' | 'overdue' | string;
  driver_username: string;
  driver_email: string;
  driver_phone: string;
  franchise: string | null;
  franchise_email?: string;
  franchise_phone?: string;
  branch: string | null;
}

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

interface Props {
  contracts: ContractsPaginator;
}

const { contracts } = defineProps<Props>();
const paginator = ref(contracts);

// -------------------------
// Watcher: update paginator when props change
// -------------------------
watch(
  () => contracts,
  (newContracts) => {
    paginator.value = newContracts;
  },
  { deep: true },
);

// -------------------------
// Breadcrumbs
// -------------------------
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Boundary Contracts', href: owner.boundaryContracts.index().url },
];

// -------------------------
// Filters / Search
// -------------------------
const globalFilter = ref('');
const statusFilter = ref('all');
const depositFilter = ref('all');

// -------------------------
// Dialog
// -------------------------
const selectedContract = ref<Contract | null>(null);
const showDialog = ref(false);

const openDialog = (contract: Contract) => {
  selectedContract.value = contract;
  showDialog.value = true;
};

// -------------------------
// Helpers
// -------------------------
const getStatusVariant = (status: string | undefined) => {
  switch (status) {
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

// -------------------------
// Computed: Filtered data for client-side search
// -------------------------
const filteredData = computed(() => {
  if (!globalFilter.value) return paginator.value.data;
  const search = globalFilter.value.toLowerCase();
  return paginator.value.data.filter((item) =>
    Object.values(item)
      .filter((v) => v !== null && v !== undefined)
      .some((v) => v.toString().toLowerCase().includes(search)),
  );
});

// -------------------------
// Pagination links without Previous/Next
// -------------------------
const paginationLinks = computed(() =>
  paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  ),
);

// -------------------------
// Pagination / server-side navigation
// -------------------------
const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(
    url,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      depositStatus:
        depositFilter.value !== 'all' ? depositFilter.value : undefined,
      search: globalFilter.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
};

// Watch filters / search and reload table
watch([statusFilter, depositFilter, globalFilter], () => {
  router.get(
    paginator.value.path,
    {
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      depositStatus:
        depositFilter.value !== 'all' ? depositFilter.value : undefined,
      search: globalFilter.value || undefined,
      per_page: paginator.value.per_page,
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
      <!-- Header -->
      <div>
        <h1 class="mb-1 text-3xl font-bold">Boundary Contracts</h1>
        <p class="text-gray-600">
          Manage Contracts for drivers assigned to vehicles.
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
            placeholder="Search contracts..."
            class="w-full rounded-md border px-10 py-2"
          />
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue>{{
              statusFilter === 'all' ? 'Filter by status' : statusFilter
            }}</SelectValue>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="overdue">Overdue</SelectItem>
          </SelectContent>
        </Select>

        <Button class="me-5" @click="createContract"
          ><PlusIcon />Add Contract</Button
        >
      </div>

      <!-- Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Contract Name</TableHead>
              <TableHead>Driver Username</TableHead>
              <TableHead>Franchise</TableHead>
              <TableHead>Amount</TableHead>
              <TableHead>Coverage Area</TableHead>
              <TableHead>Duration</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-if="filteredData.length === 0">
              <TableCell colspan="6" class="py-6 text-center text-gray-500">
                No results found.
              </TableCell>
            </TableRow>

            <TableRow v-for="contract in filteredData" :key="contract.id">
              <TableCell class="font-medium">{{ contract.name }}</TableCell>
              <TableCell>{{ contract.driver_username || '—' }}</TableCell>
              <TableCell>{{ contract.franchise || '—' }}</TableCell>
              <TableCell class="font-medium">₱{{ contract.amount }}</TableCell>
              <TableCell class="max-w-xs truncate">{{
                contract.coverage_area
              }}</TableCell>
              <TableCell class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-gray-400" />
                {{ new Date(contract.start_date).toLocaleDateString() }} -
                {{ new Date(contract.end_date).toLocaleDateString() }}
              </TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(contract.status)">
                  {{ contract.status }}
                </Badge>
              </TableCell>
              <TableCell class="flex gap-2">
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button
                        size="sm"
                        variant="outline"
                        @click="openDialog(contract)"
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

      <!-- Deposit Dialog -->
      <Dialog v-model:open="showDialog">
        <DialogContent class="max-w-3xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Contract Details</DialogTitle>
          </DialogHeader>

          <DialogDescription>
            <div v-if="selectedContract" class="grid grid-cols-2 gap-4">
              <template v-for="key in visibleContractFields" :key="key">
                <div class="font-medium capitalize">
                  {{ key.replace(/_/g, ' ') }}:
                </div>
                <div>
                  {{ selectedContract[key] }}
                </div>
              </template>
            </div>
          </DialogDescription>

          <DialogFooter>
            <Button variant="outline" @click="showDialog = false"
              >Cancel</Button
            >
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
