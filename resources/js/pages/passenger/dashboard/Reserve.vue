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
import { ArrowRight, Bus } from 'lucide-vue-next';

const props = defineProps<{
  origin: any;
  destinations: any[];
}>();

const form = useForm({
  from_bus_station_id: props.origin.id,
  to_bus_station_id: '',
  amount: 15.0,
});

const submit = () => {
  form.post(route('passenger.reservation.store'));
};
</script>

<template>
  <Head title="Confirm Trip" />
  <AppLayout>
    <div class="mx-auto max-w-2xl p-6">
      <div class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="border-b bg-slate-50/50 p-6">
          <div class="flex items-center gap-4">
            <div class="rounded-full bg-brand-blue p-3 text-white">
              <Bus class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-xl font-bold">Confirm Departure</h2>
              <p class="text-sm text-slate-500">
                You are boarding at:
                <span class="font-semibold text-brand-blue">{{
                  origin.name
                }}</span>
              </p>
            </div>
          </div>
        </div>

        <div class="space-y-6 p-6">
          <div class="aspect-video overflow-hidden rounded-xl border">
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
              :zoom="16"
              :center="[origin.lat, origin.lng]"
            />
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
              <Label>Where is your Destination?</Label>
              <Select v-model="form.to_bus_station_id">
                <SelectTrigger class="h-12 border-slate-200">
                  <SelectValue placeholder="Select drop-off station" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="dest in destinations"
                    :key="dest.id"
                    :value="dest.id.toString()"
                  >
                    {{ dest.name }} ({{ dest.code_no }})
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div
              class="mt-6 flex items-center justify-between rounded-xl bg-slate-900 p-4 text-white"
            >
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase">
                  Estimated Fare
                </p>
                <p class="text-2xl font-black">₱{{ form.amount }}</p>
              </div>
              <Button
                type="submit"
                class="bg-white px-8 text-slate-900 hover:bg-slate-100"
                :disabled="!form.to_bus_station_id || form.processing"
              >
                Confirm Reservation
                <ArrowRight class="ml-2 h-4 w-4" />
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
