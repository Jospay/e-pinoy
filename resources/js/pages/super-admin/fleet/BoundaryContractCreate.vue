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
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
defineProps<{
  franchises: { id: number; name: string }[];
  branches: { id: number; name: string; franchise_id: number }[];
}>();

const contextType = ref<'franchise' | 'branch' | ''>('');
const selectedEntityId = ref<string>('');
const availableVehicleTypes = ref<{ id: number; name: string }[]>([]);
const availableDrivers = ref<{ id: number; name: string }[]>([]);
const isLoadingVehicleTypes = ref(false);
const isLoadingDrivers = ref(false);

const form = useForm({
  context_type: '' as 'franchise' | 'branch' | '',
  franchise_id: null as number | null,
  branch_id: null as number | null,
  vehicle_type_id: null as number | null,
  driver_id: null as number | null,
  name: '',
  currency: 'PHP',
  coverage_area: '',
  contract_terms: '',
  renewal_terms: '',
  start_date: '',
  end_date: '',
  amount: '',
});

const onContextChange = (val: 'franchise' | 'branch') => {
  contextType.value = val;
  form.context_type = val;
  selectedEntityId.value = '';
  form.franchise_id = null;
  form.branch_id = null;
  form.vehicle_type_id = null;
  form.driver_id = null;
  availableVehicleTypes.value = [];
  availableDrivers.value = [];
};

const onEntityChange = async (entityId: string) => {
  selectedEntityId.value = entityId;
  form.franchise_id =
    contextType.value === 'franchise' ? parseInt(entityId) : null;
  form.branch_id = contextType.value === 'branch' ? parseInt(entityId) : null;
  form.vehicle_type_id = null;
  form.driver_id = null;
  availableVehicleTypes.value = [];
  availableDrivers.value = [];

  if (!entityId) return;

  isLoadingVehicleTypes.value = true;
  try {
    const { data } = await axios.get(
      superAdmin.boundaryContract.vehicleTypes().url,
      {
        params: { type: contextType.value, id: entityId },
      },
    );
    availableVehicleTypes.value = data.vehicleTypes;
  } catch {
    toast.error('Failed to load vehicle types.');
  } finally {
    isLoadingVehicleTypes.value = false;
  }
};

const onVehicleTypeChange = async (vtId: string) => {
  form.vehicle_type_id = parseInt(vtId);
  form.driver_id = null;
  availableDrivers.value = [];

  if (!vtId || !selectedEntityId.value) return;

  isLoadingDrivers.value = true;
  try {
    const { data } = await axios.get(
      superAdmin.boundaryContract.drivers().url,
      {
        params: {
          type: contextType.value,
          id: selectedEntityId.value,
          vehicle_type_id: vtId,
        },
      },
    );
    availableDrivers.value = data.drivers;
  } catch {
    toast.error('Failed to load drivers.');
  } finally {
    isLoadingDrivers.value = false;
  }
};

const disableSubmit = computed(
  () =>
    !contextType.value ||
    !selectedEntityId.value ||
    !form.vehicle_type_id ||
    !form.driver_id ||
    !form.name ||
    !form.coverage_area ||
    !form.contract_terms ||
    !form.start_date ||
    !form.end_date ||
    !form.renewal_terms ||
    !form.amount,
);

const submit = () => {
  form.post(superAdmin.boundaryContract.store().url, {
    onSuccess: () => {
      form.reset();
      contextType.value = '';
      selectedEntityId.value = '';
      availableVehicleTypes.value = [];
      availableDrivers.value = [];
      toast.success('Boundary contract created successfully!');
    },
  });
};

