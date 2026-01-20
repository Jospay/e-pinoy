<script setup lang="ts">
import RevenuePaymentOptionsBreakDownPieChart from '@/components/manager/charts/revenue-management/RevenuePaymentOptionsBreakDownPieChart.vue';
import RevenueTrendSparkLine from '@/components/manager/charts/revenue-management/RevenueTrendSparkLine.vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

// Interfaces here as before ...
interface Revenue {
  id: number;
  invoice_no: string;
  amount: number;
  currency: string;
  service_type: string;
  payment_date: string | null;
  notes: string | null;
  status: string | null;
  franchise: string | null;
  branch: string | null;
  payment_option: string | null;
}

interface RevenuesPaginator<T = any> {
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
  revenues: RevenuesPaginator;
  revenueServiceTypeBreakdownData: { name: string; total: number }[];
  revenueByPaymentOption: { name: string; total: number }[];
  revenueTrendData: { year: number; revenue: number }[];
}

const {
  // revenueServiceTypeBreakdownData,
  revenueByPaymentOption,
  revenues,
  revenueTrendData,
} = defineProps<Props>();
const paginator = ref<RevenuesPaginator>(revenues);

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Revenue Management', href: manager.revenueManagement().url },
];

const filters = ref({
  search: '',
  status: 'all',
  paymentOption: 'all',
  timePeriod: 'all',
});

// Reset filters when switching timePeriod to 'all'
watch(
  () => filters.value.timePeriod,
  (newPeriod) => {
    if (newPeriod === 'all') {
      filters.value.status = 'all';
      filters.value.paymentOption = 'all';
      filters.value.search = '';
    }
  },
);

const selectedRevenue = ref<Revenue | null>(null);
const dialogOpen = ref(false);
const viewRevenue = (revenue: Revenue) => {
  selectedRevenue.value = revenue;
  dialogOpen.value = true;
};

const isGrouped = computed(() => {
  return (
    filters.value.timePeriod === 'daily' ||
    filters.value.timePeriod === 'weekly' ||
    filters.value.timePeriod === 'monthly' ||
    filters.value.timePeriod === 'yearly'
  );
});

const filteredData = computed(() => {
  if (isGrouped.value) {
    return paginator.value.data;
  }
  if (!filters.value.search) return paginator.value.data;
  const search = filters.value.search.toLowerCase();

  return paginator.value.data.filter((item) =>
    Object.values(item)
      .filter((v) => v !== null && v !== undefined)
      .some((v) => v.toString().toLowerCase().includes(search)),
  );
});

const paginationLinks = computed(() => {
  return paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  );
});

const getStatusVariant = (status: string | null) => {
  switch (status?.toLowerCase()) {
    case 'paid':
      return 'default';
    case 'overdue':
      return 'destructive';
    case 'pending':
      return 'outline';
    default:
      return 'secondary';
  }
};

const goToPage = (pageUrl: string | null) => {
  if (pageUrl) applyFilters(pageUrl);
};

