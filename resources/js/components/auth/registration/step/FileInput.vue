<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { FileIcon } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
  id: string;
  name: string;
  label: string;
  required?: boolean;
  errorMsg?: string;
  accept?: string;
  // 1. Accept the file from the parent
  modelValue?: File | null;
}>();

// 2. Define the emit for v-model
const emit = defineEmits(['update:modelValue']);

// Track selected file for display purposes only
const fileName = ref<string | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

// 3. Handle file selection
function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    fileName.value = file.name;
    // Send file UP to StepUploads
    emit('update:modelValue', file);
  } else {
    fileName.value = null;
    emit('update:modelValue', null);
  }
}

// 4. Watch for changes from the parent (e.g., if form.reset() is called)
watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue) {
      fileName.value = null;
      if (inputRef.value) inputRef.value.value = ''; // clear native input
    } else {
      fileName.value = newValue.name;
    }
  },
  { immediate: true },
);
</script>

<template>
  <div class="grid gap-2">
    <Label :for="id" class="font-semibold text-auth-blue">
      {{ props.label }}
    </Label>
    <div
      class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
    >
      <div class="flex items-center justify-center bg-auth-blue px-3">
        <FileIcon class="h-5 w-5 text-white" />
      </div>

      <input
        ref="inputRef"
        :id="id"
        type="file"
        :name="name"
        :required="required"
        :accept="accept || '.doc,.docx,application/pdf,image/*'"
        class="hidden"
        @change="handleFileChange"
      />

      <label
        :for="id"
        class="flex-1 cursor-pointer truncate border-0 p-2 font-mono text-sm font-semibold hover:bg-blue-50"
        :class="{ 'text-gray-400': !fileName }"
      >
        {{ fileName ? fileName : 'Upload file' }}
      </label>
    </div>
    <InputError :message="errorMsg" />
  </div>
</template>
