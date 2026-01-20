<script setup lang="ts">
import RevenueVsExpensesAreaChart from '@/components/manager/charts/dashboard/RevenueVsExpensesAreaChart.vue';
import NetProfitTrendSparkLine from '@/components/manager/charts/reports-and-analytics/NetProfitTrendSparkLine.vue';
import RevenueVsExpensesBarChart from '@/components/manager/charts/reports-and-analytics/RevenueVsExpensesBarChart.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
  CarTaxiFront,
  CreditCard,
  DollarSign,
  Settings,
  UserCog,
  Users,
} from 'lucide-vue-next';

interface Props {
  activeVehicles: number;
  pendingVehicles: number;

  activeDrivers: number;
  pendingDrivers: number;

  activeTechnicians: number;
  pendingTechnicians: number;

  dailyEarnings: number;
  yesterdayEarnings: number;

  dailyTrips: number;
  yesterdayTrips: number;

  pendingBoundaryDueCount: number;

  vehiclesUnderMaintenance: number;

  branchExists: boolean;

  revenueExpensesData: { date: string; Revenue: number; Expenses: number }[];

  netProfitData: { year: number; 'Growth Rate': number }[];
}

// Props from controller
const {
  activeVehicles,
  pendingVehicles,

  activeDrivers,
  pendingDrivers,

  activeTechnicians,
  pendingTechnicians,

  dailyEarnings,
  yesterdayEarnings,

  dailyTrips,
  // yesterdayTrips,

  pendingBoundaryDueCount,

  vehiclesUnderMaintenance,

  branchExists,
  revenueExpensesData,
  netProfitData,
} = defineProps<Props>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: manager.dashboard().url },
];

// Map backend data for chart components
const mappedRevenueExpensesData = revenueExpensesData.map((item) => ({
  // For Bar Chart
  name: item.date,
  revenue: item.Revenue,
  expenses: item.Expenses,
  // For Area Chart
  date: item.date,
  Revenue: item.Revenue,
  Expenses: item.Expenses,
}));
</script>

<template>
  <Head title="Manager Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <!-- Header -->
      <div class="mb-2">
        <h1 class="mb-2 text-3xl font-bold">Dashboard</h1>
        <p class="text-gray-600">Central Hub for Tracking and Management</p>
      </div>

      <!-- No Franchise -->
      <div v-if="!branchExists">
        <p>No franchise assigned. Dashboard data is not available.</p>
      </div>

      <!-- Stats Cards -->
      <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Active Vehicles -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium"
              >My Active Vehicles</CardTitle
            >
            <CarTaxiFront class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeVehicles }}</div>
            <p class="text-xs text-muted-foreground">
              {{ pendingVehicles }} pending vehicles
            </p>
          </CardContent>
        </Card>

        <!-- Active Drivers -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">My Active Drivers</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeDrivers }}</div>
            <p class="text-xs text-muted-foreground">
              {{ pendingDrivers }} unassigned drivers
            </p>
          </CardContent>
        </Card>

        <!-- Active Technicians -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium"
              >My Active Technicians</CardTitle
            >
            <UserCog class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ activeTechnicians }}</div>
            <p class="text-xs text-muted-foreground">
              {{ pendingTechnicians }} unassigned technicians
            </p>
          </CardContent>
        </Card>

        <!-- Daily Earnings -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Daily Earnings</CardTitle>
            <DollarSign class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              ₱{{ dailyEarnings.toLocaleString() }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{
                (
                  ((dailyEarnings - yesterdayEarnings) /
                    (yesterdayEarnings || 1)) *
                  100
                ).toFixed(0)
              }}% from yesterday
            </p>
          </CardContent>
        </Card>

        <!-- Daily Trips -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium">Total Trips Today</CardTitle>
            <CarTaxiFront class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ new Intl.NumberFormat().format(dailyTrips) }}
            </div>
            <!-- <p class="text-xs text-muted-foreground">
              {{
                yesterdayTrips
                  ? (
                      ((dailyTrips - yesterdayTrips) / yesterdayTrips) *
                      100
                    ).toFixed(0)
                  : 0
              }}% from yesterday
            </p> -->
            <p class="text-xs text-muted-foreground">
              {{ pendingTechnicians }} yesterday trips
            </p>
          </CardContent>
        </Card>

        <!-- Pending Boundary Due -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium"
              >Pending Boundary Due</CardTitle
            >
            <CreditCard class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ pendingBoundaryDueCount }}
            </div>
            <p class="text-xs text-muted-foreground">
              contracts pending as of {{ new Date().toLocaleDateString() }}
            </p>
          </CardContent>
        </Card>

        <!-- Vehicles Under Maintenance -->
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="text-sm font-medium"
              >Vehicles Under Maintenance</CardTitle
            >
            <Settings class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ vehiclesUnderMaintenance }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Area Chart Overview -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
        <Card class="col-span-7">
          <CardHeader>
            <CardTitle>Overview</CardTitle>
          </CardHeader>
          <CardContent class="pl-2">
            <RevenueVsExpensesAreaChart
              :data="mappedRevenueExpensesData"
              :categories="['Expenses', 'Revenue']"
              :colors="['#005dcf', '#33cc66']"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Charts Section -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Revenue vs Expenses Bar Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Revenue vs Expenses</CardTitle>
          </CardHeader>
          <CardContent>
            <RevenueVsExpensesBarChart
              :data="mappedRevenueExpensesData"
              :categories="['expenses', 'revenue']"
              :colors="['#ef4444', '#22c55e']"
              :y-formatter="(val) => `₱${val.toLocaleString()}`"
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
              :y-formatter="(val) => `₱${val.toLocaleString()}`"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
