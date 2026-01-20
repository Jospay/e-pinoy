<script setup lang="ts">
import DriverRegistrationForm from '@/components/auth/registration/DriverRegistrationForm.vue';
import OwnerRegistrationForm from '@/components/auth/registration/OwnerRegistrationForm.vue';
import PassengerRegisrationForm from '@/components/auth/registration/PassengerRegisrationForm.vue';
import TechnicianRegistrationForm from '@/components/auth/registration/TechnicianRegistrationForm.vue';
import { usePageTheme } from '@/composables/usePageTheme';

defineProps<{
  genderOptions: { value: string; label: string }[];
  preferredLanguages: { value: string; label: string }[];
  accessibilityOptions: { value: string; label: string }[];
  shifts: { value: string; label: string }[];

  expertise: { value: string; label: string }[];
  idTypes: { value: string; label: string }[];

  paymentOptions: {
    id: string;
    label: string;
    color: string;
  }[];

  userType: {
    encrypted_id: string;
    name: string;
  };
}>();

// Force light mode on this page only
usePageTheme('light');
</script>

<template>
  <DriverRegistrationForm
    v-if="userType.name === 'driver'"
    :gender-options="genderOptions"
    :payment-options="paymentOptions"
    :user-type="userType"
    :shifts="shifts"
  />

  <PassengerRegisrationForm
    v-else-if="userType.name === 'passenger'"
    :gender-options="genderOptions"
    :payment-options="paymentOptions"
    :user-type="userType"
    :preferred-languages="preferredLanguages"
    :accessibility-options="accessibilityOptions"
  />

  <TechnicianRegistrationForm
    v-if="userType.name === 'technician'"
    :gender-options="genderOptions"
    :expertise="expertise"
    :user-type="userType"
    :id-types="idTypes"
  />

  <OwnerRegistrationForm
    v-if="userType.name === 'owner'"
    :gender-options="genderOptions"
    :payment-options="paymentOptions"
    :user-type="userType"
    :id-types="idTypes"
  />
</template>
