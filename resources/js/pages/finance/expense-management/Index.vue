<script setup lang="ts">
import DonutChart from '@/components/finance/charts/expense-management/ExpenseBreakDownDonutChart.vue';
import ExpenseTrendSparkLine from '@/components/finance/charts/expense-management/ExpenseTrendSparkLine.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { ArrowUpDown, FileDown } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

// ─────────────────────────────
// Type Definition
// ─────────────────────────────
interface ExpenseRecord {
  id: string;
  category: string;
  amount: number;
  date: string;
  status: 'Pending' | 'Approved' | 'Rejected';
}

// ─────────────────────────────
// Breadcrumbs
// ─────────────────────────────
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Expense Management',
    href: finance.expenseManagement().url,
  },
];

// ─────────────────────────────
// Sample Data
// ─────────────────────────────
const data = ref<ExpenseRecord[]>([
  {
    id: 'E-004',
    category: 'Maintenance',
    amount: 750,
    date: '2025-11-04',
    status: 'Pending',
  },
  {
    id: 'E-003',
    category: 'Utilities',
    amount: 1100,
    date: '2025-11-03',
    status: 'Rejected',
  },
  {
    id: 'E-002',
    category: 'Salaries',
    amount: 3200,
    date: '2025-11-02',
    status: 'Approved',
  },
  {
    id: 'E-001',
    category: 'Maintenance',
    amount: 500,
    date: '2025-11-01',
    status: 'Pending',
  },
]);

// ─────────────────────────────
// Filters + Pagination
// ─────────────────────────────
const globalFilter = ref('');
const pageSize = ref(10);

const filteredData = computed(() => {
  const search = globalFilter.value.toLowerCase();
  return data.value.filter(
    (item) =>
      item.id.toLowerCase().includes(search) ||
      item.category.toLowerCase().includes(search) ||
      item.status.toLowerCase().includes(search),
  );
});

// ─────────────────────────────
// Helpers
// ─────────────────────────────
const getStatusClass = (status: string) => {
  switch (status) {
    case 'Approved':
      return 'bg-green-600 hover:bg-green-700';
    case 'Pending':
      return 'bg-yellow-500 hover:bg-yellow-600';
    case 'Rejected':
      return 'bg-red-600 hover:bg-red-700';
    default:
      return '';
  }
};

const exportPDF = () => {
  console.log('Exporting PDF...');
  // Add PDF export logic (jsPDF / pdfmake)
};

const handleApprove = (record: ExpenseRecord) => {
  const i = data.value.findIndex((e) => e.id === record.id);
  if (i !== -1) data.value[i].status = 'Approved';
};

const handleReject = (record: ExpenseRecord) => {
  const i = data.value.findIndex((e) => e.id === record.id);
  if (i !== -1) data.value[i].status = 'Rejected';
};

// ─────────────────────────────
// Table Columns
// ─────────────────────────────
const columns: ColumnDef<ExpenseRecord>[] = [
  { accessorKey: 'id', header: 'ID' },
  { accessorKey: 'category', header: 'Category' },
  {
    accessorKey: 'amount',
    header: 'Amount',
    cell: ({ row }) =>
      `$${(row.getValue('amount') as number).toLocaleString()}`,
  },
  { accessorKey: 'date', header: 'Date' },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(
        Badge,
        {
          class: `${getStatusClass(row.getValue('status'))} text-white capitalize`,
        },
        () => row.getValue('status'),
      ),
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({ row }) => {
      const record = row.original;
      return h('div', { class: 'flex gap-2' }, [
        record.status === 'Pending'
          ? [
              h(
                Button,
                {
                  size: 'sm',
                  class: 'bg-green-600 hover:bg-green-700 text-white',
                  onClick: () => handleApprove(record),
                },
                () => 'Approve',
              ),
              h(
                Button,
                {
                  size: 'sm',
                  class: 'bg-red-600 hover:bg-red-700 text-white',
                  onClick: () => handleReject(record),
                },
                () => 'Reject',
              ),
              h(
                Button,
                {
                  size: 'sm',
                  class: 'bg-cyan-500 hover:bg-cyan-600 text-white',
                },
                () => 'Upload Receipt',
              ),
            ]
          : record.status === 'Rejected'
            ? h(
                Button,
                {
                  size: 'sm',
                  class: 'bg-cyan-500 hover:bg-cyan-600 text-white',
                },
                () => 'Upload Receipt',
              )
            : h(
                Button,
                { size: 'sm', variant: 'secondary', disabled: true },
                () => 'View',
              ),
      ]);
    },
  },
];

