<script setup lang="ts">
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
  AlertCircle,
  CheckCircle2,
  Clock,
  Lock,
  MapPin,
  Pencil,
  Plus,
  Trash2,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

// UI Components
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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

// Map Logic
import LocationMap, { type MarkerData } from '@/components/LocationMap.vue';

const props = defineProps<{
  stations: Array<{
    id: number;
    name: string;
    code_no: string;
    lat: string;
    lng: string;
    amount: number;
    status_id: number;
    station_amount_id: number | null;
    schedules: Array<{ id: number; from_time: string; to_time: string }>;
  }>;
  franchise_id: number;
  transactions: Array<{
    id: number;
    passenger_name: string;
    origin: string;
    destination: string;
    amount: string;
    date: string;
    time_window: string;
    status_text: string;
    is_paid: boolean;
    is_pending: boolean;
    is_completed: boolean;
    booked_at: string;
  }>;
  initialFilter: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Bus Station', href: owner.busstationmanagement().url },
];

// --- TAB PERSISTENCE ---
const activeTab = ref('stations');

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.has('tab')) activeTab.value = params.get('tab') as string;
});

watch(activeTab, (newTab) => {
  const url = new URL(window.location.href);
  url.searchParams.set('tab', newTab);
  window.history.replaceState({}, '', url);
});

// --- RESERVATION LOGIC ---
const filteredTransactions = computed(() => {
  if (props.initialFilter === 'completed') {
    return props.transactions.filter((t) => t.is_completed);
  } else if (props.initialFilter === 'paid') {
    return props.transactions.filter((t) => t.is_paid);
  }
  return props.transactions.filter((t) => t.is_pending);
});

const updateFilter = (status: string) => {
  router.get(
    window.location.pathname,
    { tab: 'reservations', status: status },
    { preserveState: true, replace: true, preserveScroll: true },
  );
};

// --- SCHEDULE LOGIC (STATION-SPECIFIC TIMES) ---
const isScheduleDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const scheduleToDeleteId = ref<number | null>(null);
const editingScheduleId = ref<number | null>(null);
const selectedStationForSchedule = ref<any>(null);

const scheduleForm = useForm({
  bus_station_id: null as number | null,
  from_time: '', // Departure from this station
  to_time: '', // Arrival at this station
});

const openAddSchedule = (station: any) => {
  editingScheduleId.value = null;
  selectedStationForSchedule.value = station;
  scheduleForm.reset();
  scheduleForm.bus_station_id = station.id;
  isScheduleDialogOpen.value = true;
};

const openEditSchedule = (station: any, schedule: any) => {
  editingScheduleId.value = schedule.id;
  selectedStationForSchedule.value = station;
  scheduleForm.bus_station_id = station.id;
  scheduleForm.from_time = schedule.from_time;
  scheduleForm.to_time = schedule.to_time;
  isScheduleDialogOpen.value = true;
};

const submitSchedule = () => {
  const url = editingScheduleId.value
    ? `/owner/bus-station/schedule/${editingScheduleId.value}`
    : '/owner/bus-station/schedule';

  const method = editingScheduleId.value ? 'put' : 'post';

  scheduleForm[method](url, {
    preserveScroll: true,
    onSuccess: () => {
      isScheduleDialogOpen.value = false;
      scheduleForm.reset();
      toast.success(editingScheduleId.value ? 'Time updated' : 'Time added');
    },
    onError: () => toast.error('Please check the time format'),
  });
};

const confirmDeleteSchedule = (id: number) => {
  scheduleToDeleteId.value = id;
  isDeleteDialogOpen.value = true;
};

