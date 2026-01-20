<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Calendar, FileText, IdCard, Upload, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// --- TYPES ---
interface FieldNames {
  licenseNumber: string;
  licenseExpiry: string;
  validIdType: string;
  frontValidIdPicture: string;
  backValidIdPicture: string;
  validIdNumber: string;
  expertise: string;
  yearExperience: string;
}
interface Labels {
  licenseNumber: string;
  licenseExpiry: string;
  validIdType: string;
  validIdUpload: string;
  validIdUploadFront: string;
  validIdUploadBack: string;
  validIdNumber: string;
  expertise: string;
  yearExperience: string;
}
interface ShowFields {
  licenseNumber: boolean;
  licenseExpiry: boolean;
  validIdType: boolean;
  validIdUpload: boolean;
  validIdNumber: boolean;
  expertise: boolean;
  yearExperience: boolean;
}

// --- PROPS ---
const props = defineProps<{
  errors?: Record<string, string>;
  // Data props
  idTypes?: { value: string; label: string }[];
  expertise?: { value: string; label: string }[];
  // v-model props
  licenseExpiry?: string;
  selectedIdType?: string;
  selectedExpertise?: string;
  validIdFront?: File | null;
  validIdBack?: File | null;
  validIdNumber?: string;
  // Customization props (all optional)
  fieldNames?: Partial<FieldNames>;
  labels?: Partial<Labels>;
  showFields?: Partial<ShowFields>;
}>();

const emit = defineEmits([
  'update:licenseExpiry',
  'update:selectedIdType',
  'update:selectedExpertise',
  'update:validIdFront',
  'update:validIdBack',
  'update:validIdNumber',
]);

// --- DEFAULTS ---
const defaultFieldNames: FieldNames = {
  licenseNumber: 'license_number',
  licenseExpiry: 'license_expiry',
  validIdType: 'valid_id_type',
  frontValidIdPicture: 'front_valid_id_picture',
  backValidIdPicture: 'back_valid_id_picture',
  validIdNumber: 'valid_id_number',
  expertise: 'expertise',
  yearExperience: 'year_experience',
};
const defaultLabels: Labels = {
  licenseNumber: "Driver's License Number",
  licenseExpiry: 'License Expiry Date',
  validIdType: 'Valid ID Type',
  validIdUpload: 'Upload Valid ID',
  validIdUploadFront: 'Front',
  validIdUploadBack: 'Back',
  validIdNumber: 'Valid ID Number',
  expertise: 'Area of Expertise',
  yearExperience: 'Years of Experience',
};
const defaultShowFields: ShowFields = {
  licenseNumber: true,
  licenseExpiry: true,
  validIdType: true,
  validIdUpload: true,
  validIdNumber: true,
  expertise: true,
  yearExperience: true,
};

// --- MERGED COMPUTEDS ---
const fields = computed(() => ({ ...defaultFieldNames, ...props.fieldNames }));
const labels = computed(() => ({ ...defaultLabels, ...props.labels }));
const show = computed(() => ({ ...defaultShowFields, ...props.showFields }));

// --- V-MODEL COMPUTEDS ---
const computedLicenseExpiry = computed<string | undefined>({
  get: () => props.licenseExpiry,
  set: (value) => emit('update:licenseExpiry', value),
});
const computedIdType = computed<string | undefined>({
  get: () => props.selectedIdType,
  set: (value) => emit('update:selectedIdType', value),
});
const computedExpertise = computed<string | undefined>({
  get: () => props.selectedExpertise,
  set: (value) => emit('update:selectedExpertise', value),
});
const computedValidIdFront = computed({
  get: () => props.validIdFront,
  set: (value) => emit('update:validIdFront', value),
});
const computedValidIdBack = computed({
  get: () => props.validIdBack,
  set: (value) => emit('update:validIdBack', value),
});
const computedValidIdNumber = computed<string | undefined>({
  get: () => props.validIdNumber,
  set: (value) => emit('update:validIdNumber', value),
});

// --- REFS FOR FILE INPUTS ---
const licenseFrontInput = ref<HTMLInputElement | null>(null);
const licenseBackInput = ref<HTMLInputElement | null>(null);

// --- INTERNAL METHODS ---
function handleFileUpload(event: Event, side: 'front' | 'back') {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0] || null;
  if (side === 'front') {
    computedValidIdFront.value = file;
  } else {
    computedValidIdBack.value = file;
  }
}

function removeFile(side: 'front' | 'back') {
  if (side === 'front') {
    computedValidIdFront.value = null;
    if (licenseFrontInput.value) licenseFrontInput.value.value = '';
  } else {
    computedValidIdBack.value = null;
    if (licenseBackInput.value) licenseBackInput.value.value = '';
  }
}
</script>

