<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { DateValue } from '@internationalized/date';
import {
  CalendarDate,
  getLocalTimeZone,
  parseDate,
} from '@internationalized/date';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
  modelValue?: string; // YYYY-MM-DD format
  placeholder?: string;
  disabled?: boolean;
  minDate?: string; // YYYY-MM-DD format
  maxDate?: string; // YYYY-MM-DD format;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Pick a date',
  disabled: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const open = ref(false);

// Convert string date to CalendarDate
const dateValue = computed({
  get: (): DateValue | undefined => {
    if (!props.modelValue) return undefined;
    try {
      return parseDate(props.modelValue);
    } catch {
      return undefined;
    }
  },
  set: (value: DateValue | undefined) => {
    if (value) {
      emit('update:modelValue', value.toString());
    } else {
      emit('update:modelValue', '');
    }
  },
});

// Min/Max date conversion
const minDateValue = computed(() => {
  if (!props.minDate) return undefined;
  try {
    return parseDate(props.minDate);
  } catch {
    return undefined;
  }
});

const maxDateValue = computed(() => {
  if (!props.maxDate) return undefined;
  try {
    return parseDate(props.maxDate);
  } catch {
    return undefined;
  }
});

// Format display text
const displayText = computed(() => {
  if (!dateValue.value) return props.placeholder;

  const date = dateValue.value.toDate(getLocalTimeZone());
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date);
});

// Year and month selection
const currentYear = ref(dateValue.value?.year ?? new Date().getFullYear());
const currentMonth = ref(dateValue.value?.month ?? new Date().getMonth() + 1);

// Generate year options (current year ± 10 years)
const yearOptions = computed(() => {
  const currentYearValue = new Date().getFullYear();
  const years = [];
  for (let i = currentYearValue - 10; i <= currentYearValue + 10; i++) {
    years.push(i);
  }
  return years;
});

const monthOptions = [
  { value: 1, label: 'January' },
  { value: 2, label: 'February' },
  { value: 3, label: 'March' },
  { value: 4, label: 'April' },
  { value: 5, label: 'May' },
  { value: 6, label: 'June' },
  { value: 7, label: 'July' },
  { value: 8, label: 'August' },
  { value: 9, label: 'September' },
  { value: 10, label: 'October' },
  { value: 11, label: 'November' },
  { value: 12, label: 'December' },
];

// Placeholder for calendar navigation
const placeholder = computed(() => {
  return new CalendarDate(currentYear.value, currentMonth.value, 1);
});

const handlePlaceholderUpdate = (date: DateValue) => {
  currentYear.value = date.year;
  currentMonth.value = date.month;
};
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :disabled="disabled"
        :class="
          cn(
            'w-full justify-start text-left font-normal',
            !modelValue && 'text-muted-foreground',
            $attrs.class as any,
          )
        "
      >
        <CalendarIcon class="mr-2 h-4 w-4" />
        <span>{{ displayText }}</span>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <!-- Month and Year Selectors -->
      <div class="flex items-center justify-between gap-2 border-b p-3">
        <Select
          :model-value="String(currentMonth)"
          @update:model-value="(val) => (currentMonth = Number(val))"
        >
          <SelectTrigger class="w-[130px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="month in monthOptions"
              :key="month.value"
              :value="String(month.value)"
            >
              {{ month.label }}
            </SelectItem>
          </SelectContent>
        </Select>

        <Select
          :model-value="String(currentYear)"
          @update:model-value="(val) => (currentYear = Number(val))"
        >
          <SelectTrigger class="w-[100px]">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="year in yearOptions"
              :key="year"
              :value="String(year)"
            >
              {{ year }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Calendar -->
      <Calendar
        v-model="dateValue"
        :placeholder="placeholder"
        @update:placeholder="handlePlaceholderUpdate"
        :min-value="minDateValue"
        :max-value="maxDateValue"
      />
    </PopoverContent>
  </Popover>
</template>
