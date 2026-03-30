<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue-sonner';
import { MoreHorizontal, PackagePlus, Loader2 } from 'lucide-vue-next';

// UI Components
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { Label } from '@/components/ui/label';

interface InventoryItem {
  id: number;
  franchise_id: number;
  code_no: string;
  name: string;
  category:
    | 'Electrical'
    | 'Mechanical'
    | 'Safety Equipment'
    | 'Consumables'
    | 'Other';
  specification: string;
  quantity: number;
  total_used: number | null;
  unit_price: number;
  notes?: string;
}

// Props from Inertia
const props = defineProps<{
  inventory: {
    data: InventoryItem[];
    links: any[];
    meta?: any;
    current_page: number;
    from: number;
    to: number;
    total: number;
    per_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  franchise_id: number; // Captured from your Controller
}>();

const page = usePage();
const errors = computed(() => page.props.errors as any);

// Breadcrumbs
const breadcrumbs = [
  { title: 'Inventory Management', href: '/owner/inventory' },
];

// State
const isSaving = ref(false);
const showDialog = ref(false);
const dialogMode = ref<'create' | 'edit'>('create');
const editingItem = ref<InventoryItem | null>(null);

/** * FIX: Using Partial<InventoryItem> removes the red line error
 * when assigning "item" to "form".
 */
const form = ref<Partial<InventoryItem>>({
  franchise_id: props.franchise_id,
  code_no: '',
  name: '',
  category: 'Other',
  specification: '',
  quantity: 0,
  unit_price: 0,
  notes: '',
});

const categories: InventoryItem['category'][] = [
  'Electrical',
  'Mechanical',
  'Safety Equipment',
  'Consumables',
  'Other',
];

// Helper to access pagination data easily
const paginator = computed(() => props.inventory);
const paginationLinks = computed(() => paginator.value.links || []);

const openCreate = () => {
  dialogMode.value = 'create';
  form.value = {
    franchise_id: props.franchise_id,
    code_no: '',
    name: '',
    category: 'Other',
    specification: '',
    quantity: 0,
    unit_price: 0,
    notes: '',
  };
  showDialog.value = true;
};

const openEdit = (item: InventoryItem) => {
  dialogMode.value = 'edit';
  editingItem.value = item;
  /** FIX: This spread now works perfectly with the Partial type above */
  form.value = { ...item };
  showDialog.value = true;
};

// Pagination Logic
const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(url, {}, { preserveState: true, preserveScroll: true });
};

const saveItem = () => {
  const url =
    dialogMode.value === 'create'
      ? '/owner/inventory'
      : `/owner/inventory/${editingItem.value?.id}`;

  router[dialogMode.value === 'create' ? 'post' : 'put'](
    url,
    form.value as any,
    {
      onStart: () => (isSaving.value = true),
      onFinish: () => (isSaving.value = false),
      onSuccess: () => {
        toast.success(
          `Inventory item ${dialogMode.value === 'create' ? 'created' : 'updated'}!`,
        );
        showDialog.value = false;
      },
      onError: () => toast.error('Please check the form for errors.'),
    },
  );
};

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(value);
};
</script>

<template>
  <Head title="Inventory Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <h1 class="text-3xl font-bold">Inventory</h1>
          <p class="text-sm text-muted-foreground sm:text-base">
            Manage stock levels and parts for your fleet.
          </p>
        </div>
        <Button class="w-full sm:w-auto" @click="openCreate">
          <PackagePlus class="mr-2 h-4 w-4" /> Add Item
        </Button>
      </div>

      <div class="rounded-lg border bg-card">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Code</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Category</TableHead>
              <TableHead>Spec</TableHead>
              <TableHead>Qty</TableHead>
              <TableHead>Used in Maint.</TableHead>
              <TableHead>Price</TableHead>
              <TableHead class="text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="item in inventory.data" :key="item.id">
              <TableCell class="font-mono text-xs">{{
                item.code_no
              }}</TableCell>
              <TableCell class="font-medium">{{ item.name }}</TableCell>
              <TableCell>
                <Badge variant="secondary">{{ item.category }}</Badge>
              </TableCell>
              <TableCell class="max-w-[150px] truncate text-xs">
                {{ item.specification }}
              </TableCell>
              <TableCell>
                <span
                  :class="item.quantity <= 5 ? 'font-bold text-red-600' : ''"
                >
                  {{ item.quantity }}
                </span>
              </TableCell>
              <TableCell>
                <Badge variant="outline" class="font-mono">
                  {{ item.total_used || 0 }}
                </Badge>
              </TableCell>
              <TableCell>{{ formatCurrency(item.unit_price) }}</TableCell>
              <TableCell class="text-center">
                <DropdownMenu>
                  <DropdownMenuTrigger as-child>
                    <Button variant="ghost" class="h-8 w-8 p-0">
                      <MoreHorizontal class="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuLabel class="text-xs text-muted-foreground"
                      >Actions</DropdownMenuLabel
                    >
                    <DropdownMenuItem
                      @click="openEdit(item)"
                      class="cursor-pointer"
                    >
                      Edit Details
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>

            <TableRow v-if="inventory.data.length === 0">
              <TableCell
                colspan="8"
                class="py-6 text-center text-muted-foreground"
              >
                No results found.
              </TableCell>
            </TableRow>
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

            <template v-for="link in paginationLinks" :key="link.label">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{
                    'bg-slate-200 text-black dark:bg-slate-800 dark:text-white':
                      link.active,
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
    </div>

    <Dialog v-model:open="showDialog">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>
            {{
              dialogMode === 'create'
                ? 'Add Inventory Item'
                : 'Edit Inventory Item'
            }}
          </DialogTitle>
          <DialogDescription
            >Fill in the details for your inventory record.</DialogDescription
          >
        </DialogHeader>

        <div class="grid gap-4 py-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Item Code</Label>
              <Input
                v-model="form.code_no"
                placeholder="INV-001"
                :class="{ 'border-red-500': errors.code_no }"
              />
            </div>
            <div class="space-y-2">
              <Label>Category</Label>
              <Select v-model="form.category">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="cat in categories" :key="cat" :value="cat">
                    {{ cat }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-2">
            <Label>Item Name</Label>
            <Input
              v-model="form.name"
              placeholder="Item Name"
              :class="{ 'border-red-500': errors.name }"
            />
          </div>

          <div class="space-y-2">
            <Label>Specification</Label>
            <Input
              v-model="form.specification"
              placeholder="e.g. 10W-40 Synthetic"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Quantity</Label>
              <Input type="number" v-model="form.quantity" />
            </div>
            <div class="space-y-2">
              <Label>Unit Price (₱)</Label>
              <Input type="number" step="0.01" v-model="form.unit_price" />
            </div>
          </div>

          <div class="space-y-2">
            <Label>Notes</Label>
            <Textarea
              v-model="form.notes"
              placeholder="Additional details..."
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showDialog = false">Cancel</Button>
          <Button @click="saveItem" :disabled="isSaving">
            <Loader2 v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
            {{ dialogMode === 'create' ? 'Create' : 'Update' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
