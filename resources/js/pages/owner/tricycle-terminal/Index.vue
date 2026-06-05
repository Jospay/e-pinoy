<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import { PlusIcon } from 'lucide-vue-next';
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
  latitude: string;
  longitude: string;
  status_name: string;
  source_type: 'Franchise' | 'Branch';
  source_name: string;
  full_address: string;
}

const createTricycleTerminal = () => {
  //
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Tricycle Toda Terminal',
    href: owner.tricycleToda.index().url,
  },
];

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
    id: 'location',
    header: () => h('div', { class: 'text-center' }, 'Location (Lat, Long)'),

    cell: ({ row }) => {
      const { latitude, longitude } = row.original;

      return h(
        'div',
        {
          class: 'text-center text-sm text-muted-foreground',
        },
        `${latitude}, ${longitude}`,
      );
    },
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
  </AppLayout>
</template>
