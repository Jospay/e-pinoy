<script setup lang="ts">
import LeafletMap, { type MarkerData } from '@/components/LeafletMap.vue';
import MultiSelect from '@/components/MultiSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

// --- Define Props ---
const props = defineProps<{
  mapMarkers: {
    data: MarkerData[];
  };
  franchises: { id: number; name: string }[];
  drivers: { id: number; username: string }[];
  filters: {
    franchise: string[];
    driver: string[];
  };
}>();

// --- 3. Setup Breadcrumbs ---
const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Gps Monitoring',
    href: superAdmin.gpsTracker.index().url,
  },
];

// --- 4. Setup Reactive State for Filters ---
const selectedFranchise = ref<string[]>(props.filters.franchise || []);
const selectedDriver = ref<string[]>(props.filters.driver || []);

const selectedContext = computed({
  get: () =>
    selectedFranchise.value,
  set: (val: string[]) => {
    selectedFranchise.value = val;
    selectedDriver.value = [];
  },
});

// Mapping options for the MultiSelect
const driverOptions = computed(() =>
  props.drivers.map((d) => ({ id: d.id, label: d.username })),
);
const contextOptions = computed(() => {
  const data = props.franchises;
  return data.map((item) => ({ id: item.id, label: item.name }));
});

// --- Auto-Refresh Logic ---
const userIsActive = ref(true);
let refreshInterval: ReturnType<typeof setInterval> | null = null;
let activityTimeout: ReturnType<typeof setTimeout> | null = null;

// 1. Activity Handler: Resets the "idle" timer on user interaction
const handleUserActivity = () => {
  if (!userIsActive.value) {
    userIsActive.value = true;
    // Optional: Trigger an immediate refresh when waking up from inactivity
    refreshMapMarkers();
  }
  if (activityTimeout) clearTimeout(activityTimeout);
  // Set user to inactive after 3 minutes of no movement
  activityTimeout = setTimeout(
    () => {
      userIsActive.value = false;
    },
    3 * 60 * 1000,
  );
};

// 2. The Refresh Function
const refreshMapMarkers = () => {
  // STOP if user is inactive or the tab is hidden (saves server resources)
  if (!userIsActive.value || document.hidden) return;

  router.reload({
    only: ['mapMarkers'], // Only fetch the markers, ignore dropdown lists
    // Explicitly pass current filters to ensure the backend query is accurate
    data: {
      franchise: selectedFranchise.value || [],
      driver: selectedDriver.value,
    },
    onSuccess: () => {
      console.log('GPS positions updated');
    },
    onError: (errors) => {
      console.error('Auto-refresh failed', errors);
    },
  });
};

// 3. Lifecycle Hooks
onMounted(() => {
  window.addEventListener('mousemove', handleUserActivity);
  window.addEventListener('keydown', handleUserActivity);
  document.addEventListener('visibilitychange', handleUserActivity); // Check if tab becomes active

  handleUserActivity(); // specific init
  // 10 seconds (30000ms)
  refreshInterval = setInterval(refreshMapMarkers, 10 * 1000);
});

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval);
  if (activityTimeout) clearTimeout(activityTimeout);

  window.removeEventListener('mousemove', handleUserActivity);
  window.removeEventListener('keydown', handleUserActivity);
  document.removeEventListener('visibilitychange', handleUserActivity);
});

// --- Watchers to Update URL ---
const updateFilters = () => {
  router.get(
    superAdmin.gpsTracker.index().url,
    {
      driver: selectedDriver.value,
      franchise: selectedFranchise.value || [],
    },
    {
      preserveScroll: true,
      replace: true, // Doesn't pollute browser history
    },
  );
};
</script>

<template>

  <Head title="Gps Monitoring" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <div class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="font-mono text-xl font-semibold">
            Franchise Monitoring
          </h2>

          <div class="flex gap-4">
            <MultiSelect v-model="selectedDriver" :options="driverOptions" placeholder="Select Drivers"
              all-label="All Drivers" @change="updateFilters" />

            <MultiSelect v-model="selectedContext" :options="contextOptions" placeholder="Select Franchises" all-label="
                All Franchises
              " @change="
                (val) => {
                  selectedFranchise = val;
                  updateFilters();
                }
              " />
          </div>
        </div>

        <div class="w-full rounded-lg border shadow-sm">
          <LeafletMap :locations="props.mapMarkers.data" :fit-bounds="props.mapMarkers.data.length > 0">
            <template #popup="{ item }">
              <div class="min-w-[150px] space-y-2 p-1">
                <div class="border-b pb-1">
                  <h3 class="text-sm text-gray-500">
                    {{ item.franchise_name }}
                  </h3>
                </div>
                <div class="flex items-center justify-between gap-2">
                  <h3 class="font-bold text-gray-900">
                    {{ item.username }}
                  </h3>
                  <Badge :class="[
                    item.isOnline
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700',
                  ]">
                    {{ item.isOnline ? 'Online' : 'Offline' }}
                  </Badge>
                </div>
                <div v-if="!item.isOnline">
                  <span class="font-mono text-xs text-rose-600">driver last seen is here</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-gray-600">Plate No:</span>
                  <span
                    class="inline-block rounded bg-blue-100 px-1.5 py-0.5 font-mono text-sm font-semibold text-blue-700">
                    {{ item.plate_number }}
                  </span>
                </div>
              </div>
            </template>
          </LeafletMap>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
