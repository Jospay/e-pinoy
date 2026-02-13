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
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
const props = defineProps<{
  franchises: {
    id: number;
    name: string;
    vehicle_types: [{ id: number; name: string }];
  }[];
  branches: {
    id: number;
    name: string;
    franchise_id: number;
    franchise: { name: string };
  }[];
}>();

const form = useForm({
  franchise_id: null as number | null,
  branch_id: null as number | null,
  vehicle_type_id: null as number | null,
  plate_number: '',
  vehicle_type: '',
  vin: '',
  brand: '',
  model: '',
  year: '',
  color: '',
  capacity: '',
  or_cr: null as File | null,
});

const contextType = ref<'franchise' | 'branch' | ''>('');
const selectedEntityId = ref<string>('');

// Logic to determine available Vehicle Types
const availableVehicleTypes = computed(() => {
  let targetFranchiseId = null;

  if (contextType.value === 'franchise' && form.franchise_id) {
    targetFranchiseId = form.franchise_id;
  } else if (contextType.value === 'branch' && form.branch_id) {
    const branch = props.branches.find((b) => b.id === form.branch_id);
    targetFranchiseId = branch?.franchise_id;
  }

  if (!targetFranchiseId) return [];

  const franchise = props.franchises.find((f) => f.id === targetFranchiseId);
  return franchise ? franchise.vehicle_types : [];
});

// Watcher to reset vehicle_type_id when entity changes
watch([() => form.franchise_id, () => form.branch_id], () => {
  form.vehicle_type_id = null;
});

// Watcher: When Context Type changes (Franchise vs Branch)
watch(contextType, () => {
  // Reset dependent fields
  selectedEntityId.value = '';
  form.franchise_id = null;
  form.branch_id = null;
});

// Watcher: When the specific ID changes, fetch drivers
const handleEntityChange = async (newId: any) => {
  if (!newId || !contextType.value) return;

  // Clear errors
  form.errors.franchise_id = '';
  form.errors.branch_id = '';

  selectedEntityId.value = newId;

  // Set IDs
  if (contextType.value === 'franchise') {
    form.franchise_id = parseInt(newId);
    form.branch_id = null;
  } else {
    form.branch_id = parseInt(newId);
    form.franchise_id = null;
  }
};

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
  // 1. Convert IDs to strict booleans
  const hasFranchise = !!form.franchise_id;
  const hasBranch = !!form.branch_id;

  // 2. Logic: Valid only if (Franchise exists AND Branch doesn't) OR (Branch exists AND Franchise doesn't)
  const isEntitySelectionValid = hasFranchise !== hasBranch;

  // 3. Check other required fields
  const areDetailsComplete =
    !!form.plate_number &&
    !!form.vin &&
    !!form.brand &&
    !!form.model &&
    !!form.year &&
    !!form.color &&
    !!form.or_cr;

  // 4. Return TRUE to DISABLE if selection is invalid OR details are incomplete
  return !isEntitySelectionValid || !areDetailsComplete;
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
            <Label>Select Context</Label>
            <Select v-model="contextType">
              <SelectTrigger>
                <SelectValue placeholder="Select Context" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise">Franchise</SelectItem>
                <SelectItem value="branch">Branch</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label v-if="contextType === 'franchise'">Select Franchise</Label>
            <Label v-else-if="contextType === 'branch'">Select Branch</Label>
            <Label v-else class="text-gray-500">Select Entity</Label>

            <Select
              v-model="selectedEntityId"
              :disabled="!contextType"
              @update:model-value="handleEntityChange"
            >
              <SelectTrigger
                :class="{
                  'border-red-500':
                    form.errors.franchise_id || form.errors.branch_id,
                }"
              >
                <SelectValue placeholder="Select..." />
              </SelectTrigger>
              <SelectContent>
                <template v-if="contextType === 'franchise'">
                  <SelectItem
                    v-for="item in franchises"
                    :key="item.id"
                    :value="String(item.id)"
                  >
                    {{ item.name }}
                  </SelectItem>
                </template>
                <template v-if="contextType === 'branch'">
                  <SelectItem
                    v-for="item in branches"
                    :key="item.id"
                    :value="String(item.id)"
                  >
                    {{ item.name }}
                  </SelectItem>
                </template>
              </SelectContent>
            </Select>
            <span
              v-if="contextType === 'branch'"
              class="text-xs text-muted-foreground"
            >
              Franchise:
              {{
                branches.find((b) => b.id === form.branch_id)?.franchise.name
              }}
            </span>

            <InputError
              :message="form.errors.franchise_id || form.errors.branch_id"
            />
          </div>
        </div>
        <div class="-mt-3 space-y-2">
          <Label>Vehicle Type</Label>
          <Select
            v-model="form.vehicle_type_id"
            :disabled="!availableVehicleTypes.length"
          >
            <SelectTrigger
              :class="{ 'border-red-500': form.errors.vehicle_type_id }"
            >
              <SelectValue
                :placeholder="
                  availableVehicleTypes.length
                    ? 'Select Vehicle Type'
                    : 'Select a Franchise/Branch first'
                "
              />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="type in availableVehicleTypes"
                :key="type.id"
                :value="String(type.id)"
              >
                {{ type.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.vehicle_type_id" />
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

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col space-y-2">
              <Label>Capacity</Label>
              <Input
                type="number"
                v-model="form.capacity"
                placeholder="e.g., 4"
                :class="{ 'border-red-500': form.errors.capacity }"
                @change="form.errors.capacity = ''"
              />
              <InputError :message="form.errors.capacity" />
            </div>

            <div class="flex flex-col space-y-2">
              <div class="flex items-center justify-between">
                <Label>OR-CR Document</Label>
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
        </div>

        <div class="flex justify-end gap-4">
          <Button
            type="button"
            variant="outline"
            @click="
              (form.reset(),
              clearFile(),
              (selectedEntityId = ''),
              (contextType = ''))
            "
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
