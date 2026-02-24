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
  vehicle_types: VehicleType[];
}
interface VehicleRate {
  vehicle_type_id: number | '';
  amount: string;
}
interface BoundaryForm {
  driver_id: string; // Changed to string for Select compatibility
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
}
const { drivers } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Boundary Contract', href: owner.boundaryContracts.index().url },
  {
    title: 'Create Boundary Contract',
    href: owner.boundaryContracts.create().url,
  },
];

const form = useForm<BoundaryForm>({
  driver_id: '',
  name: '',
  coverage_area: '',
  contract_terms: '',
  renewal_terms: '',
  start_date: '',
  end_date: '',
  vehicle_rates: [{ vehicle_type_id: '', amount: '' }],
});

// Watch driver selection to auto-fill vehicle type
watch(
  () => form.driver_id,
  (driverId) => {
    if (!driverId) return;
    const selectedDriver = drivers.find((d) => d.id.toString() === driverId);
    if (selectedDriver && selectedDriver.vehicle_types.length > 0) {
      form.vehicle_rates[0].vehicle_type_id =
        selectedDriver.vehicle_types[0].id;
    }
  },
);

const disableSubmit = computed(() => {
  const rate = form.vehicle_rates[0];
  return !(
    form.driver_id &&
    form.name &&
    form.start_date &&
    rate.vehicle_type_id &&
    rate.amount
  );
});

const submit = () => {
  form.post(owner.boundaryContracts.store().url, {
    onSuccess: () => {
      form.reset();
      toast.success('Boundary contract created successfully!');
    },
    onError: (errors) => {
      // Show the first error message clearly
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
                <template v-if="drivers.length > 0">
                  <SelectItem
                    v-for="driver in drivers"
                    :key="driver.id"
                    :value="driver.id.toString()"
                  >
                    {{ driver.username }}
                  </SelectItem>
                </template>

                <div
                  v-else
                  class="p-4 text-center text-sm text-muted-foreground"
                >
                  No approved drivers found.
                </div>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.driver_id" />
          </div>

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
            <Label>Vehicle Type</Label>
            <Input
              :value="
                drivers.find((d) => d.id.toString() === form.driver_id)
                  ?.vehicle_types?.[0]?.name || 'N/A'
              "
              placeholder="Auto assigned"
              disabled
              class="bg-gray-50"
            />
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
          <Button type="button" variant="outline" @click="form.reset()"
            >Reset</Button
          >
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Contract' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
