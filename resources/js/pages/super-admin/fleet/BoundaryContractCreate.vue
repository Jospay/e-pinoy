<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
defineProps<{
  franchises: { id: number; name: string }[];
}>();

const selectedEntityId = ref<string>('');
// drivers
const availableDrivers = ref<{ id: number; name: string }[]>([]);
const isLoadingDrivers = ref(false);
// vehicles
const availableVehicles = ref<{ id: number; name: string }[]>([]);
const isLoadingVehicles = ref(false);

// Watcher: When the specific ID changes, fetch drivers
const handleEntityChange = async (newId: any) => {
  if (!newId) return;

  // Clear errors
  form.errors.franchise_id = '';

  selectedEntityId.value = newId;

  // Set IDs
  form.franchise_id = parseInt(newId);

  // Reset Selections
  form.driver_id = null;
  form.vehicle_id = null;

  // Set Loading States
  isLoadingDrivers.value = true;
  isLoadingVehicles.value = true;

  // Clear Lists
  availableDrivers.value = [];
  availableVehicles.value = [];

  try {
    const response = await axios.get(
      superAdmin.boundaryContract.resources().url,
      {
        params: {
          id: newId,
        },
      },
    );

    // Destructure response
    availableDrivers.value = response.data.drivers;
    availableVehicles.value = response.data.vehicles;
  } catch (error) {
    toast.error('Failed to load available resources.');
    console.error(error);
  } finally {
    isLoadingDrivers.value = false;
    isLoadingVehicles.value = false;
  }
};

const form = useForm({
  franchise_id: null as number | null,
  driver_id: null as string | null,
  vehicle_id: null as string | null,
  name: '',
  amount: '',
  coverage_area: '',
  contract_terms: '',
  start_date: '',
  end_date: '',
  renewal_terms: '',
});

const disableSubmit = computed(() => {
  // Check other required fields
  const areDetailsComplete =
    !!form.franchise_id &&
    !!form.vehicle_id &&
    !!form.driver_id &&
    !!form.name &&
    !!form.amount &&
    !!form.coverage_area &&
    !!form.contract_terms &&
    !!form.start_date &&
    !!form.end_date &&
    !!form.renewal_terms;

  // 4. Return TRUE to DISABLE if selection is invalid OR details are incomplete
  return !areDetailsComplete;
});

const submit = () => {
  form.post(superAdmin.boundaryContract.store().url, {
    onSuccess: () => {
      form.reset();
      toast.success('Boundary contract created successfully!');
    },
  });
};

