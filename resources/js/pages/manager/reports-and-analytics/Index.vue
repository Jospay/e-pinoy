<script setup lang="ts">
import NetProfitTrendSparkLine from '@/components/manager/charts/reports-and-analytics/NetProfitTrendSparkLine.vue';
import RevenueVsExpensesBarChart from '@/components/manager/charts/reports-and-analytics/RevenueVsExpensesBarChart.vue';
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
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
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
import { ArrowUpDown, Eye, FileDown } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

interface FinancialReport {
  id: number;
  reportId: string;
  type: 'Daily' | 'Weekly' | 'Monthly';
  dateRange: string;
  totalRevenue: number;
  totalExpenses: number;
  netProfit: number;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Reports & Anaylytics',
    href: manager.reportsAndAnalytics().url,
  },
];

// Sample data
const data = ref<FinancialReport[]>([
  {
    id: 1,
    reportId: 'FR-003',
    type: 'Monthly',
    dateRange: 'October 2025',
    totalRevenue: 120000,
    totalExpenses: 98000,
    netProfit: 22000,
  },
  {
    id: 2,
    reportId: 'FR-001',
    type: 'Daily',
    dateRange: '2025-11-01',
    totalRevenue: 5200,
    totalExpenses: 3100,
    netProfit: 2100,
  },
  {
    id: 3,
    reportId: 'FR-002',
    type: 'Weekly',
    dateRange: '2025-10-25 → 2025-10-31',
    totalRevenue: 32000,
    totalExpenses: 21500,
    netProfit: 10500,
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
        item.reportId.toLowerCase().includes(search) ||
        item.type.toLowerCase().includes(search) ||
        item.dateRange.toLowerCase().includes(search),
    );
  }

  return filtered;
});

// Helper functions
const getNetProfitVariant = (profit: number) => {
  if (profit >= 20000) return 'default';
  if (profit >= 10000) return 'secondary';
  return 'success';
};

const handleViewReport = (report: FinancialReport) => {
  console.log('View report:', report);
  // Navigate to report detail page
};

const handleExportPDF = (report: FinancialReport) => {
  console.log('Export PDF:', report);
  // Export report as PDF
};

const generateReport = (type: 'daily' | 'weekly' | 'monthly') => {
  console.log('Generate report:', type);
  // Generate new report
};

