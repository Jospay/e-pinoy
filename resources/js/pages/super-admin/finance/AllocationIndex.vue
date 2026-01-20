<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Percent, PhilippinePeso, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// --- Types ---
interface PercentageType {
  id: number;
  name: string;
  type: 'Percentage' | 'PHP';
  value: string | number;
}

defineProps<{
  percentageTypes: PercentageType[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Allocation Management',
    href: superAdmin.allocation.index().url,
  },
];

// --- State Management ---
const isDialogOpen = ref(false);
const editingItem = ref<PercentageType | null>(null);

// --- Form Setup ---
const form = useForm({
  name: '',
  type: 'Percentage' as 'Percentage' | 'PHP',
  value: '' as string | number,
});

// --- Actions ---

const openCreateModal = () => {
  editingItem.value = null;
  form.reset();
  form.clearErrors();
  isDialogOpen.value = true;
};

const openEditModal = (item: PercentageType) => {
  editingItem.value = item;
  form.name = item.name;
  form.type = item.type;
  form.value = item.value;
  form.clearErrors();
  isDialogOpen.value = true;
};

const submitForm = () => {
  if (editingItem.value) {
    form.put(superAdmin.allocation.update(editingItem.value.id).url, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
        toast.success('Allocation updated successfully!');
      },
    });
  } else {
    // Create
    form.post(superAdmin.allocation.store().url, {
      onSuccess: () => {
        isDialogOpen.value = false;
        toast.success('Allocation created successfully!');
      },
    });
  }
};
</script>

<template>
  <Head title="Allocation Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-mono text-2xl font-bold tracking-tight">
            Revenue Allocations
          </h2>
          <p class="font-mono text-muted-foreground">
            Current percentage splits and fixed fee allocations.
          </p>
        </div>
      </div>

      <!-- Grid Content -->
      <div
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
      >
        <!-- Existing Items -->
        <Card
          v-for="item in percentageTypes"
          :key="item.id"
          class="flex flex-col justify-between py-0 transition-all hover:shadow-md"
        >
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-6 pb-2"
          >
            <CardTitle class="font-mono font-medium">
              {{ item.name }}
            </CardTitle>
            <component
              :is="item.type === 'Percentage' ? Percent : PhilippinePeso"
              class="h-5 w-5 text-muted-foreground"
            />
          </CardHeader>
          <CardContent class="py-2">
            <div class="font-mono text-2xl font-bold">
              {{ item.type === 'PHP' ? '₱' : '' }}{{ item.value
              }}{{ item.type === 'Percentage' ? '%' : '' }}
            </div>
            <p class="font-mono text-xs text-muted-foreground capitalize">
              Type: {{ item.type }}
            </p>
          </CardContent>
          <CardFooter
            class="flex items-center justify-end gap-2 border-t bg-muted/30 pb-4"
          >
            <Button
              variant="ghost"
              size="icon"
              class="h-8 w-8"
              @click="openEditModal(item)"
            >
              <Pencil class="h-6 w-6 text-blue-600" />
            </Button>
          </CardFooter>
        </Card>

        <!-- Add New Item Card (Last Card) -->
        <button
          @click="openCreateModal"
          class="group relative flex h-full min-h-[180px] w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/25 transition-all hover:border-primary hover:bg-muted/50 focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-full bg-muted transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
          >
            <Plus class="h-6 w-6" />
          </div>
          <span
            class="mt-4 text-sm font-medium text-muted-foreground group-hover:text-primary"
            >Create Allocation</span
          >
        </button>
      </div>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>{{
            editingItem ? 'Edit Allocation' : 'Create New Allocation'
          }}</DialogTitle>
          <DialogDescription>
            {{
              editingItem
                ? `This will affect the splits of revenue.`
                : `This will add a new split of revenue to the system.`
            }}
            <span class="text-red-500">
              This new computation will be applied to the next earnings record,
              previous records will stay in previous computation.
            </span>
            <span> Make sure you know what you're doing. </span>
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitForm" class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="e.g., Marketing Fund"
              :disabled="editingItem"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <span v-if="form.errors.name" class="text-xs text-red-500">{{
              form.errors.name
            }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
              <Label htmlFor="type">Type</Label>
              <Select v-model="form.type" :default-value="form.type">
                <SelectTrigger>
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="Percentage">Percentage (%)</SelectItem>
                  <SelectItem value="PHP">Fixed (PHP)</SelectItem>
                </SelectContent>
              </Select>
              <span v-if="form.errors.type" class="text-xs text-red-500">{{
                form.errors.type
              }}</span>
            </div>

            <div class="grid gap-2">
              <Label htmlFor="value">Value</Label>
              <div class="relative">
                <Input
                  id="value"
                  type="number"
                  step="0.01"
                  placeholder="e.g., 1.00"
                  v-model="form.value"
                  :class="{ 'border-red-500': form.errors.value }"
                />
              </div>
              <span v-if="form.errors.value" class="text-xs text-red-500">{{
                form.errors.value
              }}</span>
            </div>
          </div>
        </form>

        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false"
            >Cancel</Button
          >
          <Button type="submit" @click="submitForm" :disabled="form.processing">
            {{ editingItem ? 'Save Changes' : 'Create Allocation' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
