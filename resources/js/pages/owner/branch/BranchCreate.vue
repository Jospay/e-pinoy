<script setup lang="ts">
import StepPersonal from '@/components/auth/registration/step/Step1Personal.vue';
import StepAddress from '@/components/auth/registration/step/Step2Address.vue';
import StepAccount from '@/components/auth/registration/step/Step4Account.vue';
import StepUpload from '@/components/auth/registration/step/Step5Uploads.vue';
import StepSecurity from '@/components/auth/registration/step/Step6Security.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { useAddress } from '@/composables/useAddress';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { useForm } from '@inertiajs/vue3';
import {
  CircleDollarSignIcon,
  IdCardIcon,
  VenusAndMarsIcon,
} from 'lucide-vue-next';
import { computed, reactive, watchEffect } from 'vue';
import { toast } from 'vue-sonner';

// Props passed from Controller
defineProps<{
  genderOptions: Array<{ value: string; label: string }>;
  idTypeOptions: Array<{ value: string; label: string }>;
}>();

// 1. Initialize Address Logic for Branch
const branchAddress = reactive(useAddress());

// 2. Initialize Address Logic for Manager
const managerAddress = reactive(useAddress());

const form = useForm({
  // Branch Details
  name: '',
  email: '',
  phone: '',
  address: '',
  region: '',
  province: '',
  city: '',
  barangay: '',
  postal_code: '',

  // Files
  dti_certificate: null as File | null,
  mayor_permit: null as File | null,
  proof_capital: null as File | null,

  // Manager Toggle
  has_manager: false,

  // Manager Details
  manager: {
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    gender: '',
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
  },
});

const disableSubmit = computed(() => {
  // Helper to check required fields except province
  const checkRequired = (obj: Record<string, any>, exclude: string[] = []) => {
    return Object.entries(obj).every(([key, value]) => {
      if (exclude.includes(key)) return true; // skip excluded fields
      return value !== '' && value !== null; // must have value
    });
  };

  // Branch required fields (exclude province)
  const branchValid = checkRequired(
    {
      name: form.name,
      email: form.email,
      phone: form.phone,
      address: form.address,
      region: form.region,
      province: form.province, // excluded
      city: form.city,
      barangay: form.barangay,
      postal_code: form.postal_code,
      dti_certificate: form.dti_certificate,
      mayor_permit: form.mayor_permit,
      proof_capital: form.proof_capital,
    },
    ['province'],
  );

  // Manager required fields (exclude province)
  const managerValid = checkRequired(
    {
      name: form.manager.name,
      email: form.manager.email,
      phone: form.manager.phone,
      password: form.manager.password,
      password_confirmation: form.manager.password_confirmation,
      gender: form.manager.gender,
      address: form.manager.address,
      region: form.manager.region,
      province: form.manager.province, // excluded
      city: form.manager.city,
      barangay: form.manager.barangay,
      postal_code: form.manager.postal_code,
      valid_id_type: form.manager.valid_id_type,
      valid_id_number: form.manager.valid_id_number,
      front_valid_id_picture: form.manager.front_valid_id_picture,
      back_valid_id_picture: form.manager.back_valid_id_picture,
    },
    ['province'],
  );

  // Final rule
  return !(branchValid && (!form.has_manager || managerValid));
});

// Configuration for Branch Details Component
const branchDetailFields = {
  franchiseName: 'name',
};
const branchDetailLabels = {
  franchiseName: 'Business / Branch Name',
  email: 'Email Address (Branch)',
  phone: 'Phone Number (Branch)',
};
const branchDetailShow = {
  name: false,
  gender: false,
  birthday: false,
};

// Configuration for Branch Uploads Component
const branchUploadLabels = {
  proofOfCapital: 'Proof of Capital or Branch Agreement',
};
const branchUploadShow = {
  nbiClearance: false,
  selfiePicture: false,
  prcCertificate: false,
  professionalLicense: false,
  cvAttachment: false,
};

