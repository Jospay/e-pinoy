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
  driver_id: number | '';
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
  vehicle_rates: [
    {
      vehicle_type_id: '',
      amount: '',
    },
  ],
});

// Watch driver selection and auto-assign first vehicle type
watch(
  () => form.driver_id,
  (driverId) => {
    const driver = drivers.find((d) => d.id === Number(driverId));
    const rate = form.vehicle_rates[0];

    if (!driver || !rate || driver.vehicle_types.length === 0) {
      if (rate) rate.vehicle_type_id = '';
      return;
    }

    rate.vehicle_type_id = driver.vehicle_types[0].id;
  },
);

// Disable submit if required fields are missing
const disableSubmit = computed(() => {
  const rate = form.vehicle_rates[0];
  if (!rate) return true;

  return !(
    form.driver_id &&
    form.name &&
    form.start_date &&
    form.renewal_terms &&
    rate.vehicle_type_id &&
    rate.amount
  );
});

// Submit form
const submit = () => {
  form.post(owner.boundaryContracts.store().url, {
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
    <div class="m-6 max-w-4xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">
        Create New Boundary Contract
      </h2>

      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="driver">Driver Username</Label>
            <Select v-model="form.driver_id">
              <SelectTrigger
                :class="{ 'border-red-500': form.errors.driver_id }"
              >
                <SelectValue placeholder="Select Driver" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="driver in drivers"
                  :key="driver.id"
                  :value="driver.id"
                >
                  {{ driver.username }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.driver_id" />
          </div>

          <div class="grid gap-2">
            <Label>Contract Name</Label>
            <Input
              v-model="form.name"
              placeholder="e.g. Premium Sedan Agreement"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <InputError :message="form.errors.name" />
          </div>
        </div>

        <div class="my-2 border-t" />

        <div
          v-for="(rate, index) in form.vehicle_rates"
          :key="index"
          class="relative grid grid-cols-1 gap-6 md:grid-cols-2"
        >
          <div class="grid gap-2">
            <Label>Vehicle Type</Label>
            <Input
              :value="
                drivers.find((d) => d.id === Number(form.driver_id))
                  ?.vehicle_types?.[0]?.name || ''
              "
              placeholder="Auto assigned"
              disabled
            />
          </div>

          <div class="grid gap-2">
            <Label>Amount</Label>
            <Input v-model="rate.amount" type="number" placeholder="0.00" />
          </div>
        </div>
        <InputError :message="form.errors.vehicle_rates" />

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label>Start Date</Label>
            <DatePicker v-model="form.start_date" />
            <InputError :message="form.errors.start_date" />
          </div>
          <div class="grid gap-2">
            <Label>End Date</Label>
            <DatePicker v-model="form.end_date" :min-date="form.start_date" />
            <InputError :message="form.errors.end_date" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label>Coverage Area</Label>
          <Textarea
            v-model="form.coverage_area"
            placeholder="Define the operational area..."
          />
          <InputError :message="form.errors.coverage_area" />
        </div>

        <div class="grid gap-2">
          <Label>Contract Terms</Label>
          <Textarea
            v-model="form.contract_terms"
            class="h-24"
            placeholder="Terms and conditions..."
          />
          <InputError :message="form.errors.contract_terms" />
        </div>

        <div class="grid gap-2">
          <Label>Renewal Terms</Label>
          <Textarea
            v-model="form.renewal_terms"
            placeholder="Conditions for renewal..."
            class="h-24"
          />
          <InputError :message="form.errors.renewal_terms" />
        </div>

        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="form.reset()"
            >Reset</Button
          >
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Boundary Contract' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
