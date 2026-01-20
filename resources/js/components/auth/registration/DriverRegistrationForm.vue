<script setup lang="ts">
import { useAddress } from '@/composables/useAddress';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import MultiStepFooter from './step/MultiStepFooter.vue';
import Step1Personal from './step/Step1Personal.vue';
import Step2Address from './step/Step2Address.vue';
import Step3Preferences from './step/Step3Preferences.vue';
import Step4Account from './step/Step4Account.vue';
import Step5Uploads from './step/Step5Uploads.vue';
import Step6Security from './step/Step6Security.vue';

defineProps<{
  genderOptions: { value: string; label: string }[];
  shifts: { value: string; label: string }[];
  userType: {
    encrypted_id: string;
    name: string;
  };
}>();

const stepTitles: Record<number, string> = {
  1: 'Basic Information',
  2: 'Address Details',
  3: 'Identity Details',
  4: 'Work Details',
  5: 'Uploads',
  6: 'Account Security',
};

const nextStep = () => {
  if (currentStep.value < totalSteps) {
    currentStep.value++;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
  }
};

// --- Step 1 State & Config ---
const selectedGender = ref('');
const birthday = ref('');
const personalStep1Show = {
  franchiseName: false,
};

// --- Step 2 Address State & Config ---
const address = reactive(useAddress()); // Use reactive
const addressFields = {
  region: 'region',
  province: 'province',
  city: 'city',
  barangay: 'barangay',
  postalCode: 'postal_code',
  address: 'address',
};
const addressLabels = {
  region: 'Region',
  province: 'Province',
  city: 'City / Municipality',
  barangay: 'Barangay',
  postalCode: 'Postal Code',
  address: 'Address',
};

// Step 3 Identity State & Config ---
const driverIdFront = ref<File | null>(null);
const driverIdBack = ref<File | null>(null);
const identityStep3Show = {
  validIdNumber: false,
  validIdType: false,
  expertise: false,
  yearExperience: false,
};
const identityfields = {
  frontValidIdPicture: 'front_license_picture',
  backValidIdPicture: 'back_license_picture',
};
const identitylabels = {
  validIdUpload: 'Driver License Picture',
};

// --- Step 4 (Preferences) State & Config ---
const selectedShift = ref('');
const preferencesStep4Show = {
  language: false,
  accessibility: false,
  paymentOption: false,
};

// --- Step 5 (Documents to Upload) State & Config ---
const documentsStep5Show = {
  prcCertificate: false,
  professionalLicense: false,
  cvAttachment: false,
  dtiCertificate: false,
  mayorPermit: false,
  proofOfCapital: false,
};

// --- Step 6 (Account Security) State & Config ---
const securityStep6Labels = {
  terms1: 'I Agree to the Driver Terms and Code of Conducty',
  terms2: 'I consent to GPS tracking during active trips',
};

// --- Multi-Step Form State & Config ---
const currentStep = ref(1);
const totalSteps = 6;
const terms1 = ref(false);
const terms2 = ref(false);
const canSubmit = computed(() => {
  if (currentStep.value === totalSteps) {
    return terms1.value && terms2.value;
  }
  return true;
});
const handleStepChange = () => {
  // This will trigger the validation in MultiStepFooter
  nextTick(() => {
    // Force validation update
    const event = new Event('input', { bubbles: true });
    document
      .querySelector(`[data-step="${currentStep.value}"]`)
      ?.dispatchEvent(event);
  });
};

// --- Form Errors State & Config ---
const formErrors = ref<Record<string, string>>({});
const fieldStepMap: Record<string, number> = {
  username: 1,
  name: 1,
  phone: 1,
  email: 1,
  gender: 1,
  birth_date: 1,
  address: 2,
  region: 2,
  province: 2,
  city: 2,
  barangay: 2,
  postal_code: 2,
  license_number: 3,
  license_expiry: 3,
  front_license_picture: 3,
  back_license_picture: 3,
  shift: 4,
  nbi_clearance: 5,
  selfie_picture: 5,
};

watch(
  formErrors,
  (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
      const firstErrorField = Object.keys(newErrors)[0];
      const step = fieldStepMap[firstErrorField];
      if (step) currentStep.value = step;
    }
  },
  { deep: true, immediate: true },
);
</script>

<template>
  <AuthBase
    text-overlay="PLEASE FILL OUT THE FORM TO REGISTER AS A DRIVER UNDER ERP SYSTEM FOR CAR BOUNDARY SERVICE -PHILIPPINES. YOUR FRANCHISE WILL VERIFY YOUR ACCOUNT."
    title-registration="Driver Registration"
    :go-back-home-button="true"
    :user-type-name="userType.name"
  >
    <Head title="Driver Registration" />

    <Form
      v-bind="store.form()"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <template v-if="errors">
        <!-- Keep formErrors synced -->
        <component
          :is="
            () => {
              formErrors = errors;
              return null;
            }
          "
        />
      </template>
      <input type="hidden" name="user_type_id" :value="userType.encrypted_id" />
      <!-- Step Title -->
      <h1 class="text-center text-lg font-black text-auth-blue">
        {{ stepTitles[currentStep] }}
      </h1>

      <!-- Step 1: Personal Information -->
      <div v-show="currentStep === 1" class="space-y-4" data-step="1">
        <Step1Personal
          :errors="errors"
          :gender-options="genderOptions"
          :show-fields="personalStep1Show"
          v-model:selectedGender="selectedGender"
          v-model:birthday="birthday"
        />
      </div>

      <!-- Step 2: Address -->
      <div v-show="currentStep === 2" class="space-y-4" data-step="2">
        <Step2Address
          :address-data="address"
          :errors="errors"
          :field-names="addressFields"
          :labels="addressLabels"
          @change="handleStepChange"
        />
      </div>

      <!-- Step 3: Identity -->
      <div v-show="currentStep === 3" class="space-y-4" data-step="3">
        <Step4Account
          :errors="errors"
          :show-fields="identityStep3Show"
          v-model:validIdFront="driverIdFront"
          v-model:validIdBack="driverIdBack"
          :field-names="identityfields"
          :labels="identitylabels"
        />
      </div>

      <!-- Step 4: Preferences -->
      <div v-show="currentStep === 4" class="space-y-4" data-step="4">
        <Step3Preferences
          :errors="errors"
          :shifts="shifts"
          :show-fields="preferencesStep4Show"
          v-model:selectedShift="selectedShift"
        />
      </div>

      <!-- Step 5: Uploads -->
      <div v-show="currentStep === 5" class="space-y-4" data-step="5">
        <Step5Uploads :errors="errors" :show-fields="documentsStep5Show" />
      </div>

      <!-- Step 6: Security -->
      <div v-show="currentStep === 6" class="space-y-4" data-step="6">
        <Step6Security
          :errors="errors"
          :labels="securityStep6Labels"
          v-model:terms1="terms1"
          v-model:terms2="terms2"
        />
      </div>

      <MultiStepFooter
        :current-step="currentStep"
        :total-steps="totalSteps"
        :processing="processing"
        :errors="errors"
        :can-submit="canSubmit"
        @next="nextStep"
        @prev="prevStep"
        @submit="$event.target.closest('form')?.submit()"
      />
    </Form>
  </AuthBase>
</template>
