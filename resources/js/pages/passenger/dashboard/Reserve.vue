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
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, MapPin, Wallet } from 'lucide-vue-next';
import { watch } from 'vue';

const props = defineProps<{
  origin: any;
  destinations: any[];
}>();

const form = useForm({
  from_bus_station_id: props.origin.id,
  to_bus_station_id: '',
  amount: 0,
});

// Update the amount automatically when destination changes
watch(
  () => form.to_bus_station_id,
  (newId) => {
    const selected = props.destinations.find((d) => d.id.toString() === newId);
    if (selected) {
      form.amount = selected.calculated_fare;
    }
  },
);

const submit = () => {
  form.post(route('passenger.reservation.store'));
};
</script>

<template>
  <Head title="Confirm Trip" />
  <AppLayout>
    <div class="p-4 md:p-8">
      <div
        class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl"
      >
        <div class="bg-slate-900 p-6 text-center text-white">
          <h2 class="text-xl font-bold tracking-tight">Confirm Your Trip</h2>
          <p class="text-xs text-slate-400">Please select your destination</p>
        </div>

        <div class="space-y-6 p-6">
          <div
            class="aspect-[16/2] overflow-hidden rounded-2xl border shadow-inner"
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

          <div class="space-y-4">
            <div class="flex items-center gap-4">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400"
              >
                <div class="h-3 w-3 rounded-full bg-brand-blue"></div>
              </div>
              <div>
                <Label class="text-[10px] text-slate-400 uppercase"
                  >Starting Point</Label
                >
                <p class="font-bold text-slate-900">{{ origin.name }}</p>
              </div>
            </div>

            <div
              class="ml-5 h-8 border-l-2 border-dashed border-slate-200"
            ></div>

            <div class="flex items-center gap-4">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-red-50 text-red-500"
              >
                <MapPin class="h-5 w-5" />
              </div>
              <div class="flex-1">
                <Label class="text-[10px] text-slate-400 uppercase"
                  >Destination</Label
                >
                <Select v-model="form.to_bus_station_id">
                  <SelectTrigger
                    class="h-11 rounded-xl border-slate-200 bg-white shadow-sm focus:ring-2 focus:ring-brand-blue"
                  >
                    <SelectValue placeholder="Where are you going?" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="dest in destinations"
                      :key="dest.id"
                      :value="dest.id.toString()"
                    >
                      {{ dest.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-slate-50 p-6">
            <div class="mb-6 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Wallet class="h-5 w-5 text-slate-400" />
                <span class="text-sm font-medium text-slate-600"
                  >Total Amount</span
                >
              </div>
              <p class="text-3xl font-black text-slate-900">
                ₱{{ form.amount }}
              </p>
            </div>

            <form @submit.prevent="submit">
              <Button
                type="submit"
                class="h-12 w-full rounded-xl bg-brand-blue font-bold text-white shadow-lg transition-all hover:opacity-90 disabled:bg-slate-300"
                :disabled="!form.to_bus_station_id || form.processing"
              >
                Confirm Reservation
                <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </form>
          </div>

          <p class="text-center text-[10px] text-slate-400 italic">
            Double check your destination before confirming.
          </p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
