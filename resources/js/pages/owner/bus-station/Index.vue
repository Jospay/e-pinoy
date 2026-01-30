<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import owner from '@/routes/owner';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Pencil } from 'lucide-vue-next';

// UI Components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

// Define Props from Controller
const props = defineProps<{
  stations: Array<{
    id: number;
    name: string;
    code_no: string;
    lat: string;
    lng: string;
    amount: number;
  }>;
  franchise_id: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Bus Station', href: owner.busstationmanagement().url },
];

const isDialogOpen = ref(false);
const editMode = ref(false);
const editingId = ref<number | null>(null);

// Inertia Form Logic
const form = useForm({
  name: '',
  code_no: '',
  latitude: '',
  longitude: '',
  amount: 0,
  franchise_id: props.franchise_id,
  previous_station_id: null as number | null,
});

const lastStation = computed(() => props.stations[props.stations.length - 1]);
const nextLetter = computed(() =>
  String.fromCharCode(65 + props.stations.length),
);
const totalRouteCost = computed(() =>
  props.stations.reduce((acc, curr) => acc + Number(curr.amount), 0),
);

// Open Modal for Creating
const openModal = () => {
  editMode.value = false;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  form.previous_station_id = lastStation.value ? lastStation.value.id : null;
  isDialogOpen.value = true;
};

// Open Modal for Editing
const editStation = (station: any) => {
  editMode.value = true;
  editingId.value = station.id;
  form.clearErrors();

  form.name = station.name;
  form.code_no = station.code_no;
  form.latitude = station.lat;
  form.longitude = station.lng;
  form.amount = station.amount;
  form.previous_station_id = null;

  isDialogOpen.value = true;
};

const submit = () => {
  if (editMode.value && editingId.value) {
    form.put(`/owner/bus-station/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
    });
  } else {
    // CREATE
    form.post(owner.busstationmanagement.store().url, {
      preserveScroll: true,
      onSuccess: () => {
        isDialogOpen.value = false;
        form.reset();
      },
      onError: (errors) => {
        console.error('Validation failed:', errors);
      },
    });
  }
};
</script>

<template>
  <Head title="Bus Station Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div
        class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
      >
        <div>
          <h1 class="text-3xl font-bold">Station Management</h1>
          <p class="tracking-tight text-gray-600">
            Manage the terminal sequence for Franchise #{{ franchise_id }}
          </p>
        </div>
        <div
          class="flex items-center gap-3 rounded-xl border bg-white p-2 shadow-sm"
        >
          <div
            class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-1"
          >
            <p class="text-[10px] font-bold text-slate-500 uppercase">
              Total Fare:
            </p>
            <p class="text-lg font-bold">
              ₱{{ totalRouteCost.toLocaleString() }}
            </p>
          </div>
          <Button @click="openModal"> + Add Station {{ nextLetter }} </Button>
        </div>
      </div>

      <div v-if="props.stations.length > 0" class="relative">
        <div v-for="(station, index) in props.stations" :key="station.id">
          <div v-if="index !== 0" class="my-1 ml-6 flex h-12 items-center">
            <div class="h-full w-1 rounded-full bg-brand-blue"></div>
            <div class="ml-6 flex items-center gap-2">
              <span
                class="rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-bold text-brand-blue"
              >
                + ₱{{ station.amount }} fare from
                {{ props.stations[index - 1].name }}
              </span>
            </div>
          </div>

          <div
            class="group relative rounded-2xl border-2 border-slate-100 bg-white p-5 transition-all hover:border-blue-200 hover:shadow-sm"
          >
            <div class="flex items-center gap-5">
              <div
                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xl font-bold text-white"
              >
                {{ String.fromCharCode(65 + index) }}
              </div>
              <div class="grid flex-grow grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                  <p class="text-[10px] font-black text-brand-blue uppercase">
                    Station Name
                  </p>
                  <h3 class="font-bold text-slate-800">{{ station.name }}</h3>
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase">
                    Code
                  </p>
                  <p class="font-mono text-sm">{{ station.code_no }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase">
                    Location
                  </p>
                  <p class="font-mono text-xs text-slate-500">
                    {{ station.lat || '0.0' }}, {{ station.lng || '0.0' }}
                  </p>
                </div>
              </div>

              <Button variant="outline" size="sm" @click="editStation(station)">
                <Pencil class="mr-2 h-4 w-4" />
                Edit
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-else
        class="rounded-3xl border-2 border-dashed bg-gray-50/50 py-20 text-center"
      >
        <p class="text-gray-400">
          No stations found. Click "Add Station A" to begin your route.
        </p>
      </div>
    </div>

    <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
      <DialogContent class="sm:max-w-[425px]">
        <form @submit.prevent="submit">
          <DialogHeader>
            <DialogTitle>{{
              editMode ? 'Edit Station Details' : 'Add Station ' + nextLetter
            }}</DialogTitle>
            <DialogDescription v-if="!editMode && lastStation">
              Connecting from
              <span class="font-bold text-brand-blue">{{
                lastStation.name
              }}</span>
            </DialogDescription>
          </DialogHeader>

          <div class="grid gap-4 py-4">
            <div class="space-y-2">
              <Label>Station Name</Label>
              <Input
                v-model="form.name"
                placeholder="Ex: San Fernando Terminal"
                required
              />
              <p v-if="form.errors.name" class="text-xs text-red-500">
                {{ form.errors.name }}
              </p>
            </div>
            <div class="space-y-2">
              <Label>Station Code</Label>
              <Input v-model="form.code_no" placeholder="SF-01" required />
              <p v-if="form.errors.code_no" class="text-xs text-red-500">
                {{ form.errors.code_no }}
              </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Latitude</Label>
                <Input v-model="form.latitude" placeholder="14.5" />
              </div>
              <div class="space-y-2">
                <Label>Longitude</Label>
                <Input v-model="form.longitude" placeholder="121.0" />
              </div>
            </div>

            <div
              v-if="
                (props.stations.length > 0 && !editMode) ||
                (editMode && props.stations[0]?.id !== editingId)
              "
              class="mt-2 rounded-xl border-2 border-dashed border-blue-200 bg-blue-50 p-4 text-center"
            >
              <Label class="text-xs font-bold text-brand-blue uppercase"
                >Fare Amount to reach this station (₱)</Label
              >
              <Input
                v-model="form.amount"
                type="number"
                step="0.01"
                class="mt-2 text-center text-xl font-bold"
              />
            </div>
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              @click="isDialogOpen = false"
              >Cancel</Button
            >
            <Button type="submit" :disabled="form.processing">
              {{
                form.processing
                  ? 'Saving...'
                  : editMode
                    ? 'Update Station'
                    : 'Confirm & Save'
              }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
