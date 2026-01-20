<script setup lang="ts">
import { Card, CardContent, CardTitle } from '@/components/ui/card';
import logo from '@/assets/epinoy/nav_logo.png';

// --- MAP IMPORTS ---

interface FranchiseData {
  id: number;
  name: string;
  region: string;
  province: string;
  city: string;
  latitude: string;
  longitude: string;
}

// --- CONSOLIDATED PROPS ---
const props = defineProps<{
  userTypes?: any[];
  franchises?: FranchiseData[]; // Made optional to prevent crashes if undefined
  userTypeName: string;
  textOverlay?: string;
  title?: string;
  description?: string;
  titleRegistration?: string;
  goBackHomeButton?: boolean;
}>();
</script>

<template>
  <div
    class="flex min-h-svh flex-col items-center justify-center bg-muted bg-[url(@/assets/epinoy/home_bg.jpg)] bg-cover bg-center bg-no-repeat p-1.5 sm:p-6 md:p-10"
  >
    <div class="w-full max-w-sm md:max-w-md">
      <Card class="overflow-hidden border-0 p-0">
        <CardContent class="border-0 p-0">
          <div>
            <Card class="border-0 p-0 shadow-none">
              <img :src="logo" alt="Logo" class="mx-auto mt-8 h-14 w-auto" />

              <CardTitle
                v-if="titleRegistration"
                class="w-full bg-brand-red py-2 text-center text-3xl text-white"
              >
                {{ titleRegistration }}
              </CardTitle>

              <CardTitle
                v-else
                class="w-full bg-brand-red py-2 text-center text-3xl text-white"
                >{{ title }}</CardTitle
              >

              <CardContent class="px-3 md:px-8">
                <slot />
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
