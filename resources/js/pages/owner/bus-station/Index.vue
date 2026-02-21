<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// UI Components
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AlertCircle, CheckCircle2, Clock, Lock } from 'lucide-vue-next';

// Define Props from Controller
const props = defineProps<{
  stations: Array<{
    id: number;
    name: string;
    code_no: string;
    lat: string;
    lng: string;
    amount: number;
    status_id: number;
  }>;
  franchise_id: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Bus Station', href: owner.busstationmanagement().url },
];

// Status mapping helper
const getStatusDetails = (statusId: number) => {
  switch (statusId) {
    case 1:
      return {
        label: 'Active',
        class: 'bg-green-100 text-green-700 border-green-200',
        icon: CheckCircle2,
        canEdit: true,
      };
    case 18:
      return {
        label: 'Denied',
        class: 'bg-red-100 text-red-700 border-red-200',
        icon: AlertCircle,
        canEdit: true,
      };
    default: // Status 6
      return {
        label: 'Pending',
        class: 'bg-amber-100 text-amber-700 border-amber-200',
        icon: Clock,
        canEdit: false,
      };
  }
};

// --- MAP IMPORTS & LOGIC ---
import LocationMap, { type MarkerData } from '@/components/LocationMap.vue';

// New refs for state management
const originalLocation = ref<{ lat: string; lng: string } | null>(null);
const viewMode = ref(false); // New: Tracks if we are just looking at the map
const isDialogOpen = ref(false);
const editMode = ref(false);
const editingId = ref<number | null>(null);

const mapMarkers = computed<MarkerData[]>(() => {
  if (isDialogOpen.value) {
    const markers: MarkerData[] = [];

    // 1. Show the "Original" position as a reference (Ghost Pin)
    // Only show this when editing, not when just viewing or adding new
    if (originalLocation.value && editMode.value && !viewMode.value) {
      markers.push({
        id: 'original-pos',
        latitude: parseFloat(originalLocation.value.lat),
        longitude: parseFloat(originalLocation.value.lng),
        type: 'Start',
        name: 'Original Location',
      });
    }

    // 2. Show the "Active" pin from the form
    if (form.latitude && form.longitude) {
      markers.push({
        id: 'active-editor-pin',
        latitude: parseFloat(form.latitude),
        longitude: parseFloat(form.longitude),
        type: 'Pin',
        name: form.name || (editMode.value ? 'Target Location' : 'New Station'),
      });
    }
    return markers;
  }

  // Default: Show all stations when modal is closed
  return props.stations.map((s, idx) => ({
    id: s.id,
    latitude: parseFloat(s.lat),
    longitude: parseFloat(s.lng),
    type: idx === 0 ? 'Start' : 'End',
    name: s.name,
  }));
});

const handleLocationSelected = (coords: { lat: number; lng: number }) => {
  if (viewMode.value) return; // Prevent clicking map in view-only mode
  form.latitude = coords.lat.toFixed(6);
  form.longitude = coords.lng.toFixed(6);
};

// --- DIALOG ACTIONS ---

const openModal = () => {
  viewMode.value = false;
  editMode.value = false;
  editingId.value = null;
  originalLocation.value = null;
  form.reset();
  form.clearErrors();
  form.previous_station_id = lastStation.value ? lastStation.value.id : null;
  isDialogOpen.value = true;
};

const editStation = (station: any) => {
  viewMode.value = false;
  editMode.value = true;
  editingId.value = station.id;
  originalLocation.value = { lat: station.lat, lng: station.lng };

  form.clearErrors();
  form.name = station.name;
  form.code_no = station.code_no;
  form.latitude = station.lat;
  form.longitude = station.lng;
  form.amount = station.amount;
  form.previous_station_id = null;

  isDialogOpen.value = true;
};

const viewLocation = (station: any) => {
  viewMode.value = true;
  editMode.value = false;
  editingId.value = station.id;
  originalLocation.value = null;

  form.name = station.name;
  form.latitude = station.lat;
  form.longitude = station.lng;

  isDialogOpen.value = true;
};

const submit = () => {
  if (viewMode.value) return;

  const method = editMode.value ? 'put' : 'post';
  const url = editMode.value
    ? `/owner/bus-station/${editingId.value}`
    : owner.busstationmanagement.store().url;

  form[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      isDialogOpen.value = false;
      originalLocation.value = null;
      form.reset();
    },
  });
};

// --- FORM & COMPUTED ---
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

const hasPendingOrDenied = computed(() =>
  props.stations.some((s) => s.status_id === 6 || s.status_id === 18),
);
</script>

