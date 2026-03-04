<script setup lang="ts">
import LocationMap from '@/components/ReservedMap.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  MapPin,
  Navigation,
  Users,
  ChevronLeft,
  CalendarDays,
  Bus,
  ArrowRight,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
  origin: any;
  destinations: any[];
  vehicle_info: any;
  route_stations: any[];
  available_days: string[];
}>();

// Get today's date in YYYY-MM-DD format to prevent previous date selection
const todayDate = new Date().toISOString().split('T')[0];

const form = useForm({
  vehicle_id: props.vehicle_info.id,
  from_bus_station_id: props.origin.id,
  to_bus_station_id: '',
  station_schedule_id: props.origin.schedule_id,
  amount: 0,
  passenger_count: 1,
  reserve_date: '',
});

const selectedDest = computed(() =>
  props.destinations.find((d) => d.id.toString() === form.to_bus_station_id),
);

// Check if the selected date's day (e.g. "Monday") is in the available_days array
// Replace your existing isOperationalDay with this:
const isOperationalDay = computed(() => {
  if (!form.reserve_date) return true;

  // Split the YYYY-MM-DD string
  const [year, month, day] = form.reserve_date.split('-').map(Number);

  // Create a date using local time (month is 0-indexed in JS, so -1)
  const date = new Date(year, month - 1, day);

  const dayName = date.toLocaleDateString('en-US', { weekday: 'long' });

  // Debugging: uncomment the line below to see what JS thinks the day is in your console
  // console.log("Selected Date:", form.reserve_date, "Calculated Day:", dayName);

  return props.available_days
    .map((d) => d.toLowerCase())
    .includes(dayName.toLowerCase());
});

const formattedAllowedDays = computed(() => props.available_days.join(', '));

watch([() => form.to_bus_station_id, () => form.passenger_count], () => {
  if (selectedDest.value) {
    form.amount = selectedDest.value.calculated_fare * form.passenger_count;
  }
});

const submit = () => {
  if (!isOperationalDay.value) {
    alert(`This trip only operates on: ${props.available_days.join(', ')}`);
    return;
  }
  form.post('/passenger/reservation');
};

const goBack = () => window.history.back();
</script>