// Table columns
const columns: ColumnDef<FinancialReport>[] = [
  {
    accessorKey: 'reportId',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Report ID'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
    cell: ({ row }) =>
      h('div', { class: 'font-medium' }, row.getValue('reportId')),
  },
  {
    accessorKey: 'type',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Type'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
  },
  {
    accessorKey: 'dateRange',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Date Range'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
  },
  {
    accessorKey: 'totalRevenue',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Total Revenue'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
    cell: ({ row }) =>
      h(
        'div',
        { class: 'font-medium' },
        `$${(row.getValue('totalRevenue') as number).toLocaleString()}`,
      ),
  },
  {
    accessorKey: 'totalExpenses',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Total Expenses'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
    cell: ({ row }) =>
      h(
        'div',
        { class: 'font-medium' },
        `$${(row.getValue('totalExpenses') as number).toLocaleString()}`,
      ),
  },
  {
    accessorKey: 'netProfit',
    header: ({ column }) => {
      return h(
        'div',
        {
          class: 'flex items-center gap-2 cursor-pointer select-none',
          onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
        },
        [h('span', 'Net'), h(ArrowUpDown, { class: 'h-4 w-4' })],
      );
    },
    cell: ({ row }) => {
      const profit = row.getValue('netProfit') as number;
      return h(
        Badge,
        {
          variant: getNetProfitVariant(profit) as any,
          class: 'font-semibold',
        },
        () => `+$${profit.toLocaleString()}`,
      );
    },
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({ row }) =>
      h('div', { class: 'flex gap-2' }, [
        h(
          Button,
          {
            size: 'sm',
            onClick: () => handleViewReport(row.original),
          },
          () => [h(Eye, { class: 'h-4 w-4 mr-1' }), 'View'],
        ),
        h(
          Button,
          {
            size: 'sm',
            variant: 'destructive',
            onClick: () => handleExportPDF(row.original),
          },
          () => [h(FileDown, { class: 'h-4 w-4 mr-1' }), 'Export PDF'],
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
  state: {
    get pagination() {
      return {
        pageSize: pageSize.value,
        pageIndex: 0,
      };
    },
  },
});

const revenueExpensesData = [
  { name: 'Jan', expenses: 1200, revenue: 1800 },
  { name: 'Feb', expenses: 1500, revenue: 1900 },
  { name: 'Mar', expenses: 1700, revenue: 2000 },
  { name: 'Apr', expenses: 1300, revenue: 1600 },
  { name: 'May', expenses: 1400, revenue: 1700 },
  { name: 'Jun', expenses: 1800, revenue: 2100 },
  { name: 'Jul', expenses: 1600, revenue: 2200 },
];

const netProfitData = [
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
  <Head title="Report Analytics" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Report Analytics</h1>
          <p class="mt-1 text-sm text-muted-foreground">
            Generated Financial Reports
          </p>
        </div>
        <div class="flex gap-2">
          <Button @click="generateReport('daily')"> Generate Daily </Button>
          <Button
            @click="generateReport('weekly')"
            class="bg-cyan-500 hover:bg-cyan-600"
          >
            Generate Weekly
          </Button>
          <Button
            @click="generateReport('monthly')"
            class="bg-green-600 hover:bg-green-700"
          >
            Generate Monthly
          </Button>
        </div>
      </div>

      <!-- Table Section -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm">Show</span>
              <Select v-model="pageSize">
                <SelectTrigger class="w-20">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="10">10</SelectItem>
                  <SelectItem :value="25">25</SelectItem>
                  <SelectItem :value="50">50</SelectItem>
                  <SelectItem :value="100">100</SelectItem>
                </SelectContent>
              </Select>
              <span class="text-sm">entries</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm">Search:</span>
              <Input
                v-model="globalFilter"
                placeholder="Search reports..."
                class="w-64"
              />
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="rounded-lg border">
            <Table>
              <TableHeader>
                <TableRow
                  v-for="headerGroup in table.getHeaderGroups()"
                  :key="headerGroup.id"
                >
                  <TableHead
                    v-for="header in headerGroup.headers"
                    :key="header.id"
                  >
                    <FlexRender
                      v-if="!header.isPlaceholder"
                      :render="header.column.columnDef.header"
                      :props="header.getContext()"
                    />
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
                  class="hover:bg-muted/50"
                >
                  <TableCell
                    v-for="cell in row.getVisibleCells()"
                    :key="cell.id"
                  >
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
            <div class="text-sm text-muted-foreground">
              Showing {{ table.getState().pagination.pageIndex * pageSize + 1 }}
              to
              {{
                Math.min(
                  (table.getState().pagination.pageIndex + 1) * pageSize,
                  filteredData.length,
                )
              }}
              of {{ filteredData.length }} entries
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
              <Button variant="default" size="sm" disabled class="min-w-10">
                {{ table.getState().pagination.pageIndex + 1 }}
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
        </CardContent>
      </Card>

      <!-- Charts Section -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Revenue vs Expenses Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Revenue vs Expenses</CardTitle>
          </CardHeader>
          <CardContent>
            <RevenueVsExpensesBarChart
              :data="revenueExpensesData"
              :colors="['#ef4444', '#22c55e']"
              :categories="['expenses', 'revenue']"
              :y-formatter="(val) => `$ ${val.toLocaleString()}`"
            />
          </CardContent>
        </Card>

        <!-- Net Profit Trend Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Net Profit Trend</CardTitle>
          </CardHeader>
          <CardContent>
            <NetProfitTrendSparkLine
              :data="netProfitData"
              :colors="['#3b82f6']"
              :y-formatter="(val) => `$ ${val.toFixed(2)}`"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
