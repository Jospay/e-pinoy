<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// --- Types ---
interface VehicleType {
  id: number;
  name: string;
}

defineProps<{
  percentageTypes: VehicleType[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Vehicle Type Management',
    href: superAdmin.vehicleType.index().url,
  },
];

// --- State ---
const isDialogOpen = ref(false);
const editingItem = ref<VehicleType | null>(null);

// --- Form ---
const form = useForm({
  name: '',
});

// --- Actions ---
const openCreateModal = () => {
  editingItem.value = null;
  form.reset();
  form.clearErrors();
  isDialogOpen.value = true;
};

const openEditModal = (item: VehicleType) => {
  editingItem.value = item;
  form.name = item.name;
  form.clearErrors();
  isDialogOpen.value = true;
};

const submitForm = () => {
  if (editingItem.value) {
    form.put(superAdmin.vehicleType.update(editingItem.value.id).url, {
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
        toast.success('Vehicle Type updated successfully!');
      },
    });
  } else {
    form.post(superAdmin.vehicleType.store().url, {
      onSuccess: () => {
        isDialogOpen.value = false;
        toast.success('Vehicle Type created successfully!');
      },
    });
  }
};
</script>

<template>
  <Head title="Vehicle Type Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h2 class="font-mono text-2xl font-bold tracking-tight">
            Vehicle Types Management
          </h2>
          <p class="font-mono text-muted-foreground">
            Manage and add different vehicle types.
          </p>
        </div>
      </div>

      <!-- Grid -->
      <div
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4"
      >
        <!-- Vehicle Cards -->
        <Card
          v-for="item in percentageTypes"
          :key="item.id"
          class="flex flex-col justify-between pb-0 transition-all hover:shadow-md"
        >
          <CardHeader
            class="flex items-center justify-between border border-red-500 pt-3"
          >
            <CardTitle class="font-mono text-xl font-extrabold">
              {{ item.name }}
            </CardTitle>
          </CardHeader>

          <CardFooter
            class="flex items-center justify-end border-t bg-muted/30"
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

        <!-- Add New -->
        <button
          @click="openCreateModal"
          class="group relative flex min-h-[180px] w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/25 transition-all hover:border-primary hover:bg-muted/50"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-full bg-muted transition-colors group-hover:bg-primary group-hover:text-primary-foreground"
          >
            <Plus class="h-6 w-6" />
          </div>
          <span class="mt-4 text-sm font-medium text-muted-foreground">
            Create Vehicle Type
          </span>
        </button>
      </div>
    </div>

    <!-- Dialog -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>
            {{ editingItem ? 'Edit Vehicle Type' : 'Create New Vehicle Type' }}
          </DialogTitle>
          <DialogDescription>
            Add or update a vehicle type for registration in the system.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitForm" class="grid gap-4 py-4">
          <div class="grid gap-2">
            <Label htmlFor="name">Vehicle Type Name</Label>
            <Input
              id="name"
              v-model="form.name"
              placeholder="e.g., Bus, Taxi, Tricycle"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <span v-if="form.errors.name" class="text-xs text-red-500">
              {{ form.errors.name }}
            </span>
          </div>
        </form>

        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false">
            Cancel
          </Button>
          <Button type="submit" @click="submitForm" :disabled="form.processing">
            {{ editingItem ? 'Save Changes' : 'Create Vehicle Type' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