// ─────────────────────────────
// Table Instance
// ─────────────────────────────
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

const expenseBreakdownData = [
  { name: 'Jan', total: Math.floor(Math.random() * 2000) + 500 },
  { name: 'Feb', total: Math.floor(Math.random() * 2000) + 500 },
  { name: 'Mar', total: Math.floor(Math.random() * 2000) + 500 },
  { name: 'Apr', total: Math.floor(Math.random() * 2000) + 500 },
  { name: 'May', total: Math.floor(Math.random() * 2000) + 500 },
  { name: 'Jun', total: Math.floor(Math.random() * 2000) + 500 },
];

const expenseTrendData = [
  { year: 2000, 'Growth Rate': 2.07 },
  { year: 2001, 'Growth Rate': 2.08 },
  { year: 2002, 'Growth Rate': 2.1 },
  { year: 2003, 'Growth Rate': 2.15 },
  { year: 2004, 'Growth Rate': 2.21 },
  { year: 2005, 'Growth Rate': 2.23 },
  { year: 2006, 'Growth Rate': 2.29 },
  { year: 2007, 'Growth Rate': 2.34 },
  { year: 2008, 'Growth Rate': 2.36 },
  { year: 2009, 'Growth Rate': 2.36 },
  { year: 2010, 'Growth Rate': 2.35 },
  { year: 2011, 'Growth Rate': 2.34 },
  { year: 2012, 'Growth Rate': 2.39 },
  { year: 2013, 'Growth Rate': 2.3 },
  { year: 2014, 'Growth Rate': 2.35 },
  { year: 2015, 'Growth Rate': 2.39 },
  { year: 2016, 'Growth Rate': 2.41 },
  { year: 2017, 'Growth Rate': 2.44 },
  { year: 2018, 'Growth Rate': 2.45 },
  { year: 2019, 'Growth Rate': 2.47 },
  { year: 2020, 'Growth Rate': 2.48 },
  { year: 2021, 'Growth Rate': 2.51 },
  { year: 2022, 'Growth Rate': 2.55 },
  { year: 2023, 'Growth Rate': 2.58 },
  { year: 2024, 'Growth Rate': 2.6 },
  { year: 2025, 'Growth Rate': 2.63 },
];
</script>

<template>
  <Head title="Expense Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl bg-white p-4 shadow"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <h1 class="text-xl font-semibold">Expense Records</h1>
        <Button @click="exportPDF" class="bg-red-600 hover:bg-red-700">
          <FileDown class="mr-2 h-4 w-4" />
          Export PDF
        </Button>
      </div>

      <!-- Controls -->
      <div class="flex items-center justify-between border-b pb-4">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Show</span>
          <Select v-model="pageSize">
            <SelectTrigger class="w-20">
              <SelectValue placeholder="10" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="10">10</SelectItem>
              <SelectItem value="25">25</SelectItem>
              <SelectItem value="50">50</SelectItem>
            </SelectContent>
          </Select>
          <span class="text-sm text-gray-600">entries</span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Search:</span>
          <Input v-model="globalFilter" placeholder="" class="w-48" />
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
              v-for="(row, index) in table.getRowModel().rows"
              :key="row.id"
              :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
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

      <!-- Charts Section -->
      <div class="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Expense Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <DonutChart
              :data="expenseBreakdownData"
              category="total"
              title="Monthly Revenue"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Expense Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <ExpenseTrendSparkLine
              :data="expenseTrendData"
              :colors="['#3b82f6']"
              :y-formatter="(val) => `$ ${val.toFixed(2)}`"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
