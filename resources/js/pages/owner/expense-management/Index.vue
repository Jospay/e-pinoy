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
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// -------------------------
// Interfaces
// -------------------------
interface Expense {
  id: number;
  invoice_no: string;
  amount: number;
  currency: string;
  payment_date: string | null;
  notes: string | null;
  franchise: string | null;
}

interface ExpensesPaginator<T = any> {
  current_page: number;
  data: T[];
  first_page_url: string | null;
  from: number | null;
  last_page: number;
  last_page_url: string | null;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

interface Props {
  expenses: ExpensesPaginator;
  expenseByPaymentOption: { name: string; total: number }[]; // Keeping name for compatibility
  expenseTrendData: { year: number; expense: number }[];
}

// -------------------------
// Props and State
// -------------------------
const props = defineProps<Props>();
const paginator = ref(props.expenses);

// ─────────────────────────────
// Breadcrumbs
// ─────────────────────────────
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Expense Management', href: owner.expenseManagement().url },
];

const filters = ref({
  search: '',
  timePeriod: 'all',
});

// Reset search when switching timePeriod
watch(
  () => filters.value.timePeriod,
  (newPeriod) => {
    if (newPeriod !== 'all') {
      filters.value.search = '';
    }
    applyFilters();
  },
);

// -------------------------
// Computed: Filtered Data
// -------------------------
const isGrouped = computed(() => {
  return ['daily', 'weekly', 'monthly', 'yearly'].includes(
    filters.value.timePeriod,
  );
});

const filteredData = computed(() => {
  return paginator.value.data;
});

const paginationLinks = computed(() => {
  return paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  );
});

const goToPage = (pageUrl: string | null) => {
  if (pageUrl) applyFilters(pageUrl);
};

const applyFilters = (url?: string) => {
  router.get(
    url || paginator.value.path,
    {
      search: filters.value.search || undefined,
      timePeriod: filters.value.timePeriod,
      per_page: paginator.value.per_page,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

// -------------------------
// Watchers
// -------------------------
let searchTimeout: any;
watch(
  () => filters.value.search,
  () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      applyFilters();
    }, 300);
  },
);

watch(
  () => props.expenses,
  (newExpenses) => {
    paginator.value = newExpenses;
  },
  { deep: true },
);

/**
 * Helper to display correct date label for grouped rows
 */
const getGroupedLabel = (row: any) => {
  if (filters.value.timePeriod === 'daily') return row.payment_date;
  if (filters.value.timePeriod === 'weekly')
    return `${row.week_start} to ${row.week_end}`;
  if (filters.value.timePeriod === 'monthly') return row.month_name;
  if (filters.value.timePeriod === 'yearly') return row.year;
  return '—';
};
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
                <TableHead>Franchise</TableHead>
                <TableHead>Notes</TableHead>
              </TableRow>
            </template>

            <template v-else>
              <TableRow>
                <TableHead>
                  {{
                    filters.timePeriod === 'daily'
                      ? 'Date'
                      : filters.timePeriod === 'weekly'
                        ? 'Week Range'
                        : filters.timePeriod === 'monthly'
                          ? 'Month'
                          : 'Year'
                  }}
                </TableHead>
                <TableHead>Total Expense</TableHead>
              </TableRow>
            </template>
          </TableHeader>

          <TableBody>
            <TableRow v-if="filteredData.length === 0">
              <TableCell
                :colspan="isGrouped ? 2 : 5"
                class="py-10 text-center text-gray-500"
              >
                No results found.
              </TableCell>
            </TableRow>

            <template v-if="!isGrouped">
              <TableRow
                v-for="expense in filteredData"
                :key="'exp-' + expense.id"
                class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
              >
                <TableCell class="font-medium">{{
                  expense.invoice_no
                }}</TableCell>
                <TableCell>
                  {{ expense.currency }}
                  {{
                    (expense.amount || 0).toLocaleString(undefined, {
                      minimumFractionDigits: 2,
                    })
                  }}
                </TableCell>
                <TableCell>{{ expense.payment_date || '—' }}</TableCell>
                <TableCell>{{ expense.franchise || '—' }}</TableCell>
                <TableCell
                  class="max-w-[250px] truncate text-xs text-muted-foreground"
                >
                  {{ expense.notes || '—' }}
                </TableCell>
              </TableRow>
            </template>

            <template v-else>
              <TableRow v-for="(row, idx) in filteredData" :key="'grp-' + idx">
                <TableCell class="font-medium">{{
                  getGroupedLabel(row)
                }}</TableCell>
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
        <span class="text-sm text-gray-600">
          Showing {{ paginator.from || 0 }} to {{ paginator.to || 0 }} of
          {{ paginator.total }} entries
        </span>

        <Pagination
          v-if="paginator.total > paginator.per_page"
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

            <template v-for="(link, index) in paginationLinks" :key="index">
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
