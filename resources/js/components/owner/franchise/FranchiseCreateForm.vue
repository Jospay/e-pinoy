<script setup lang="ts">
import { useAddress } from '@/composables/useAddress';
import { store } from '@/routes/super-admin/franchise';
import { Form } from '@inertiajs/vue3';
import { computed, nextTick, reactive, ref, watch } from 'vue';
import MultiStepFooter from './step/MultiStepFooter.vue';
import Step1Personal from './step/Step1Personal.vue';
import Step2Address from './step/Step2Address.vue';
import Step4Account from './step/Step4Account.vue';
import Step5Uploads from './step/Step5Uploads.vue';
import Step6Security from './step/Step6Security.vue';

defineProps<{
  idTypes: { value: string; label: string }[];
}>();

const stepTitles: Record<number, string> = {
  1: 'Basic Information',
  2: 'Home Address',
  3: 'Franchise Details',
  5: 'Identity Verification',
  6: 'Documents to Upload',
  7: 'Account Security',
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
const personalStep1Show = {
  birthday: false,
  franchiseName: false,
  gender: false,
};

// --- Step 2 (Home Address) State & Config ---
const homeAddress = reactive(useAddress()); // Use reactive
const homeAddressFields = {
  region: 'home_region',
  province: 'home_province',
  city: 'home_city',
  barangay: 'home_barangay',
  postalCode: 'home_postal_code',
  address: 'home_address',
};
const homeAddressLabels = {
  region: 'Region (home)',
  province: 'Province (home)',
  city: 'City / Municipality (home)',
  barangay: 'Barangay (home)',
  postalCode: 'Postal Code (home)',
  address: 'Home Address',
};

// --- Step 3 (Franchise Address) State & Config ---
const franchiseAddress = reactive(useAddress()); // Use reactive
const franchiseAddressFields = {
  region: 'franchise_region',
  province: 'franchise_province',
  city: 'franchise_city',
  barangay: 'franchise_barangay',
  postalCode: 'franchise_postal_code',
  address: 'franchise_address',
};
const franchiseAddressLabels = {
  region: 'Region (franchise)',
  province: 'Province (franchise)',
  city: 'City / Municipality (franchise)',
  barangay: 'Barangay (franchise)',
  postalCode: 'Postal Code (franchise)',
  address: 'Franchise Address',
};
const personalStep3Show = {
  name: false,
  phone: false,
  email: false,
  gender: false,
  birthday: false,
  userName: false,
};

// --- Step 4 (Identity Verification) State & Config ---
const selectedIdType = ref('');
const validIdFront = ref<File | null>(null);
const validIdBack = ref<File | null>(null);
const identityStep4Show = {
  licenseNumber: false,
  licenseExpiry: false,
  expertise: false,
  yearExperience: false,
};

// --- Step 5 (Documents to Upload) State & Config ---
const documentsStep5Show = {
  nbiClearance: false,
  selfiePicture: false,
  prcCertificate: false,
  professionalLicense: false,
  cvAttachment: false,
};

// --- Step 6 (Account Security) State & Config ---
const securityStep6Labels = {
  terms1: 'I Agree to the Franchise Terms and Boundary Policy',
  terms2: 'I confirm all details are true and valid',
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
  name: 1,
  phone: 1,
  email: 1,
  home_region: 2,
  home_province: 2,
  home_city: 2,
  home_barangay: 2,
  home_postal_code: 2,
  home_address: 2,
  franchise_name: 3,
  franchise_region: 3,
  franchise_province: 3,
  franchise_city: 3,
  franchise_barangay: 3,
  franchise_postal_code: 3,
  franchise_address: 3,
  valid_id_type: 4,
  valid_id_number: 4,
  front_valid_id_picture: 4,
  back_valid_id_picture: 4,
  dti_certificate: 5,
  mayor_permit: 5,
  proof_capital: 5,
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

    <!-- Step Title -->
    <h1 class="text-center text-2xl font-black text-auth-blue">
      {{ stepTitles[currentStep] }}
    </h1>

    <!-- Step 1: Personal Information -->
    <div v-show="currentStep === 1" class="space-y-4" data-step="1">
      <Step1Personal :errors="errors" :show-fields="personalStep1Show" />
    </div>

    <!-- Step 2: Home Address -->
    <div v-show="currentStep === 2" class="space-y-4" data-step="2">
      <Step2Address
        :address-data="homeAddress"
        :errors="errors"
        :field-names="homeAddressFields"
        :labels="homeAddressLabels"
        @change="handleStepChange"
      />
    </div>

    <!-- Step 3: Franchise Address -->
    <div v-show="currentStep === 3" class="space-y-4" data-step="3">
      <Step1Personal :errors="errors" :show-fields="personalStep3Show" />
      <Step2Address
        :address-data="franchiseAddress"
        :errors="errors"
        :field-names="franchiseAddressFields"
        :labels="franchiseAddressLabels"
        @change="handleStepChange"
      />
    </div>

    <!-- Step 4: Identification -->
    <div v-show="currentStep === 4" class="space-y-4" data-step="4">
      <Step4Account
        :errors="errors"
        :id-types="idTypes"
        :show-fields="identityStep4Show"
        v-model:selectedIdType="selectedIdType"
        v-model:validIdFront="validIdFront"
        v-model:validIdBack="validIdBack"
      />
    </div>

    <!-- Step 5: Uploads -->
    <div v-show="currentStep === 5" class="space-y-4" data-step="6">
      <Step5Uploads :errors="errors" :show-fields="documentsStep5Show" />
    </div>

    <!-- Step 6: Security -->
    <div v-show="currentStep === 6" class="space-y-4" data-step="7">
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
</template>
