<script setup lang="ts">
import { usePageTheme } from '@/composables/usePageTheme';
import { home, logout } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';

// Force light mode on this page only
usePageTheme('light');
</script>

<template>
  <Head title="Success!" />

  <div
    class="flex h-screen w-full items-center justify-center bg-[#EFF8FF] p-2 sm:p-6"
  >
    <div class="relative z-0 w-full max-w-md">
      <!-- Background circle with subtle ping -->
      <div
        class="absolute top-[-60px] left-1/2 z-0 h-[125px] w-[125px] -translate-x-1/2 animate-ping rounded-full bg-white shadow-brand-shadow"
      ></div>

      <!-- Success Icon  -->
      <div>
        <i
          class="fa-solid fa-circle-check absolute top-[-45px] left-1/2 z-10 -translate-x-1/2 animate-bounce text-[90px] text-green-500"
        ></i>
      </div>

      <!-- Card background -->
      <div
        class="absolute left-1/2 z-0 h-full w-full -translate-x-1/2 rounded-2xl bg-white shadow-brand-shadow"
      ></div>

      <!-- Content -->
      <div
        class="animate-fadeIn relative rounded-2xl bg-white p-3 text-center sm:p-8"
      >
        <div class="h-14 sm:h-1"></div>

        <h1
          class="animate-slideDown mb-4 rounded-md bg-green-500 px-1 py-2 text-xl text-white sm:mt-9 sm:px-2 sm:text-2xl"
        >
          Success!
        </h1>
        <p class="text-md animate-fadeIn mb-6 py-2 text-center">
          Thank you for registering! We're reviewing your information and will
          notify you by email or text once your account is approved. We
          appreciate your patience!
        </p>

        <div class="animate-fadeIn flex gap-3">
          <Link
            :href="home()"
            class="block w-full rounded-lg bg-green-500 px-4 py-2 text-center font-semibold text-white transition hover:bg-green-700"
          >
            Home
          </Link>
          <Link
            :href="logout()"
            class="block w-full rounded-lg bg-[#CE2D3C] px-4 py-2 text-center font-semibold text-white transition hover:bg-red-700"
          >
            Logout
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@keyframes slideDown {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}
.animate-fadeIn {
  animation: fadeIn 0.8s ease forwards;
}
.animate-slideDown {
  animation: slideDown 0.6s ease forwards;
}
</style>
