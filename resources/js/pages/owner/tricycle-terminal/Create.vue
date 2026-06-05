<script setup lang="ts">
import CoordinatePickerMap from '@/components/CoordinatePickerMap.vue';
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
import { useAddress } from '@/composables/useAddress';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { useForm } from '@inertiajs/vue3';
import { reactive, watchEffect } from 'vue';
import { toast } from 'vue-sonner';

const props = defineProps<{
  ownershipOptions: {
    value: string;
    label: string;
  }[];
}>();

const tricycleTerminalAddress = reactive(useAddress());

const form = useForm({
  ownership: 'franchise',
  name: '',
  region: '',
  province: '',
  city: '',
  barangay: '',
  street: '',
  postal_code: '',

  latitude: '',
  longitude: '',
});

const submit = () => {
  form.post(owner.tricycleToda.store().url, {
    preserveScroll: true,

    onSuccess: () => {
      resetForm();
      toast.success('Tricycle Toda Terminal requested successfully.');
    },
  });
};

const breadcrumbs = [
  {
    title: 'Tricycle Toda Terminal',
    href: owner.tricycleToda.index().url,
  },
  {
    title: 'Request Tricycle Toda',
    href: owner.tricycleToda.create().url,
  },
];

watchEffect(() => {
  // Sync Tricycle Terminal Address
  form.region = tricycleTerminalAddress.selectedRegion;
  form.province = tricycleTerminalAddress.selectedProvince;
  form.city = tricycleTerminalAddress.selectedCity;
  form.barangay = tricycleTerminalAddress.selectedBarangay;
});

const resetForm = () => {
  form.reset();
  tricycleTerminalAddress.reset();
  form.ownership = '';
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-6xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">
        Request New Tricycle Toda
      </h2>

      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>Connect Terminal To</Label>

            <Select v-model="form.ownership">
              <SelectTrigger>
                <SelectValue placeholder="Select ownership" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem
                  v-for="option in ownershipOptions"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <InputError :message="form.errors.ownership" />
          </div>

          <div class="space-y-2">
            <Label>Terminal Name</Label>

            <Input
              v-model="form.name"
              placeholder="Enter terminal name"
              class="py-4.5"
            />

            <InputError :message="form.errors.name" />
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-semibold">Address Information</h3>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Region</Label>

              <Select v-model="tricycleTerminalAddress.selectedRegion">
                <SelectTrigger>
                  <SelectValue placeholder="Select region" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="region in tricycleTerminalAddress.regions"
                    :key="region.code"
                    :value="region.name"
                  >
                    {{ region.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.region" />
            </div>

            <div class="space-y-2">
              <Label>Province</Label>

              <Select
                v-model="tricycleTerminalAddress.selectedProvince"
                :disabled="tricycleTerminalAddress.isNcr"
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select province" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="province in tricycleTerminalAddress.provinces"
                    :key="province.code"
                    :value="province.name"
                  >
                    {{ province.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.province" />
            </div>

            <div class="space-y-2">
              <Label>City / Municipality</Label>

              <Select v-model="tricycleTerminalAddress.selectedCity">
                <SelectTrigger>
                  <SelectValue placeholder="Select city" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="city in tricycleTerminalAddress.cities"
                    :key="city.code"
                    :value="city.name"
                  >
                    {{ city.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.city" />
            </div>

            <div class="space-y-2">
              <Label>Barangay</Label>

              <Select v-model="tricycleTerminalAddress.selectedBarangay">
                <SelectTrigger>
                  <SelectValue placeholder="Select barangay" />
                </SelectTrigger>

                <SelectContent>
                  <SelectItem
                    v-for="barangay in tricycleTerminalAddress.barangays"
                    :key="barangay.code"
                    :value="barangay.name"
                  >
                    {{ barangay.name }}
                  </SelectItem>
                </SelectContent>
              </Select>

              <InputError :message="form.errors.barangay" />
            </div>

            <div class="space-y-2">
              <Label>Street</Label>

              <Input v-model="form.street" placeholder="e.g. 123 Main St" />

              <InputError :message="form.errors.street" />
            </div>

            <div class="space-y-2">
              <Label>Postal Code</Label>

              <Input v-model="form.postal_code" placeholder="e.g. 1234" />

              <InputError :message="form.errors.postal_code" />
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <h3 class="font-semibold">Pick Terminal Location</h3>

          <CoordinatePickerMap
            :latitude="form.latitude ? Number(form.latitude) : null"
            :longitude="form.longitude ? Number(form.longitude) : null"
            @locationSelected="
              ({ lat, lng }) => {
                form.latitude = String(lat);
                form.longitude = String(lng);
              }
            "
          />
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label>Latitude</Label>

              <Input
                v-model="form.latitude"
                placeholder="select coordinates"
                readonly
              />

              <InputError :message="form.errors.latitude" />
            </div>

            <div class="space-y-2">
              <Label>Longitude</Label>

              <Input
                v-model="form.longitude"
                placeholder="select coordinates"
                readonly
              />
              <InputError :message="form.errors.longitude" />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="resetForm"
            >Reset</Button
          >
          <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Saving...' : 'Request Tricycle Toda ' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
