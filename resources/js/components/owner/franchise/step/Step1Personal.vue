<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Building2Icon,
  CalendarIcon,
  MailIcon,
  PhoneCallIcon,
  UserIcon,
} from 'lucide-vue-next';
import { computed } from 'vue';

// --- TYPES ---
interface FieldNames {
  name: string;
  phone: string;
  email: string;
  gender: string;
  birthDate: string;
  age: string;
  franchiseName: string;
  userName: string;
}
interface Labels {
  name: string;
  phone: string;
  email: string;
  gender: string;
  birthDate: string;
  age: string;
  franchiseName: string;
  userName: string;
}
interface ShowFields {
  name: boolean;
  phone: boolean;
  email: boolean;
  gender: boolean;
  birthday: boolean; // Controls the whole birthday/age block
  franchiseName: boolean;
  userName: boolean;
}

// --- PROPS ---
const props = defineProps<{
  errors?: Record<string, string>;
  genderOptions?: { value: string; label: string }[];
  // v-model props
  name?: string;
  phone?: string;
  email?: string;
  franchiseName?: string;
  userName?: string;
  birthday?: string;
  selectedGender?: string;
  // Customization props (all optional)
  fieldNames?: Partial<FieldNames>;
  labels?: Partial<Labels>;
  showFields?: Partial<ShowFields>;
}>();

// DEFINE EMITS
const emit = defineEmits([
  'update:birthday',
  'update:selectedGender',
  'update:name',
  'update:phone',
  'update:email',
  'update:franchiseName',
  'update:userName',
]);

// --- DEFAULTS ---
const defaultFieldNames: FieldNames = {
  name: 'name',
  phone: 'phone',
  email: 'email',
  gender: 'gender',
  birthDate: 'birth_date',
  age: 'age',
  franchiseName: 'franchise_name',
  userName: 'username',
};
const defaultLabels: Labels = {
  name: 'Full Name (optional)',
  phone: 'Phone Number',
  email: 'Email Address',
  gender: 'Gender',
  birthDate: 'Date of Birth',
  age: 'Age',
  franchiseName: 'Business / Franchise Name',
  userName: 'Username',
};
const defaultShowFields: ShowFields = {
  name: true,
  phone: true,
  email: true,
  gender: true,
  birthday: true,
  franchiseName: true,
  userName: true,
};

// Merges defaults with any provided props. Props win.
const fields = computed(() => ({ ...defaultFieldNames, ...props.fieldNames }));
const labels = computed(() => ({ ...defaultLabels, ...props.labels }));
const show = computed(() => ({ ...defaultShowFields, ...props.showFields }));

// --- V-MODEL COMPUTEDS ---
const computedBirthday = computed<string | undefined>({
  get: () => props.birthday,
  set: (value) => emit('update:birthday', value),
});
const computedGender = computed<string | undefined>({
  get: () => props.selectedGender,
  set: (value) => emit('update:selectedGender', value),
});

// --- INTERNAL LOGIC ---
const calculatedAge = computed(() => {
  if (!props.birthday) return null;
  const birthDate = new Date(props.birthday);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  if (
    monthDiff < 0 ||
    (monthDiff === 0 && today.getDate() < birthDate.getDate())
  ) {
    age--;
  }
  return age;
});
</script>

