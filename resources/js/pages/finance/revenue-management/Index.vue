<script setup lang="ts">
import RevenueBreakDownPieChart from '@/components/finance/charts/revenue-management/RevenueBreakDownPieChart.vue';
import RevenueTrendSparkLine from '@/components/finance/charts/revenue-management/RevenueTrendSparkLine.vue';

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

interface RevenueRecord {
  id: string;
  source: string;
  amount: number;
  date: string;
  status: 'Flagged' | 'Received' | 'Pending';
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Revenue Management',
    href: finance.revenueManagement().url,
  },
];

// Sample data
const data = ref<RevenueRecord[]>([
  {
    id: 'R-004',
    source: 'Trip',
    amount: 1500,
    date: '2025-11-04',
    status: 'Flagged',
  },
  {
    id: 'R-003',
    source: 'Logistics',
    amount: 2800,
    date: '2025-11-03',
    status: 'Received',
  },
  {
    id: 'R-002',
    source: 'Ads',
    amount: 3500,
    date: '2025-11-02',
    status: 'Pending',
  },
  {
    id: 'R-001',
    source: 'Trip',
    amount: 1200,
    date: '2025-11-01',
    status: 'Received',
  },
]);

const globalFilter = ref('');
const pageSize = ref(10);

// Filtered data
const filteredData = computed(() => {
  let filtered = data.value;

  if (globalFilter.value) {
    const search = globalFilter.value.toLowerCase();
    filtered = filtered.filter(
      (item) =>
        item.id.toLowerCase().includes(search) ||
        item.source.toLowerCase().includes(search) ||
        item.status.toLowerCase().includes(search) ||
        item.date.includes(search),
    );
  }

  return filtered;
});

// Helper functions
const getStatusVariant = (status: string) => {
  switch (status) {
    case 'Flagged':
      return 'destructive';
    case 'Received':
      return 'success';
    case 'Pending':
      return 'secondary';
    default:
      return 'secondary';
  }
};

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Flagged':
      return 'bg-red-500 hover:bg-red-600';
    case 'Received':
      return 'bg-green-600 hover:bg-green-700';
    case 'Pending':
      return 'bg-amber-500 hover:bg-amber-600';
    default:
      return '';
  }
};

const exportPDF = () => {
  console.log('Exporting PDF...');
  // Add your PDF export logic here
};

const handleFlag = (record: RevenueRecord) => {
  const index = data.value.findIndex((item) => item.id === record.id);
  if (index !== -1) {
    data.value[index].status = 'Flagged';
  }
};

// Table columns
const columns: ColumnDef<RevenueRecord>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')),
  },
  {
    accessorKey: 'source',
    header: 'Source',
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
    accessorKey: 'date',
    header: 'Date',
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(
        Badge,
        {
          variant: getStatusVariant(row.getValue('status')) as any,
          class: getStatusClass(row.getValue('status')),
        },
        () => row.getValue('status'),
      ),
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({ row }) => {
      const status = row.original.status;
      const record = row.original;

      return h('div', { class: 'flex gap-2' }, [
        h(
          Button,
          {
            size: 'sm',
            variant: status === 'Flagged' ? 'destructive' : 'default',
          },
          () => (status === 'Flagged' ? 'Review' : 'View'),
        ),
        record.id === 'R-001'
          ? h(
              Button,
              {
                size: 'sm',
                variant: 'destructive',
                onClick: () => handleFlag(record),
              },
              () => 'Flag',
            )
          : null,
      ]);
    },
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

const expenseBreakdownData = [
  {
    name: 'Trip',
    total: Math.floor(Math.random() * 2000) + 500,
  },
  {
    name: 'Ads',
    total: Math.floor(Math.random() * 2000) + 500,
  },
  {
    name: 'Logistics',
    total: Math.floor(Math.random() * 2000) + 500,
  },
];

const expenseTrendData = [
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
  <Head title="Revenue Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl bg-white p-4 shadow"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b pb-4">
        <h1 class="text-xl font-semibold">Revenue Records</h1>
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
        <!-- Revenue vs Expenses Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Revenue Breakdown</CardTitle>
          </CardHeader>
          <CardContent>
            <!-- <RevenueBreakDownPieChart /> -->
            <RevenueBreakDownPieChart
              :data="expenseBreakdownData"
              category="total"
              title="Monthly Revenue"
            />
          </CardContent>
        </Card>

        <!-- Net Profit Trend Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Net Profit Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <RevenueTrendSparkLine
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
