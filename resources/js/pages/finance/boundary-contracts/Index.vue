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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select/';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import finance from '@/routes/finance';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
  type ColumnDef,
} from '@tanstack/vue-table';
import { ArrowUpDown, Calendar, Check, Search, X } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

interface Contract {
  id: number;
  contractNumber: string;
  propertyAddress: string;
  client: string;
  dueDate: string;
  amount: number;
  status: 'pending' | 'paid' | 'overdue';
  depositStatus: 'pending' | 'approved' | 'flagged';
  depositAmount: number;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Boundary Contracts',
    href: finance.boundaryContracts().url,
  },
];

// Sample data
const data = ref<Contract[]>([
  {
    id: 1,
    contractNumber: 'BC-2024-001',
    propertyAddress: '123 Main St, Springfield',
    client: 'John Doe',
    dueDate: '2024-12-15',
    amount: 5000,
    status: 'pending',
    depositStatus: 'pending',
    depositAmount: 1000,
  },
  {
    id: 2,
    contractNumber: 'BC-2024-002',
    propertyAddress: '456 Oak Ave, Riverside',
    client: 'Jane Smith',
    dueDate: '2024-11-20',
    amount: 7500,
    status: 'paid',
    depositStatus: 'approved',
    depositAmount: 1500,
  },
  {
    id: 3,
    contractNumber: 'BC-2024-003',
    propertyAddress: '789 Pine Rd, Lakeside',
    client: 'Bob Johnson',
    dueDate: '2024-11-08',
    amount: 3200,
    status: 'overdue',
    depositStatus: 'flagged',
    depositAmount: 800,
  },
  {
    id: 4,
    contractNumber: 'BC-2024-004',
    propertyAddress: '321 Elm St, Hilltown',
    client: 'Alice Brown',
    dueDate: '2024-12-01',
    amount: 4500,
    status: 'pending',
    depositStatus: 'pending',
    depositAmount: 900,
  },
]);

const globalFilter = ref('');
const statusFilter = ref('all');
const depositFilter = ref('all');
const showDepositDialog = ref(false);
const selectedContract = ref<Contract | null>(null);

// Filtered data
const filteredData = computed(() => {
  let filtered = data.value;

  if (statusFilter.value !== 'all') {
    filtered = filtered.filter((item) => item.status === statusFilter.value);
  }

  if (depositFilter.value !== 'all') {
    filtered = filtered.filter(
      (item) => item.depositStatus === depositFilter.value,
    );
  }

  if (globalFilter.value) {
    const search = globalFilter.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        item.contractNumber.toLowerCase().includes(search) ||
        item.client.toLowerCase().includes(search) ||
        item.propertyAddress.toLowerCase().includes(search),
    );
  }

  return filtered;
});

