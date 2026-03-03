<template>
  <div class="mx-auto max-w-4xl space-y-6 p-6">
    <h2 class="text-2xl font-bold">Create Fleet Schedule</h2>

    <div class="grid grid-cols-2 gap-4 rounded-xl border bg-white p-6">
      <div>
        <label class="mb-1 block text-sm font-bold">Assign Vehicle</label>
        <select v-model="form.vehicleId" class="w-full rounded border p-2">
          <option v-for="v in vehicles" :value="v.id">
            {{ v.name }} (Cap: {{ v.capacity }})
          </option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-bold"
          >Select Route Template</label
        >
        <select
          v-model="selectedRouteId"
          @change="loadRouteStations"
          class="w-full rounded border p-2"
        >
          <option :value="1">Route 1 (A -> B -> C -> D)</option>
          <option :value="2">Express (A -> D)</option>
        </select>
      </div>
    </div>

    <div v-if="form.stations.length" class="rounded-xl border bg-slate-50 p-6">
      <h3 class="mb-4 font-bold">Set Arrival/Departure Times</h3>
      <div class="space-y-4">
        <div
          v-for="(station, index) in form.stations"
          :key="index"
          class="flex items-center gap-4 rounded border bg-white p-3"
        >
          <div
            class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 font-bold text-white"
          >
            {{ index + 1 }}
          </div>
          <div class="flex-1 font-bold">{{ station.name }}</div>

          <div class="flex gap-2">
            <div>
              <span class="block text-[10px] uppercase">Arrival</span>
              <input
                type="time"
                v-model="station.arrivalTime"
                class="rounded border p-1 text-sm"
              />
            </div>
            <div>
              <span class="block text-[10px] uppercase">Departure</span>
              <input
                type="time"
                v-model="station.departureTime"
                class="rounded border p-1 text-sm"
              />
            </div>
          </div>
        </div>
      </div>

      <button
        @click="saveFullSchedule"
        class="mt-6 w-full rounded-lg bg-green-600 py-3 font-bold text-white hover:bg-green-700"
      >
        Deploy Schedule to Fleet
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const vehicles = ref([
  { id: 101, name: 'Bus 101', capacity: 50 },
  { id: 202, name: 'Van 05', capacity: 15 },
]);

const selectedRouteId = ref(null);
const form = ref({
  vehicleId: null,
  stations: [],
});

// Mocking the flow: Selecting a route generates the station list
function loadRouteStations() {
  if (selectedRouteId.value === 1) {
    form.value.stations = [
      { name: 'Station A', arrivalTime: '06:00', departureTime: '06:10' },
      { name: 'Station B', arrivalTime: '', departureTime: '' },
      { name: 'Station C', arrivalTime: '', departureTime: '' },
      { name: 'Station D', arrivalTime: '', departureTime: '' },
    ];
  }
}

function saveFullSchedule() {
  console.log('Saving schedule for Vehicle:', form.value.vehicleId);
  console.log('Stop sequence:', form.value.stations);
  alert('Schedule Created! This vehicle is now active on this route.');
}
</script>