const executeDeleteSchedule = () => {
  if (!scheduleToDeleteId.value) return;

  router.delete(`/owner/bus-station/schedule/${scheduleToDeleteId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteDialogOpen.value = false;
      toast.success('Time slot removed');
    },
    onError: () => toast.error('Failed to delete time slot'),
  });
};

// --- STATUS HELPER ---
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
    default:
      return {
        label: 'Pending',
        class: 'bg-amber-100 text-amber-700 border-amber-200',
        icon: Clock,
        canEdit: false,
      };
  }
};

// --- STATION FORM & MAP LOGIC ---
const originalLocation = ref<{ lat: string; lng: string } | null>(null);
const viewMode = ref(false);
const isStationDialogOpen = ref(false);
const editMode = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
  name: '',
  code_no: '',
  latitude: '',
  longitude: '',
  amount: 0,
  franchise_id: props.franchise_id,
  previous_station_id: null as number | null,
});

const mapMarkers = computed<MarkerData[]>(() => {
  if (isStationDialogOpen.value) {
    const markers: MarkerData[] = [];
    if (originalLocation.value && editMode.value && !viewMode.value) {
      markers.push({
        id: 'orig',
        latitude: parseFloat(originalLocation.value.lat),
        longitude: parseFloat(originalLocation.value.lng),
        type: 'Start',
        name: 'Original',
      });
    }
    if (form.latitude && form.longitude) {
      markers.push({
        id: 'pin',
        latitude: parseFloat(form.latitude),
        longitude: parseFloat(form.longitude),
        type: 'Pin',
        name: form.name || 'Location',
      });
    }
    return markers;
  }
  return props.stations.map((s, idx) => ({
    id: s.id,
    latitude: parseFloat(s.lat),
    longitude: parseFloat(s.lng),
    type: idx === 0 ? 'Start' : 'End',
    name: s.name,
  }));
});

const handleLocationSelected = (coords: { lat: number; lng: number }) => {
  if (viewMode.value) return;
  form.latitude = coords.lat.toFixed(6);
  form.longitude = coords.lng.toFixed(6);
};

const openModal = () => {
  viewMode.value = false;
  editMode.value = false;
  editingId.value = null;
  originalLocation.value = null;
  form.reset();
  form.clearErrors();
  form.previous_station_id = lastStation.value ? lastStation.value.id : null;
  isStationDialogOpen.value = true;
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
  isStationDialogOpen.value = true;
};

const viewLocation = (station: any) => {
  viewMode.value = true;
  editMode.value = false;
  editingId.value = station.id;
  form.name = station.name;
  form.latitude = station.lat;
  form.longitude = station.lng;
  isStationDialogOpen.value = true;
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
      isStationDialogOpen.value = false;
      form.reset();
      toast.success(editMode.value ? 'Station updated' : 'Station added');
    },
    onError: () => toast.error('Check your inputs'),
  });
};

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
      <Tabs v-model="activeTab" class="w-full">
        <TabsList
          class="w-full justify-start gap-3 overflow-x-auto bg-muted/50 p-1.5"
        >
          <TabsTrigger value="stations" class="px-4"
            >Station Management</TabsTrigger
          >
          <TabsTrigger value="schedules" class="px-4"
            >Schedule Management</TabsTrigger
          >
          <TabsTrigger value="reservations" class="px-4"
            >Reservation Management</TabsTrigger
          >
        </TabsList>
      </Tabs>

      <div v-if="activeTab === 'stations'" class="space-y-6">
        <div
          class="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center"
        >
          <div>
            <h1 class="text-3xl font-bold tracking-tight">
              Station Management
            </h1>
            <p class="text-gray-600">Define terminals and stopping points</p>
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
              <template v-if="hasPendingOrDenied"
                ><Lock class="mr-2 h-4 w-4" /> Action Required</template
              >
              <template v-else>+ Add Station {{ nextLetter }}</template>
            </Button>
          </div>
        </div>

        <div v-if="props.stations.length > 0">
          <div v-for="(station, index) in props.stations" :key="station.id">
            <div v-if="index !== 0" class="my-1 ml-6 flex h-12 items-center">
              <div class="h-full w-1 rounded-full bg-brand-blue"></div>
              <span
                class="ml-6 rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-bold text-brand-blue"
              >
                + ₱{{ station.amount }} from previous
              </span>
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
                  <Pencil class="mr-2 h-4 w-4" /> Edit
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'schedules'" class="space-y-6">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Station Schedules</h1>
          <p class="text-gray-600">
            Manage arrival and departure times for each station
          </p>
        </div>
        <div
          v-for="(station, index) in props.stations"
          :key="'sched-' + station.id"
          class="rounded-2xl border-2 border-slate-100 bg-white p-6 transition-all hover:border-blue-100"
        >
          <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-sm font-bold text-white"
              >
                {{ String.fromCharCode(65 + index) }}
              </div>
              <h3 class="text-lg font-bold text-slate-800">
                {{ station.name }}
              </h3>
            </div>
            <Button
              v-if="station.status_id === 1"
              size="sm"
              @click="openAddSchedule(station)"
            >
              <Plus class="mr-2 h-4 w-4" /> Add Time Slot
            </Button>
          </div>

          <div class="flex flex-wrap gap-3">
            <div
              v-for="sched in station.schedules"
              :key="sched.id"
              class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2"
            >
              <div class="flex flex-col">
                <span class="text-[9px] font-bold text-slate-400 uppercase"
                  >Arrive</span
                >
                <span class="font-mono font-bold text-slate-700">{{
                  sched.to_time
                }}</span>
              </div>
              <div class="mx-2 h-6 w-px bg-slate-200"></div>
              <div class="flex flex-col">
                <span class="text-[9px] font-bold text-brand-blue uppercase"
                  >Depart</span
                >
                <span class="font-mono font-bold text-brand-blue">{{
                  sched.from_time
                }}</span>
              </div>
              <div class="ml-4 flex gap-1 border-l pl-2">
                <button
                  @click="openEditSchedule(station, sched)"
                  class="text-slate-400 hover:text-blue-600"
                >
                  <Pencil class="h-3.5 w-3.5" />
                </button>
                <button
                  @click="confirmDeleteSchedule(sched.id)"
                  class="text-slate-400 hover:text-red-600"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
            <p
              v-if="station.schedules.length === 0"
              class="text-sm text-slate-400 italic"
            >
              No times set for this station.
            </p>
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'reservations'" class="space-y-6">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">
            Station Reservations
          </h1>
          <p class="text-gray-600">
            Monitor and manage passenger bookings originating from your
            stations.
          </p>
        </div>

        <div
          class="mt-6 mb-8 flex w-fit items-center rounded-2xl bg-slate-200/50 p-1"
        >
          <button
            @click="updateFilter('completed')"
            :class="[
              initialFilter === 'completed'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-700',
            ]"
            class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
          >
            Completed
          </button>
          <button
            @click="updateFilter('paid')"
            :class="[
              initialFilter === 'paid'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-700',
            ]"
            class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
          >
            Paid Trips
          </button>
          <button
            @click="updateFilter('pending')"
            :class="[
              initialFilter === 'pending'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-700',
            ]"
            class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
          >
            Pending
          </button>
        </div>

        <div
          v-if="filteredTransactions.length === 0"
          class="rounded-3xl border-2 border-dashed border-slate-200 bg-white py-20 text-center"
        >
          <div class="flex flex-col items-center justify-center space-y-2">
            <div class="rounded-full bg-slate-50 p-4">
              <Clock class="h-8 w-8 text-slate-300" />
            </div>
            <p class="text-sm font-medium text-slate-400 italic">
              No {{ initialFilter }} reservations found for your stations.
            </p>
          </div>
        </div>

        <div
          v-else
          class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-1"
        >
          <div v-for="tx in filteredTransactions" :key="tx.id">
            <div
              class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/40"
            >
              <div
                class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-3"
              >
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase',
                      tx.is_completed
                        ? 'bg-blue-100 text-blue-700'
                        : tx.is_paid
                          ? 'bg-green-100 text-green-700'
                          : 'bg-amber-100 text-amber-700',
                    ]"
                  >
                    {{ tx.status_text }}
                  </span>
                  <span class="text-[10px] font-bold text-slate-400 uppercase"
                    >#{{ tx.id }}</span
                  >
                </div>
                <span
                  class="text-[10px] font-medium tracking-widest text-slate-400 uppercase"
                >
                  Booked: {{ tx.booked_at }}
                </span>
              </div>

              <div class="p-6">
                <div
                  class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-start"
                >
                  <div class="flex gap-4">
                    <div class="flex flex-col items-center py-1">
                      <div
                        class="h-2.5 w-2.5 rounded-full border-2 border-blue-500 bg-white"
                      ></div>
                      <div
                        class="my-1 h-8 w-0.5 border-l-2 border-dotted border-slate-200"
                      ></div>
                      <div class="h-2.5 w-2.5 rounded-full bg-red-500"></div>
                    </div>

                    <div class="space-y-4">
                      <div>
                        <p
                          class="mb-1 text-[10px] leading-none font-bold text-slate-400 uppercase"
                        >
                          From
                        </p>
                        <p class="leading-tight font-bold text-slate-800">
                          {{ tx.origin }}
                        </p>
                      </div>
                      <div>
                        <p
                          class="mb-1 text-[10px] leading-none font-bold text-slate-400 uppercase"
                        >
                          To
                        </p>
                        <p class="leading-tight font-bold text-slate-800">
                          {{ tx.destination }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div
                    class="flex items-end justify-between md:flex-col md:text-right"
                  >
                    <div>
                      <p
                        class="text-[10px] font-bold tracking-tight text-slate-400 uppercase"
                      >
                        Passenger Name
                      </p>
                      <div class="flex items-center gap-1 md:justify-end">
                        <span class="font-bold text-slate-900">{{
                          tx.passenger_name
                        }}</span>
                      </div>
                    </div>
                    <div class="mt-2">
                      <p class="text-2xl font-black text-slate-900">
                        ₱{{ tx.amount }}
                      </p>
                      <p class="text-[10px] font-bold text-slate-400 uppercase">
                        Fare Amount
                      </p>
                    </div>
                  </div>
                </div>

                <div
                  class="mb-4 flex flex-wrap items-center gap-4 rounded-2xl bg-slate-50 p-4"
                >
                  <div class="flex items-center gap-2">
                    <Clock class="h-4 w-4 text-slate-400" />
                    <div>
                      <p class="text-[9px] font-bold text-slate-400 uppercase">
                        Schedule Time
                      </p>
                      <p class="text-xs font-bold text-slate-700">
                        {{ tx.time_window }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <MapPin class="h-4 w-4 text-slate-400" />
                    <div>
                      <p class="text-[9px] font-bold text-slate-400 uppercase">
                        Departure Date
                      </p>
                      <p class="text-xs font-bold text-slate-700">
                        {{ tx.date }}
                      </p>
                    </div>
                  </div>
                </div>

                <div v-if="tx.is_completed">
                  <div
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-100 py-4 text-sm font-bold text-slate-500"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                    Trip Finished
                  </div>
                </div>

                <div v-else-if="tx.is_paid">
                  <div
                    class="flex w-full items-center justify-center gap-2 rounded-2xl border border-green-100 bg-green-50 py-4 text-sm font-bold text-green-700"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                    Ticket Active & Paid
                  </div>
                </div>

                <div
                  v-else
                  class="flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50 py-4 text-xs font-bold tracking-widest text-slate-400 uppercase"
                >
                  Awaiting Passenger Payment
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog
      :open="isScheduleDialogOpen"
      @update:open="isScheduleDialogOpen = $event"
    >
      <DialogContent class="max-w-sm">
        <DialogHeader>
          <DialogTitle
            >{{ editingScheduleId ? 'Edit' : 'Add' }} Station Time</DialogTitle
          >
          <DialogDescription
            >Set specific times for
            {{ selectedStationForSchedule?.name }}</DialogDescription
          >
        </DialogHeader>
        <div class="space-y-4 py-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Arrival Time</Label>
              <Input type="time" v-model="scheduleForm.to_time" />
            </div>
            <div class="space-y-2">
              <Label>Departure Time</Label>
              <Input type="time" v-model="scheduleForm.from_time" />
            </div>
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" @click="isScheduleDialogOpen = false"
            >Cancel</Button
          >
          <Button @click="submitSchedule" :disabled="scheduleForm.processing"
            >Save Times</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog
      :open="isStationDialogOpen"
      @update:open="isStationDialogOpen = $event"
    >
      <DialogContent class="max-w-md overflow-hidden p-0">
        <form @submit.prevent="submit" class="flex max-h-[90vh] flex-col">
          <DialogHeader class="border-bottom p-6 pb-2">
            <DialogTitle>{{
              viewMode
                ? 'Station Location'
                : editMode
                  ? 'Edit Station Details'
                  : 'Add Station ' + nextLetter
            }}</DialogTitle>
            <DialogDescription>
              <template v-if="viewMode"
                >Viewing
                <span class="font-bold text-slate-900">{{
                  form.name
                }}</span></template
              >
              <template v-else
                >Configure details for station {{ nextLetter }}</template
              >
            </DialogDescription>
          </DialogHeader>

          <div class="flex-1 space-y-4 overflow-y-auto p-4 pt-2">
            <template v-if="!viewMode">
              <div class="space-y-2">
                <Label>Station Name</Label>
                <Input
                  v-model="form.name"
                  placeholder="Ex: San Fernando Terminal"
                  required
                />
              </div>
              <div class="space-y-2">
                <Label>Station Code</Label>
                <Input v-model="form.code_no" placeholder="SF-01" required />
              </div>
            </template>
            <div>
              <Label>{{ viewMode ? 'Map Preview' : 'Station Location' }}</Label>
              <div
                class="relative mt-2.5 overflow-hidden rounded-xl border-2 border-slate-100"
              >
                <LocationMap
                  :locations="mapMarkers"
                  :selectable="!viewMode"
                  @locationSelected="handleLocationSelected"
                  :center="
                    form.latitude
                      ? [parseFloat(form.latitude), parseFloat(form.longitude)]
                      : [15.1465, 120.5794]
                  "
                  :zoom="16"
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
                >Fare from Previous Station (₱)</Label
              >
              <Input
                v-model="form.amount"
                type="number"
                step="0.01"
                class="mt-2 text-center text-xl font-bold"
              />
            </div>
          </div>

          <DialogFooter class="border-t p-6 pt-4">
            <Button
              type="button"
              variant="outline"
              @click="isStationDialogOpen = false"
              >{{ viewMode ? 'Close' : 'Cancel' }}</Button
            >
            <Button v-if="!viewMode" type="submit" :disabled="form.processing">
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

    <AlertDialog
      :open="isDeleteDialogOpen"
      @update:open="isDeleteDialogOpen = $event"
    >
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Remove time slot?</AlertDialogTitle>
          <AlertDialogDescription
            >This will delete the arrival/departure times for this
            station.</AlertDialogDescription
          >
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction
            @click="executeDeleteSchedule"
            class="bg-red-600 hover:bg-red-700"
            >Delete</AlertDialogAction
          >
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </AppLayout>
</template>