const resetAll = () => {
  form.reset();
  contextType.value = '';
  selectedEntityId.value = '';
  availableVehicleTypes.value = [];
  availableDrivers.value = [];
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
            <Label>Context</Label>
            <Select
              :model-value="contextType"
              @update:model-value="
                (val) => onContextChange(val as 'franchise' | 'branch')
              "
            >
              <SelectTrigger
                ><SelectValue placeholder="Franchise or Branch?"
              /></SelectTrigger>
              <SelectContent>
                <SelectItem value="franchise">Franchise</SelectItem>
                <SelectItem value="branch">Branch</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-2">
            <Label>{{
              contextType === 'branch' ? 'Branch' : 'Franchise'
            }}</Label>
            <Select
              :model-value="selectedEntityId"
              :disabled="!contextType"
              @update:model-value="(val) => onEntityChange(val as string)"
            >
              <SelectTrigger>
                <SelectValue
                  :placeholder="
                    contextType ? 'Select...' : 'Select context first'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <template v-if="contextType === 'franchise'">
                  <SelectItem
                    v-for="f in franchises"
                    :key="f.id"
                    :value="String(f.id)"
                  >
                    {{ f.name }}
                  </SelectItem>
                </template>
                <template v-else-if="contextType === 'branch'">
                  <SelectItem
                    v-for="b in branches"
                    :key="b.id"
                    :value="String(b.id)"
                  >
                    {{ b.name }}
                  </SelectItem>
                </template>
              </SelectContent>
            </Select>
            <InputError
              :message="form.errors.franchise_id || form.errors.branch_id"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Vehicle Type</Label>
            <Select
              :model-value="
                form.vehicle_type_id ? String(form.vehicle_type_id) : ''
              "
              :disabled="!selectedEntityId || isLoadingVehicleTypes"
              @update:model-value="(val) => onVehicleTypeChange(val as string)"
            >
              <SelectTrigger>
                <SelectValue
                  :placeholder="
                    isLoadingVehicleTypes
                      ? 'Loading...'
                      : selectedEntityId
                        ? 'Select vehicle type'
                        : 'Select entity first'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <div
                  v-if="
                    availableVehicleTypes.length === 0 && !isLoadingVehicleTypes
                  "
                  class="p-2 text-sm text-gray-500"
                >
                  No active vehicle types found
                </div>
                <SelectItem
                  v-for="vt in availableVehicleTypes"
                  :key="vt.id"
                  :value="String(vt.id)"
                >
                  {{ vt.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <InputError :message="form.errors.vehicle_type_id" />
          </div>

          <div class="space-y-2">
            <Label>Assign Driver</Label>
            <Select
              :model-value="form.driver_id ? String(form.driver_id) : ''"
              :disabled="!form.vehicle_type_id || isLoadingDrivers"
              @update:model-value="
                (v) => {
                  form.driver_id = parseInt(v as string);
                  form.errors.driver_id = '';
                }
              "
            >
              <SelectTrigger
                :class="{ 'border-red-500': form.errors.driver_id }"
              >
                <SelectValue
                  :placeholder="
                    isLoadingDrivers
                      ? 'Loading...'
                      : form.vehicle_type_id
                        ? 'Select driver'
                        : 'Select vehicle type first'
                  "
                />
              </SelectTrigger>
              <SelectContent>
                <div
                  v-if="availableDrivers.length === 0 && !isLoadingDrivers"
                  class="p-2 text-sm text-gray-500"
                >
                  No available active drivers
                </div>
                <SelectItem
                  v-for="driver in availableDrivers"
                  :key="driver.id"
                  :value="String(driver.id)"
                >
                  {{ driver.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p
              v-if="
                availableDrivers.length === 0 &&
                form.vehicle_type_id &&
                !isLoadingDrivers
              "
              class="text-xs text-rose-500"
            >
              * Only active drivers without active contracts are shown.
            </p>
            <InputError :message="form.errors.driver_id" />
          </div>
        </div>
        <div class="my-4 border-t" />

        <div class="space-y-4">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Contract Name</Label>
              <Input
                v-model="form.name"
                placeholder="e.g. Standard Boundary Agreement 2024"
                :class="{ 'border-red-500': form.errors.name }"
                @change="form.errors.name = ''"
              />
              <InputError :message="form.errors.name" />
            </div>
            <div class="space-y-2">
              <Label>Amount</Label>
              <Input
                id="amount"
                type="number"
                step="0.01"
                placeholder="e.g., 1.00"
                v-model="form.amount"
                :class="{ 'border-red-500': form.errors.amount }"
                @change="form.errors.amount = ''"
              />
              <InputError :message="form.errors.amount" />
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="flex flex-col space-y-2">
              <Label>Start Date</Label>
              <DatePicker
                v-model="form.start_date"
                placeholder="Pick start date"
                :class="{ 'border-red-500': form.errors.start_date }"
                @update:model-value="form.errors.start_date = ''"
              />
              <InputError :message="form.errors.start_date" />
            </div>

            <div class="flex flex-col space-y-2">
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

          <div class="space-y-2">
            <Label>Coverage Area</Label>
            <Textarea
              v-model="form.coverage_area"
              placeholder="Define the operational area..."
              :class="{ 'border-red-500': form.errors.coverage_area }"
              @change="form.errors.coverage_area = ''"
            />
            <InputError :message="form.errors.coverage_area" />
          </div>

          <div class="space-y-2">
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

          <div class="space-y-2">
            <Label>Renewal Terms</Label>
            <Textarea
              v-model="form.renewal_terms"
              placeholder="Conditions for renewal..."
              :class="{ 'border-red-500': form.errors.renewal_terms }"
              @change="form.errors.renewal_terms = ''"
            />
            <InputError :message="form.errors.renewal_terms" />
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <Button
            type="button"
            variant="outline"
            @click="(form.reset(), (selectedEntityId = ''))"
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
