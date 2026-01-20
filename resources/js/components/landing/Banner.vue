<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
  userTypes: { name: string; encrypted_id: string }[];
}

const { userTypes } = defineProps<Props>();

const franchisee = userTypes.find((t) => t.name === 'owner');
const driver = userTypes.find((t) => t.name === 'driver');
</script>

<template>
  <div
    class="bg-[url('@/assets/epinoy/home_bg.jpg')] bg-cover bg-center bg-no-repeat px-5"
  >
    <div
      class="mx-auto w-full max-w-[1200px] pt-[115px] pb-14 text-center text-white lg:pt-[120px] lg:pb-18"
    >
      <h1 class="text-3xl font-bold sm:text-4xl lg:text-5xl xl:text-6xl">
        CLEAN ● ELECTRIC ● CONNECTED
      </h1>

      <p class="text-md/[28px] pt-2 pb-7 text-gray-300">
        E-Pinoy Mobility Systems Inc. (EMSi) is a Philippine-based mobility
        company established to modernize public and <br />
        commercial transportation through integrated transport operations and
        digital coordination systems.
      </p>

      <h1 class="px-2 py-2 text-lg font-bold text-white sm:text-3xl xl:px-5">
        What EMSi Offers
      </h1>

      <p
        class="text-md/[28px] mx-auto mt-2 w-fit rounded-2xl bg-white px-8 py-3 text-brand-blue"
      >
        Fully electric public transport services
        <span class="font-bold">∘</span> Safe, reliable, and scheduled routes
        <span class="font-bold">∘</span> Cashless and digital fare systems
        <br />
        <span class="font-bold">∘</span> Real-time vehicle tracking
        <span class="font-bold">∘</span> Environment-friendly operations
      </p>

      <div class="grid justify-center gap-5 pt-7 lg:mt-10 lg:flex xl:gap-7">
        <h1 class="px-2 py-2 text-lg font-bold text-white sm:text-3xl xl:px-5">
          Get Started
        </h1>
        <a
          href="#"
          target="_blank"
          class="rounded-xl border-3 border-white bg-brand-red px-2 py-2 text-lg font-bold text-white transition-all hover:opacity-85 sm:text-xl xl:px-8"
        >
          Become a Franchise Partner
        </a>

        <Link
          v-if="franchisee"
          :href="`/register/${franchisee.encrypted_id}`"
          class="rounded-xl border-3 border-white bg-brand-red px-7 py-2 text-lg font-bold text-white transition-all hover:opacity-85 sm:text-xl xl:px-8"
        >
          Become a Driver
        </Link>

        <Link
          v-if="driver"
          :href="`/register/${driver.encrypted_id}`"
          class="rounded-xl border-3 border-white bg-white px-7 py-2 text-lg font-bold text-brand-blue transition-all hover:opacity-85 sm:text-xl xl:px-8"
        >
          Download App
        </Link>
      </div>
    </div>
  </div>
</template>
