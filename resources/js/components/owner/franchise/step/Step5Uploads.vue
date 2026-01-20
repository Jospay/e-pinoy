<script setup lang="ts">
import { computed } from 'vue';
import FileInput from './FileInput.vue';

// --- TYPES ---
interface FieldNames {
  nbiClearance: string;
  selfiePicture: string;
  prcCertificate: string;
  professionalLicense: string;
  cvAttachment: string;
  dtiCertificate: string;
  mayorPermit: string;
  proofOfCapital: string;
}
interface Labels {
  nbiClearance: string;
  selfiePicture: string;
  prcCertificate: string;
  professionalLicense: string;
  cvAttachment: string;
  dtiCertificate: string;
  mayorPermit: string;
  proofOfCapital: string;
}
interface ShowFields {
  nbiClearance: boolean;
  selfiePicture: boolean;
  prcCertificate: boolean;
  professionalLicense: boolean;
  cvAttachment: boolean;
  dtiCertificate: boolean;
  mayorPermit: boolean;
  proofOfCapital: boolean;
}

// --- PROPS ---
const props = defineProps<{
  errors?: Record<string, string>;
  // Customization props (all optional)
  fieldNames?: Partial<FieldNames>;
  labels?: Partial<Labels>;
  showFields?: Partial<ShowFields>;
  // v-model props
  dtiCertificate?: File | null;
  mayorPermit?: File | null;
  proofOfCapital?: File | null;
}>();

// --- DEFAULTS ---
const defaultFieldNames: FieldNames = {
  nbiClearance: 'nbi_clearance',
  selfiePicture: 'selfie_picture',
  prcCertificate: 'certificate_prc_no',
  professionalLicense: 'professional_license',
  cvAttachment: 'cv_attachment',
  dtiCertificate: 'dti_certificate',
  mayorPermit: 'mayor_permit',
  proofOfCapital: 'proof_capital',
};
const defaultLabels: Labels = {
  nbiClearance: 'NBI or Police Clearance',
  selfiePicture: '1x1 Photo/Selfie',
  prcCertificate: 'Certification or PRC Number (if applicable)',
  professionalLicense: 'Professional Licence / Certificate (if applicable)',
  cvAttachment: 'CV / Resume Attachment',
  dtiCertificate: 'DTI/SEC Registration',
  mayorPermit: "Mayor's Permit",
  proofOfCapital: 'Proof of Capital or Franchise Agreement',
};
const defaultShowFields: ShowFields = {
  nbiClearance: true,
  selfiePicture: true,
  prcCertificate: true,
  professionalLicense: true,
  cvAttachment: true,
  dtiCertificate: true,
  mayorPermit: true,
  proofOfCapital: true,
};

// define emits
const emit = defineEmits([
  'update:dtiCertificate',
  'update:mayorPermit',
  'update:proofOfCapital',
]);

// --- MERGED COMPUTEDS ---
const fields = computed(() => ({ ...defaultFieldNames, ...props.fieldNames }));
const labels = computed(() => ({ ...defaultLabels, ...props.labels }));
const show = computed(() => ({ ...defaultShowFields, ...props.showFields }));
</script>

<template>
  <!-- NBI or Police Clearance -->
  <FileInput
    v-if="show.nbiClearance"
    :id="fields.nbiClearance"
    :name="fields.nbiClearance"
    :label="labels.nbiClearance"
    :required="true"
    :errorMsg="errors?.[fields.nbiClearance]"
  />

  <!-- 1x1 Photo / Selfie -->
  <FileInput
    v-if="show.selfiePicture"
    :id="fields.selfiePicture"
    :name="fields.selfiePicture"
    :label="labels.selfiePicture"
    accept="image/*"
    :required="true"
    :errorMsg="errors?.[fields.selfiePicture]"
  />

  <!-- Certification or PRC Number -->
  <FileInput
    v-if="show.prcCertificate"
    :id="fields.prcCertificate"
    :name="fields.prcCertificate"
    :label="labels.prcCertificate"
    :errorMsg="errors?.[fields.prcCertificate]"
  />

  <!-- Professional Licence -->
  <FileInput
    v-if="show.professionalLicense"
    :id="fields.professionalLicense"
    :name="fields.professionalLicense"
    :label="labels.professionalLicense"
    :errorMsg="errors?.[fields.professionalLicense]"
  />

  <!-- Photo or Resume -->
  <FileInput
    v-if="show.cvAttachment"
    :id="fields.cvAttachment"
    :name="fields.cvAttachment"
    :label="labels.cvAttachment"
    :required="true"
    :errorMsg="errors?.[fields.cvAttachment]"
  />

  <!-- DTI/SEC Registration -->
  <FileInput
    v-if="show.dtiCertificate"
    :id="fields.dtiCertificate"
    :name="fields.dtiCertificate"
    :model-value="dtiCertificate"
    @update:model-value="emit('update:dtiCertificate', $event)"
    :label="labels.dtiCertificate"
    :required="true"
    :errorMsg="errors?.[fields.dtiCertificate]"
  />

  <!-- Mayor's Permit -->
  <FileInput
    v-if="show.mayorPermit"
    :id="fields.mayorPermit"
    :name="fields.mayorPermit"
    :model-value="mayorPermit"
    @update:model-value="emit('update:mayorPermit', $event)"
    :label="labels.mayorPermit"
    :required="true"
    :errorMsg="errors?.[fields.mayorPermit]"
  />

  <!-- Proof of Capital -->
  <FileInput
    v-if="show.proofOfCapital"
    :id="fields.proofOfCapital"
    :name="fields.proofOfCapital"
    :model-value="proofOfCapital"
    @update:model-value="emit('update:proofOfCapital', $event)"
    :label="labels.proofOfCapital"
    :required="true"
    :errorMsg="errors?.[fields.proofOfCapital]"
  />
</template>
