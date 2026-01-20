<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Option {
    id: number | string;
    label: string;
}

const props = defineProps<{
    options: Option[];
    modelValue: string[]; // Array of IDs
    placeholder: string;
    allLabel: string;
}>();

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const localValue = ref<string[]>([...props.modelValue]);

// Sync local value when prop changes (from external reset)
watch(() => props.modelValue, (newVal) => {
    localValue.value = [...newVal];
});

const selectedLabels = computed(() => {
    if (localValue.value.length === 0) return props.placeholder;
    if (localValue.value.length > 2) return `${localValue.value.length} Selected`;
    return props.options
        .filter(opt => localValue.value.includes(String(opt.id)))
        .map(opt => opt.label)
        .join(', ');
});

const toggleOption = (id: string) => {
    const index = localValue.value.indexOf(id);
    if (index > -1) {
        localValue.value.splice(index, 1);
    } else {
        localValue.value.push(id);
    }
};

const handleAll = () => {
    localValue.value = [];
    emit('update:modelValue', []);
    emit('change', []);
    open.value = false; // Close immediately
};

const handleOpenChange = (isOpen: boolean) => {
    open.value = isOpen;
    // When clicking outside (closing), trigger the filter
    if (!isOpen) {
        emit('update:modelValue', localValue.value);
        emit('change', localValue.value);
    }
};
</script>

<template>
    <Popover :open="open" @update:open="handleOpenChange">
        <PopoverTrigger as-child>
            <Button variant="outline" role="combobox" :aria-expanded="open"
                class="w-[200px] justify-between font-normal p-5">
                <span class="truncate">{{ selectedLabels }}</span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[200px] p-0" align="start">
            <div class="flex flex-col">
                <div class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                    @click="handleAll">
                    <Check :class="['mr-2 h-4 w-4', localValue.length === 0 ? 'opacity-100' : 'opacity-0']" />
                    {{ allLabel }}
                </div>
                <Separator />
                <div class="max-h-64 overflow-y-auto p-1">
                    <Label v-for="option in options" :key="option.id" :for="String(option.id)"
                        class="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground w-full h-full">
                        <Checkbox :id="String(option.id)" :model-value="localValue.includes(String(option.id))"
                            @update:model-value="() => toggleOption(String(option.id))"
                            class="mr-2 h-4 w-4 cursor-pointer border-accent-foreground/30" />
                        <span class="truncate ">{{ option.label }}</span>
                    </Label>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>