<script setup lang="ts">
import { home, logout } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Download Our App" />

  <div
    class="flex h-screen w-full items-center justify-center bg-[#EFF8FF] p-4 sm:p-6"
  >
    <div class="relative z-0 w-full max-w-md">
      <div
        class="absolute top-[-60px] left-1/2 z-0 h-[125px] w-[125px] -translate-x-1/2 animate-ping rounded-full bg-white shadow-brand-shadow"
      ></div>

      <div>
        <i
          class="fa-solid fa-mobile-screen-button absolute top-[-45px] left-1/2 z-10 -translate-x-1/2 animate-bounce text-[90px] text-auth-blue"
        ></i>
      </div>

      <div
        class="absolute left-1/2 z-0 h-full w-full -translate-x-1/2 rounded-2xl bg-white shadow-brand-shadow"
      ></div>

      <div
        class="animate-fadeIn relative rounded-2xl bg-white p-6 text-center sm:p-10"
      >
        <div class="h-14 sm:h-1"></div>

        <h1
          class="animate-slideDown mb-4 text-2xl font-bold text-auth-blue sm:text-3xl"
        >
          Download Our App
        </h1>

        <p class="text-md animate-fadeIn mb-6 leading-relaxed text-gray-700">
          Get the best experience right at your fingertips. Download our app now
          and enjoy seamless access, exclusive updates, and a smoother journey!
        </p>

        <div class="animate-fadeIn">
          <a
            href="https://play.google.com/store/apps/details?id=com.example.app"
            target="_blank"
            rel="noopener"
            class="inline-block w-full rounded-lg bg-auth-blue px-6 py-3 text-center font-semibold text-white shadow-lg transition hover:scale-105 hover:bg-brand-blue"
          >
            <i class="fa-brands fa-google-play mr-2"></i>
            Get it on Google Play
          </a>

          <div class="animate-fadeIn mt-5 flex gap-3">
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