const applyFilters = (url?: string) => {
  router.get(
    url || paginator.value.path,
    {
      status: filters.value.status !== 'all' ? filters.value.status : undefined,
      paymentOption:
        filters.value.paymentOption !== 'all'
          ? filters.value.paymentOption
          : undefined,
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

watch(
  filters,
  () => {
    applyFilters();
  },
  { deep: true },
);

watch(
  () => revenues,
  (newRevenues) => {
    paginator.value = newRevenues;
  },
  { deep: true },
);
</script>

<template>
  <Head title="Revenue Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div>
        <h1 class="mb-1 text-3xl font-bold">Revenue Management</h1>
        <p class="text-gray-600">
          Monitor Daily, Weekly, Monthly, and Yearly Revenue
        </p>
      </div>
      <div class="flex flex-col gap-4 md:flex-row md:items-center">
        <!-- Search/Filters/Period -->
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="filters.search"
            placeholder="Search contracts..."
            class="w-full rounded-md border px-10 py-2"
            :disabled="isGrouped"
          />
        </div>
        <!-- ... omit for brevity: filter selects (same as before) ... -->
        <Select v-model="filters.status">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue>{{
              filters.status === 'all' ? 'Filter by status' : filters.status
            }}</SelectValue>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="Pending">Pending</SelectItem>
            <SelectItem value="Paid">Paid</SelectItem>
            <SelectItem value="Overdue">Overdue</SelectItem>
          </SelectContent>
        </Select>
        <Select v-model="filters.paymentOption">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue>{{
              filters.paymentOption === 'all'
                ? 'Payment Option'
                : filters.paymentOption
            }}</SelectValue>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Payment Options</SelectItem>
            <SelectItem value="Cash">Cash</SelectItem>
            <SelectItem value="Credit Card">Credit Card</SelectItem>
            <SelectItem value="Gcash">Gcash</SelectItem>
            <SelectItem value="Paymaya">Paymaya</SelectItem>
          </SelectContent>
        </Select>
        <Select v-model="filters.timePeriod">
          <SelectTrigger class="w-full md:w-48">
            <SelectValue>
              {{
                filters.timePeriod === 'all'
                  ? 'All Time'
                  : filters.timePeriod === 'daily'
                    ? 'Daily'
                    : filters.timePeriod === 'weekly'
                      ? 'Weekly'
                      : filters.timePeriod === 'monthly'
                        ? 'Monthly'
                        : filters.timePeriod === 'yearly'
                          ? 'Yearly'
                          : 'Select Period'
              }}
            </SelectValue>
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
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <template v-if="!isGrouped">
              <TableRow>
                <TableHead>Invoice No</TableHead>
                <!-- <TableHead>Service Type</TableHead> -->
                <TableHead>Amount</TableHead>
                <TableHead>Payment Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Branch</TableHead>
                <TableHead>Payment Option</TableHead>
                <TableHead>Action</TableHead>
              </TableRow>
            </template>
            <template v-else>
              <TableRow>
                <TableHead>
                  {{
                    filters.timePeriod === 'daily'
                      ? 'Date'
                      : filters.timePeriod === 'weekly'
                        ? 'Week'
                        : filters.timePeriod === 'monthly'
                          ? 'Month'
                          : filters.timePeriod === 'yearly'
                            ? 'Year'
                            : 'Group'
                  }}
                </TableHead>
                <TableHead>Total Revenue</TableHead>
              </TableRow>
            </template>
          </TableHeader>
          <TableBody>
            <!-- No results -->
            <TableRow v-if="filteredData.length === 0">
              <TableCell
                :colspan="isGrouped ? 2 : 9"
                class="py-6 text-center text-gray-500"
              >
                No results found.
              </TableCell>
            </TableRow>
            <template v-if="!isGrouped">
              <TableRow
                v-for="revenue in filteredData"
                :key="revenue.id"
                class="hover:bg-gray-50"
              >
                <TableCell class="font-medium">{{
                  revenue.invoice_no
                }}</TableCell>
                <!-- <TableCell>{{ revenue.service_type }}</TableCell> -->
                <TableCell class="font-medium"
                  >{{ revenue.currency }} {{ revenue.amount }}</TableCell
                >
                <TableCell>{{ revenue.payment_date || '—' }}</TableCell>
                <TableCell>
                  <Badge
                    :variant="getStatusVariant(revenue.status)"
                    class="px-2 py-1 capitalize"
                    >{{ revenue.status }}</Badge
                  >
                </TableCell>
                <TableCell>{{ revenue.branch || '—' }}</TableCell>
                <TableCell>{{ revenue.payment_option || '—' }}</TableCell>
                <TableCell>
                  <Button
                    size="sm"
                    variant="default"
                    @click="viewRevenue(revenue)"
                    >View</Button
                  >
                </TableCell>
              </TableRow>
            </template>
            <template v-else>
              <TableRow
                v-for="(row, gidx) in filteredData"
                :key="
                  row.payment_date ||
                  row.week_start ||
                  row.month_name ||
                  row.year ||
                  gidx
                "
                class="hover:bg-gray-50"
              >
                <TableCell>
                  <template v-if="filters.timePeriod === 'daily'">
                    {{ row.payment_date }}
                  </template>
                  <template v-else-if="filters.timePeriod === 'weekly'">
                    {{ row.week_start }} - {{ row.week_end }}
                  </template>
                  <template v-else-if="filters.timePeriod === 'monthly'">
                    {{ row.month_name }}
                  </template>
                  <template v-else-if="filters.timePeriod === 'yearly'">
                    {{ row.year }}
                  </template>
                  <template v-else> &mdash; </template>
                </TableCell>
                <TableCell> ₱ {{ row.total || row.amount || 0 }} </TableCell>
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
      <!-- <div class="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Revenue Breakdown by Type</CardTitle>
          </CardHeader>
          <CardContent>
            <RevenueBreakDownPieChart
              :data="revenueServiceTypeBreakdownData"
              category="total"
              title="Revenue"
            />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Revenue Breakdown by Payment Options</CardTitle>
          </CardHeader>
          <CardContent>
            <RevenuePaymentOptionsBreakDownPieChart
              :data="revenueByPaymentOption"
              category="total"
              title="Revenue"
            />
          </CardContent>
        </Card>
      </div> -->
      <Card>
        <CardHeader>
          <CardTitle>Revenue Breakdown by Payment Options</CardTitle>
        </CardHeader>
        <CardContent>
          <RevenuePaymentOptionsBreakDownPieChart
            :data="revenueByPaymentOption"
            category="total"
            title="Revenue"
          />
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Net Profit Trend</CardTitle></CardHeader>
        <CardContent>
          <RevenueTrendSparkLine
            :data="
              revenueTrendData.map((item) => ({
                year: item.year,
                value: item.revenue,
              }))
            "
            label="Revenue"
            :colors="['#3b82f6']"
            :y-formatter="
              (val) =>
                `₱ ${val.toLocaleString(undefined, { minimumFractionDigits: 2 })}`
            "
          />
        </CardContent>
      </Card>
    </div>
    <Dialog v-model:open="dialogOpen">
      <DialogContent class="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Revenue Details</DialogTitle>
          <DialogDescription>
            Detailed information for invoice
            <strong>{{ selectedRevenue?.invoice_no }}</strong
            >.
          </DialogDescription>
        </DialogHeader>
        <div class="mt-2 space-y-2" v-if="selectedRevenue">
          <p><strong>Invoice No:</strong> {{ selectedRevenue.invoice_no }}</p>
          <p>
            <strong>Service Type:</strong> {{ selectedRevenue.service_type }}
          </p>
          <p>
            <strong>Amount:</strong> {{ selectedRevenue.currency }}
            {{ selectedRevenue.amount.toLocaleString() }}
          </p>
          <p>
            <strong>Payment Date:</strong>
            {{ selectedRevenue.payment_date || '—' }}
          </p>
          <p><strong>Status:</strong> {{ selectedRevenue.status || '—' }}</p>
          <p><strong>Branch:</strong> {{ selectedRevenue.branch || '—' }}</p>
          <p><strong>Branch:</strong> {{ selectedRevenue.branch || '—' }}</p>
          <p>
            <strong>Payment Option:</strong>
            {{ selectedRevenue.payment_option || '—' }}
          </p>
          <p><strong>Notes:</strong> {{ selectedRevenue.notes || '—' }}</p>
        </div>
        <DialogFooter>
          <Button @click="dialogOpen = false">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
