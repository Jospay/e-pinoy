<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { MapPinIcon } from 'lucide-vue-next';
import { watch } from 'vue';

// Define the shape of the address object from useAddress
interface AddressData {
  regions: { code: string; name: string }[];
  provinces: { code: string; name: string }[];
  cities: { code: string; name: string }[];
  barangays: { code: string; name: string }[];
  selectedRegion: string;
  selectedProvince: string;
  selectedCity: string;
  selectedBarangay: string;
  isNcr: boolean;
  isLoadingRegions: boolean;
  isLoadingProvinces: boolean;
  isLoadingCities: boolean;
  isLoadingBarangays: boolean;
}

// Define the shapes of the dynamic props
interface FieldNames {
  region: string;
  province: string;
  city: string;
  barangay: string;
  postalCode: string;
  address: string;
}

interface Labels {
  region: string;
  province: string;
  city: string;
  barangay: string;
  postalCode: string;
  address: string;
}

const props = defineProps<{
  addressData: AddressData;
  errors?: Record<string, string>;
  fieldNames: FieldNames;
  labels: Labels;
  // v-model props
  postalCode?: string | number;
  streetAddress?: string;
}>();

// Emit events when address data changes
const emit = defineEmits([
  'change',
  'update:postalCode',
  'update:streetAddress',
]);

// Watch for changes in address data and emit change events
watch(
  () => props.addressData.selectedRegion,
  () => {
    emit('change', new Event('change'));
  },
);

watch(
  () => props.addressData.selectedProvince,
  () => {
    emit('change', new Event('change'));
  },
);

watch(
  () => props.addressData.selectedCity,
  () => {
    emit('change', new Event('change'));
  },
);

watch(
  () => props.addressData.selectedBarangay,
  () => {
    emit('change', new Event('change'));
  },
);
</script>

<template>
  <!-- Region -->
  <div class="grid gap-2">
    <Label :for="fieldNames.region" class="font-semibold text-auth-blue">{{
      labels.region
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Select
        v-model="addressData.selectedRegion"
        :name="fieldNames.region"
        required
        @update:modelValue="$emit('change', $event)"
      >
        <SelectTrigger
          class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
          :disabled="addressData.isLoadingRegions"
        >
          <SelectValue placeholder="Select Region" />
          <Spinner
            v-if="addressData.isLoadingRegions"
            class="ml-auto h-4 w-4"
          />
        </SelectTrigger>
        <SelectContent class="font-mono font-semibold">
          <SelectItem
            v-for="r in addressData.regions"
            :key="r.code"
            :value="r.name"
            class="cursor-pointer"
          >
            {{ r.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
    <InputError :message="errors?.[fieldNames.region]" />
  </div>

  <!-- Province -->
  <div
    v-if="!addressData.isNcr && addressData.provinces.length"
    class="grid gap-2"
  >
    <Label :for="fieldNames.province" class="font-semibold text-auth-blue">{{
      labels.province
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Select
        v-model="addressData.selectedProvince"
        :name="fieldNames.province"
        required
        @update:modelValue="$emit('change', $event)"
      >
        <SelectTrigger
          class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
          :disabled="
            addressData.isLoadingProvinces || !addressData.selectedRegion
          "
        >
          <SelectValue placeholder="Select Province" />
          <Spinner
            v-if="addressData.isLoadingProvinces"
            class="ml-auto h-4 w-4"
          />
        </SelectTrigger>
        <SelectContent class="font-mono font-semibold">
          <SelectItem
            v-for="p in addressData.provinces"
            :key="p.code"
            :value="p.name"
            class="cursor-pointer"
          >
            {{ p.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
    <InputError :message="errors?.[fieldNames.province]" />
  </div>

  <!-- Show "N/A" Province for NCR -->
  <div v-else-if="addressData.isNcr" class="grid gap-2">
    <Label :for="fieldNames.province" class="font-semibold text-auth-blue">{{
      labels.province
    }}</Label>
    <div
      class="flex w-full max-w-sm cursor-not-allowed items-center rounded-md border border-gray-300 bg-gray-50 px-3 py-2 font-mono font-semibold text-gray-500"
    >
      N/A
    </div>
  </div>

  <!-- City -->
  <div
    class="grid gap-2"
    v-if="addressData.cities.length || addressData.isLoadingCities"
  >
    <Label :for="fieldNames.city" class="font-semibold text-auth-blue">{{
      labels.city
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Select
        v-model="addressData.selectedCity"
        :name="fieldNames.city"
        required
        @update:modelValue="$emit('change', $event)"
      >
        <SelectTrigger
          class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
          :disabled="addressData.isLoadingCities"
        >
          <SelectValue placeholder="Select City / Municipality" />
          <Spinner v-if="addressData.isLoadingCities" class="ml-auto h-4 w-4" />
        </SelectTrigger>
        <SelectContent class="font-mono font-semibold">
          <SelectItem
            v-for="c in addressData.cities"
            :key="c.code"
            :value="c.name"
            class="cursor-pointer"
          >
            {{ c.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
    <InputError :message="errors?.[fieldNames.city]" />
  </div>

  <!-- Barangay -->
  <div
    class="grid gap-2"
    v-if="addressData.barangays.length || addressData.isLoadingBarangays"
  >
    <Label :for="fieldNames.barangay" class="font-semibold text-auth-blue">{{
      labels.barangay
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Select
        v-model="addressData.selectedBarangay"
        :name="fieldNames.barangay"
        required
        @update:modelValue="$emit('change', $event)"
      >
        <SelectTrigger
          class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
          :disabled="addressData.isLoadingBarangays"
        >
          <SelectValue placeholder="Select Barangay" />
          <Spinner
            v-if="addressData.isLoadingBarangays"
            class="ml-auto h-4 w-4"
          />
        </SelectTrigger>
        <SelectContent class="font-mono font-semibold">
          <SelectItem
            v-for="b in addressData.barangays"
            :key="b.code"
            :value="b.name"
            class="cursor-pointer"
          >
            {{ b.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>
    <InputError :message="errors?.[fieldNames.barangay]" />
  </div>

  <!-- Postal Code -->
  <div class="grid gap-2">
    <Label :for="fieldNames.postalCode" class="font-semibold text-auth-blue">{{
      labels.postalCode
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fieldNames.postalCode"
        type="number"
        :name="fieldNames.postalCode"
        required
        :model-value="postalCode"
        @update:model-value="emit('update:postalCode', $event)"
        :autocomplete="fieldNames.postalCode"
        placeholder="2009"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fieldNames.postalCode]" />
  </div>

  <!-- Address -->
  <div class="grid gap-2">
    <Label :for="fieldNames.address" class="font-semibold text-auth-blue">{{
      labels.address
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MapPinIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fieldNames.address"
        type="text"
        :name="fieldNames.address"
        required
        :model-value="streetAddress"
        @update:model-value="emit('update:streetAddress', $event)"
        autocomplete="address"
        placeholder="123 St. / Building Name"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fieldNames.address]" />
  </div>
</template>