<template>
  <!-- DRIVER'S LICENSE -->
  <div v-if="show.licenseNumber" class="grid gap-2">
    <Label :for="fields.licenseNumber" class="font-semibold text-auth-blue">{{
      labels.licenseNumber
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <IdCard class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.licenseNumber"
        type="text"
        :name="fields.licenseNumber"
        required
        :autocomplete="fields.licenseNumber"
        placeholder="Driver's License Number"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.licenseNumber]" />
  </div>

  <!-- LICENSE EXPIRY -->
  <div v-if="show.licenseExpiry" class="grid gap-2">
    <Label :for="fields.licenseExpiry" class="font-semibold text-auth-blue">{{
      labels.licenseExpiry
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <Calendar class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.licenseExpiry"
        type="date"
        required
        :name="fields.licenseExpiry"
        v-model="computedLicenseExpiry"
        class="flex-1 cursor-text border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.licenseExpiry]" />
  </div>

  <!-- VALID ID -->
  <div v-if="show.validIdType" class="grid gap-2">
    <Label :for="fields.validIdType" class="font-semibold text-auth-blue">{{
      labels.validIdType
    }}</Label>
    <select
      :id="fields.validIdType"
      :name="fields.validIdType"
      required
      v-model="computedIdType"
      class="flex h-10 w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 font-mono text-sm font-semibold focus-visible:ring-2 focus-visible:ring-auth-blue focus-visible:ring-offset-2 focus-visible:outline-none"
    >
      <option value="" disabled>Select Valid ID Type</option>
      <option
        v-for="idType in idTypes"
        :key="idType.value"
        :value="idType.value"
      >
        {{ idType.label }}
      </option>
    </select>
    <InputError :message="errors?.[fields.validIdType]" />
  </div>

  <!-- VALID ID UPLOAD -->
  <div v-if="show.validIdUpload" class="space-y-3">
    <Label class="text-base font-semibold text-auth-blue">{{
      labels.validIdUpload
    }}</Label>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <Label class="mb-2 block text-sm font-semibold">{{
          labels.validIdUploadFront
        }}</Label>
        <div
          class="relative flex h-24 flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:bg-accent"
          :class="{ 'cursor-pointer': !computedValidIdFront }"
          @click="!computedValidIdFront && licenseFrontInput?.click()"
        >
          <input
            ref="licenseFrontInput"
            type="file"
            :name="fields.frontValidIdPicture"
            accept="image/*"
            required
            class="hidden"
            @change="handleFileUpload($event, 'front')"
          />
          <template v-if="!computedValidIdFront">
            <Upload class="mb-1 h-6 w-6 text-auth-blue" />
            <span class="text-xs font-medium text-auth-blue">Front +</span>
          </template>
          <template v-else>
            <div class="flex items-center gap-2 p-2">
              <FileText class="h-5 w-5 text-auth-blue" />
              <span class="max-w-[100px] truncate text-xs text-gray-700">
                {{ computedValidIdFront.name }}
              </span>
            </div>
            <button
              type="button"
              @click.stop="removeFile('front')"
              class="absolute top-1 right-1 cursor-pointer rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
            >
              <X class="h-3 w-3" />
            </button>
          </template>
        </div>
      </div>
      <div>
        <Label class="mb-2 block text-sm font-semibold">{{
          labels.validIdUploadBack
        }}</Label>
        <div
          class="relative flex h-24 flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:bg-accent"
          :class="{ 'cursor-pointer': !computedValidIdBack }"
          @click="!computedValidIdBack && licenseBackInput?.click()"
        >
          <input
            ref="licenseBackInput"
            type="file"
            :name="fields.backValidIdPicture"
            required
            accept="image/*"
            class="hidden"
            @change="handleFileUpload($event, 'back')"
          />
          <template v-if="!computedValidIdBack">
            <Upload class="mb-1 h-6 w-6 text-auth-blue" />
            <span class="text-xs font-medium text-auth-blue">Back +</span>
          </template>
          <template v-else>
            <div class="flex items-center gap-2 p-2">
              <FileText class="h-5 w-5 text-auth-blue" />
              <span class="max-w-[100px] truncate text-xs text-gray-700">
                {{ computedValidIdBack.name }}
              </span>
            </div>
            <button
              type="button"
              @click.stop="removeFile('back')"
              class="absolute top-1 right-1 cursor-pointer rounded-full bg-red-500 p-1 text-white hover:bg-red-600"
            >
              <X class="h-3 w-3" />
            </button>
          </template>
        </div>
      </div>
    </div>
    <InputError :message="errors?.[fields.frontValidIdPicture]" />
    <InputError :message="errors?.[fields.backValidIdPicture]" />
  </div>

  <!-- VALID ID NUMBER -->
  <div v-if="show.validIdNumber" class="grid gap-2">
    <Label :for="fields.validIdNumber" class="font-semibold text-auth-blue">{{
      labels.validIdNumber
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <IdCard class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.validIdNumber"
        type="text"
        :name="fields.validIdNumber"
        v-model="computedValidIdNumber"
        required
        :autocomplete="fields.validIdNumber"
        placeholder="123456789"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.validIdNumber]" />
  </div>

  <!-- EXPERTISE -->
  <div v-if="show.expertise" class="grid gap-2">
    <Label :for="fields.expertise" class="font-semibold text-auth-blue">{{
      labels.expertise
    }}</Label>
    <div class="flex gap-2">
      <Button
        v-for="item in expertise"
        :key="item.value"
        type="button"
        variant="default"
        @click="computedExpertise = item.value"
        :class="[
          'flex-1 cursor-pointer bg-blue-500 font-semibold text-white hover:bg-gray-700',
          computedExpertise === item.value
            ? 'bg-gray-700 ring-2 ring-auth-blue ring-offset-2'
            : '',
        ]"
      >
        {{ item.label }}
      </Button>
    </div>
    <InputError :message="errors?.[fields.expertise]" />
    <input
      type="hidden"
      :name="fields.expertise"
      :value="computedExpertise"
      required
    />
  </div>

  <!-- YEAR EXPERIENCE -->
  <div v-if="show.yearExperience" class="grid gap-2">
    <Label :for="fields.yearExperience" class="font-semibold text-auth-blue">{{
      labels.yearExperience
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <Calendar class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.yearExperience"
        type="number"
        :name="fields.yearExperience"
        required
        placeholder="0"
        :autocomplete="fields.yearExperience"
        class="flex-1 border-0 focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.yearExperience]" />
  </div>
</template>
