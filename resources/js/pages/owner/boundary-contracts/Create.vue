<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { toast } from 'vue-sonner';

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
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';

interface VehicleType {
  id: number;
  name: string;
}

interface Driver {
  id: number;
  username: string;
  branch_id: number | null;
  branch_name: string | null;
  assignment_name: string;
  prangkisa_attachment: string | null;
  vehicle_types: VehicleType[];
}

interface Terminal {
  id: number;
  name: string;
  branch_id: number | null;
}

interface VehicleRate {
  vehicle_type_id: number | '';
  amount: string;
}

interface Vehicle {
  id: number;
  vehicle_type_id: number;
  branch_id: number | null;
  label: string;
}

interface BoundaryForm {
  driver_id: string;
  vehicle_id: string;
  terminal_id: string;
  prangkisa_attachment: File | null;
  name: string;
  coverage_area: string;
  contract_terms: string;
  renewal_terms: string;
  start_date: string;
  end_date: string;
  vehicle_rates: VehicleRate[];
}

interface Props {
  drivers: Driver[];
  vehicles: Vehicle[];
  terminals: Terminal[];
}

const { drivers, vehicles, terminals } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Boundary Contract', href: owner.boundaryContracts.index().url },
  {
    title: 'Create Boundary Contract',
    href: owner.boundaryContracts.create().url,
  },
];

const form = useForm<BoundaryForm>({
  driver_id: '',
  vehicle_id: '',
  terminal_id: '',
  prangkisa_attachment: null,
  name: '',
  coverage_area: '',
  contract_terms: '',
  renewal_terms: '',
  start_date: '',
  end_date: '',
  vehicle_rates: [{ vehicle_type_id: '', amount: '' }],
});

const selectedDriver = computed(() =>
  drivers.find((d) => d.id.toString() === form.driver_id),
);

const isTricycle = computed(() => {
  return (
    selectedDriver.value?.vehicle_types?.[0]?.name?.toLowerCase() === 'tricycle'
  );
});

// Filters terminals: shows only same branch terminals if driver has a branch, or main franchise (null) terminals if driver is direct
const filteredTerminals = computed(() => {
  if (!selectedDriver.value) return [];

  const branchId = selectedDriver.value.branch_id;

  return terminals.filter((terminal) => {
    if (branchId !== null && branchId !== undefined) {
      return terminal.branch_id === branchId;
    }
    return terminal.branch_id === null || terminal.branch_id === undefined;
  });
});

// Filter vehicles based on vehicle type and operational branch structure
const filteredVehicles = computed(() => {
  if (!selectedDriver.value) return [];

  const vehicleTypeId = selectedDriver.value.vehicle_types[0]?.id;

  return vehicles.filter((vehicle) => {
    const sameVehicleType = vehicle.vehicle_type_id === vehicleTypeId;

    const sameBranch = selectedDriver.value?.branch_id
      ? vehicle.branch_id === selectedDriver.value.branch_id
      : vehicle.branch_id === null;

    return sameVehicleType && sameBranch;
  });
});

// Watch driver selection to auto-fill vehicle type id and clear conditional elements
watch(
  () => form.driver_id,
  (driverId) => {
    form.vehicle_id = '';
    form.terminal_id = '';
    form.prangkisa_attachment = null;

    if (!driverId) return;

    if (selectedDriver.value && selectedDriver.value.vehicle_types.length > 0) {
      form.vehicle_rates[0].vehicle_type_id =
        selectedDriver.value.vehicle_types[0].id;
    }
  },
);

const disableSubmit = computed(() => {
  const rate = form.vehicle_rates[0];
  return !(
    form.driver_id &&
    form.vehicle_id &&
    form.name &&
    form.start_date &&
    rate.vehicle_type_id &&
    rate.amount
  );
});

const submit = () => {
  form.post(owner.boundaryContracts.store().url, {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      toast.success('Boundary contract created successfully!');
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0] as string;
      toast.error(firstError);
    },
  });
};
</script>

