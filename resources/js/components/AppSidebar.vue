<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import driver from '@/routes/driver';
import manager from '@/routes/manager';
import owner from '@/routes/owner';
import passenger from '@/routes/passenger';
import superAdmin from '@/routes/super-admin';
import technician from '@/routes/technician';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Banknote,
  BanknoteArrowDown,
  Box,
  CarTaxiFront,
  ChartNoAxesCombined,
  DollarSign,
  FileSpreadsheet,
  FileText,
  HandCoins,
  HelpCircle,
  History,
  LayoutGrid,
  Map,
  ReceiptText,
  Ticket,
  Users,
  Wrench,
  FileUser,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import NavMain from './NavMain.vue';

// 🧠 1. Get the logged-in user
const page = usePage();
const user = page.props.auth.user;
const userFranchise = user.owner?.franchises?.[0];

// 🧭 2. Map user_type_id to role name
const typeMap: Record<number, string> = {
  1: 'super_admin',
  2: 'owner',
  3: 'manager',
  4: 'driver',
  5: 'technician',
  6: 'passenger',
};

const userType = typeMap[user?.user_type_id] || 'guest';

// 🧱 3. Define navigation sets per user type
const navConfig: Record<string, NavItem[]> = {
  super_admin: [
    {
      title: 'Dashboard',
      href: superAdmin.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
    // {
    //   title: 'Driver Verification',
    //   href: superAdmin.driver.verification(),
    //   icon: UserCheck,
    //   group: 'Fleet',
    // },
    {
      title: 'Driver Management',
      href: superAdmin.driver.index(),
      icon: Users,
      group: 'Fleet',
    },
    {
      title: 'Vehicle Management',
      href: superAdmin.vehicle.index(),
      icon: CarTaxiFront,
      group: 'Fleet',
    },
    {
      title: 'Inventory Management',
      href: superAdmin.inventory.index(),
      icon: Box,
      group: 'Fleet',
    },
    {
      title: 'Boundary Contract',
      href: superAdmin.boundaryContract.index(),
      icon: ReceiptText,
      group: 'Fleet',
    },
    {
      title: 'Gps Monitoring',
      href: superAdmin.gpsTracker.index(),
      icon: Map,
      group: 'Fleet',
    },
    {
      title: 'Revenue Report',
      href: superAdmin.revenue.index(),
      icon: DollarSign,
      group: 'Finance',
    },
    {
      title: 'Expense Report',
      href: superAdmin.expense.index(),
      icon: BanknoteArrowDown,
      group: 'Finance',
    },
    {
      title: 'Earning Report',
      href: superAdmin.earning.index(),
      icon: ChartNoAxesCombined,
      group: 'Finance',
    },
    {
      title: 'Transaction History',
      href: superAdmin.transaction.index(),
      icon: History,
      group: 'Finance',
    },
    {
      title: 'Allocation Management',
      href: superAdmin.allocation.index(),
      icon: HandCoins,
      group: 'Finance',
    },
    {
      title: 'Feedback Management',
      href: superAdmin.feedbacks.index(),
      icon: FileUser,
      group: 'Others',
    },
  ],

  manager: [
    {
      title: 'Dashboard',
      href: manager.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
    // {
    //   title: 'Notifications',
    //   href: manager.notifications(),
    //   icon: Bell,
    //   group: 'Overview',
    // },

    {
      title: 'Payout',
      href: manager.payout(),
      icon: Banknote,
      group: 'Payment',
    },

    {
      title: 'Driver Applications',
      href: manager.driversApplication.index(),
      icon: Users,
      group: 'Management',
    },
    {
      title: 'Driver Management',
      href: manager.drivers.index(),
      icon: Users,
      group: 'Management',
    },
    {
      title: 'Vehicle Management',
      href: manager.vehicles.index(),
      icon: CarTaxiFront,
      group: 'Management',
    },
    {
      title: 'Boundary Contracts',
      href: manager.boundaryContracts.index(),
      icon: FileText,
      group: 'Management',
    },
    // {
    //   title: 'Assign Drivers',
    //   href: manager.vehicleDrivers.index(),
    //   icon: Octagon,
    //   group: 'Management',
    // },

    // {
    //   title: 'Reports & Analytics',
    //   href: manager.reportsAndAnalytics(),
    //   icon: BarChart3,
    //   group: 'Operations',
    // },

    {
      title: 'Revenue Management',
      href: manager.revenueManagement(),
      icon: DollarSign,
      group: 'Finance',
    },
    {
      title: 'Payroll Management',
      href: manager.driverownerpayroll(),
      icon: FileSpreadsheet,
      group: 'Finance',
    },
    {
      title: 'Earning Report',
      href: manager.driverownerreport(),
      icon: ChartNoAxesCombined,
      group: 'Finance',
    },
    {
      title: 'Expense Management',
      href: manager.expenseManagement(),
      icon: FileSpreadsheet,
      group: 'Finance',
    },

    {
      title: 'Support Center',
      href: manager.supportCenter(),
      icon: HelpCircle,
      group: 'Support',
    },
    {
      title: 'Maintenance Requests',
      href: manager.maintenanceRequests.index(),
      icon: Wrench,
      group: 'Support',
    },
  ],

  owner: [
    {
      title: 'Dashboard',
      href: owner.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
    // {
    //   title: 'Notifications',
    //   href: owner.notifications(),
    //   icon: Bell,
    //   group: 'Overview',
    // },

    {
      title: 'Payout',
      href: owner.payout(),
      icon: Banknote,
      group: 'Payment',
    },

    {
      title: 'Driver Applications',
      href: owner.driversApplication.index(),
      icon: Users,
      group: 'Management',
    },
    {
      title: 'Driver Management',
      href: owner.drivers.index(),
      icon: Users,
      group: 'Management',
    },
    {
      title: 'Vehicle Management',
      href: owner.vehicles.index(),
      icon: CarTaxiFront,
      group: 'Management',
    },
    // {
    //   title: 'Assign Drivers',
    //   href: owner.vehicleDrivers.index(),
    //   icon: Octagon,
    //   group: 'Management',
    // },
    {
      title: 'Boundary Contracts',
      href: owner.boundaryContracts.index(),
      icon: FileText,
      group: 'Management',
    },
    // {
    //   title: 'Suspend Drivers',
    //   href: owner.suspendDrivers.index(),
    //   icon: UserX,
    //   group: 'Management',
    // },

    // {
    //   title: 'Reports & Analytics',
    //   href: owner.reportsAndAnalytics(),
    //   icon: BarChart3,
    //   group: 'Operations',
    // },

    {
      title: 'Revenue Management',
      href: owner.revenueManagement(),
      icon: DollarSign,
      group: 'Finance',
    },
    {
      title: 'Payroll Management',
      href: owner.driverownerpayroll(),
      icon: FileSpreadsheet,
      group: 'Finance',
    },
    {
      title: 'Expense Management',
      href: owner.expenseManagement(),
      icon: FileSpreadsheet,
      group: 'Finance',
    },

    {
      title: 'Earning Report',
      href: owner.driverownerreport(),
      icon: ChartNoAxesCombined,
      group: 'Finance',
    },

    {
      title: 'Support Center',
      href: owner.supportCenter(),
      icon: HelpCircle,
      group: 'Support',
    },
    {
      title: 'Maintenance Requests',
      href: owner.maintenanceRequests.index(),
      icon: Wrench,
      group: 'Support',
    },

    {
      title: 'My Contract',
      href: owner.franchise.myContract(),
      icon: ReceiptText,
      group: 'Others',
    },
  ],

  driver: [
    {
      title: 'Dashboard',
      href: driver.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
    // {
    //   title: 'Support Center',
    //   href: finance.supportCenter(),
    //   icon: HelpCircle,
    //   group: 'Support',
    // },
  ],

  technician: [
    {
      title: 'Dashboard',
      href: technician.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
    {
      title: 'Tickets / Jobs',
      href: technician.ticket(),
      icon: Ticket,
      group: 'Management',
    },
  ],

  passenger: [
    {
      title: 'Dashboard',
      href: passenger.dashboard(),
      icon: LayoutGrid,
      group: 'Overview',
    },
  ],

  guest: [], // fallback
};

// 🧩 4. Load the correct nav items for the user
const allNavItems = navConfig[userType] || [];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboard()">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="allNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <NavUser />
    </SidebarFooter>
  </Sidebar>

  <slot />
</template>
