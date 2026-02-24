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
import { Input } from '@/components/ui/input';
import {
  Pagination,
  PaginationContent,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { Eye, Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
  contracts: {
    current_page: number;
    data: any[];
    total: number;
    per_page: number;
    from: number;
    to: number;
    links: any[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  branches: any[];
  franchiseVehicleTypes: any[];
  statuses: any[];
  filters?: {
    search?: string;
    status?: string;
    vehicle_type?: string;
    branch_id?: string | number;
  };
}

const props = defineProps<Props>();
const paginator = ref(props.contracts);

// Keep paginator in sync with server data
watch(
  () => props.contracts,
  (newVal) => {
    paginator.value = newVal;
  },
  { deep: true },
);

const breadcrumbs = [
  { title: 'Boundary Contracts', href: '/owner/boundary-contracts' },
];

// Initialize filters from props
const globalFilter = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const branchFilter = ref(props.filters?.branch_id?.toString() || 'all');
const activeTab = ref(
  props.filters?.vehicle_type || props.franchiseVehicleTypes[0]?.name || '',
);

const updateFilters = debounce(() => {
  router.get(
    window.location.pathname,
    {
      search: globalFilter.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      branch_id: branchFilter.value !== 'all' ? branchFilter.value : undefined,
      vehicle_type: activeTab.value || undefined,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  );
}, 300);

// Watch for changes and update results
watch(
  [globalFilter, statusFilter, branchFilter, activeTab],
  (newValues, oldValues) => {
    const isInitialLoad = oldValues.every(
      (val) => val === undefined || val === '',
    );
    if (!isInitialLoad) {
      updateFilters();
    }
  },
);

const showDialog = ref(false);
const selectedContract = ref<any>(null);

const openDetails = (contract: any) => {
  selectedContract.value = contract;
  showDialog.value = true;
};

const getStatusVariant = (status: string) => {
  if (!status) return 'outline';
  const s = status.toLowerCase();
  switch (s) {
    case 'active':
      return 'default';
    case 'pending':
      return 'secondary';
    case 'terminated':
    case 'expired':
      return 'destructive';
    default:
      return 'outline';
  }
};

const goToPage = (url: string | null) => {
  if (url) router.get(url, {}, { preserveState: true, preserveScroll: true });
};
</script>

<template>
  <Head title="Boundary Contracts" />
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
            class="px-4"
          >
            {{ type.name }}
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Boundary Contracts</h1>
          <p class="text-muted-foreground">
            Manage and monitor driver lease agreements
          </p>
        </div>
        <Button @click="router.get('/owner/boundary-contracts/create')">
          <Plus class="mr-2 h-4 w-4" /> Add Contract
        </Button>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <Input
          v-model="globalFilter"
          placeholder="Search by contract name or driver..."
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
                  >{{ branch.name }}</SelectItem
                >
              </SelectGroup>
            </SelectContent>
          </Select>
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="expired">Expired</SelectItem>
            <SelectItem value="terminated">Terminated</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="rounded-lg border bg-white">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Contract Name</TableHead>
              <TableHead>Driver</TableHead>
              <TableHead>Assignment</TableHead>
              <TableHead>Vehicle Type</TableHead>
              <TableHead>Daily Amount</TableHead>
              <TableHead>Status</TableHead>
              <TableHead class="text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="c in paginator.data" :key="c.id">
              <TableCell class="font-medium">{{ c.name }}</TableCell>
              <TableCell>{{ c.driver_username }}</TableCell>
              <TableCell>
                <div class="flex flex-col">
                  <span class="text-sm font-medium">{{ c.branch_name }}</span>
                  <span class="text-[10px] text-muted-foreground uppercase">{{
                    c.is_branch ? 'Branch' : 'Franchise'
                  }}</span>
                </div>
              </TableCell>
              <TableCell>
                <Badge
                  variant="outline"
                  class="border-primary/20 bg-primary/5 text-[10px] font-bold text-primary uppercase"
                >
                  {{ c.vehicle_type_name }}
                </Badge>
              </TableCell>
              <TableCell>₱{{ c.amount }}</TableCell>
              <TableCell>
                <Badge :variant="getStatusVariant(c.status_name)">{{
                  c.status_name
                }}</Badge>
              </TableCell>
              <TableCell class="text-center">
                <Button variant="ghost" size="icon" @click="openDetails(c)">
                  <Eye class="h-4 w-4" />
                </Button>
              </TableCell>
            </TableRow>
            <TableRow v-if="paginator.data.length === 0">
              <TableCell
                colspan="7"
                class="py-10 text-center text-muted-foreground"
              >
                No contracts found matching your filters.
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
        >
          <PaginationContent>
            <PaginationPrevious
              :disabled="!paginator.prev_page_url"
              @click="goToPage(paginator.prev_page_url)"
            />
            <PaginationNext
              :disabled="!paginator.next_page_url"
              @click="goToPage(paginator.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>
    </div>

    <Dialog v-model:open="showDialog">
      <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-[600px]">
        <DialogHeader>
          <div class="flex items-center justify-between">
            <div>
              <DialogTitle class="text-xl"
                >Contract: {{ selectedContract?.name }}</DialogTitle
              >
              <DialogDescription
                >Full summary and contact information</DialogDescription
              >
            </div>
            <Badge
              :variant="getStatusVariant(selectedContract?.status_name)"
              class="mr-6 uppercase"
            >
              {{ selectedContract?.status_name }}
            </Badge>
          </div>
        </DialogHeader>

        <div v-if="selectedContract" class="space-y-6 py-4">
          <div
            class="grid grid-cols-3 gap-4 rounded-lg bg-muted/50 p-4 text-sm"
          >
            <div>
              <p class="text-[10px] font-bold text-muted-foreground uppercase">
                Daily Amount
              </p>
              <p class="text-lg font-bold text-green-600">
                ₱{{ selectedContract.amount }}
              </p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-muted-foreground uppercase">
                Start Date
              </p>
              <p class="font-medium">{{ selectedContract.start_date }}</p>
            </div>
            <div>
              <p class="text-[10px] font-bold text-muted-foreground uppercase">
                End Date
              </p>
              <p class="font-medium">{{ selectedContract.end_date }}</p>
            </div>
          </div>

          <div class="space-y-2">
            <h3 class="text-xs font-bold tracking-wider text-primary uppercase">
              Driver Information
            </h3>
            <div class="grid grid-cols-2 gap-4 rounded-md border p-3 text-sm">
              <div>
                <p class="text-[11px] text-gray-500">Username</p>
                <p class="font-medium">
                  {{ selectedContract.driver_username }}
                </p>
              </div>
              <div>
                <p class="text-[11px] text-gray-500">Phone</p>
                <p class="font-medium">{{ selectedContract.driver_phone }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-[11px] text-gray-500">Email Address</p>
                <p class="font-medium">{{ selectedContract.driver_email }}</p>
              </div>
            </div>
          </div>

          <div class="space-y-2">
            <h3 class="text-xs font-bold tracking-wider text-primary uppercase">
              {{
                selectedContract.is_branch
                  ? 'Branch Details'
                  : 'Franchise Details'
              }}
            </h3>
            <div
              class="grid grid-cols-2 gap-4 rounded-md border bg-blue-50/30 p-3 text-sm"
            >
              <div>
                <p class="text-[11px] text-gray-500">Entity Name</p>
                <p class="font-medium">{{ selectedContract.branch_name }}</p>
              </div>
              <div>
                <p class="text-[11px] text-gray-500">
                  {{
                    selectedContract.is_branch
                      ? 'Branch Phone'
                      : 'Franchise Phone'
                  }}
                </p>
                <p class="font-medium">{{ selectedContract.branch_phone }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-[11px] text-gray-500">
                  {{
                    selectedContract.is_branch
                      ? 'Branch Email'
                      : 'Franchise Email'
                  }}
                </p>
                <p class="font-medium">{{ selectedContract.branch_email }}</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 border-t pt-4 text-sm">
            <div>
              <p class="font-bold text-gray-700">Coverage Area</p>
              <p class="text-muted-foreground">
                {{ selectedContract.coverage_area }}
              </p>
            </div>
            <div>
              <p class="font-bold text-gray-700">Contract Terms</p>
              <p class="whitespace-pre-line text-muted-foreground">
                {{ selectedContract.contract_terms }}
              </p>
            </div>
            <div>
              <p class="font-bold text-gray-700">Renewal Terms</p>
              <p class="whitespace-pre-line text-muted-foreground">
                {{ selectedContract.renewal_terms }}
              </p>
            </div>
          </div>
        </div>

        <DialogFooter class="border-t pt-4">
          <Button
            variant="outline"
            class="w-full sm:w-auto"
            @click="showDialog = false"
            >Close</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
