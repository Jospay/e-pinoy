<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Lock, Clock, CheckCircle } from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface VehicleType {
  id: number;
  name: string;
  is_assigned: boolean;
  status: {
    id: number | null;
    name: string;
  };
}

interface Props {
  vehicleTypes: VehicleType[];
}

const props = defineProps<Props>();

// Dialog state
const isDialogOpen = ref(false);
const selectedVehicleType = ref<VehicleType | null>(null);

// Debug
console.log('VehicleTypes Component - Received:', props.vehicleTypes);

const getStatusColor = (statusName: string) => {
  switch (statusName) {
    case 'active':
      return 'bg-green-100 text-green-800 border-green-300';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 border-yellow-300';
    case 'locked':
      return 'bg-gray-100 text-gray-800 border-gray-300';
    default:
      return 'bg-gray-100 text-gray-800 border-gray-300';
  }
};

const getStatusIcon = (statusName: string) => {
  switch (statusName) {
    case 'active':
      return CheckCircle;
    case 'pending':
      return Clock;
    case 'locked':
      return Lock;
    default:
      return Lock;
  }
};

const getStatusText = (statusName: string) => {
  switch (statusName) {
    case 'active':
      return 'Active';
    case 'pending':
      return 'Pending Approval';
    case 'locked':
      return 'Locked';
    default:
      return 'Unknown';
  }
};

const openRequestDialog = (type: VehicleType) => {
  selectedVehicleType.value = type;
  isDialogOpen.value = true;
};

const confirmRequest = () => {
  if (!selectedVehicleType.value) return;

  router.post(
    `/owner/vehicle-types/${selectedVehicleType.value.id}/request-unlock`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        isDialogOpen.value = false;
        selectedVehicleType.value = null;
      },
    },
  );
};

const cancelRequest = () => {
  isDialogOpen.value = false;
  selectedVehicleType.value = null;
};
</script>

<template>
  <div class="space-y-4 px-2">
    <div
      v-if="vehicleTypes.length === 0"
      class="py-8 text-center text-gray-500"
    >
      No vehicle types available.
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="type in vehicleTypes"
        :key="type.id"
        :class="[
          'relative rounded-lg border p-6 shadow-sm transition-all',
          type.status.name === 'locked'
            ? 'bg-gray-50 opacity-75'
            : 'hover:shadow-md',
        ]"
      >
        <!-- Status Badge -->
        <div class="absolute top-3 right-3">
          <span
            :class="[
              'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
              getStatusColor(type.status.name),
            ]"
          >
            <component :is="getStatusIcon(type.status.name)" class="h-3 w-3" />
            {{ getStatusText(type.status.name) }}
          </span>
        </div>

        <!-- Vehicle Type Name -->
        <div class="mt-2 mb-4 pt-5 text-center">
          <h3 class="text-xl font-semibold">{{ type.name }}</h3>
        </div>

        <!-- Action Button (only for locked types) -->
        <div v-if="type.status.name === 'locked'" class="mt-4 text-center">
          <button
            @click="openRequestDialog(type)"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary/90"
          >
            <Lock class="h-4 w-4" />
            Request Access
          </button>
        </div>

        <!-- Info text for pending -->
        <div
          v-else-if="type.status.name === 'pending'"
          class="mt-4 text-center"
        >
          <p class="text-xs text-muted-foreground">
            Your request is being reviewed
          </p>
        </div>

        <!-- Info text for active -->
        <div v-else-if="type.status.name === 'active'" class="mt-4 text-center">
          <p class="text-xs text-green-600">
            You have access to this vehicle type
          </p>
        </div>
      </div>
    </div>

    <!-- Confirmation Dialog -->
    <Dialog v-model:open="isDialogOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Request Vehicle Type Access</DialogTitle>
          <DialogDescription>
            Are you sure you want to request access to
            <span class="font-semibold">{{ selectedVehicleType?.name }}</span
            >? Your request will be sent to the administrator for approval.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button variant="outline" @click="cancelRequest"> Cancel </Button>
          <Button @click="confirmRequest"> Confirm Request </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
