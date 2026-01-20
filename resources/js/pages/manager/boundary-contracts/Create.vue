<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

interface Vehicle {
  id: number;
  plate_number: string;
  brand: string;
  model: string;
}

interface Driver {
  id: number;
  username: string;
}

interface Props {
  vehicles: Vehicle[];
  drivers: Driver[];
}

const { vehicles, drivers } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Boundary Contract',
    href: manager.boundaryContracts.index().url,
  },
  {
    title: 'Create Boundary Contract',
    href: manager.boundaryContracts.create().url,
  },
];

const form = useForm({
  driver: '',
  vehicle: '',
  name: '',
  amount: '',
  coverage_area: '',
  contract_terms: '',
  start_date: '',
  end_date: '',
  renewal_terms: '',
});

const disableSubmit = computed(() => {
  const areDetailsComplete =
    !!form.driver &&
    !!form.vehicle &&
    !!form.name &&
    !!form.amount &&
    !!form.coverage_area &&
    !!form.contract_terms &&
    !!form.start_date &&
    !!form.end_date &&
    !!form.renewal_terms;

  return !areDetailsComplete;
});

const submit = () => {
  form.post(manager.boundaryContracts.store().url, {
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
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-3xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">
        Create New Boundary Contract
      </h2>

      <form @submit.prevent="submit" class="flex flex-col gap-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label for="driver">Driver Username</Label>
            <Select
              v-model="form.driver"
              @update:model-value="form.errors.driver = ''"
            >
              <SelectTrigger
                :class="{
                  'border-red-500': form.errors.driver,
                }"
              >
                <SelectValue placeholder="Select Driver" />
              </SelectTrigger>

              <SelectContent>
                <div
                  v-if="drivers.length === 0"
                  class="p-2 text-sm text-gray-500"
                >
                  <p>No available active drivers found</p>
                </div>
                <SelectItem
                  v-else
                  v-for="driver in drivers"
                  :key="driver.id"
                  :value="driver.id"
                >
                  {{ driver.username }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.driver" />
          </div>

          <div class="grid gap-2">
            <Label for="vehicle">Vehicle</Label>
            <Select
              v-model="form.vehicle"
              @update:model-value="form.errors.vehicle = ''"
            >
              <SelectTrigger
                :class="{
                  'border-red-500': form.errors.vehicle,
                }"
              >
                <SelectValue placeholder="Select Vehicle" />
              </SelectTrigger>

              <SelectContent>
                <div
                  v-if="vehicles.length === 0"
                  class="p-2 text-sm text-gray-500"
                >
                  <p>No available vehicles found</p>
                </div>
                <SelectItem
                  v-else
                  v-for="vehicle in vehicles"
                  :key="vehicle.id"
                  :value="vehicle.id"
                >
                  {{ vehicle.plate_number }} - {{ vehicle.brand }}
                  {{ vehicle.model }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.vehicle" />
          </div>
        </div>

        <div class="my-4 border-t" />

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="grid gap-2">
            <Label>Contract Name</Label>
            <Input
              id="name"
              name="name"
              v-model="form.name"
              type="text"
              required
              placeholder="e.g. Standard Boundary Agreement 2024"
              :class="{ 'border-red-500': form.errors.name }"
              @change="form.errors.name = ''"
            />
            <InputError :message="form.errors.name" />
          </div>

          <div class="grid gap-2">
            <Label>Amount</Label>
            <Input
              id="amount"
              name="amount"
              type="number"
              required
              v-model="form.amount"
              :class="{ 'border-red-500': form.errors.amount }"
              placeholder="e.g., 1.00"
            />
            <InputError :message="form.errors.amount" />
          </div>

          <div class="grid gap-2">
            <Label>Start Date</Label>
            <DatePicker
              v-model="form.start_date"
              placeholder="Pick start date"
              :class="{ 'border-red-500': form.errors.start_date }"
              @update:model-value="form.errors.start_date = ''"
            />
            <InputError :message="form.errors.start_date" />
          </div>

          <div class="grid gap-2">
            <Label>End Date</Label>
            <DatePicker
              v-model="form.end_date"
              :min-date="form.start_date"
              placeholder="Pick end date"
              :class="{ 'border-red-500': form.errors.end_date }"
              @update:model-value="form.errors.end_date = ''"
            />
            <InputError :message="form.errors.end_date" />
          </div>
        </div>

        <div class="grid gap-2">
          <Label>Coverage Area</Label>
          <Textarea
            v-model="form.coverage_area"
            placeholder="Define the operational area..."
            :class="{ 'border-red-500': form.errors.coverage_area }"
            @change="form.errors.coverage_area = ''"
          />
          <InputError :message="form.errors.coverage_area" />
        </div>

        <div class="grid gap-2">
          <Label>Contract Terms</Label>
          <Textarea
            v-model="form.contract_terms"
            class="h-24"
            placeholder="Terms and conditions..."
            :class="{ 'border-red-500': form.errors.contract_terms }"
            @change="form.errors.contract_terms = ''"
          />
          <InputError :message="form.errors.contract_terms" />
        </div>

        <div class="grid gap-2">
          <Label>Renewal Terms</Label>
          <Textarea
            v-model="form.renewal_terms"
            placeholder="Conditions for renewal..."
            :class="{ 'border-red-500': form.errors.renewal_terms }"
            @change="form.errors.renewal_terms = ''"
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
