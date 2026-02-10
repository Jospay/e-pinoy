<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { h } from 'vue';

interface BranchRow {
  id: number;
  name: string;
  email: string;
  phone: string;
  status_name: string;
}

const props = defineProps<{
  branches: {
    data: BranchRow[];
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Branch',
    href: owner.branch.index().url,
  },
];

const branchColumns: ColumnDef<BranchRow>[] = [
  {
    accessorKey: 'name',
    header: () => h('div', { class: 'text-center' }, 'Branch'),
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('name')),
  },
  {
    accessorKey: 'email',
    header: () => h('div', { class: 'text-center' }, 'Email'),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, row.getValue('email')),
  },
  {
    accessorKey: 'phone',
    header: () => h('div', { class: 'text-center' }, 'Phone'),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, row.getValue('phone')),
  },
  {
    accessorKey: 'status_name',
    header: () => h('div', { class: 'text-center' }, 'Status'),
    cell: ({ row }) => {
      const status = row.getValue('status_name') as string;
      const badgeClass = {
        'bg-blue-500 hover:bg-blue-600': status === 'active',
        'bg-amber-500 hover:bg-amber-600': status === 'pending',
      };
      return h('div', { class: 'text-center' }, [
        h(Badge, { class: [badgeClass, 'text-white'] }, () => status || 'N/A'),
      ]);
    },
  },
];
</script>

<template>
  <Head title="Super Admin Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">Branch Management</h2>
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
