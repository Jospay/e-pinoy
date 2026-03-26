<script setup lang="ts">
import StepPersonal from '@/components/auth/registration/step/Step1Personal.vue';
import StepAddress from '@/components/auth/registration/step/Step2Address.vue';
import StepAccount from '@/components/auth/registration/step/Step4Account.vue';
import StepUpload from '@/components/auth/registration/step/Step5Uploads.vue';
import StepSecurity from '@/components/auth/registration/step/Step6Security.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import superAdmin from '@/routes/super-admin';
import { useForm } from '@inertiajs/vue3';
import { IdCardIcon } from 'lucide-vue-next';
import { computed, reactive, watchEffect } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
defineProps<{
  idTypeOptions: Array<{ value: string; label: string }>;
}>();

// 1. Initialize Address Logic for Franchise
const franchiseAddress = reactive(useAddress());

// 2. Initialize Address Logic for Owner
const ownerAddress = reactive(useAddress());

const form = useForm({
  // Franchise Details
  franchise_name: '',
  franchise_email: '',
  franchise_phone: '',
  franchise_address: '',
  franchise_region: '',
  franchise_province: '',
  franchise_city: '',
  franchise_barangay: '',
  franchise_postal_code: '',

  // Files
  dti_certificate: null as File | null,
  mayor_permit: null as File | null,
  proof_capital: null as File | null,

  // Owner Details
  username: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  address: '',
  region: '',
  province: '',
  city: '',
  barangay: '',
  postal_code: '',
  valid_id_type: '',
  valid_id_number: '',
  front_valid_id_picture: null as File | null,
  back_valid_id_picture: null as File | null,
});

const disableSubmit = computed(() => {
  // Define which keys we want to IGNORE in the validation
  const excludedKeys = ['franchise_province', 'province', 'name'];

  // 1. Explicitly type 'val' as any or unknown
  const isEmpty = (val: any) => val === '' || val === null || val === undefined;

  // 2. Cast the keys of form.data() to the specific keys of your form
  const isFormInvalid = (
    Object.keys(form.data()) as Array<keyof typeof form>
  ).some((key) => {
    // Skip if it's an excluded key
    if ((excludedKeys as readonly string[]).includes(key as string))
      return false;

    return isEmpty(form[key]);
  });

  return isFormInvalid;
});

// Configuration for Franchise Details Component
const franchiseDetailFields = {
  franchiseName: 'franchise_name',
  email: 'franchise_email',
  phone: 'franchise_phone',
};
const franchiseDetailLabels = {
  franchiseName: 'Business / Franchise Name',
  email: 'Email Address (Franchise)',
  phone: 'Phone Number (Franchise)',
};
const franchiseDetailShow = {
  name: false,
  gender: false,
  birthday: false,
  userName: false,
};

// Configuration for Franchise Uploads Component
const franchiseUploadLabels = {
  proofOfCapital: 'Proof of Capital or Franchise Agreement',
};
const franchiseUploadShow = {
  nbiClearance: false,
  selfiePicture: false,
  prcCertificate: false,
  professionalLicense: false,
  cvAttachment: false,
};

// Configuration for Franchise Address Component
const franchiseAddressFields = {
  region: 'franchise_region',
  province: 'franchise_province',
  city: 'franchise_city',
  barangay: 'franchise_barangay',
  postalCode: 'franchise_postal_code',
  address: 'franchise_address',
};
const franchiseAddressLabels = {
  region: 'Region',
  province: 'Province',
  city: 'City',
  barangay: 'Barangay',
  postalCode: 'Postal Code',
  address: 'Street Address',
};

// Configuration for Owner Details Component
const ownerDetailFields = {
  name: 'name',
  email: 'email',
  phone: 'phone',
  userName: 'username',
};
const ownerDetailLabels = {
  name: 'Full Name (Owner) (optional)',
  email: 'Email Address (Owner)',
  phone: 'Phone Number (Owner)',
  userName: 'Username (Owner)',
};
const ownerDetailShow = {
  gender: false,
  birthday: false,
  franchiseName: false,
};

// Configuration for Owner Security Component
const ownerSecurityFields = {
  password: 'password',
  passwordConfirmation: 'password_confirmation',
};
const ownerSecurityShow = {
  terms1: false,
  terms2: false,
};

// Configuration for Owner Identity Component
const ownerIdentityShow = {
  licenseNumber: false,
  licenseExpiry: false,
  validIdType: false,
  validIdUpload: false,
  expertise: false,
  yearExperience: false,
  vehicleType: false,
};
const ownerIdentityField = {
  validIdNumber: 'valid_id_number',
};
const ownerIdShow = {
  licenseNumber: false,
  licenseExpiry: false,
  validIdType: false,
  expertise: false,
  yearExperience: false,
  validIdNumber: false,
  vehicleType: false,
};
const ownerIdField = {
  frontValidIdPicture: 'front_valid_id_picture',
  backValidIdPicture: 'back_valid_id_picture',
  validIdNumber: 'valid_id_number',
};