const breadcrumbs = [
  { title: 'Boundary Contract', href: superAdmin.boundaryContract.index().url },
  {
    title: 'Create Boundary Contract',
    href: superAdmin.boundaryContract.create().url,
  },
];
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-3xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">
        Create New Boundary Contract
      </h2>

      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

          <div class="space-y-2">
            <Label>Select Franchise</Label>

            <Select v-model="selectedEntityId" @update:model-value="handleEntityChange">
              <SelectTrigger :class="{
                'border-red-500':
                  form.errors.franchise_id,
              }">
                <SelectValue placeholder="Select..." />
              </SelectTrigger>
              <SelectContent>

                <SelectItem v-for="item in franchises" :key="item.id" :value="String(item.id)">
                  {{ item.name }}
                </SelectItem>

              </SelectContent>
            </Select>
            <InputError :message="form.errors.franchise_id" />
          </div>
        </div>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Assign Driver</Label>
            <Select v-model="form.driver_id" :disabled="!form.franchise_id"
              @update:model-value="form.errors.driver_id = ''">
              <SelectTrigger :class="{
                'border-red-500': form.errors.driver_id,
              }">
                <SelectValue :placeholder="isLoadingDrivers ? 'Loading...' : 'Select Driver'
                  " />
              </SelectTrigger>
              <SelectContent>
                <div v-if="availableDrivers.length === 0" class="p-2 text-sm text-gray-500">
                  {{
                    isLoadingDrivers
                      ? 'Loading...'
                      : 'No available active drivers found'
                  }}
                </div>
                <SelectItem v-for="driver in availableDrivers" :key="driver.id" :value="String(driver.id)">
                  {{ driver.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-rose-500" v-if="
              availableDrivers.length === 0 &&
              form.franchise_id
            ">
              * Only "active" drivers without current contracts are shown.
            </p>
            <InputError :message="form.errors.driver_id" />
          </div>

          <div class="space-y-2">
            <Label>Assign Vehicle</Label>
            <Select v-model="form.vehicle_id" :disabled="!form.franchise_id"
              @update:model-value="form.errors.vehicle_id = ''">
              <SelectTrigger :class="{
                'border-red-500': form.errors.vehicle_id,
              }">
                <SelectValue :placeholder="isLoadingVehicles ? 'Loading...' : 'Select Vehicle'
                  " />
              </SelectTrigger>
              <SelectContent>
                <div v-if="availableVehicles.length === 0" class="p-2 text-sm text-gray-500">
                  {{
                    isLoadingVehicles
                      ? 'Loading...'
                      : 'No available available vehicles found'
                  }}
                </div>
                <SelectItem v-for="vehicle in availableVehicles" :key="vehicle.id" :value="String(vehicle.id)">
                  {{ vehicle.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-rose-500" v-if="
              availableVehicles.length === 0 &&
              form.franchise_id
            ">
              * Only "available" vehicles without assign drivers are shown.
            </p>
            <InputError :message="form.errors.vehicle_id" />
          </div>
        </div>
        <div class="my-4 border-t" />

        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Contract Name</Label>
              <Input v-model="form.name" placeholder="e.g. Standard Boundary Agreement 2024"
                :class="{ 'border-red-500': form.errors.name }" @change="form.errors.name = ''" />
              <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
              <Label>Amount</Label>
              <Input id="amount" type="number" step="0.01" placeholder="e.g., 1.00" v-model="form.amount"
                :class="{ 'border-red-500': form.errors.amount }" @change="form.errors.amount = ''" />
              <InputError :message="form.errors.amount" />
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col space-y-2">
              <Label>Start Date</Label>
              <DatePicker v-model="form.start_date" placeholder="Pick start date"
                :class="{ 'border-red-500': form.errors.start_date }"
                @update:model-value="form.errors.start_date = ''" />
              <InputError :message="form.errors.start_date" />
            </div>

            <div class="flex flex-col space-y-2">
              <Label>End Date</Label>
              <DatePicker v-model="form.end_date" :min-date="form.start_date" placeholder="Pick end date"
                :class="{ 'border-red-500': form.errors.end_date }" @update:model-value="form.errors.end_date = ''" />
              <InputError :message="form.errors.end_date" />
            </div>
          </div>

          <div class="space-y-2">
            <Label>Coverage Area</Label>
            <Textarea v-model="form.coverage_area" placeholder="Define the operational area..."
              :class="{ 'border-red-500': form.errors.coverage_area }" @change="form.errors.coverage_area = ''" />
            <InputError :message="form.errors.coverage_area" />
          </div>

          <div class="space-y-2">
            <Label>Contract Terms</Label>
            <Textarea v-model="form.contract_terms" class="h-24" placeholder="Terms and conditions..."
              :class="{ 'border-red-500': form.errors.contract_terms }" @change="form.errors.contract_terms = ''" />
            <InputError :message="form.errors.contract_terms" />
          </div>

          <div class="space-y-2">
            <Label>Renewal Terms</Label>
            <Textarea v-model="form.renewal_terms" placeholder="Conditions for renewal..."
              :class="{ 'border-red-500': form.errors.renewal_terms }" @change="form.errors.renewal_terms = ''" />
            <InputError :message="form.errors.renewal_terms" />
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="(form.reset(), (selectedEntityId = ''))">Reset</Button>
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Boundary Contract' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
