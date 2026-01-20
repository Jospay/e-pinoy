<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import AppLayout from '@/layouts/AppLayout.vue'
import manager from '@/routes/manager'
import type { BreadcrumbItem } from '@/types'
import { Head, usePage, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import {
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  useVueTable,
  type ColumnDef,
  type SortingState,
} from '@tanstack/vue-table'
import { ArrowUpDown } from 'lucide-vue-next'
import { computed, h, ref, watch } from 'vue'

/* Breadcrumbs */
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Support Center', href: manager.supportCenter().url },
]

/* Page props */
const page = usePage<{ tickets: any }>()
const tickets = computed(() => page.props.tickets.data)
const pagination = computed(() => page.props.tickets)

/* Local reactive tickets array */
const localTickets = ref([...tickets.value])

/* Filters */
const search = ref(page.props.ziggy?.query?.search ?? '')
const status = ref(page.props.ziggy?.query?.status ?? 'all')

watch([search, status], () => {
  router.get(
    manager.supportCenter().url,
    {
      search: search.value || undefined,
      status: status.value !== 'all' ? status.value : undefined,
    },
    {
      preserveState: true,
      replace: true,
      onSuccess: (page) => {
        // Update localTickets when filters change
        localTickets.value = [...page.props.tickets.data]
      },
    },
  )
})

/* Status badge */
const getStatusClass = (status: string) => {
  switch (status) {
    case 'Open':
      return 'bg-amber-500'
    case 'In Progress':
      return 'bg-blue-500'
    case 'Resolved':
      return 'bg-green-600'
    default:
      return 'bg-gray-400'
  }
}

/* Sorting */
const sorting = ref<SortingState>([])

/* Columns */
const columns: ColumnDef<any>[] = [
  { accessorKey: 'ticket_code', header: 'Code' },
  { accessorKey: 'type', header: 'Type' },
  { accessorKey: 'description', header: 'Description' },
  { accessorKey: 'date', header: 'Date' },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }) =>
      h(
        Badge,
        { class: getStatusClass(row.getValue('status')) },
        () => row.getValue('status'),
      ),
  },
  {
    id: 'actions',
    header: 'Action',
    cell: ({ row }) => {
      const ticket = row.original

      return h('div', { class: 'flex gap-2' }, [
        h(
          Button,
          {
            size: 'sm',
            variant: 'outline',
            onClick: () => openViewDialog(ticket),
          },
          () => 'View',
        ),

        h(
          Button,
          {
            size: 'sm',
            variant: 'default',
            disabled: ticket.status_id === 16,
            onClick: () => markAsCompleted(ticket.id),
          },
          () => 'Complete',
        ),
      ])
    },
  },
]

/* Table */
const table = useVueTable({
  data: localTickets,
  columns,
  state: { sorting },
  onSortingChange: updater => {
    sorting.value =
      typeof updater === 'function' ? updater(sorting.value) : updater
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
})

/* Modal */
const viewDialogOpen = ref(false)
const selectedTicket = ref<any>(null)

const openViewDialog = (ticket: any) => {
  selectedTicket.value = ticket
  viewDialogOpen.value = true
}

/* Mark ticket as completed */
const markAsCompleted = (ticketId: number) => {
  router.put(
    `/manager/support-tickets/${ticketId}/complete`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        const ticket = localTickets.value.find(t => t.id === ticketId)
        if (ticket) {
          ticket.status_id = 16
          ticket.status = 'Resolved'
        }
        toast.success('Ticket marked as completed!')
      },
      onError: () => {
        toast.error('Something went wrong!')
      },
    },
  )
}
</script>

<template>
  <Head title="Support Center" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-4 rounded-xl bg-white p-4 shadow">
      <!-- Header + Filters -->
      <div class="flex flex-wrap items-center justify-between gap-4 border-b pb-4">
        <h1 class="text-xl font-semibold">Support Center</h1>

        <div class="flex gap-2">
          <!-- Status Filter -->
          <select v-model="status" class="rounded-md border px-3 py-2 text-sm">
            <option value="all">All Status</option>
            <option value="Open">Open</option>
            <option value="In Progress">In Progress</option>
            <option value="Resolved">Resolved</option>
          </select>

          <!-- Search -->
          <input
            v-model="search"
            type="text"
            placeholder="Search tickets..."
            class="rounded-md border px-3 py-2 text-sm"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
              <TableHead v-for="header in headerGroup.headers" :key="header.id">
                <div
                  v-if="!header.isPlaceholder"
                  class="flex cursor-pointer items-center gap-2 select-none"
                  @click="header.column.getToggleSortingHandler()?.($event)"
                >
                  <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                  <ArrowUpDown class="h-4 w-4" />
                </div>
              </TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-if="!table.getRowModel().rows.length">
              <TableCell :colspan="table.getAllColumns().length" class="text-center">
                No tickets found.
              </TableCell>
            </TableRow>

            <TableRow
              v-for="row in table.getRowModel().rows"
              :key="row.id"
              class="even:bg-gray-50"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex items-center justify-between">
        <div>
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
        </div>

        <div class="flex gap-1">
          <Button
            v-for="link in pagination.links"
            :key="link.label"
            size="sm"
            variant="outline"
            :disabled="!link.url || link.active"
            @click="link.url && $inertia.get(link.url)"
          >
            <span v-html="link.label" />
          </Button>
        </div>
      </div>
    </div>

    <!-- View Ticket Modal -->
    <Dialog v-model:open="viewDialogOpen">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Ticket Details</DialogTitle>
        </DialogHeader>

        <div v-if="selectedTicket" class="space-y-3 text-sm">
          <div>
            <strong>Ticket Code:</strong> {{ selectedTicket.ticket_code }}
          </div>

          <div>
            <strong>Type:</strong> {{ selectedTicket.type }}
          </div>

          <div>
            <strong>Status:</strong>
            <Badge :class="getStatusClass(selectedTicket.status)">
              {{ selectedTicket.status }}
            </Badge>
          </div>

          <div>
            <strong>Description:</strong>
            <p class="mt-1 text-gray-600">{{ selectedTicket.description }}</p>
          </div>

          <div v-if="selectedTicket.user">
            <strong>User:</strong> {{ selectedTicket.user.name }} ({{ selectedTicket.user.email }})
          </div>

          <div v-if="selectedTicket.franchise">
            <strong>Franchise:</strong> {{ selectedTicket.franchise.name }}
          </div>

          <div v-if="selectedTicket.branch">
            <strong>Branch:</strong> {{ selectedTicket.branch.name }}
          </div>

          <div>
            <strong>Date:</strong> {{ selectedTicket.date }}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
