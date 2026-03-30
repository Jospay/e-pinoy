<script setup lang="ts">
import ExpenseTrendSparkLine from '@/components/owner/charts/expense-management/ExpenseTrendSparkLine.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Branch {
  id: number;
  name: string;
}

interface Expense {
  id: number;
  invoice_no: string;
  amount: number;
  currency: string;
  created_at: string | null;
  notes: string | null;
  branch_id: number | null;
  branch_name: string;
}

interface ExpensesPaginator {
  current_page: number;
  data: any[];
  from: number | null;
  last_page: number;
  links: any[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

interface Props {
  expenses: ExpensesPaginator;
  branches: Branch[];
  expenseTrendData: { year: number; expense: number }[];
}

const props = defineProps<Props>();
const paginator = ref(props.expenses);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Expense Management', href: owner.expenseManagement().url },
];

const filters = ref({
  search: '',
  timePeriod: 'all',
  branch: 'all',
});

const isGrouped = computed(() =>
  ['daily', 'weekly', 'monthly', 'yearly'].includes(filters.value.timePeriod),
);

const applyFilters = (url?: string) => {
  router.get(
    url || paginator.value.path,
    {
      search: filters.value.search || undefined,
      timePeriod: filters.value.timePeriod,
      branch: filters.value.branch !== 'all' ? filters.value.branch : undefined,
      per_page: paginator.value.per_page,
    },
    { preserveState: true, preserveScroll: true },
  );
};

// Watchers
watch([() => filters.value.timePeriod, () => filters.value.branch], () =>
  applyFilters(),
);

let searchTimeout: any;
watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
  },
);

watch(
  () => props.expenses,
  (newVal) => (paginator.value = newVal),
  { deep: true },
);

const getGroupedLabel = (row: any) => {
  if (filters.value.timePeriod === 'daily') return row.payment_date;
  if (filters.value.timePeriod === 'weekly')
    return `${row.week_start} to ${row.week_end}`;
  if (filters.value.timePeriod === 'monthly') return row.month_name;
  if (filters.value.timePeriod === 'yearly') return row.year;
  return '—';
};

const paginationLinks = computed(() =>
  paginator.value.links.filter(
    (l) => l.label !== 'Previous' && l.label !== 'Next',
  ),
);
const goToPage = (url: string | null) => url && applyFilters(url);
</script>

<template>
  <Head title="Expense Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="mb-1 text-3xl font-bold">Expense Records</h1>
        <p class="text-gray-600">
          Monitor Daily, Weekly, Monthly, and Yearly Expenses
        </p>
      </div>

      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="filters.search"
            placeholder="Search invoice or notes..."
            class="w-full rounded-md border px-10 py-2 focus:ring-2 focus:ring-primary focus:outline-none"
            :disabled="isGrouped"
          />
        </div>

        <Select v-model="filters.branch">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Select Assignment" />
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
            <SelectGroup v-if="branches.length > 0">
              <SelectLabel
                class="px-2 py-1.5 text-xs font-semibold text-muted-foreground uppercase"
                >Branches</SelectLabel
              >
              <SelectItem v-if="branches.length > 1" value="only_branches"
                >All Branches</SelectItem
              >
              <SelectItem
                v-for="b in branches"
                :key="b.id"
                :value="b.id.toString()"
                >{{ b.name }}</SelectItem
              >
            </SelectGroup>
          </SelectContent>
        </Select>

        <Select v-model="filters.timePeriod">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue placeholder="Select Period" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Time</SelectItem>
            <SelectItem value="daily">Daily</SelectItem>
            <SelectItem value="weekly">Weekly</SelectItem>
            <SelectItem value="monthly">Monthly</SelectItem>
            <SelectItem value="yearly">Yearly</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div class="rounded-lg border bg-white dark:bg-slate-900">
        <Table>
          <TableHeader>
            <template v-if="!isGrouped">
              <TableRow>
                <TableHead>Invoice No</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Date Recorded</TableHead>
                <TableHead>Assignment</TableHead>
                <TableHead>Notes</TableHead>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableHead>{{
                  filters.timePeriod === 'daily'
                    ? 'Date'
                    : filters.timePeriod === 'weekly'
                      ? 'Week Range'
                      : filters.timePeriod === 'monthly'
                        ? 'Month'
                        : 'Year'
                }}</TableHead>
                <TableHead>Total Expense</TableHead>
              </TableRow>
            </template>
          </TableHeader>

          <TableBody>
            <TableRow v-if="paginator.data.length === 0">
              <TableCell
                :colspan="isGrouped ? 2 : 5"
                class="py-10 text-center text-gray-500"
                >No results found.</TableCell
              >
            </TableRow>

            <template v-if="!isGrouped">
              <TableRow
                v-for="exp in paginator.data"
                :key="exp.id"
                class="hover:bg-slate-50 dark:hover:bg-slate-800/50"
              >
                <TableCell class="font-medium">{{ exp.invoice_no }}</TableCell>
                <TableCell
                  >{{ exp.currency }}
                  {{
                    (exp.amount || 0).toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                    })
                  }}</TableCell
                >
                <TableCell>{{ exp.created_at || '—' }}</TableCell>
                <TableCell>
                  <div class="flex flex-col">
                    <span class="text-sm font-medium">{{
                      exp.branch_name
                    }}</span>
                    <span class="text-[10px] text-muted-foreground uppercase">{{
                      exp.branch_id ? 'Branch' : 'Franchise'
                    }}</span>
                  </div>
                </TableCell>
                <TableCell
                  class="max-w-[250px] truncate text-xs text-muted-foreground"
                  >{{ exp.notes || '—' }}</TableCell
                >
              </TableRow>
            </template>

            <template v-else>
              <TableRow
                v-for="(row, idx) in paginator.data"
                :key="'grp-' + idx"
              >
                <TableCell class="font-medium">
                  {{ row.display_label }}
                </TableCell>
                <TableCell class="font-bold text-primary">
                  ₱
                  {{
                    (row.total || 0).toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                    })
                  }}
                </TableCell>
              </TableRow>
            </template>
          </TableBody>
        </Table>
      </div>

      <div class="flex items-center justify-between pt-4">
        <span class="text-sm text-gray-600"
          >Showing {{ paginator.from }} to {{ paginator.to }} of
          {{ paginator.total }} entries</span
        >
        <Pagination
          v-if="paginator.total > paginator.per_page"
          :total="paginator.total"
          :items-per-page="paginator.per_page"
          :default-page="paginator.current_page"
        >
          <PaginationContent>
            <PaginationPrevious
              :disabled="!paginator.prev_page_url"
              @click="goToPage(paginator.prev_page_url)"
            />
            <template v-for="(link, i) in paginationLinks" :key="i">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{
                    'bg-slate-100 font-bold dark:bg-slate-800': link.active,
                  }"
                  @click="goToPage(link.url)"
                  >{{ link.label }}</Button
                >
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

      <Card>
        <CardHeader>
          <CardTitle>Expense Trend</CardTitle>
        </CardHeader>
        <CardContent>
          <ExpenseTrendSparkLine
            v-if="expenseTrendData.length > 0"
            :data="
              expenseTrendData.map((item) => ({
                year: item.year,
                value: item.expense,
              }))
            "
            label="Expenses"
            :colors="['#3b82f6']"
            :y-formatter="
              (val) =>
                `₱ ${val.toLocaleString(undefined, { minimumFractionDigits: 2 })}`
            "
          />
          <div v-else class="py-10 text-center text-muted-foreground">
            No trend data available.
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
