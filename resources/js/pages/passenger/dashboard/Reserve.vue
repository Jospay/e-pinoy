<script setup lang="ts">
import LocationMap from '@/components/ReservedMap.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
  ArrowRight,
  CalendarDays,
  CheckCircle2,
  ChevronLeft,
  Clock,
  MapPin,
  Wallet,
} from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
  origin: any;
  destinations: any[];
}>();

const form = useForm({
  from_bus_station_id: props.origin.id,
  to_bus_station_id: '',
  station_schedule_id: '',
  reserve_date: new Date().toISOString().split('T')[0], // Defaults to today
  amount: 0,
});

const selectedDestination = computed(() =>
  props.destinations.find((d) => d.id.toString() === form.to_bus_station_id),
);

watch(
  () => form.to_bus_station_id,
  (newId) => {
    if (selectedDestination.value) {
      form.amount = selectedDestination.value.calculated_fare;
    }
  },
);

const submit = () => {
  const url = `/passenger/reservation`;
  const method = 'post';

  router.visit(url, {
    method: method,
    data: form.data(),
    onStart: () => {
      form.processing = true;
    },
    onFinish: () => {
      form.processing = false;
    },
    onError: (errors) => {
      console.error('Submission errors:', errors);
    },
  });
};

const breadcrumbs = [{ title: 'Available Terminals', href: '#' }];

const goBack = () => {
  window.history.back();
};
</script>

<template>
  <Head title="Confirm Trip" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="min-h-[calc(100vh-64px)] bg-slate-50/50 px-4 py-8 sm:px-6 lg:px-8"
    >
      <div class="mx-auto max-w-2xl sm:p-6">
        <div class="mb-10">
          <div class="flex-1">
            <button
              @click="goBack"
              class="mb-6 flex items-center gap-2 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900"
            >
              <ChevronLeft class="h-4 w-4" />
              Change Starting Terminal
            </button>

            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
              Available Terminals
            </h1>
            <p class="mt-1 text-slate-500">
              Select your starting point to view available destinations and
              fares.
            </p>
          </div>
        </div>

        <div
          class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50"
        >
          <div class="relative z-0 p-4 sm:p-8">
            <div
              class="mb-8 aspect-[21/16] w-full overflow-hidden rounded-2xl border border-slate-100 shadow-sm sm:aspect-[21/9]"
            >
              <LocationMap
                :locations="[
                  {
                    id: origin.id,
                    latitude: origin.lat,
                    longitude: origin.lng,
                    name: origin.name,
                    type: 'Pin',
                  },
                ]"
                :zoom="15"
                :center="[origin.lat, origin.lng]"
              />
            </div>

            <div class="relative mb-8 space-y-0">
              <div
                class="absolute top-10 left-[19px] h-[calc(100%-40px)] w-0.5 border-l-2 border-dashed border-slate-200"
              ></div>

              <div class="relative flex items-start gap-5 pb-10">
                <div
                  class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600 ring-8 ring-white"
                >
                  <div
                    class="h-3 w-3 animate-pulse rounded-full bg-blue-600"
                  ></div>
                </div>
                <div class="flex-1 pt-1">
                  <Label
                    class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                    >Origin Terminal</Label
                  >
                  <p class="mt-0.5 text-lg font-bold text-slate-900">
                    {{ origin.name }}
                  </p>
                </div>
              </div>

              <div class="relative flex items-start gap-5 pb-10">
                <div
                  class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-red-100 bg-red-50 text-red-500 shadow-sm ring-8 ring-white"
                >
                  <MapPin class="h-5 w-5" />
                </div>
                <div class="flex-1 pt-1">
                  <Label
                    class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                    >Destination Terminal</Label
                  >
                  <div class="mt-2">
                    <Select v-model="form.to_bus_station_id">
                      <SelectTrigger
                        class="h-12 rounded-xl border-slate-200 bg-white"
                      >
                        <SelectValue placeholder="Where are you going?" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="dest in destinations"
                          :key="dest.id"
                          :value="dest.id.toString()"
                        >
                          {{ dest.name }} ({{ dest.code }})
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <div class="relative flex items-start gap-5 pb-10">
                <div
                  class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-blue-600 shadow-sm ring-8 ring-white"
                >
                  <CalendarDays class="h-5 w-5" />
                </div>
                <div class="flex-1 pt-1">
                  <Label
                    class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                    >Travel Date</Label
                  >
                  <div class="mt-2">
                    <input
                      type="date"
                      v-model="form.reserve_date"
                      :min="new Date().toISOString().split('T')[0]"
                      class="flex h-12 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm ring-offset-white focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                  </div>
                </div>
              </div>

              <div class="relative flex items-start gap-5">
                <div
                  class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-amber-100 bg-amber-50 text-amber-600 shadow-sm ring-8 ring-white"
                >
                  <Clock class="h-5 w-5" />
                </div>
                <div class="flex-1 pt-1">
                  <Label
                    class="text-[10px] font-bold tracking-widest text-slate-400 uppercase"
                    >Departure Schedule</Label
                  >
                  <div class="mt-3 grid grid-cols-1 gap-3">
                    <button
                      v-for="sched in origin.schedules"
                      :key="sched.id"
                      type="button"
                      @click="form.station_schedule_id = sched.id.toString()"
                      :class="[
                        'flex items-center justify-between rounded-2xl border-2 p-4 text-left transition-all',
                        form.station_schedule_id === sched.id.toString()
                          ? 'border-blue-600 bg-blue-50/50 ring-4 ring-blue-600/5'
                          : 'border-slate-100 bg-white hover:border-slate-200',
                      ]"
                    >
                      <div>
                        <p class="text-sm font-bold text-slate-900">
                          {{ sched.time_range }}
                        </p>
                        <p class="text-[10px] font-medium text-slate-500">
                          Standard Trip
                        </p>
                      </div>
                      <CheckCircle2
                        v-if="form.station_schedule_id === sched.id.toString()"
                        class="h-5 w-5 text-blue-600"
                      />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="rounded-2xl border border-slate-100 bg-slate-50/50 p-3 transition-all sm:p-6"
              :class="{
                'bg-blue-50/30 ring-2 ring-blue-600/10':
                  form.to_bus_station_id && form.station_schedule_id,
              }"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div
                    class="rounded-lg border border-slate-100 bg-white p-2 shadow-sm"
                  >
                    <Wallet class="h-5 w-5 text-blue-600" />
                  </div>
                  <div>
                    <span class="block text-sm font-semibold text-slate-600"
                      >Total Fare</span
                    >
                    <span
                      class="text-[10px] font-bold tracking-tighter text-slate-400 uppercase"
                      >VAT Inclusive</span
                    >
                  </div>
                </div>
                <div class="text-right">
                  <p
                    class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl"
                  >
                    ₱{{
                      form.amount.toLocaleString(undefined, {
                        minimumFractionDigits: 2,
                      })
                    }}
                  </p>
                </div>
              </div>

              <div class="mt-8">
                <form @submit.prevent="submit">
                  <Button
                    type="submit"
                    class="h-14 w-full rounded-2xl bg-slate-900 text-lg font-bold text-white transition-all hover:-translate-y-0.5 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400"
                    :disabled="
                      !form.to_bus_station_id ||
                      !form.station_schedule_id ||
                      !form.reserve_date ||
                      form.processing
                    "
                  >
                    <span v-if="form.processing">Processing...</span>
                    <span v-else class="flex items-center gap-2"
                      >Confirm Reservation <ArrowRight class="h-5 w-5"
                    /></span>
                  </Button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
