<script setup lang="ts">
import { nextTick, onMounted } from 'vue';

declare const $: any;

// Owl Carousel Init
onMounted(async () => {
  await nextTick();

  const $carousel = $('.owl-1');

  if ($carousel.length > 0) {
    const owl1 = $carousel.owlCarousel({
      center: false,
      items: 2,
      loop: true,
      stagePadding: 0,
      margin: 20,
      smartSpeed: 1000,
      autoplay: false,
      nav: false,
      dots: false,
      pauseOnHover: false,
      responsive: {
        500: { margin: 20, nav: false, items: 4 },
        1000: { margin: 20, stagePadding: 0, nav: false, items: 5 },
        1280: { margin: 20, stagePadding: 0, nav: false, items: 6 },
      },
    });

    $('.bg-left-half123').on('click', () =>
      owl1.trigger('prev.owl.carousel', [1000]),
    );
    $('.bg-right-half').on('click', () =>
      owl1.trigger('next.owl.carousel', [1000]),
    );
  }
});
</script>

<template>
  <div id="partners" class="scroll-mt-12">
    <div
      class="scroll-mt-12 bg-[url('@/assets/epinoy/partners-bg.jpg')] bg-cover bg-center bg-no-repeat px-5 py-10 text-center text-white"
    >
      <h1 class="text-4xl font-bold">PARTNERS</h1>
      <p class="pt-3 text-xl font-bold">Working Together for Better Mobility</p>
      <P class="text-md text-gray-300"
        >EMSi collaborates with institutions to expand electric transport
        services.</P
      >
    </div>

    <div class="mx-auto w-full max-w-[1320px] px-5 py-12">
      <div class="grid grid-cols-12 items-center gap-5">
        <div
          class="col-span-12 rounded-2xl p-5 shadow-brand-shadow lg:col-span-3"
        >
          <p class="text-lg font-semibold text-brand-blue">
            Partner Advantages
          </p>
          <ul class="list-disc ps-7 pt-1 text-sm text-gray-600">
            <li>Scalable electric mobility solutions</li>
            <li>Transparent operations</li>
            <li>Technology-enabled coordination</li>
          </ul>
        </div>

        <div class="col-span-12 px-10 lg:col-span-9">
          <div class="relative">
            <div class="absolute start-[-40px] top-1/2 z-10 -translate-y-1/2">
              <button
                class="bg-left-half123 h-[40px] w-[40px] rounded-[50%] bg-white font-bold text-brand-blue shadow-blue"
              >
                <i class="fa-solid fa-chevron-left"></i>
              </button>
            </div>

            <div class="absolute end-[-40px] top-1/2 z-10 -translate-y-1/2">
              <button
                class="bg-right-half h-[40px] w-[40px] rounded-[50%] bg-white font-bold text-brand-blue shadow-blue"
              >
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

            <div class="owl-carousel owl-1">
              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-bagsakan.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-bb88.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-ccnph.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-fldc.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-gpcf.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-migs.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-npo.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-pitas.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-adkoto.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-prepdi.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>

              <div class="flex h-24 items-center justify-center">
                <img
                  src="@/assets/epinoy/logo-rifac.png"
                  class="max-h-full max-w-full object-contain"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
