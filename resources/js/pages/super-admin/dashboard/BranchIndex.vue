<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Skeleton } from '@/components/ui/skeleton';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { AlertCircleIcon, MoreHorizontal } from 'lucide-vue-next';
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

interface BranchModal {
  id: number;
  status: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  region: string;
  city: string;
  barangay: string;
  province?: string;
  postal_code: string;
  created_at: string;
  dti_registration_attachment?: string;
  mayor_permit_attachment?: string;
  proof_agreement_attachment?: string;
}
const branchDetails = computed(() => {
  const data = branchModal.data.value;
  if (!data) return [];

  return [
    { label: 'Name', value: data.name, type: 'text' },
    { label: 'Email', value: data.email, type: 'text' },
    { label: 'Phone', value: data.phone, type: 'text' },
    { label: 'Status', value: data.status, type: 'text' },
    { label: 'Region', value: data.region, type: 'text' },
    { label: 'Province', value: data.province, type: 'text' },
    { label: 'City', value: data.city, type: 'text' },
    { label: 'Barangay', value: data.barangay, type: 'text' },
    { label: 'Postal Code', value: data.postal_code, type: 'text' },
    { label: 'Address', value: data.address, type: 'text' },
    { label: 'Registered At', value: data.created_at, type: 'text' },
    {
      label: 'DTI Registration',
      value: data.dti_registration_attachment,
      type: 'link',
    },
    {
      label: "Mayor's Permit",
      value: data.mayor_permit_attachment,
      type: 'link',
    },
    {
      label: 'Proof of Agreement',
      value: data.proof_agreement_attachment,
      type: 'link',
    },
  ].filter((item) => item.value);
});

// --- Modal State ---
const branchModal = useDetailsModal<BranchModal>({
  baseUrl: '/super-admin/branch',
});

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
    {
      id: 'actions',
      header: () => h('div', { class: 'text-center' }, 'Actions'),
      cell: ({ row }) => {
        const branch = row.original as any;

        return h('div', { class: 'relative text-center' }, [
          h(DropdownMenu, null, () => [
            h(
              DropdownMenuTrigger,
              { asChild: true, class: 'cursor-pointer' },
              () =>
                h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () => [
                  h('span', { class: 'sr-only' }, 'Open menu'),
                  h(MoreHorizontal, { class: 'h-4 w-4' }),
                ]),
            ),
            h(DropdownMenuContent, { align: 'end', class: 'border-2' }, () => [
              h(DropdownMenuLabel, null, () => 'Actions'),
              h(
                DropdownMenuItem,
                {
                  class: 'cursor-pointer',
                  onClick: () => branchModal.open(branch.id),
                },
                () => 'View Branch Details',
              ),
            ]),
          ]),
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

    <Dialog v-model:open="branchModal.isOpen.value">
      <DialogContent class="max-w-2xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Branch Details</DialogTitle>
        </DialogHeader>
        <DialogDescription>
          <div
            v-if="branchModal.isLoading.value"
            class="grid grid-cols-2 gap-4"
          >
            <template v-for="item in 10" :key="item">
              <Skeleton class="h-5 w-24" />
              <Skeleton class="h-5 w-3/4" />
            </template>
          </div>

          <div
            v-else-if="branchDetails.length > 0"
            class="grid grid-cols-2 gap-4"
          >
            <template v-for="item in branchDetails" :key="item.label">
              <div class="font-medium">{{ item.label }}:</div>

              <div v-if="item.type === 'link'">
                <a
                  :href="item.value"
                  target="_blank"
                  class="text-blue-500 hover:underline"
                  >View</a
                >
              </div>

              <div v-else>
                {{ item.value }}
              </div>
            </template>
          </div>

          <div v-else-if="branchModal.isError.value">
            <Alert
              variant="destructive"
              class="border-2 border-red-500 shadow-lg"
            >
              <AlertCircleIcon class="h-4 w-4" />
              <AlertTitle class="font-bold">Error</AlertTitle>
              <AlertDescription class="font-semibold">
                Failed to load franchise details.
              </AlertDescription>
            </Alert>
          </div>
        </DialogDescription>
        <DialogFooter class="mt-5">
          <Button variant="outline" @click="branchModal.close">Close</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
