<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Clock, CreditCard, Pencil, Route, Car, Bike } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

// --- Types ---
interface RateMetricType {
  id: number;
  flag: string | number;
  per_minute: string | number;
  per_km: string | number;
}

const props = defineProps<{
  taxiMetric: RateMetricType;
  tricycleMetric: RateMetricType;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Trip Matrix',
    href: superAdmin.rateMetric.index().url,
  },
];

// --- State Management ---
const isDialogOpen = ref(false);
const editingTarget = ref<'Taxi' | 'Tricycle' | null>(null);

// --- Form Setup ---
const form = useForm({
  id: 0,
  flag: 0 as string | number,
  per_minute: 0 as string | number,
  per_km: 0 as string | number,
});

// --- Actions ---
const openEditModal = (type: 'Taxi' | 'Tricycle') => {
  editingTarget.value = type;
  const targetData = type === 'Taxi' ? props.taxiMetric : props.tricycleMetric;

  // Load selected data into form
  form.id = targetData.id;
  form.flag = targetData.flag;
  form.per_minute = targetData.per_minute;
  form.per_km = targetData.per_km;

  form.clearErrors();
  isDialogOpen.value = true;
};

const submitForm = () => {
  form.patch(superAdmin.rateMetric.update(form.id).url, {
    onSuccess: () => {
      isDialogOpen.value = false;
      toast.success(`${editingTarget.value} rates updated successfully!`);
    },
  });
};
</script>

<template>
  <Head title="Trip Matrix Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-8 p-4">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <!-- <Car class="h-6 w-6 text-blue-600" /> -->
            <h2 class="font-mono text-xl font-bold tracking-tight uppercase">
              Taxi Pricing
            </h2>
          </div>
          <Button variant="outline" size="sm" @click="openEditModal('Taxi')">
            <Pencil class="mr-1 h-4 w-4" /> Edit Taxi
          </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Flag Fall</CardTitle>
              <CreditCard class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ taxiMetric?.flag }}
              </div>
              <p class="text-xs text-muted-foreground">Base pickup rate</p>
            </CardContent>
          </Card>
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Distance Rate</CardTitle>
              <Route class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ taxiMetric?.per_km }}
              </div>
              <p class="text-xs text-muted-foreground">Per kilometer</p>
            </CardContent>
          </Card>
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Time Rate</CardTitle>
              <Clock class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ taxiMetric?.per_minute }}
              </div>
              <p class="text-xs text-muted-foreground">Per minute</p>
            </CardContent>
          </Card>
        </div>
      </div>

      <hr class="border-border" />

      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <!-- <Bike class="h-6 w-6 text-green-600" /> -->
            <h2 class="font-mono text-xl font-bold tracking-tight uppercase">
              Tricycle Pricing
            </h2>
          </div>
          <Button
            variant="outline"
            size="sm"
            @click="openEditModal('Tricycle')"
          >
            <Pencil class="mr-1 h-4 w-4" /> Edit Tricycle
          </Button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Flag Fall</CardTitle>
              <CreditCard class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ tricycleMetric?.flag }}
              </div>
              <p class="text-xs text-muted-foreground">Base pickup rate</p>
            </CardContent>
          </Card>
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Distance Rate</CardTitle>
              <Route class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ tricycleMetric?.per_km }}
              </div>
              <p class="text-xs text-muted-foreground">Per kilometer</p>
            </CardContent>
          </Card>
          <Card class="transition-all hover:shadow-md">
            <CardHeader class="flex flex-row items-center justify-between pb-2">
              <CardTitle class="font-mono text-sm">Time Rate</CardTitle>
              <Clock class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div class="font-mono text-2xl font-bold">
                ₱{{ tricycleMetric?.per_minute }}
              </div>
              <p class="text-xs text-muted-foreground">Per minute</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <Dialog v-model:open="isDialogOpen">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Update {{ editingTarget }} Pricing</DialogTitle>
          <DialogDescription>
            Modify the rates for {{ editingTarget }} trips. Changes apply
            immediately to new bookings.
          </DialogDescription>
        </DialogHeader>

        <form @submit.prevent="submitForm" class="space-y-4 py-4">
          <div class="grid gap-2">
            <Label for="flag">Flag Fall (PHP)</Label>
            <Input id="flag" type="number" step="0.01" v-model="form.flag" />
            <p v-if="form.errors.flag" class="text-xs text-red-500">
              {{ form.errors.flag }}
            </p>
          </div>
          <div class="grid gap-2">
            <Label for="per_km">Rate per Kilometer (PHP)</Label>
            <Input
              id="per_km"
              type="number"
              step="0.01"
              v-model="form.per_km"
            />
            <p v-if="form.errors.per_km" class="text-xs text-red-500">
              {{ form.errors.per_km }}
            </p>
          </div>
          <div class="grid gap-2">
            <Label for="per_minute">Rate per Minute (PHP)</Label>
            <Input
              id="per_minute"
              type="number"
              step="0.01"
              v-model="form.per_minute"
            />
            <p v-if="form.errors.per_minute" class="text-xs text-red-500">
              {{ form.errors.per_minute }}
            </p>
          </div>
        </form>

        <DialogFooter>
          <Button variant="outline" @click="isDialogOpen = false"
            >Cancel</Button
          >
          <Button type="submit" @click="submitForm" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Update Pricing' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