// Configuration for Owner Address Component
const ownerFieldNames = {
  region: 'region',
  province: 'province',
  city: 'city',
  barangay: 'barangay',
  postalCode: 'postal_code',
  address: 'address',
};
const ownerLabels = {
  region: 'Owner Region',
  province: 'Owner Province',
  city: 'Owner City',
  barangay: 'Owner Barangay',
  postalCode: 'Owner Postal',
  address: 'Owner Address',
};

const submit = () => {
  form.post(superAdmin.franchise.store().url, {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      toast.success('Franchise created successfully!');
    },
  });
};

const breadcrumbs = [
  { title: 'Dashboard', href: superAdmin.dashboard.index().url },
  { title: 'Create Franchise', href: superAdmin.franchise.create().url },
];

watchEffect(() => {
  // Sync Franchise Address
  form.franchise_region = franchiseAddress.selectedRegion;
  form.franchise_province = franchiseAddress.selectedProvince;
  form.franchise_city = franchiseAddress.selectedCity;
  form.franchise_barangay = franchiseAddress.selectedBarangay;

  // Sync Owner Address
  form.region = ownerAddress.selectedRegion;
  form.province = ownerAddress.selectedProvince;
  form.city = ownerAddress.selectedCity;
  form.barangay = ownerAddress.selectedBarangay;
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-6xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">Create New Franchise</h2>

      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid grid-cols-[2fr_1fr] items-start gap-4">
          <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
            <StepPersonal
              :errors="form.errors"
              :field-names="franchiseDetailFields"
              :labels="franchiseDetailLabels"
              :show-fields="franchiseDetailShow"
              v-model:franchiseName="form.franchise_name"
              v-model:email="form.franchise_email"
              v-model:phone="form.franchise_phone"
            />

            <StepAddress
              :address-data="franchiseAddress"
              :field-names="franchiseAddressFields"
              :labels="franchiseAddressLabels"
              :errors="form.errors"
              v-model:postal-code="form.franchise_postal_code"
              v-model:street-address="form.franchise_address"
            />
          </div>

          <div class="grid grid-cols-1 gap-5">
            <StepUpload
              :errors="form.errors"
              :labels="franchiseUploadLabels"
              :show-fields="franchiseUploadShow"
              v-model:dti-certificate="form.dti_certificate"
              v-model:mayor-permit="form.mayor_permit"
              v-model:proof-of-capital="form.proof_capital"
            />
          </div>
        </div>

        <div class="space-y-4">
          <div class="space-y-4 rounded-lg border p-4">
            <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
              <StepPersonal
                :errors="form.errors"
                :show-fields="ownerDetailShow"
                :field-names="ownerDetailFields"
                :labels="ownerDetailLabels"
                v-model:name="form.name"
                v-model:userName="form.username"
                v-model:email="form.email"
                v-model:phone="form.phone"
              />

              <StepSecurity
                :errors="form.errors"
                :show-fields="ownerSecurityShow"
                :field-names="ownerSecurityFields"
                v-model:password="form.password"
                v-model:confirm-password="form.password_confirmation"
              />
            </div>

            <div class="grid grid-cols-1 items-start gap-4 pt-4 md:grid-cols-3">
              <StepAddress
                :address-data="ownerAddress"
                :field-names="ownerFieldNames"
                :labels="ownerLabels"
                :errors="form.errors"
                v-model:street-address="form.address"
                v-model:postal-code="form.postal_code"
              />
            </div>
            <div
              class="grid grid-cols-[1fr_1.5fr] items-start gap-4 border-t pt-4"
            >
              <div class="grid grid-cols-1 items-start gap-4">
                <div class="grid gap-2">
                  <Label class="font-semibold text-auth-blue"
                    >Valid ID Type</Label
                  >
                  <div
                    class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
                  >
                    <div
                      class="flex items-center justify-center bg-auth-blue px-3"
                    >
                      <IdCardIcon class="h-5 w-5 text-white" />
                    </div>
                    <Select v-model="form.valid_id_type">
                      <SelectTrigger
                        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
                        ><SelectValue placeholder="Select Option"
                      /></SelectTrigger>
                      <SelectContent class="font-mono font-semibold">
                        <SelectItem
                          v-for="id in idTypeOptions"
                          :key="id.value"
                          :value="id.value"
                          >{{ id.label }}</SelectItem
                        >
                      </SelectContent>
                    </Select>
                  </div>
                  <InputError :message="form.errors['valid_id_type']" />
                </div>
                <StepAccount
                  :errors="form.errors"
                  :show-fields="ownerIdentityShow"
                  :field-names="ownerIdentityField"
                  v-model:valid-id-number="form.valid_id_number"
                />
              </div>
              <div class="col-span-1 grid items-start gap-4">
                <StepAccount
                  :errors="form.errors"
                  :show-fields="ownerIdShow"
                  :field-names="ownerIdField"
                  v-model:valid-id-front="form.front_valid_id_picture"
                  v-model:valid-id-back="form.back_valid_id_picture"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-4">
          <Button type="button" variant="outline" @click="form.reset()"
            >Reset</Button
          >
          <Button type="submit" :disabled="form.processing || disableSubmit">
            {{ form.processing ? 'Saving...' : 'Create Franchise' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