<template>
  <Head title="Bus Station Management" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <div
        class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
      >
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Station Management</h1>
          <!-- <p class="text-gray-600">
            Manage terminal sequence for Franchise #{{ franchise_id }}
          </p> -->
          <p class="text-gray-600">Manage terminal sequence</p>
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
          <Button @click="openModal" :disabled="hasPendingOrDenied">
            <template v-if="hasPendingOrDenied">
              <Lock class="mr-2 h-4 w-4" />
              Action Required on Route
            </template>
            <template v-else> + Add Station {{ nextLetter }} </template>
          </Button>
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
            :class="[
              'group relative rounded-2xl border-2 p-5 transition-all',
              station.status_id === 1
                ? 'border-slate-100 bg-white hover:border-blue-200 hover:shadow-sm'
                : 'border-slate-50 bg-slate-50/50 opacity-80',
            ]"
          >
            <div class="flex items-center gap-5">
              <div
                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xl font-bold text-white"
              >
                {{ String.fromCharCode(65 + index) }}
              </div>

              <div class="grid flex-grow grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                  <p class="text-[10px] font-black text-brand-blue uppercase">
                    Station Name
                  </p>
                  <h3 class="font-bold text-slate-800">{{ station.name }}</h3>
                </div>

                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase">
                    Status
                  </p>
                  <div
                    :class="[
                      'mt-1 inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-bold',
                      getStatusDetails(station.status_id).class,
                    ]"
                  >
                    <component
                      :is="getStatusDetails(station.status_id).icon"
                      class="h-3 w-3"
                    />
                    {{ getStatusDetails(station.status_id).label }}
                  </div>
                </div>

                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase">
                    Code
                  </p>
                  <p class="font-mono text-sm">{{ station.code_no }}</p>
                </div>

                <div
                  @click="viewLocation(station)"
                  class="group/loc cursor-pointer"
                >
                  <p
                    class="text-[10px] font-black text-slate-400 uppercase group-hover/loc:text-brand-blue"
                  >
                    Location
                  </p>
                  <p
                    class="font-mono text-xs text-slate-500 underline decoration-dotted group-hover/loc:text-brand-blue"
                  >
                    {{ station.lat }}, {{ station.lng }}
                  </p>
                </div>
              </div>

              <Button
                variant="outline"
                size="sm"
                :disabled="!getStatusDetails(station.status_id).canEdit"
                @click="editStation(station)"
              >
                <template v-if="getStatusDetails(station.status_id).canEdit">
                  <Pencil class="mr-2 h-4 w-4" /> Edit
                </template>
                <template v-else>
                  <Lock class="mr-2 h-4 w-4 text-slate-400" /> Locked
                </template>
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
      <DialogContent class="max-w-md overflow-hidden p-0">
        <form @submit.prevent="submit" class="flex max-h-[90vh] flex-col">
          <DialogHeader class="border-bottom p-6 pb-2">
            <DialogTitle>
              {{
                viewMode
                  ? 'Station Location'
                  : editMode
                    ? 'Edit Station Details'
                    : 'Add Station ' + nextLetter
              }}
            </DialogTitle>

            <DialogDescription>
              <template v-if="viewMode">
                Viewing location for
                <span class="font-bold text-slate-900">{{ form.name }}</span>
              </template>

              <template v-else-if="editMode">
                Update coordinates or station info for
                <span class="font-bold text-slate-900">{{ form.name }}</span>
              </template>

              <template v-else>
                <span v-if="lastStation">
                  Connecting from
                  <span class="font-bold text-brand-blue">{{
                    lastStation.name
                  }}</span>
                </span>
                <span v-else
                  >Set the starting point for this franchise route.</span
                >
              </template>
            </DialogDescription>
          </DialogHeader>

          <div
            class="flex-1 overflow-y-auto p-1"
            style="scrollbar-gutter: stable both-edges"
          >
            <div class="space-y-4 p-4 pt-2">
              <template v-if="!viewMode">
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
              </template>

              <div>
                <Label>{{
                  viewMode ? 'Map Preview' : 'Station Location'
                }}</Label>
                <div
                  class="relative mt-2.5 overflow-hidden rounded-xl border-2 border-slate-100"
                >
                  <LocationMap
                    :locations="mapMarkers"
                    :selectable="!viewMode"
                    @locationSelected="handleLocationSelected"
                    :center="
                      form.latitude
                        ? [
                            parseFloat(form.latitude),
                            parseFloat(form.longitude),
                          ]
                        : [15.1465, 120.5794]
                    "
                    :zoom="16"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label>Latitude</Label>
                  <Input
                    :model-value="form.latitude"
                    readonly
                    class="bg-slate-50 font-mono text-xs"
                  />
                </div>
                <div class="space-y-2">
                  <Label>Longitude</Label>
                  <Input
                    :model-value="form.longitude"
                    readonly
                    class="bg-slate-50 font-mono text-xs"
                  />
                </div>
              </div>

              <div
                v-if="
                  !viewMode &&
                  ((props.stations.length > 0 && !editMode) ||
                    (editMode && props.stations[0]?.id !== editingId))
                "
                class="rounded-xl border-2 border-dashed border-blue-200 bg-blue-50 p-4 text-center"
              >
                <Label class="text-xs font-bold text-brand-blue uppercase"
                  >Fare Amount (₱)</Label
                >
                <Input
                  v-model="form.amount"
                  type="number"
                  step="0.01"
                  class="mt-2 text-center text-xl font-bold"
                />
              </div>

              <p
                v-if="!viewMode"
                class="pb-2 text-center text-[10px] text-slate-400 italic"
              >
                Click on the map to pin the station location.
              </p>
            </div>
          </div>

          <DialogFooter class="border-t p-6 pt-4">
            <Button
              type="button"
              variant="outline"
              @click="isDialogOpen = false"
            >
              {{ viewMode ? 'Close' : 'Cancel' }}
            </Button>
            <Button v-if="!viewMode" type="submit" :disabled="form.processing">
              {{
                form.processing
                  ? 'Saving...'
                  : editMode
                    ? props.stations.find((s) => s.id === editingId)
                        ?.status_id === 18
                      ? 'Fix & Resubmit'
                      : 'Update Station'
                    : 'Confirm & Save'
              }}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
