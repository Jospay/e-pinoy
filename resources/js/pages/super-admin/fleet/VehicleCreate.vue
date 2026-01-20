<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
defineProps<{
  franchises: { id: number; name: string }[];
}>();

const form = useForm({
  franchise_id: null as number | null,
  plate_number: '',
  vin: '',
  brand: '',
  model: '',
  year: '',
  color: '',
  or_cr: null as File | null, // Added for file upload
});

// Handle file selection
const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.or_cr = target.files[0];
    form.errors.or_cr = '';
  }
};

// Clear file selection
const clearFile = () => {
  form.or_cr = null;
  const input = document.getElementById('or_cr_input') as HTMLInputElement;
  if (input) input.value = '';
};

const disableSubmit = computed(() => {
  const areDetailsComplete =
    !!form.franchise_id &&
    !!form.plate_number &&
    !!form.vin &&
    !!form.brand &&
    !!form.model &&
    !!form.year &&
    !!form.color &&
    !!form.or_cr; // Added or_cr to validation

  return !areDetailsComplete;
});

const submit = () => {
  form.post(superAdmin.vehicle.store().url, {
    forceFormData: true, // Necessary for file uploads
    onSuccess: () => {
      form.reset();
      toast.success('Vehicle created successfully!');
    },
  });
};

const breadcrumbs = [
  { title: 'Vehicle Management', href: superAdmin.vehicle.index().url },
  {
    title: 'Create Vehicle',
    href: superAdmin.vehicle.create().url,
  },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-3xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">Create New Vehicle</h2>

      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Select Franchise</Label>

            <Select v-model="form.franchise_id">
              <SelectTrigger
                :class="{
                  'border-red-500': form.errors.franchise_id,
                }"
              >
                <SelectValue placeholder="Select..." />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="item in franchises"
                  :key="item.id"
                  :value="String(item.id)"
                >
                  {{ item.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.franchise_id" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Plate Number</Label>
            <Input
              v-model="form.plate_number"
              placeholder="e.g., ABC123"
              :class="{ 'border-red-500': form.errors.plate_number }"
              @change="form.errors.plate_number = ''"
            />
            <InputError :message="form.errors.plate_number" />
          </div>

          <div class="space-y-2">
            <Label>Vehicle Identification Number</Label>
            <Input
              v-model="form.vin"
              placeholder="e.g., 1A2B3C4D5E6F7G8H9"
              :class="{ 'border-red-500': form.errors.vin }"
              @change="form.errors.vin = ''"
            />
            <InputError :message="form.errors.vin" />
          </div>
        </div>

        <div class="my-4 border-t" />

        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Brand</Label>
              <Input
                v-model="form.brand"
                placeholder="e.g., Toyota"
                :class="{ 'border-red-500': form.errors.brand }"
                @change="form.errors.brand = ''"
              />
              <InputError :message="form.errors.brand" />
            </div>
            <div class="space-y-2">
              <Label>Model</Label>
              <Input
                v-model="form.model"
                placeholder="e.g., Corolla"
                :class="{ 'border-red-500': form.errors.model }"
                @change="form.errors.model = ''"
              />
              <InputError :message="form.errors.model" />
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col space-y-2">
              <Label>Year</Label>
              <Input
                type="number"
                v-model="form.year"
                placeholder="e.g., 2022"
                :class="{ 'border-red-500': form.errors.year }"
                @change="form.errors.year = ''"
              />
              <InputError :message="form.errors.year" />
            </div>
            <div class="flex flex-col space-y-2">
              <Label>Color</Label>
              <Input
                v-model="form.color"
                placeholder="e.g., Black"
                :class="{ 'border-red-500': form.errors.color }"
                @change="form.errors.color = ''"
              />
              <InputError :message="form.errors.color" />
            </div>
          </div>

          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <Label class="font-bold">OR-CR Document</Label>
              <button
                v-if="form.or_cr"
                type="button"
                @click="clearFile"
                class="text-xs font-medium text-red-500 hover:text-red-700"
              >
                Clear Selection
              </button>
            </div>

            <Input
              id="or_cr_input"
              type="file"
              accept=".jpg,.jpeg,.png,.pdf"
              @change="handleFileChange"
              :class="{ 'border-red-500': form.errors.or_cr }"
            />
            <p class="text-xs text-muted-foreground">
              Upload a scan or photo of the OR-CR (PDF, JPG, PNG).
            </p>
            <InputError :message="form.errors.or_cr" />
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="form.reset()"
            >Reset</Button
          >
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Vehicle' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