// Helper functions
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'paid':
      return 'success';
    case 'pending':
      return 'secondary';
    case 'overdue':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const getDepositVariant = (status: string) => {
  switch (status) {
    case 'approved':
      return 'success';
    case 'pending':
      return 'secondary';
    case 'flagged':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const openDepositDialog = (contract: Contract) => {
  selectedContract.value = contract;
  showDepositDialog.value = true;
};

const handleDepositAction = (action: 'approved' | 'flagged') => {
  if (selectedContract.value) {
    const index = data.value.findIndex(
      (item) => item.id === selectedContract.value!.id,
    );
    if (index !== -1) {
      data.value[index].depositStatus = action;
    }
  }
  showDepositDialog.value = false;
};

// Table columns
const columns: ColumnDef<Contract>[] = [
  {
    accessorKey: 'contractNumber',
    header: 'Contract #',
    cell: ({ row }) =>
      h('div', { class: 'font-medium' }, row.getValue('contractNumber')),
  },
  {
    accessorKey: 'client',
    header: 'Client',
  },
  {
    accessorKey: 'propertyAddress',
    header: 'Property',
    cell: ({ row }) =>
      h('div', { class: 'max-w-xs truncate' }, row.getValue('propertyAddress')),
  },
  {
    accessorKey: 'dueDate',
    header: 'Due Date',
    cell: ({ row }) =>
      h('div', { class: 'flex items-center gap-2' }, [
        h(Calendar, { class: 'h-4 w-4 text-gray-400' }),
        new Date(row.getValue('dueDate')).toLocaleDateString(),
      ]),
  },
  {
    accessorKey: 'amount',
    header: 'Amount',
    cell: ({ row }) =>
      h(
        'div',
        { class: 'font-medium' },
        `$${(row.getValue('amount') as number).toLocaleString()}`,
      ),
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(
        Badge,
        { variant: getStatusVariant(row.getValue('status')) as any },
        () => row.getValue('status'),
      ),
  },
  {
    accessorKey: 'depositStatus',
    header: 'Deposit',
    cell: ({ row }) =>
      h(
        Badge,
        { variant: getDepositVariant(row.getValue('depositStatus')) as any },
        () => row.getValue('depositStatus'),
      ),
  },
  {
    id: 'actions',
    header: 'Actions',
    cell: ({ row }) =>
      h('div', { class: 'flex gap-2' }, [
        h(
          Button,
          {
            size: 'sm',
            variant: 'outline',
            onClick: () => openDepositDialog(row.original),
          },
          () => 'Review Deposit',
        ),
      ]),
  },
];

// Table instance
const table = useVueTable({
  get data() {
    return filteredData.value;
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  initialState: {
    pagination: {
      pageSize: 10,
    },
  },
});
</script>

<template>
  <Head title="Boundary Contracts" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <!-- Header -->
      <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold">Boundary Contracts</h1>
        <p class="text-gray-600">
          Monitor due payments, set schedules, and approve boundary deposits
        </p>
      </div>

      <!-- Filters and Search -->
      <div class="mb-6 flex flex-col gap-4 md:flex-row">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400"
          />
          <Input
            v-model="globalFilter"
            placeholder="Search contracts..."
            class="pl-10"
          />
        </div>

        <Select v-model="statusFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="paid">Paid</SelectItem>
            <SelectItem value="overdue">Overdue</SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="depositFilter">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Deposit status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Deposits</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="flagged">Flagged</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow
              v-for="headerGroup in table.getHeaderGroups()"
              :key="headerGroup.id"
            >
              <TableHead v-for="header in headerGroup.headers" :key="header.id">
                <div
                  v-if="!header.isPlaceholder"
                  @click="header.column.getToggleSortingHandler()?.($event)"
                  :class="
                    header.column.getCanSort()
                      ? 'flex cursor-pointer items-center gap-2 select-none'
                      : ''
                  "
                >
                  <FlexRender
                    :render="header.column.columnDef.header"
                    :props="header.getContext()"
                  />
                  <ArrowUpDown
                    v-if="header.column.getCanSort()"
                    class="h-4 w-4"
                  />
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="table.getRowModel().rows?.length === 0">
              <TableCell :colspan="columns.length" class="h-24 text-center">
                No results found.
              </TableCell>
            </TableRow>
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              :data-state="row.getIsSelected() ? 'selected' : undefined"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender
                  :render="cell.column.columnDef.cell"
                  :props="cell.getContext()"
                />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing
          {{
            table.getState().pagination.pageIndex *
              table.getState().pagination.pageSize +
            1
          }}
          to
          {{
            Math.min(
              (table.getState().pagination.pageIndex + 1) *
                table.getState().pagination.pageSize,
              filteredData.length,
            )
          }}
          of {{ filteredData.length }} results
        </div>
        <div class="flex gap-2">
          <Button
            variant="outline"
            size="sm"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
          >
            Previous
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
          >
            Next
          </Button>
        </div>
      </div>

      <!-- Deposit Action Dialog -->
      <Dialog v-model:open="showDepositDialog">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Review Deposit</DialogTitle>
            <DialogDescription>
              Contract: {{ selectedContract?.contractNumber }}
            </DialogDescription>
          </DialogHeader>
          <div v-if="selectedContract" class="py-4">
            <div class="space-y-2">
              <p><strong>Client:</strong> {{ selectedContract.client }}</p>
              <p>
                <strong>Property:</strong>
                {{ selectedContract.propertyAddress }}
              </p>
              <p>
                <strong>Deposit Amount:</strong> ${{
                  selectedContract.depositAmount.toLocaleString()
                }}
              </p>
              <p>
                <strong>Current Status:</strong>
                <Badge
                  :variant="
                    getDepositVariant(selectedContract.depositStatus) as any
                  "
                >
                  {{ selectedContract.depositStatus }}
                </Badge>
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" @click="showDepositDialog = false"
              >Cancel</Button
            >
            <Button
              variant="destructive"
              @click="handleDepositAction('flagged')"
            >
              <X class="mr-2 h-4 w-4" />
              Flag
            </Button>
            <Button @click="handleDepositAction('approved')">
              <Check class="mr-2 h-4 w-4" />
              Approve
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