<template>
  <!-- User Name -->
  <div v-if="show.userName" class="grid gap-2">
    <Label :for="fields.userName" class="font-semibold text-auth-blue">{{
      labels.userName
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <UserIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.userName"
        type="text"
        :name="fields.userName"
        :model-value="userName"
        @update:model-value="emit('update:userName', $event)"
        required
        autofocus
        :autocomplete="fields.userName"
        placeholder="jdelacruz"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.userName]" />
  </div>

  <!-- Full Name -->
  <div v-if="show.name" class="grid gap-2">
    <Label :for="fields.name" class="font-semibold text-auth-blue">{{
      labels.name
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <UserIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.name"
        type="text"
        :name="fields.name"
        :model-value="name"
        @update:model-value="emit('update:name', $event)"
        autofocus
        :autocomplete="fields.name"
        placeholder="Juan Delacruz"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.name]" />
  </div>

  <!-- Franchise Name -->
  <div v-if="show.franchiseName" class="grid gap-2">
    <Label :for="fields.franchiseName" class="font-semibold text-auth-blue">{{
      labels.franchiseName
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <Building2Icon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.franchiseName"
        type="text"
        :name="fields.franchiseName"
        :model-value="franchiseName"
        @update:model-value="emit('update:franchiseName', $event)"
        required
        autofocus
        :autocomplete="fields.franchiseName"
        placeholder="Business Name"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.franchiseName]" />
  </div>

  <!-- Phone -->
  <div v-if="show.phone" class="grid gap-2">
    <Label :for="fields.phone" class="font-semibold text-auth-blue">{{
      labels.phone
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <PhoneCallIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.phone"
        type="tel"
        :name="fields.phone"
        :model-value="phone"
        @update:model-value="emit('update:phone', $event)"
        required
        :autocomplete="fields.phone"
        placeholder="639123456789"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.phone]" />
  </div>

  <!-- Email -->
  <div v-if="show.email" class="grid gap-2">
    <Label :for="fields.email" class="font-semibold text-auth-blue">{{
      labels.email
    }}</Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <MailIcon class="h-5 w-5 text-white" />
      </div>
      <Input
        :id="fields.email"
        type="text"
        :name="fields.email"
        :model-value="email"
        @update:model-value="emit('update:email', $event)"
        required
        :autocomplete="fields.email"
        placeholder="email@example.com"
        class="flex-1 border-0 font-mono font-semibold focus-visible:ring-0"
      />
    </div>
    <InputError :message="errors?.[fields.email]" />
  </div>

  <!-- Gender -->
  <div v-if="show.gender" class="grid gap-2">
    <Label :for="fields.gender" class="font-semibold text-auth-blue">{{
      labels.gender
    }}</Label>
    <select
      :id="fields.gender"
      :name="fields.gender"
      required
      v-model="computedGender"
      class="flex h-10 w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 font-mono text-sm font-semibold focus-visible:ring-2 focus-visible:ring-auth-blue focus-visible:ring-offset-2 focus-visible:outline-none"
    >
      <option value="" disabled>Select your gender</option>
      <option
        v-for="option in genderOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <InputError :message="errors?.[fields.gender]" />
  </div>

  <!-- Birthday -->
  <div v-if="show.birthday" class="flex flex-wrap items-end gap-3">
    <div class="flex min-w-[200px] flex-1 flex-col">
      <Label
        :for="fields.birthDate"
        class="mb-1 font-semibold text-auth-blue"
        >{{ labels.birthDate }}</Label
      >
      <div class="flex overflow-hidden rounded-md border border-gray-300">
        <div class="flex items-center justify-center bg-auth-blue px-3">
          <CalendarIcon class="h-5 w-5 text-white" />
        </div>
        <Input
          :id="fields.birthDate"
          type="date"
          :name="fields.birthDate"
          v-model="computedBirthday"
          required
          class="flex-1 cursor-text border-0 font-mono font-semibold focus-visible:ring-0"
        />
      </div>
    </div>

    <!-- Age -->
    <div class="flex w-24 flex-col">
      <Label for="age" class="mb-1 font-semibold text-auth-blue">{{
        labels.age
      }}</Label>
      <div
        class="flex h-10 w-full items-center justify-center rounded-md border border-gray-300 bg-gray-50 font-mono text-lg font-semibold"
      >
        {{ calculatedAge !== null ? calculatedAge : '00' }}
      </div>
      <input type="hidden" :name="fields.age" :value="calculatedAge" />
    </div>
    <InputError :message="errors?.[fields.birthDate]" />
  </div>
</template>
