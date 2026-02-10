<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { computed, h } from 'vue';

// Define Props
const props = defineProps<{
  branches: {
    data: BranchRow[];
  };
  franchise: FranchiseDetails;
}>();

interface BranchRow {
  id: number;
  name: string;
  email: string;
  phone: string;
  status_name: string;
}

interface FranchiseDetails {
  name: string;
  owner_name: string;
}

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: superAdmin.dashboard.index().url },
  { title: 'Branches', href: '#' },
];

// --- Dynamic Column Definitions ---
const branchColumns = computed<ColumnDef<any>[]>(() => {
  const cols: ColumnDef<any>[] = [
    {
      accessorKey: 'name',
      header: 'Branch',
    },
    {
      accessorKey: 'email',
      header: 'Email',
    },
    {
      accessorKey: 'phone',
      header: 'Phone',
    },
    {
      accessorKey: 'status_name',
      header: () => h('div', { class: 'text-center' }, 'Status'),
      cell: ({ row }) => {
        const status = row.getValue('status_name') as string;
        const badgeClass = {
          'bg-blue-500 hover:bg-blue-600': status === 'active',
        };
        return h('div', { class: 'text-center' }, [
          h(
            Badge,
            { class: [badgeClass, 'text-white'] },
            () => status || 'N/A',
          ),
        ]);
      },
    },
  ];

  return cols;
});

const goBack = () => {
  window.history.back();
};
</script>

<template>
  <Head title="Branch Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div
          class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between"
        >
          <div>
            <Button
              variant="outline"
              class="mb-4 gap-2 sm:mb-0"
              @click="goBack"
            >
              <span>&larr;</span> Back
            </Button>
            <h2 class="mt-4 font-mono text-2xl font-bold">Branch List</h2>
          </div>
          <div
            class="rounded-lg border border-primary/10 bg-primary/5 p-4 text-right"
          >
            <p class="text-lg font-semibold text-primary">
              {{ props.franchise.name }}
            </p>
            <p class="font-mono text-sm text-muted-foreground">
              {{ props.franchise.owner_name }}
            </p>
          </div>
        </div>

        <DataTable
          :columns="branchColumns"
          :data="branches.data"
          search-placeholder="Search branches..."
        />
      </div>
    </div>
  </AppLayout>
</template>