// Configuration for Branch Address Component
const branchAddressFields = {
  region: 'region',
  province: 'province',
  city: 'city',
  barangay: 'barangay',
  postalCode: 'postal_code',
  address: 'address',
};
const branchAddressLabels = {
  region: 'Region',
  province: 'Province',
  city: 'City',
  barangay: 'Barangay',
  postalCode: 'Postal Code',
  address: 'Street Address',
};

// Configuration for Manager Details Component
const managerDetailFields = {
  name: 'manager.name',
  email: 'manager.email',
  phone: 'manager.phone',
};
const managerDetailLabels = {
  name: 'Full Name (Manager)',
  email: 'Email Address (Manager)',
  phone: 'Phone Number (Manager)',
};
const managerDetailShow = {
  gender: false,
  birthday: false,
  franchiseName: false,
};

// Configuration for Manager Security Component
const managerSecurityFields = {
  password: 'manager.password',
  passwordConfirmation: 'manager.password_confirmation',
};
const managerSecurityShow = {
  terms1: false,
  terms2: false,
};

// Configuration for Manager Identity Component
const managerIdentityShow = {
  licenseNumber: false,
  licenseExpiry: false,
  validIdType: false,
  validIdUpload: false,
  expertise: false,
  yearExperience: false,
};
const managerIdentityField = {
  validIdNumber: 'manager.valid_id_number',
};
const managerIdShow = {
  licenseNumber: false,
  licenseExpiry: false,
  validIdType: false,
  expertise: false,
  yearExperience: false,
  validIdNumber: false,
};
const managerIdField = {
  frontValidIdPicture: 'manager.front_valid_id_picture',
  backValidIdPicture: 'manager.back_valid_id_picture',
  validIdNumber: 'manager.valid_id_number',
};

// Configuration for Manager Address Component
const managerFieldNames = {
  region: 'manager.region',
  province: 'manager.province',
  city: 'manager.city',
  barangay: 'manager.barangay',
  postalCode: 'manager.postal_code',
  address: 'manager.address',
};
const managerLabels = {
  region: 'Manager Region',
  province: 'Manager Province',
  city: 'Manager City',
  barangay: 'Manager Barangay',
  postalCode: 'Manager Postal',
  address: 'Manager Address',
};

const submit = () => {
  form.post(owner.branch.store().url, {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      toast.success('Branch requested successfully!');
    },
  });
};

const breadcrumbs = [
  { title: 'Branch', href: owner.branch.index().url },
  { title: 'Request Branch', href: owner.branch.create().url },
];

