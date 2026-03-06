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
  Users,
  Lock,
  Calendar,
  Pencil,
  Bus,
  Loader2,
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

// --- TYPES FOR TS RESOLUTION ---
interface Stop {
  id: number;
  station_name: string;
  station_id: number;
  from_time: string;
  to_time: string;
  order: number;
}

interface GroupedRoute {
  vehicle_name: string;
  all_days: string[];
  vehicle_id: number;
  reservation_id: number;
  day_ids: number[];
  stops: Stop[];
}

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
    schedules: Array<{
      id: number;
      from_time: string;
      to_time: string;
      vehicle_id?: number;
      reservation_id: number;
      day_schedule_id?: number;
      day_schedule_ids?: number[];
      vehicle_name?: string;
      day_name?: string;
      order?: number;
    }>;
  }>;
  franchise_id: number;
  vehicles: Array<{ id: number; plate_number: string; model: string }>;
  daySchedules: Array<{ id: number; name: string }>;
  transactions: Array<{
    id: number;
    passenger_name: string;
    origin: string;
    destination: string;
    passenger_count: string;
    amount: string;
    date: string;
    time_window: string;
    status_text: string;
    is_paid: boolean;
    is_pending: boolean;
    is_refund: boolean;
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

// --- GROUPED SCHEDULES LOGIC ---
const groupedSchedules = computed<any[]>(() => {
  const routes: Record<string, any> = {};
  const activeStations = props.stations.filter((s) => s.status_id === 1);
  const activeStationIds = activeStations.map((s) => s.id);

  activeStations.forEach((station) => {
    station.schedules.forEach((sched) => {
      // FIX: Group by reservation_id. This links all days and stations together.
      const groupKey = `res-${sched.reservation_id}`;

      if (!routes[groupKey]) {
        const vehicle = props.vehicles.find((v) => v.id === sched.vehicle_id);

        // Map the IDs we got from the controller to their actual names
        const dayNames = (sched.day_schedule_ids || [])
          .map((id) => {
            const dayDoc = props.daySchedules.find((d) => d.id === id);
            return dayDoc ? dayDoc.name : '';
          })
          .filter((name) => name !== '');

        routes[groupKey] = {
          vehicle_name: vehicle
            ? `${vehicle.plate_number} (${vehicle.model})`
            : 'Assigned Vehicle',
          vehicle_id: sched.vehicle_id,
          reservation_id: sched.reservation_id,
          day_ids: sched.day_schedule_ids || [],
          all_days: dayNames,
          stops: [],
        };
      }

      // Avoid duplicate stops in the array
      if (!routes[groupKey].stops.find((s) => s.station_id === station.id)) {
        routes[groupKey].stops.push({
          id: sched.id,
          station_name: station.name,
          station_id: station.id,
          from_time: sched.from_time,
          to_time: sched.to_time,
          order: sched.order || 0,
        });
      }
    });
  });

  return Object.values(routes)
    .map((route: GroupedRoute) => {
      route.stops = route.stops.filter((stop: Stop) =>
        activeStationIds.includes(stop.station_id),
      );

      route.stops.sort((a: Stop, b: Stop) => a.order - b.order);

      return route;
    })
    .filter((route: GroupedRoute) => route.stops.length > 0);
});

// --- RESERVATION LOGIC ---
const filteredTransactions = computed(() => {
  if (props.initialFilter === 'completed') {
    return props.transactions.filter((t) => t.is_completed);
  } else if (props.initialFilter === 'paid') {
    return props.transactions.filter((t) => t.is_paid);
  } else if (props.initialFilter === 'refund') {
    return props.transactions.filter((t) => t.is_refund);
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

// --- SCHEDULE LOGIC ---
const isScheduleDialogOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const scheduleToDeleteId = ref<number | null>(null);
const editingScheduleId = ref<number | null>(null);
const selectedStationForSchedule = ref<any>(null);

const scheduleForm = useForm({
  reservation_id: null as number | null, // Add this
  vehicle_id: null as number | null,
  day_schedule_ids: [] as number[],
  stations: {} as Record<number, any>,
});

const handleStationToggle = (stationId: number) => {
  const station = scheduleForm.stations[stationId];

  if (station.selected) {
    const currentOrders = Object.values(scheduleForm.stations)
      .map((s) => s.order)
      .filter((o): o is number => o !== null);

    station.order =
      currentOrders.length > 0 ? Math.max(...currentOrders) + 1 : 1;
  } else {
    const removedOrder = station.order;
    station.order = null;
    station.from_time = '';
    station.to_time = '';

    Object.values(scheduleForm.stations).forEach((s) => {
      if (s.order && removedOrder && s.order > removedOrder) {
        s.order -= 1;
      }
    });
  }
};

const getOrdinal = (n: number | null) => {
  return n ? n.toString() : '';
};

const openAddSchedule = (station?: any) => {
  editingScheduleId.value = null;
  scheduleForm.reset();
  selectedStationForSchedule.value = station || null;

  const stationInit: Record<number, any> = {};
  // ONLY iterate through active stations to avoid scheduling issues
  props.stations
    .filter((s) => s.status_id === 1)
    .forEach((s) => {
      stationInit[s.id] = {
        selected: station ? s.id === station.id : false,
        from_time: '',
        to_time: '',
        order: station && s.id === station.id ? 1 : null,
      };
    });

  scheduleForm.reset();
  scheduleForm.stations = stationInit;
  scheduleForm.day_schedule_ids = [];
  isScheduleDialogOpen.value = true;
};

const editFullRoute = (route: GroupedRoute) => {
  // 1. Set the IDs to identify this specific route for the Controller
  editingScheduleId.value = route.reservation_id;
  scheduleForm.reservation_id = route.reservation_id;
  scheduleForm.vehicle_id = route.vehicle_id;

  // 2. Set the operating days directly from the route data
  scheduleForm.day_schedule_ids = route.day_ids ? [...route.day_ids] : [];

  // 3. Initialize the station checklist
  const stationInit: Record<number, any> = {};

  props.stations
    .filter((s) => s.status_id === 1)
    .forEach((s) => {
      const existingStop = route.stops.find(
        (stop: Stop) => stop.station_id === s.id,
      );

      stationInit[s.id] = {
        selected: !!existingStop,
        from_time: existingStop ? existingStop.from_time : '',
        to_time: existingStop ? existingStop.to_time : '',
        order: existingStop ? existingStop.order : null,
      };
    });

  // 4. Update form state and open UI
  scheduleForm.stations = stationInit;
  isScheduleDialogOpen.value = true;
};

const toggleDay = (dayId: number) => {
  const index = scheduleForm.day_schedule_ids.indexOf(dayId);
  if (index > -1) {
    scheduleForm.day_schedule_ids.splice(index, 1);
  } else {
    scheduleForm.day_schedule_ids.push(dayId);
  }
};

const submitSchedule = () => {
  const payloadStations: Record<number, any> = {};
  let hasSelection = false;

  Object.keys(scheduleForm.stations).forEach((id) => {
    const stationId = parseInt(id);
    const data = scheduleForm.stations[stationId];
    if (data && data.selected) {
      payloadStations[stationId] = {
        to_time: data.to_time,
        from_time: data.order === 1 ? data.from_time : data.to_time,
        order: data.order,
      };
      hasSelection = true;
    }
  });

  if (!hasSelection) {
    toast.error('Please select at least one station');
    return;
  }

  if (scheduleForm.day_schedule_ids.length === 0) {
    toast.error('Please select at least one operation day');
    return;
  }

  scheduleForm
    .transform((data) => ({
      ...data,
      stations: payloadStations,
    }))
    .post('/owner/bus-station/bulk-schedule', {
      preserveScroll: true,
      preserveState: true, // Keep state to avoid flickering
      onSuccess: () => {
        isScheduleDialogOpen.value = false;
        toast.success('Route schedule updated successfully');
        scheduleForm.reset();
      },
      onError: (errors) => {
        console.error('Submission errors:', errors);
        toast.error('Could not save schedule. Check your time formats.');
      },
      onFinish: () => {
        // This ensures the button stops loading even if onSuccess fails
        scheduleForm.processing = false;
      },
    });
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
        <div
          class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
          <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
              Route Schedules
            </h1>
            <p class="text-slate-500">
              View and manage complete journeys across all stations
            </p>
          </div>
          <Button
            variant="outline"
            class="border-2 font-bold hover:bg-slate-50"
            @click="openAddSchedule()"
          >
            <Clock class="mr-2 h-4 w-4 text-brand-blue" />
            Assign Full Route
          </Button>
        </div>

        <div v-if="groupedSchedules.length > 0" class="space-y-8">
          <div
            v-for="(route, rIndex) in groupedSchedules"
            :key="'route-' + rIndex"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md"
          >
            <div
              class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-6 py-4"
            >
              <div class="flex items-center gap-4">
                <div
                  class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-blue text-white shadow-lg shadow-brand-blue/20"
                >
                  <Bus class="h-6 w-6" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900">
                    {{ route.vehicle_name }}
                  </h3>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="day in route.all_days"
                        :key="day"
                        class="rounded-md border border-blue-100 bg-blue-50 px-2 py-0.5 text-[9px] font-black tracking-widest text-brand-blue uppercase"
                      >
                        {{ day }}
                      </span>
                    </div>
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    <span
                      class="text-[10px] font-extrabold text-brand-blue uppercase"
                    >
                      {{ route.stops.length }} Route
                    </span>
                  </div>
                </div>
              </div>
              <Button
                variant="ghost"
                size="sm"
                @click="editFullRoute(route)"
                class="rounded-xl text-slate-500 hover:bg-white hover:text-brand-blue"
              >
                <Pencil class="mr-1.5 h-4 w-4" /> Edit Route
              </Button>
            </div>

            <div class="overflow-x-auto p-8">
              <div class="flex min-w-max items-start pb-6">
                <div
                  v-for="(step, sIndex) in route.stops"
                  :key="step.id"
                  class="flex items-start"
                >
                  <div
                    class="relative flex w-56 flex-col items-center text-center"
                  >
                    <div
                      class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full border-4 border-white bg-brand-blue text-xs font-black text-white shadow-md ring-2 ring-brand-blue/10"
                    >
                      {{ sIndex + 1 }}
                    </div>

                    <div class="mt-4 w-full space-y-3 px-4">
                      <p
                        class="text-md leading-tight font-bold break-words whitespace-normal text-slate-900"
                      >
                        {{ step.station_name }}
                      </p>

                      <div
                        v-if="step.order === 1"
                        class="flex items-center justify-evenly gap-1 rounded-2xl border border-slate-100 bg-slate-50/50 p-3 shadow-inner"
                      >
                        <div class="flex flex-col items-center">
                          <span
                            class="text-[12px] font-black tracking-tighter text-slate-400 uppercase"
                            >Arrive</span
                          >
                          <span
                            class="font-mono text-sm font-bold text-slate-700"
                          >
                            {{ step.from_time || '--:--' }}
                          </span>
                        </div>

                        <div class="h-8 w-px bg-slate-200"></div>

                        <div class="flex flex-col items-center">
                          <span
                            class="text-[12px] font-black tracking-tighter text-brand-blue uppercase"
                            >Depart</span
                          >
                          <span
                            class="font-mono text-sm font-bold text-brand-blue"
                          >
                            {{ step.to_time || '--:--' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div
                    v-if="sIndex < route.stops.length - 1"
                    class="relative mt-5 h-[2px] w-16 bg-slate-200"
                  >
                    <div
                      class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-slate-200"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
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
            Paid
          </button>

          <button
            @click="updateFilter('refund')"
            :class="[
              initialFilter === 'refund'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:text-slate-700',
            ]"
            class="rounded-xl px-6 py-2 text-xs font-bold transition-all"
          >
            Refund
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

                <div class="mb-6 grid grid-cols-2 gap-3">
                  <div
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                  >
                    <Bus class="h-4 w-4 text-slate-400" />
                    <div>
                      <p
                        class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                      >
                        Vehicle
                      </p>
                      <p class="max-w-[100px] truncate text-xs font-bold">
                        {{ tx.vehicle_name }}
                      </p>
                    </div>
                  </div>

                  <div
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                  >
                    <Users class="h-4 w-4 text-slate-400" />
                    <div>
                      <p
                        class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                      >
                        Passengers
                      </p>
                      <p class="max-w-[100px] truncate text-xs font-bold">
                        {{ tx.passenger_count }}
                        {{ tx.passenger_count > 1 ? 'Seats' : 'Seat' }}
                      </p>
                    </div>
                  </div>

                  <div
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                  >
                    <Calendar class="h-4 w-4 text-slate-400" />
                    <div>
                      <p
                        class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                      >
                        Travel Date
                      </p>
                      <p class="text-xs font-bold">{{ tx.date }}</p>
                    </div>
                  </div>

                  <div
                    class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3"
                  >
                    <Clock class="h-4 w-4 text-slate-400" />
                    <div>
                      <p
                        class="text-[9px] font-bold tracking-wider text-slate-400 uppercase"
                      >
                        Departure
                      </p>
                      <p class="text-xs font-bold">{{ tx.time_window }}</p>
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
      <DialogContent class="max-w-md overflow-hidden p-0">
        <DialogHeader class="border p-3 pb-4">
          <DialogTitle class="text-2xl font-bold text-slate-900">
            {{ editingScheduleId ? 'Edit' : 'Assign' }} Full Route
          </DialogTitle>
          <DialogDescription class="text-slate-500">
            Define the vehicle's journey, sequence of stops, and timing.
          </DialogDescription>
        </DialogHeader>

        <div class="flex-1 space-y-8 overflow-y-auto px-5">
          <div class="space-y-3">
            <Label
              class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
            >
              1. Select Vehicle
            </Label>
            <select
              v-model="scheduleForm.vehicle_id"
              class="flex h-12 w-full rounded-2xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm font-medium transition-all focus:border-brand-blue focus:bg-white focus:ring-4 focus:ring-brand-blue/10 focus:outline-none"
            >
              <option :value="null" disabled>
                Choose a bus from your fleet...
              </option>
              <option v-for="v in props.vehicles" :key="v.id" :value="v.id">
                {{ v.plate_number }} - {{ v.model }}
              </option>
            </select>
          </div>

          <div class="space-y-3">
            <Label
              class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
            >
              2. Operation Days
            </Label>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
              <div
                v-for="d in props.daySchedules"
                :key="d.id"
                @click="toggleDay(d.id)"
                class="flex cursor-pointer items-center space-x-1 rounded-xl border p-2 transition-all"
                :class="
                  scheduleForm.day_schedule_ids.includes(d.id)
                    ? 'border-brand-blue bg-blue-50/50 ring-1 ring-brand-blue'
                    : 'border-slate-100 bg-white hover:border-slate-300'
                "
              >
                <div class="relative flex h-5 w-5 items-center justify-center">
                  <input
                    type="checkbox"
                    :value="d.id"
                    v-model="scheduleForm.day_schedule_ids"
                    class="h-4 w-4 rounded border-slate-300 text-brand-blue focus:ring-brand-blue"
                  />
                </div>
                <span class="text-xs font-bold text-slate-700">{{
                  d.name
                }}</span>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <Label
                class="text-[10px] font-black tracking-widest text-slate-400 uppercase"
              >
                3. Route Sequence & Timings
              </Label>
              <span class="text-[10px] font-medium text-slate-400 italic"
                >Click routes in order</span
              >
            </div>

            <div class="grid gap-3 pb-6">
              <div
                v-for="station in props.stations.filter(
                  (s) => s.status_id === 1,
                )"
                :key="'form-st-' + station.id"
                class="group relative flex flex-col gap-4 rounded-2xl border p-4 transition-all duration-300"
                :class="
                  scheduleForm.stations[station.id]?.selected
                    ? 'border-brand-blue bg-blue-50/10 shadow-sm'
                    : 'border-slate-100 bg-white opacity-60 hover:opacity-100'
                "
              >
                <div class="flex items-center gap-4">
                  <div class="flex h-8 w-8 items-center justify-center">
                    <input
                      v-if="scheduleForm.stations[station.id]"
                      type="checkbox"
                      v-model="scheduleForm.stations[station.id].selected"
                      @change="handleStationToggle(station.id)"
                      class="h-6 w-6 rounded-lg border-slate-300 text-brand-blue transition-all focus:ring-brand-blue"
                    />
                  </div>

                  <div class="flex-grow">
                    <div class="flex items-center gap-3">
                      <p class="font-bold text-slate-900">{{ station.name }}</p>
                      <span
                        v-if="scheduleForm.stations[station.id]?.selected"
                        class="rounded-full bg-brand-blue px-2 py-0.5 text-[9px] font-black text-white uppercase"
                      >
                        {{
                          getOrdinal(scheduleForm.stations[station.id]?.order)
                        }}
                        Route
                      </span>
                    </div>
                    <p
                      class="font-mono text-[10px] font-bold text-slate-400 uppercase"
                    >
                      {{ station.code_no }}
                    </p>
                  </div>
                </div>

                <div
                  v-if="scheduleForm.stations[station.id]?.selected"
                  class="ml-10 border-t border-blue-100/50 pt-4"
                >
                  <div
                    v-if="scheduleForm.stations[station.id].order === 1"
                    class="grid grid-cols-2 gap-4"
                  >
                    <div class="space-y-1.5">
                      <label
                        class="text-[9px] font-black tracking-tight text-slate-400 uppercase"
                      >
                        Arrival
                      </label>
                      <Input
                        type="time"
                        v-model="scheduleForm.stations[station.id].from_time"
                        class="h-10 rounded-xl border-brand-blue/30 bg-white font-mono text-sm font-bold text-brand-blue"
                      />
                    </div>

                    <div class="space-y-1.5">
                      <label
                        class="text-[9px] font-black tracking-tight text-brand-blue uppercase"
                      >
                        Departure
                      </label>
                      <Input
                        type="time"
                        v-model="scheduleForm.stations[station.id].to_time"
                        class="h-10 rounded-xl border-slate-200 bg-white font-mono text-sm"
                      />
                    </div>
                  </div>

                  <!-- <div v-else class="flex items-center gap-2 py-1">
                    <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                    <span class="text-[10px] font-medium text-slate-400 italic">
                      Follows the sequence after Stop 1
                    </span>
                  </div> -->
                </div>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter class="border-t bg-slate-50/80 p-6">
          <Button
            variant="ghost"
            class="font-bold text-slate-500 hover:bg-slate-100"
            @click="isScheduleDialogOpen = false"
          >
            Cancel
          </Button>
          <Button
            @click="submitSchedule"
            class="min-w-[160px] rounded-xl bg-brand-blue px-8 font-bold shadow-lg shadow-brand-blue/20 hover:bg-brand-blue/90"
            :disabled="scheduleForm.processing"
          >
            <Loader2
              v-if="scheduleForm.processing"
              class="mr-2 h-4 w-4 animate-spin"
            />
            {{ scheduleForm.processing ? 'Saving...' : 'Confirm Full Route' }}
          </Button>
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
