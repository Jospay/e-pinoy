<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { computed } from 'vue';

// --- TYPES ---
interface FieldNames {
  paymentOption: string;
  shift: string;
  language: string;
  accessibility: string;
}
interface Labels {
  paymentOption: string;
  shift: string;
  language: string;
  accessibility: string;
}
interface ShowFields {
  paymentOption: boolean;
  shift: boolean;
  language: boolean;
  accessibility: boolean;
}

// --- PROPS ---
const props = defineProps<{
  errors?: Record<string, string>;
  // Data props
  paymentOptions?: { id: string; label: string }[];
  shifts?: { value: string; label: string }[];
  preferredLanguages?: { value: string; label: string }[];
  accessibilityOptions?: { value: string; label: string }[];
  // v-model props
  selectedPayout?: string;
  selectedShift?: string;
  selectedLanguage?: string;
  selectedAccessibilityOptions?: string;
  // Customization props (all optional)
  fieldNames?: Partial<FieldNames>;
  labels?: Partial<Labels>;
  showFields?: Partial<ShowFields>;
}>();

const emit = defineEmits([
  'update:selectedPayout',
  'update:selectedShift',
  'update:selectedLanguage',
  'update:selectedAccessibilityOptions',
]);

// --- DEFAULTS ---
const defaultFieldNames: FieldNames = {
  paymentOption: 'payment_option_id',
  shift: 'shift',
  language: 'preferred_language',
  accessibility: 'accessibility_option',
};
const defaultLabels: Labels = {
  paymentOption: 'Payment Option',
  shift: 'Preferred Shift',
  language: 'Preferred Language',
  accessibility: 'Accessibility Options',
};
const defaultShowFields: ShowFields = {
  paymentOption: true,
  shift: true,
  language: true,
  accessibility: true,
};

// --- MERGED COMPUTEDS ---
const fields = computed(() => ({ ...defaultFieldNames, ...props.fieldNames }));
const labels = computed(() => ({ ...defaultLabels, ...props.labels }));
const show = computed(() => ({ ...defaultShowFields, ...props.showFields }));

// --- V-MODEL COMPUTEDS ---
const computedPayout = computed<string | undefined>({
  get: () => props.selectedPayout,
  set: (value) => emit('update:selectedPayout', value),
});
const computedShift = computed<string | undefined>({
  get: () => props.selectedShift,
  set: (value) => emit('update:selectedShift', value),
});
const computedLanguage = computed<string | undefined>({
  get: () => props.selectedLanguage,
  set: (value) => emit('update:selectedLanguage', value),
});
const computedAccessibility = computed<string | undefined>({
  get: () => props.selectedAccessibilityOptions,
  set: (value) => emit('update:selectedAccessibilityOptions', value),
});
</script>

<template>
  <!-- PAYMENT OPTION -->
  <div v-if="show.paymentOption" class="grid gap-2">
    <Label :for="fields.paymentOption" class="font-semibold text-auth-blue">{{
      labels.paymentOption
    }}</Label>
    <div class="grid grid-cols-2 gap-2">
      <Button
        v-for="payout in paymentOptions"
        :key="payout.id"
        type="button"
        variant="default"
        @click="computedPayout = payout.id"
        :class="[
          'relative h-12 cursor-pointer bg-blue-500 text-base font-semibold text-white hover:bg-gray-700',
          computedPayout === payout.id
            ? 'bg-gray-700 ring-2 ring-auth-blue ring-offset-2'
            : '',
        ]"
      >
        {{ payout.label }}
      </Button>
    </div>
    <InputError :message="errors?.[fields.paymentOption]" />
    <input
      type="hidden"
      :name="fields.paymentOption"
      :value="computedPayout"
      required
    />
  </div>

  <!-- SHIFT -->
  <div v-if="show.shift" class="grid gap-2">
    <Label :for="fields.shift" class="font-semibold text-auth-blue">{{
      labels.shift
    }}</Label>
    <div class="flex gap-2">
      <Button
        v-for="shift in shifts"
        :key="shift.value"
        type="button"
        variant="default"
        @click="computedShift = shift.value"
        :class="[
          'flex-1 cursor-pointer bg-blue-500 font-semibold text-white hover:bg-gray-700',
          computedShift === shift.value
            ? 'bg-gray-700 ring-2 ring-auth-blue ring-offset-2'
            : '',
        ]"
      >
        {{ shift.label }}
      </Button>
    </div>
    <InputError :message="errors?.[fields.shift]" />
    <input type="hidden" :name="fields.shift" :value="computedShift" required />
  </div>

  <!-- LANGUAGE -->
  <div v-if="show.language" class="grid gap-2">
    <Label :for="fields.language" class="font-semibold text-auth-blue">{{
      labels.language
    }}</Label>
    <div class="flex gap-2">
      <Button
        v-for="language in preferredLanguages"
        :key="language.value"
        type="button"
        variant="default"
        @click="computedLanguage = language.value"
        :class="[
          'flex-1 cursor-pointer bg-blue-500 font-semibold text-white hover:bg-gray-700',
          computedLanguage === language.value
            ? 'bg-gray-700 ring-2 ring-auth-blue ring-offset-2'
            : '',
        ]"
      >
        {{ language.label }}
      </Button>
    </div>
    <InputError :message="errors?.[fields.language]" />
    <input
      type="hidden"
      :name="fields.language"
      :value="computedLanguage"
      required
    />
  </div>

  <!-- ACCESSIBILITY -->
  <div v-if="show.accessibility" class="grid gap-2">
    <Label :for="fields.accessibility" class="font-semibold text-auth-blue">{{
      labels.accessibility
    }}</Label>
    <div class="flex flex-col gap-2">
      <Button
        v-for="option in accessibilityOptions"
        :key="option.value"
        type="button"
        variant="default"
        @click="computedAccessibility = option.value"
        :class="[
          'flex-1 cursor-pointer bg-blue-500 font-semibold text-white hover:bg-gray-700',
          computedAccessibility === option.value
            ? 'bg-gray-700 ring-2 ring-auth-blue ring-offset-2'
            : '',
        ]"
      >
        {{ option.label }}
      </Button>
    </div>
    <InputError :message="errors?.[fields.accessibility]" />
    <input
      type="hidden"
      :name="fields.accessibility"
      :value="computedAccessibility"
      required
    />
  </div>
</template>
