<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea/';
import AppLayout from '@/layouts/AppLayout.vue';
import finance from '@/routes/finance';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  useVueTable,
  type ColumnDef,
} from '@tanstack/vue-table';
import { ArrowUpDown, MessageSquarePlus } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

// 🧭 Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Support Center',
    href: finance.supportCenter().url,
  },
];

// 🎫 Ticket Type
interface SupportTicket {
  id: string;
  type: 'Payment Dispute' | 'Adjustment Request';
  description: string;
  date: string;
  status: 'Open' | 'In Progress' | 'Resolved';
}

// 🔹 Data
const tickets = ref<SupportTicket[]>([
  {
    id: 'T-004',
    type: 'Payment Dispute',
    description: 'Discrepancy in last payment of $2,500',
    date: '2025-11-04',
    status: 'In Progress',
  },
  {
    id: 'T-003',
    type: 'Adjustment Request',
    description: 'Need correction for trip revenue entry R-002',
    date: '2025-11-03',
    status: 'Resolved',
  },
  {
    id: 'T-002',
    type: 'Payment Dispute',
    description: 'Incorrect tax applied on payment R-001',
    date: '2025-11-02',
    status: 'Open',
  },
  {
    id: 'T-001',
    type: 'Adjustment Request',
    description: 'Manual adjustment needed for logistics revenue',
    date: '2025-11-01',
    status: 'Resolved',
  },
]);

// 🔍 Search + Pagination
const globalFilter = ref('');
const filteredTickets = computed(() => {
  if (!globalFilter.value) return tickets.value;
  const q = globalFilter.value.toLowerCase();
  return tickets.value.filter(
    (t) =>
      t.id.toLowerCase().includes(q) ||
      t.type.toLowerCase().includes(q) ||
      t.status.toLowerCase().includes(q) ||
      t.description.toLowerCase().includes(q),
  );
});

// 🎨 Helpers
const getStatusClass = (status: string) => {
  switch (status) {
    case 'Open':
      return 'bg-amber-500 hover:bg-amber-600';
    case 'In Progress':
      return 'bg-blue-500 hover:bg-blue-600';
    case 'Resolved':
      return 'bg-green-600 hover:bg-green-700';
    default:
      return 'bg-gray-400';
  }
};

// 🧾 Raise new ticket
const newTicket = ref({
  type: 'Payment Dispute',
  description: '',
});

const addTicket = () => {
  const id = `T-${String(tickets.value.length + 1).padStart(3, '0')}`;
  tickets.value.unshift({
    id,
    type: newTicket.value.type as 'Payment Dispute' | 'Adjustment Request',
    description: newTicket.value.description,
    date: new Date().toISOString().slice(0, 10),
    status: 'Open',
  });
  newTicket.value.description = '';
  openDialog.value = false;
};

const openDialog = ref(false);

// 🧩 Table Columns
const columns: ColumnDef<SupportTicket>[] = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'type', header: 'Type' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'date', header: 'Date' },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(Badge, { class: getStatusClass(row.getValue('status')) }, () =>
        row.getValue('status'),
      ),
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({}) =>
      h('div', { class: 'flex gap-2' }, [
        h(
          Button,
          {
            size: 'sm',
            variant: 'outline',
          },
          () => 'View',
        ),
      ]),
  },
];

// 🧠 Table instance
const table = useVueTable({
  get data() {
    return filteredTickets.value;
  },
  columns,
  getCoreRowModel: getCoreRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
});
</script>

<template>
  <Head title="Support Center" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl bg-white p-4 shadow">
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <h1 class="text-xl font-semibold">Support Center</h1>

        <Dialog v-model:open="openDialog">
          <DialogTrigger as-child>
            <Button class="bg-brand-blue">
              <MessageSquarePlus class="mr-2 h-4 w-4" />
              Raise Ticket
            </Button>
          </DialogTrigger>
          <DialogContent class="max-w-lg">
            <DialogHeader>
              <DialogTitle>Raise a New Support Ticket</DialogTitle>
            </DialogHeader>

            <div class="space-y-4">
              <label class="block text-sm font-medium">Type</label>
              <select
                v-model="newTicket.type"
                class="w-full rounded-md border border-gray-300 p-2 text-sm"
              >
                <option>Payment Dispute</option>
                <option>Adjustment Request</option>
              </select>

              <label class="block text-sm font-medium">Description</label>
              <Textarea
                v-model="newTicket.description"
                placeholder="Describe your issue..."
              />

              <Button @click="addTicket" class="w-full bg-brand-blue">
                Submit Ticket
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      <!-- Controls -->
      <div class="flex items-center justify-between border-b pb-4">
        <div class="text-sm text-gray-600">
          Manage disputes and adjustment requests
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Search:</span>
          <Input
            v-model="globalFilter"
            class="w-48"
            placeholder="Search tickets..."
          />
        </div>
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
                  class="flex cursor-pointer items-center gap-2 select-none"
                  @click="header.column.getToggleSortingHandler()?.($event)"
                >
                  <FlexRender
                    :render="header.column.columnDef.header"
                    :props="header.getContext()"
                  />
                  <ArrowUpDown class="h-4 w-4" />
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-if="table.getRowModel().rows?.length === 0"
              class="text-center"
            >
              <TableCell :colspan="columns.length">No tickets found.</TableCell>
            </TableRow>
            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              class="even:bg-gray-50"
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
    </div>
  </AppLayout>
</template>