<template>
  <Head title="Confirm Reservation" />
  <AppLayout>
    <div class="min-h-screen bg-slate-50/50 px-2 py-10">
      <div class="mx-auto max-w-4xl">
        <div
          class="rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-200/60"
        >
          <div class="items-center justify-between border-b p-4 sm:flex">
            <button
              @click="goBack"
              class="group flex items-center gap-2 text-sm font-semibold text-slate-500 transition-colors hover:text-slate-900"
            >
              <ChevronLeft
                class="h-5 w-5 transition-transform group-hover:-translate-x-1"
              />
              Back to Trips
            </button>
            <div class="pt-3 text-right sm:pt-0">
              <p
                class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
              >
                Current Fleet
              </p>
              <p class="text-sm font-black text-slate-900">
                {{ vehicle_info.name }} • {{ vehicle_info.plate }}
              </p>
            </div>
          </div>

          <div class="relative h-64 w-full px-3 pt-3">
            <LocationMap
              :locations="[
                {
                  id: 1,
                  latitude: origin.lat,
                  longitude: origin.lng,
                  name: origin.name,
                  type: 'Pin',
                },
              ]"
              :zoom="15"
              :center="[origin.lat, origin.lng]"
            />
            <div
              class="absolute bottom-6 left-6 rounded-2xl border border-white/20 bg-white/95 p-4 shadow-xl backdrop-blur-md"
            >
              <div class="flex items-center gap-4">
                <div
                  class="rounded-xl bg-brand-blue p-2.5 text-white shadow-lg shadow-blue-200"
                >
                  <Bus class="h-5 w-5" />
                </div>
                <div>
                  <p
                    class="text-[10px] leading-none font-bold text-slate-400 uppercase"
                  >
                    Departure Time
                  </p>
                  <p class="mt-1 text-lg font-black text-slate-900">
                    {{ origin.departure_time }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="p-3 sm:p-8 lg:p-12">
            <div class="grid grid-cols-1 gap-9 lg:grid-cols-12 lg:gap-12">
              <div class="lg:col-span-5">
                <div class="mb-8 flex items-center gap-3 pt-3 sm:pt-0">
                  <div class="h-1 w-8 rounded-full bg-brand-blue"></div>
                  <h3
                    class="text-sm font-black tracking-widest text-slate-900 uppercase"
                  >
                    Journey Timeline
                  </h3>
                </div>

                <div class="relative ml-2">
                  <div
                    class="absolute top-2 left-[15px] h-[calc(100%-24px)] w-0.5 border-l-2 border-dashed border-slate-200"
                  ></div>
                  <div
                    v-for="(stop, index) in route_stations"
                    :key="index"
                    class="relative mb-10 pl-12 last:mb-0"
                  >
                    <div
                      class="absolute top-0 left-0 z-10 flex h-8 w-8 items-center justify-center rounded-full border-4 border-white shadow-md transition-all duration-500"
                      :class="[
                        stop.name === origin.name
                          ? 'scale-125 bg-brand-blue shadow-[0_0_15px_rgba(37,99,235,0.4)]'
                          : form.to_bus_station_id &&
                              stop.name === selectedDest?.name
                            ? 'scale-125 bg-red-500 shadow-[0_0_15px_rgba(239,68,68,0.4)]'
                            : 'bg-slate-200',
                      ]"
                    >
                      <MapPin
                        v-if="
                          stop.name === origin.name ||
                          (selectedDest && stop.name === selectedDest.name)
                        "
                        class="h-3 w-3 text-white"
                      />
                      <div
                        v-else
                        class="h-1.5 w-1.5 rounded-full bg-white"
                      ></div>
                    </div>
                    <div
                      :class="{
                        'opacity-30':
                          selectedDest &&
                          index >
                            route_stations.findIndex(
                              (s) => s.name === selectedDest.name,
                            ),
                      }"
                    >
                      <div class="flex items-center justify-between">
                        <p
                          class="text-sm font-black text-slate-900"
                          :class="{
                            'text-brand-blue': stop.name === origin.name,
                          }"
                        >
                          {{ stop.name }}
                        </p>
                        <span class="text-[10px] font-bold text-slate-400">{{
                          index === 0 ? 'START' : stop.arrival
                        }}</span>
                      </div>
                      <p
                        class="mt-1 flex items-start gap-1 text-[11px] font-medium text-slate-500 italic"
                      >
                        <Navigation class="mt-0.5 h-2.5 w-2.5 shrink-0" />
                        {{ stop.address }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="space-y-8 lg:col-span-7">
                <div
                  class="space-y-6 rounded-2xl border border-slate-100 bg-slate-50/80 p-6"
                >
                  <div class="space-y-3">
                    <Label
                      class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                      >Select Drop-off</Label
                    >
                    <Select v-model="form.to_bus_station_id">
                      <SelectTrigger
                        class="h-14 rounded-xl border-slate-200 bg-white px-4 text-base font-bold shadow-sm focus:ring-4 focus:ring-blue-100"
                      >
                        <SelectValue placeholder="Where are you heading?" />
                      </SelectTrigger>
                      <SelectContent position="popper" class="z-50 rounded-xl">
                        <SelectItem
                          v-for="d in destinations"
                          :key="d.id"
                          :value="d.id.toString()"
                          class="py-4 font-bold"
                        >
                          {{ d.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="space-y-3">
                      <Label
                        class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                      >
                        Travel Date
                      </Label>
                      <div class="relative">
                        <CalendarDays
                          class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2"
                          :class="
                            isOperationalDay ? 'text-slate-400' : 'text-red-500'
                          "
                        />
                        <input
                          type="date"
                          v-model="form.reserve_date"
                          :min="todayDate"
                          class="h-14 w-full rounded-xl border px-4 pl-12 text-sm font-bold shadow-sm transition-all focus:ring-4"
                          :class="[
                            isOperationalDay
                              ? 'border-slate-200 bg-white focus:ring-blue-100'
                              : 'border-red-500 bg-red-50 focus:ring-red-100',
                          ]"
                        />
                      </div>

                      <p
                        v-if="!isOperationalDay"
                        class="flex items-center gap-1 px-1 text-[10px] font-bold text-red-600 uppercase"
                      >
                        <Info class="h-3 w-3" />
                        Bus only operates on: {{ formattedAllowedDays }}
                      </p>
                      <p
                        v-else
                        class="px-1 text-[10px] font-medium text-slate-400"
                      >
                        Available days: {{ formattedAllowedDays }}
                      </p>
                    </div>

                    <div class="space-y-3">
                      <Label
                        class="ml-1 text-[10px] font-black tracking-widest text-slate-400 uppercase"
                        >Passengers</Label
                      >
                      <div class="relative">
                        <Users
                          class="absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-slate-400"
                        />
                        <input
                          type="number"
                          v-model="form.passenger_count"
                          min="1"
                          max="20"
                          class="h-14 w-full rounded-xl border border-slate-200 bg-white pr-4 pl-12 text-sm font-bold shadow-sm"
                        />
                      </div>
                    </div>
                  </div>

                  <div class="border-t border-slate-200/60 pt-4">
                    <div
                      class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between"
                    >
                      <div>
                        <p
                          class="text-xs font-extrabold tracking-widest text-blue-400 uppercase"
                        >
                          Total Payable
                        </p>
                        <div
                          class="mt-1 flex items-baseline gap-1 text-2xl font-black"
                        >
                          <span>₱</span
                          ><span>{{ form.amount.toFixed(2) }}</span>
                        </div>
                      </div>

                      <Button
                        @click="submit"
                        :disabled="
                          !form.to_bus_station_id ||
                          !isOperationalDay ||
                          form.processing
                        "
                        class="h-14 min-w-[200px] rounded-xl bg-brand-blue text-lg font-black shadow-lg shadow-blue-200 transition-all hover:scale-[1.02] hover:bg-blue-600 active:scale-95 disabled:bg-slate-400 disabled:shadow-none"
                      >
                        <span v-if="form.processing">Processing...</span>
                        <span v-else class="flex items-center gap-2"
                          >Confirm <ArrowRight class="h-5 w-5"
                        /></span>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <p
          class="mt-8 text-center text-[10px] font-bold tracking-[0.2em] text-slate-400 uppercase"
        >
          Secure Payment via PayMongo • QR Code Ticket Generation
        </p>
      </div>
    </div>
  </AppLayout>
</template>
