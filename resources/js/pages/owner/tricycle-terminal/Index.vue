<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import LightWeightMap from '@/components/LightWeightMap.vue';
import MultiSelect from '@/components/MultiSelect.vue';
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
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { AlertCircleIcon, MoreHorizontalIcon, PlusIcon } from 'lucide-vue-next';
import { computed, h, ref } from 'vue';

// --- Define Props ---
const props = defineProps<{
  tricycleTerminals: {
    data: TricycleTerminalRow[];
  };
  filterOptions: {
    id: string;
    name: string;
  }[];

  filters: {
    scope: string[];
  };
}>();

// --- Define tricycleTerminalRow Interface ---
interface TricycleTerminalRow {
  id: number;
  name: string;
  status_name: string;
  source_type: 'Franchise' | 'Branch';
  source_name: string;
  full_address: string;
}

const createTricycleTerminal = () => {
  router.get(owner.tricycleToda.create().url);
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tricycle Toda Terminal',
    href: owner.tricycleToda.index().url,
  },
];

interface TricycleTodaModal {
  id: number;
  status: string;
  name: string;
  source_type: string;
  source_name: string;
  address: string;
  latitude: number;
  longitude: number;
}
const tricycleTerminalDetails = computed(() => {
  const data = tricycleTerminalModal.data.value;
  if (!data) return [];

  return [
    { label: 'Name', value: data.name, type: 'text' },
    { label: 'Status', value: data.status, type: 'text' },
    { label: data.source_type, value: data.source_name, type: 'text' },
    { label: 'Address', value: data.address, type: 'text' },
  ].filter((item) => item.value);
});

const tricycleTerminalModal = useDetailsModal<TricycleTodaModal>({
  baseUrl: '/owner/tricycle-toda',
});

const tricycleTerminalColumns: ColumnDef<TricycleTerminalRow>[] = [
  {
    accessorKey: 'name',
    header: () => h('div', { class: 'text-center' }, 'Toda Name'),
    cell: (info) => h('div', { class: 'text-center' }, info.getValue<string>()),
  },
  {
    accessorKey: 'source_type',
    header: () => h('div', { class: 'text-center' }, 'Connected To'),

    cell: ({ row }) => {
      return h('div', { class: 'text-center' }, [
        h('div', row.original.source_type),
        h(
          'div',
          {
            class: 'text-xs text-muted-foreground',
          },
          row.original.source_name,
        ),
      ]);
    },
  },
  {
    accessorKey: 'full_address',
    header: () => h('div', { class: 'text-center' }, 'Address'),
    cell: (info) =>
      h('div', { class: 'text-center truncate' }, info.getValue<string>()),
  },
  {
    accessorKey: 'status_name',
    header: () => h('div', { class: 'text-center' }, 'Status'),

    cell: (info) => {
      const status = info.getValue<string>();

      const badgeClass =
        status === 'active'
          ? 'bg-blue-500 hover:bg-blue-600'
          : status === 'pending'
            ? 'bg-amber-500 hover:bg-amber-600'
            : 'bg-gray-500 hover:bg-gray-600';

      return h(
        'div',
        { class: 'text-center' },
        h(
          Badge,
          {
            class: [badgeClass, 'text-white'],
          },
          () => status || 'N/A',
        ),
      );
    },
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-center' }, 'Actions'),
    cell: ({ row }) => {
      const tricycleTerminal = row.original as any;

      return h('div', { class: 'relative text-center' }, [
        h(DropdownMenu, null, () => [
          h(
            DropdownMenuTrigger,
            { asChild: true, class: 'cursor-pointer' },
            () =>
              h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () => [
                h('span', { class: 'sr-only' }, 'Open menu'),
                h(MoreHorizontalIcon, { class: 'h-4 w-4' }),
              ]),
          ),
          h(DropdownMenuContent, { align: 'end', class: 'border-2' }, () => [
            h(DropdownMenuLabel, null, () => 'Actions'),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer',
                onClick: () => tricycleTerminalModal.open(tricycleTerminal.id),
              },
              () => 'View Toda Details',
            ),
          ]),
        ]),
      ]);
    },
  },
];

const scopeOptions = computed(() =>
  props.filterOptions.map((option) => ({
    id: option.id,
    label: option.name,
  })),
);

const selectedScope = ref<string[]>(
  props.filters.scope?.length ? props.filters.scope : ['franchise'],
);

const applyFilter = (values: string[]) => {
  router.get(
    owner.tricycleToda.index().url,
    {
      scope: values.length ? values : ['franchise'],
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
};
</script>

<template>
  <Head title="Tricycle Toda Terminal" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Tricycle Toda Terminal
          </h2>
          <MultiSelect
            v-model="selectedScope"
            :options="scopeOptions"
            placeholder="Select terminal source"
            all-label="All Branches / Franchise"
            @change="applyFilter"
          />
        </div>
        <DataTable
          :columns="tricycleTerminalColumns"
          :data="tricycleTerminals.data"
          search-placeholder="Search tricycle toda terminals..."
        >
          <template #custom-actions>
            <Button class="me-5" @click="createTricycleTerminal"
              ><PlusIcon />Request Tricycle Toda</Button
            >
          </template>
        </DataTable>
      </div>
    </div>

    <Dialog v-model:open="tricycleTerminalModal.isOpen.value">
      <DialogContent class="max-w-4xl overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Tricycle Toda Details</DialogTitle>
        </DialogHeader>
        <DialogDescription>
          <div
            v-if="tricycleTerminalModal.isLoading.value"
            class="grid grid-cols-2 gap-4"
          >
            <template v-for="item in 10" :key="item">
              <Skeleton class="h-5 w-24" />
              <Skeleton class="h-5 w-3/4" />
            </template>
          </div>

          <div
            v-else-if="tricycleTerminalDetails.length > 0"
            class="grid grid-cols-2 gap-4"
          >
            <template v-for="item in tricycleTerminalDetails" :key="item.label">
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

            <div class="col-span-2">
              <LightWeightMap
                v-if="
                  tricycleTerminalModal.data.value?.latitude &&
                  tricycleTerminalModal.data.value?.longitude
                "
                :latitude="Number(tricycleTerminalModal.data.value?.latitude)"
                :longitude="Number(tricycleTerminalModal.data.value?.longitude)"
              />
            </div>
          </div>

          <div v-else-if="tricycleTerminalModal.isError.value">
            <Alert
              variant="destructive"
              class="border-2 border-red-500 shadow-lg"
            >
              <AlertCircleIcon class="h-4 w-4" />
              <AlertTitle class="font-bold">Error</AlertTitle>
              <AlertDescription class="font-semibold">
                Failed to load tricycle toda details.
              </AlertDescription>
            </Alert>
          </div>
        </DialogDescription>
        <DialogFooter class="mt-5">
          <Button variant="outline" @click="tricycleTerminalModal.close"
            >Close</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