watchEffect(() => {
  // Sync Branch Address
  form.region = branchAddress.selectedRegion;
  form.province = branchAddress.selectedProvince;
  form.city = branchAddress.selectedCity;
  form.barangay = branchAddress.selectedBarangay;

  // Sync Manager Address
  form.manager.region = managerAddress.selectedRegion;
  form.manager.province = managerAddress.selectedProvince;
  form.manager.city = managerAddress.selectedCity;
  form.manager.barangay = managerAddress.selectedBarangay;
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="m-6 max-w-6xl rounded-xl border p-6 shadow-sm">
      <h2 class="mb-6 font-mono text-2xl font-bold">Request New Branch</h2>

      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid grid-cols-[2fr_1fr] items-start gap-4">
          <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-2">
            <StepPersonal
              :errors="form.errors"
              :field-names="branchDetailFields"
              :labels="branchDetailLabels"
              :show-fields="branchDetailShow"
              v-model:franchiseName="form.name"
              v-model:email="form.email"
              v-model:phone="form.phone"
            />

            <StepAddress
              :address-data="branchAddress"
              :field-names="branchAddressFields"
              :labels="branchAddressLabels"
              :errors="form.errors"
              v-model:postal-code="form.postal_code"
              v-model:street-address="form.address"
            />
          </div>

          <div class="grid grid-cols-1 gap-5">
            <StepUpload
              :errors="form.errors"
              :labels="branchUploadLabels"
              :show-fields="branchUploadShow"
              v-model:dti-certificate="form.dti_certificate"
              v-model:mayor-permit="form.mayor_permit"
              v-model:proof-of-capital="form.proof_capital"
            />
          </div>
        </div>

        <Separator />

        <div class="space-y-4">
          <div class="flex items-center space-x-2">
            <Checkbox id="hasManager" v-model="form.has_manager" />
            <Label
              for="hasManager"
              class="cursor-pointer font-mono text-lg font-semibold"
            >
              Register a new Manager?
            </Label>
          </div>

          <div v-if="form.has_manager" class="space-y-4 rounded-lg border p-4">
            <div class="grid grid-cols-1 items-start gap-4 md:grid-cols-3">
              <StepPersonal
                :errors="form.errors"
                :show-fields="managerDetailShow"
                :field-names="managerDetailFields"
                :labels="managerDetailLabels"
                v-model:name="form.manager.name"
                v-model:email="form.manager.email"
                v-model:phone="form.manager.phone"
              />

              <div class="grid gap-2">
                <Label class="font-semibold text-auth-blue">Gender</Label>
                <div
                  class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
                >
                  <div
                    class="flex items-center justify-center bg-auth-blue px-3"
                  >
                    <VenusAndMarsIcon class="h-5 w-5 text-white" />
                  </div>
                  <Select v-model="form.manager.gender">
                    <SelectTrigger
                      class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
                      ><SelectValue placeholder="Select Gender"
                    /></SelectTrigger>
                    <SelectContent class="font-mono font-semibold">
                      <SelectItem
                        v-for="g in genderOptions"
                        :key="g.value"
                        :value="g.value"
                        class="cursor-pointer"
                        >{{ g.label }}</SelectItem
                      >
                    </SelectContent>
                  </Select>
                </div>
                <InputError :message="form.errors['manager.gender']" />
              </div>

              <StepSecurity
                :errors="form.errors"
                :show-fields="managerSecurityShow"
                :field-names="managerSecurityFields"
                v-model:password="form.manager.password"
                v-model:confirm-password="form.manager.password_confirmation"
              />
            </div>

            <div class="grid grid-cols-1 items-start gap-4 pt-4 md:grid-cols-3">
              <StepAddress
                :address-data="managerAddress"
                :field-names="managerFieldNames"
                :labels="managerLabels"
                :errors="form.errors"
                v-model:street-address="form.manager.address"
                v-model:postal-code="form.manager.postal_code"
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
                    <Select v-model="form.manager.valid_id_type">
                      <SelectTrigger
                        class="flex-1 cursor-pointer border-0 font-mono font-semibold focus-visible:ring-0"
                        ><SelectValue placeholder="Select Option"
                      /></SelectTrigger>
                      <SelectContent class="font-mono font-semibold">
                        <SelectItem
                          v-for="id in idTypeOptions"
                          :key="id.value"
                          :value="id.value"
                          class="cursor-pointer"
                          >{{ id.label }}</SelectItem
                        >
                      </SelectContent>
                    </Select>
                  </div>
                  <InputError :message="form.errors['manager.valid_id_type']" />
                </div>
                <StepAccount
                  :errors="form.errors"
                  :show-fields="managerIdentityShow"
                  :field-names="managerIdentityField"
                  v-model:valid-id-number="form.manager.valid_id_number"
                />
              </div>
              <div class="col-span-1 grid items-start gap-4">
                <StepAccount
                  :errors="form.errors"
                  :show-fields="managerIdShow"
                  :field-names="managerIdField"
                  v-model:valid-id-front="form.manager.front_valid_id_picture"
                  v-model:valid-id-back="form.manager.back_valid_id_picture"
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
            {{ form.processing ? 'Saving...' : 'Request Branch' }}
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>