<template>
  <Head title="Create Boundary Contract" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-4xl rounded-xl border bg-white p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">
        Create New Boundary Contract
      </h2>

      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid grid-cols-1 gap-6">
          <div class="grid gap-2">
            <Label>Contract Name</Label>
            <Input
              v-model="form.name"
              placeholder="e.g. Daily Boundary Agreement"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <InputError :message="form.errors.name" />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label>Driver Assignment</Label>
            <Select v-model="form.driver_id" :disabled="drivers.length === 0">
              <SelectTrigger
                :class="{ 'border-red-500': form.errors.driver_id }"
              >
                <SelectValue
                  :placeholder="
                    drivers.length > 0
                      ? 'Select Approved Driver'
                      : 'No drivers available'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="driver in drivers"
                  :key="driver.id"
                  :value="driver.id.toString()"
                >
                  {{ driver.username }}
                  <span class="ml-1 text-xs text-muted-foreground">
                    ({{
                      driver.branch_id
                        ? driver.assignment_name
                        : 'Main Franchise'
                    }})
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.driver_id" />
          </div>

          <div class="grid gap-2">
            <Label>Vehicle Type</Label>
            <Input
              :value="selectedDriver?.vehicle_types?.[0]?.name || 'N/A'"
              disabled
              class="bg-gray-50"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label>Vehicle (Plate - Brand Model)</Label>
            <Select
              v-model="form.vehicle_id"
              :disabled="!form.driver_id || filteredVehicles.length === 0"
            >
              <SelectTrigger
                :class="{ 'border-red-500': form.errors.vehicle_id }"
              >
                <SelectValue
                  :placeholder="
                    form.driver_id
                      ? filteredVehicles.length > 0
                        ? 'Select Vehicle'
                        : 'No matching vehicles found'
                      : 'Select a driver first'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="vehicle in filteredVehicles"
                  :key="vehicle.id"
                  :value="vehicle.id.toString()"
                >
                  {{ vehicle.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.vehicle_id" />
          </div>

          <div class="grid gap-2">
            <Label>Daily Rate (PHP)</Label>
            <Input
              v-model="form.vehicle_rates[0].amount"
              type="number"
              placeholder="0.00"
              :class="{
                'border-red-500': form.errors['vehicle_rates.0.amount'],
              }"
            />
            <InputError :message="form.errors['vehicle_rates.0.amount']" />
          </div>
        </div>

        <div v-if="isTricycle" class="grid gap-2">
          <Label>Terminal</Label>
          <Select
            v-model="form.terminal_id"
            :disabled="filteredTerminals.length === 0"
          >
            <SelectTrigger
              :class="{ 'border-red-500': form.errors.terminal_id }"
            >
              <SelectValue
                :placeholder="
                  filteredTerminals.length > 0
                    ? 'Select Terminal'
                    : 'No terminals found for this assignment'
                "
              />
            </SelectTrigger>
            <SelectContent>
              <SelectItem
                v-for="terminal in filteredTerminals"
                :key="terminal.id"
                :value="terminal.id.toString()"
              >
                {{ terminal.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <InputError :message="form.errors.terminal_id" />
        </div>

        <div
          v-if="isTricycle && selectedDriver?.prangkisa_attachment"
          class="grid gap-2"
        >
          <Label>Current Prangkisa Attachment</Label>
          <a
            :href="selectedDriver.prangkisa_attachment"
            target="_blank"
            class="text-sm text-blue-600 underline"
          >
            View Current Attachment
          </a>
        </div>

        <div v-if="isTricycle" class="grid gap-2">
          <Label>Upload Prangkisa Attachment</Label>
          <Input
            type="file"
            accept=".pdf,.jpg,.jpeg,.png"
            @change="
              form.prangkisa_attachment =
                ($event.target as HTMLInputElement).files?.[0] || null
            "
          />
          <InputError :message="form.errors.prangkisa_attachment" />
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label>Start Date</Label>
            <DatePicker v-model="form.start_date" />
            <InputError :message="form.errors.start_date" />
          </div>
          <div class="grid gap-2">
            <Label>End Date (Optional)</Label>
            <DatePicker v-model="form.end_date" :min-date="form.start_date" />
            <InputError :message="form.errors.end_date" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label>Coverage Area</Label>
          <Textarea
            v-model="form.coverage_area"
            placeholder="Specify operational boundaries..."
          />
        </div>

        <div class="grid gap-2">
          <Label>Contract Terms</Label>
          <Textarea
            v-model="form.contract_terms"
            class="h-24"
            placeholder="General terms and conditions..."
          />
        </div>

        <div class="grid gap-2">
          <Label>Renewal Terms</Label>
          <Textarea
            v-model="form.renewal_terms"
            placeholder="Terms for extending the contract..."
          />
        </div>

        <div class="flex justify-end gap-4 border-t pt-6">
          <Button type="button" variant="outline" @click="form.reset()">
            Reset
          </Button>
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Contract' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
