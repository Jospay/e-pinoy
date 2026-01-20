<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Eye, EyeOff, Lock } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// --- TYPES ---
interface FieldNames {
  password: string;
  confirmPassword: string;
  terms1: string;
  terms2: string;
}
interface Labels {
  terms1: string;
  terms2: string;
}
interface ShowFields {
  password: boolean;
  terms1: boolean;
  terms2: boolean;
}

// --- PROPS ---
const props = defineProps<{
  errors?: Record<string, string>;
  // v-model props
  password?: string;
  confirmPassword?: string;
  terms1?: boolean;
  terms2?: boolean;
  // Customization props (all optional)
  fieldNames?: Partial<FieldNames>;
  labels?: Partial<Labels>;
  showFields?: Partial<ShowFields>;
}>();

// --- EMITS ---
const emit = defineEmits([
  'update:terms1',
  'update:terms2',
  'update:password',
  'update:confirmPassword',
]);

// --- V-MODEL COMPUTEDS ---
const computedTerms1 = computed<boolean>({
  get: () => props.terms1,
  set: (value) => emit('update:terms1', value),
});
const computedTerms2 = computed<boolean>({
  get: () => props.terms2,
  set: (value) => emit('update:terms2', value),
});

// --- DEFAULTS ---
const defaultFieldNames: FieldNames = {
  password: 'password',
  confirmPassword: 'password_confirmation',
  terms1: 'terms1',
  terms2: 'terms2',
};
const defaultLabels: Labels = {
  terms1: 'I Agree to the Franchise Terms and Boundary Policy',
  terms2: 'I confirm all details are true and valid',
};
const defaultShowFields: ShowFields = {
  password: true,
  terms1: true,
  terms2: true,
};

// --- MERGED COMPUTEDS ---
const fields = computed(() => ({ ...defaultFieldNames, ...props.fieldNames }));
const labels = computed(() => ({ ...defaultLabels, ...props.labels }));
const show = computed(() => ({ ...defaultShowFields, ...props.showFields }));

// --- INTERNAL STATE ---
const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
  <!-- Password -->
  <template v-if="show.password">
    <div class="grid gap-2">
      <Label for="password" class="font-semibold text-auth-blue"
        >Password</Label
      >
      <div
        class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
      >
        <div class="flex items-center justify-center bg-auth-blue px-3">
          <Lock class="h-5 w-5 text-white" />
        </div>
        <div class="relative w-full items-center">
          <Input
            id="password"
            :type="showPassword ? 'text' : 'password'"
            :name="fields.password"
            :model-value="password"
            @update:model-value="emit('update:password', $event)"
            required
            placeholder="Password"
            class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-gray-500 hover:text-gray-700"
            @click="showPassword = !showPassword"
          >
            <Eye v-if="!showPassword" class="h-5 w-5" />
            <EyeOff v-else class="h-5 w-5" />
          </button>
        </div>
      </div>
      <InputError :message="errors?.[fields.password]" />
    </div>

    <!-- Confirm Password -->
    <div class="grid gap-2">
      <Label for="password_confirmation" class="font-semibold text-auth-blue"
        >Confirm Password</Label
      >
      <div
        class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
      >
        <div class="flex items-center justify-center bg-auth-blue px-3">
          <Lock class="h-5 w-5 text-white" />
        </div>
        <div class="relative w-full items-center">
          <Input
            id="password_confirmation"
            :type="showConfirmPassword ? 'text' : 'password'"
            :name="fields.confirmPassword"
            :model-value="confirmPassword"
            @update:model-value="emit('update:confirmPassword', $event)"
            required
            placeholder="Confirm Password"
            class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-0 flex cursor-pointer items-center pr-3 text-gray-500 hover:text-gray-700"
            @click="showConfirmPassword = !showConfirmPassword"
          >
            <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
            <EyeOff v-else class="h-5 w-5" />
          </button>
        </div>
      </div>
      <InputError :message="errors?.[fields.confirmPassword]" />
    </div>
  </template>

  <!-- Terms 1 -->
  <div v-if="show.terms1" class="flex items-center gap-x-2">
    <Checkbox
      :id="fields.terms1"
      :name="fields.terms1"
      v-model="computedTerms1"
      class="border-black"
    />
    <div class="grid gap-1.5 leading-none">
      <label
        :for="fields.terms1"
        class="cursor-pointer text-xs leading-none font-medium text-auth-blue peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {{ labels.terms1 }}
      </label>
      <InputError :message="errors?.[fields.terms1]" />
    </div>
  </div>

  <!-- Terms 2 -->
  <div v-if="show.terms2" class="flex items-center gap-x-2">
    <Checkbox
      :id="fields.terms2"
      :name="fields.terms2"
      v-model="computedTerms2"
      class="border-black"
    />
    <div class="grid gap-1.5 leading-none">
      <label
        :for="fields.terms2"
        class="cursor-pointer text-xs leading-none font-medium text-auth-blue peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
      >
        {{ labels.terms2 }}
      </label>
      <InputError :message="errors?.[fields.terms2]" />
    </div>
  </div>
</template>
