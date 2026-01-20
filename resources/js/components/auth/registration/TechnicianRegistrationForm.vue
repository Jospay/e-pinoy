<script setup lang="ts">
import { useAddress } from '@/composables/useAddress';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';
import { File } from 'lucide-vue-next';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import MultiStepFooter from './step/MultiStepFooter.vue';
import Step1Personal from './step/Step1Personal.vue';
import Step2Address from './step/Step2Address.vue';
import Step4Account from './step/Step4Account.vue';
import Step5Uploads from './step/Step5Uploads.vue';
import Step6Security from './step/Step6Security.vue';

defineProps<{
  genderOptions: { value: string; label: string }[];
  expertise: { value: string; label: string }[];
  idTypes: { value: string; label: string }[];

  userType: {
    encrypted_id: string;
    name: string;
  };
}>();

const stepTitles: Record<number, string> = {
  1: 'Basic Information',
  2: 'Home Address',
  3: 'Professional Details',
  4: 'Uploads',
  5: 'Account Security',
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

// --- Step 1 Personal State & Config ---
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

// --- Step 3 Professional Details State & Config ---
const selectedExpertise = ref('');
const identityStep3Show = {
  licenseNumber: false,
  licenseExpiry: false,
  validIdType: false,
  validIdUpload: false,
  validIdNumber: false,
};
const uploadStep3Show = {
  nbiClearance: false,
  selfiePicture: false,
  cvAttachment: false,
  dtiCertificate: false,
  mayorPermit: false,
  proofOfCapital: false,
};

// --- Step 4 Uploads State & Config ---
const selectedIdType = ref('');
const validIdFront = ref<File | null>(null);
const validIdBack = ref<File | null>(null);
const identityStep4Show = {
  licenseNumber: false,
  licenseExpiry: false,
  expertise: false,
  yearExperience: false,
};
const uploadStep4Show = {
  prcCertificate: false,
  dtiCertificate: false,
  mayorPermit: false,
  proofOfCapital: false,
  nbiClearance: false,
  selfiePicture: false,
  professionalLicense: false,
};

// --- Step 5 Account Security State & Config ---
const securityStep5Labels = {
  terms1: 'I Agree to the Franchise Terms and Boundary Policy',
  terms2: 'I confirm all details are true and valid',
};

// --- Multi-Step Form State & Config ---
const currentStep = ref(1);
const totalSteps = 5;
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
  expertise: 3,
  year_experience: 3,
  certificate_prc_no: 3,
  professional_license: 3,
  valid_id_type: 4,
  front_valid_id_picture: 4,
  back_valid_id_picture: 4,
  valid_id_number: 4,
  cv_attachment: 4,
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
    text-overlay="SIGN UP AND ENJOY FAST, SAFE, AND ECO-FRIENDLY RIDES WITH ERP E-TAXI."
    title-registration="Technician Registration"
    :go-back-home-button="true"
    :user-type-name="userType.name"
  >
    <Head title="Technician Registration" />

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
      <h1 class="text-center text-2xl font-black text-auth-blue">
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

      <!-- Step 2: Address Details -->
      <div v-show="currentStep === 2" class="space-y-4" data-step="2">
        <Step2Address
          :errors="errors"
          :address-data="address"
          :field-names="addressFields"
          :labels="addressLabels"
          @change="handleStepChange"
        />
      </div>

      <!-- Step 3: Professional Details -->
      <div v-show="currentStep === 3" class="space-y-4" data-step="3">
        <Step4Account
          :errors="errors"
          :expertise="expertise"
          :show-fields="identityStep3Show"
          v-model:selected-expertise="selectedExpertise"
        />
        <Step5Uploads :errors="errors" :show-fields="uploadStep3Show" />
      </div>

      <!-- Step 4: Uploads -->
      <div v-show="currentStep === 4" class="space-y-4" data-step="4">
        <Step4Account
          :errors="errors"
          :id-types="idTypes"
          :show-fields="identityStep4Show"
          v-model:selectedIdType="selectedIdType"
          v-model:validIdFront="validIdFront"
          v-model:validIdBack="validIdBack"
        />
        <Step5Uploads :errors="errors" :show-fields="uploadStep4Show" />
      </div>

      <!-- Step 5: Security -->
      <div v-show="currentStep === 5" class="space-y-4" data-step="5">
        <Step6Security
          :errors="errors"
          :labels="securityStep5Labels"
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
