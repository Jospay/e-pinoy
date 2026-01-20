<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { CheckIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-vue-next';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
  currentStep: number;
  totalSteps: number;
  processing: boolean;
  errors?: Record<string, any>;
  canSubmit?: boolean;
}>();

// const emit = defineEmits(['next', 'prev', 'goToStep', 'submit']);

const canProceed = ref(false);

let currentListenerStep: Element | null = null;

function validateRequiredInputs() {
  const stepContainer = document.querySelector(
    `[data-step="${props.currentStep}"]`,
  );
  if (!stepContainer) {
    canProceed.value = true;
    return;
  }

  const requiredInputs = Array.from(
    stepContainer.querySelectorAll<
      HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
    >('[required]'),
  );

  // Enhanced validation for different input types
  const allFilled = requiredInputs.every((input) => {
    // Handle ShadCN Vue checkboxes
    if (input.type === 'checkbox') {
      return (input as HTMLInputElement).checked;
    }

    // Handle radio groups
    if (input.type === 'radio') {
      const group = stepContainer.querySelectorAll(`[name="${input.name}"]`);
      return Array.from(group).some((el: any) => el.checked);
    }

    // Handle select elements
    if (input.tagName === 'SELECT') {
      return (input as HTMLSelectElement).value !== '';
    }

    // handle birthdate input should be a valid birthdate
    if (input.type === 'date' && input.name === 'birth_date') {
      const birthDate = new Date(input.value);
      const isComplete = !isNaN(birthDate.getTime());

      // Check min and max date
      const minDate = new Date(1900, 0, 1);
      const maxDate = new Date();
      const age = maxDate.getFullYear() - birthDate.getFullYear();
      if (
        birthDate < minDate ||
        birthDate > maxDate ||
        age < 10 ||
        !isComplete
      ) {
        return false;
      }
      return true;
    }

    // handle email input should be a valid email
    // if (input.type === 'text' && input.name === 'email') {
    //   const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    //   return emailRegex.test(input.value);
    // }

    // Handle regular inputs
    return input.value.trim() !== '';
  });

  canProceed.value = allFilled;
}

// Enhanced listener binding
function bindListeners() {
  // Remove old listeners
  if (currentListenerStep) {
    currentListenerStep.removeEventListener('input', validateRequiredInputs);
    currentListenerStep.removeEventListener('change', validateRequiredInputs);
  }

  // Add new ones
  const stepContainer = document.querySelector(
    `[data-step="${props.currentStep}"]`,
  );
  if (stepContainer) {
    stepContainer.addEventListener('input', validateRequiredInputs);
    stepContainer.addEventListener('change', validateRequiredInputs);
    currentListenerStep = stepContainer;

    // Also listen for custom change events from child components
    stepContainer.addEventListener('custom-change', validateRequiredInputs);
  }

  // Immediately validate on bind
  nextTick(() => {
    validateRequiredInputs();
    // Additional validation after DOM updates
    setTimeout(validateRequiredInputs, 100);
  });
}

// Watch for step changes and re-bind listeners
watch(
  () => props.currentStep,
  () => {
    nextTick(() => {
      bindListeners();
    });
  },
  { immediate: true },
);

onMounted(() => {
  bindListeners();
  // Periodic validation for dynamic content
  setInterval(validateRequiredInputs, 500);
});

onBeforeUnmount(() => {
  if (currentListenerStep) {
    currentListenerStep.removeEventListener('input', validateRequiredInputs);
    currentListenerStep.removeEventListener('change', validateRequiredInputs);
    currentListenerStep.removeEventListener(
      'custom-change',
      validateRequiredInputs,
    );
  }
});
</script>

<template>
  <div class="mt-4 flex flex-col gap-4">
    <!-- Buttons -->
    <div class="flex gap-3">
      <Button
        v-if="props.currentStep > 1"
        type="button"
        variant="outline"
        class="flex-1 cursor-pointer font-semibold"
        @click="$emit('prev')"
        :disabled="props.processing"
      >
        <ChevronLeftIcon class="mr-2 h-4 w-4" />
        Previous
      </Button>

      <Button
        v-if="props.currentStep < props.totalSteps"
        type="button"
        class="flex-1 cursor-pointer bg-auth-blue font-semibold text-white hover:bg-auth-blue hover:opacity-80"
        @click="$emit('next')"
        :disabled="!canProceed || props.processing"
      >
        Next
        <ChevronRightIcon class="ml-2 h-4 w-4" />
      </Button>

      <Button
        v-if="props.currentStep === props.totalSteps"
        type="submit"
        class="flex-1 cursor-pointer bg-brand-green font-semibold hover:bg-brand-green hover:opacity-80"
        :disabled="props.processing || !props.canSubmit || !canProceed"
        @click="$emit('submit')"
      >
        <Spinner v-if="props.processing" />
        <span v-else>Create account</span>
      </Button>
    </div>

    <!-- Progress Bar -->
    <div class="h-2 w-full rounded-full bg-gray-200">
      <div
        class="h-2 rounded-full bg-auth-blue transition-all duration-300"
        :style="{ width: `${(props.currentStep / props.totalSteps) * 100}%` }"
      ></div>
    </div>

    <!-- Step Indicators -->
    <div class="mt-4 flex justify-between">
      <button
        v-for="step in props.totalSteps"
        :key="step"
        type="button"
        class="flex flex-col items-center gap-1"
        :disabled="true"
      >
        <div
          class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold transition-all"
          :class="{
            'bg-green-500 text-white': step < props.currentStep,
            'bg-auth-blue text-white': step === props.currentStep,
            'bg-gray-200 text-gray-500': step > props.currentStep,
          }"
        >
          <CheckIcon v-if="step < props.currentStep" class="h-4 w-4" />
          <span v-else>{{ step }}</span>
        </div>
      </button>
    </div>
  </div>
</template>
