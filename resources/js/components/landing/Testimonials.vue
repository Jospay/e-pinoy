<script setup lang="ts">
import { nextTick, onMounted } from 'vue';

declare const $: any;

// Owl Carousel Init
onMounted(async () => {
  await nextTick();

  const $carousel = $('.owl-3');

  if ($carousel.length > 0) {
    const owl2 = $carousel.owlCarousel({
      center: false,
      items: 1,
      loop: true,
      stagePadding: 0,
      margin: 20,
      smartSpeed: 1000,
      autoplay: false,
      nav: false,
      dots: false,
      pauseOnHover: false,
      responsive: {
        700: { margin: 20, stagePadding: 0, nav: false, items: 2 },
        1280: { margin: 20, stagePadding: 0, nav: false, items: 3 },
      },
    });

    $('.bg-left-news').on('click', () =>
      owl2.trigger('prev.owl.carousel', [1000]),
    );
    $('.bg-right-news').on('click', () =>
      owl2.trigger('next.owl.carousel', [1000]),
    );
  }
});
</script>

<template>
  <div id="testimonials" class="relative scroll-mt-10 bg-[#e3f0fa]">
    <div class="h-18 w-full"></div>
    <div class="h-7 w-full bg-gradient-to-r from-brand-blue to-[#05AEE9]"></div>

    <div
      class="absolute top-11 right-0 left-0 mx-auto w-fit bg-[#e3f0fa] px-3 py-1 text-center font-bold text-brand-blue sm:px-20"
    >
      <h1 class="text-4xl">TESTIMONIALS</h1>
      <p class="pt-3 text-xl">What People Say About EMSi</p>
    </div>

    <div class="mx-auto w-full max-w-[1320px] px-5 pt-16 pb-10">
      <div class="relative">
        <div
          class="absolute start-3 top-1/2 z-10 -translate-y-1/2 2xl:start-[-60px]"
        >
          <button
            class="bg-left-news h-[40px] w-[40px] rounded-[50%] bg-white/50 font-bold text-brand-blue shadow-blue 2xl:bg-white"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>
        </div>

        <div
          class="absolute end-3 top-1/2 z-10 -translate-y-1/2 2xl:end-[-60px]"
        >
          <button
            class="bg-right-news h-[40px] w-[40px] rounded-[50%] bg-white/50 font-bold text-brand-blue shadow-blue 2xl:bg-white"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>

        <div class="owl-carousel owl-3">
          <div class="px-2 pt-16 pb-1">
            <div
              class="relative rounded-2xl bg-white pt-20 shadow-brand-shadow"
            >
              <!-- Image-->
              <div
                class="absolute top-[-60px] right-0 left-0 mx-auto w-fit rounded-full bg-white p-2 shadow-brand-shadow"
              >
                <div class="h-25 w-25 overflow-hidden rounded-full">
                  <img
                    src="@/assets/epinoy/testimonials-pic-1.png"
                    class="h-full w-full object-cover"
                  />
                </div>
              </div>

              <div class="p-5 text-center">
                <p class="text-xl font-bold text-brand-blue">
                  Engr. Ramon Dela Cruz
                </p>
                <p class="text-xs text-blue-900">
                  City Transport Officer, Local Government Unit
                </p>

                <p class="pt-5 text-gray-700">
                  “EMSi’s electric transport operations demonstrate how
                  technology and sustainability can work together. Their system
                  improves route management, service transparency, and commuter
                  experience.”
                </p>
              </div>
            </div>
          </div>

          <div class="px-2 pt-16 pb-1">
            <div
              class="relative rounded-2xl bg-white pt-20 shadow-brand-shadow"
            >
              <!-- Image-->
              <div
                class="absolute top-[-60px] right-0 left-0 mx-auto w-fit rounded-full bg-white p-2 shadow-brand-shadow"
              >
                <div class="h-25 w-25 overflow-hidden rounded-full">
                  <img
                    src="@/assets/epinoy/testimonials-pic-2.png"
                    class="h-full w-full object-cover"
                  />
                </div>
              </div>

              <div class="p-5 text-center">
                <p class="text-xl font-bold text-brand-blue">
                  Ms. Liza Fernandez
                </p>
                <p class="text-xs text-blue-900">
                  President, Transport Cooperative
                </p>

                <p class="pt-5 text-gray-700">
                  “Partnering with EMSi allowed our cooperative to shift to
                  electric transport smoothly. The operational system, training,
                  and ongoing support helped us maintain reliable and modern
                  services.”
                </p>
              </div>
            </div>
          </div>

          <div class="px-2 pt-16 pb-1">
            <div
              class="relative rounded-2xl bg-white pt-20 shadow-brand-shadow"
            >
              <!-- Image-->
              <div
                class="absolute top-[-60px] right-0 left-0 mx-auto w-fit rounded-full bg-white p-2 shadow-brand-shadow"
              >
                <div class="h-25 w-25 overflow-hidden rounded-full">
                  <img
                    src="@/assets/epinoy/testimonials-pic-3.png"
                    class="h-full w-full object-cover"
                  />
                </div>
              </div>

              <div class="p-5 text-center">
                <p class="text-xl font-bold text-brand-blue">
                  Mr. Jonathan Reyes
                </p>
                <p class="text-xs text-blue-900">
                  Facilities Manager, Industrial Park
                </p>

                <p class="pt-5 text-gray-700">
                  “The electric shuttle services provided by EMSi are efficient
                  and environmentally responsible. Real-time monitoring and
                  reporting make daily transport operations easier to manage.”
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div
        class="mx-auto mt-10 flex w-full max-w-[500px] flex-col items-center gap-3 rounded-xl bg-white p-2 lg:flex-row lg:justify-between"
      >
        <p
          class="w-full text-center text-xl font-bold whitespace-nowrap text-brand-blue lg:ps-4"
        >
          Add Testimonials
        </p>
        <button
          class="w-full rounded-xl bg-brand-red py-3 text-xl font-bold whitespace-nowrap text-white sm:w-fit sm:px-16"
        >
          Create <i class="fa-solid fa-arrow-right ps-3"></i>
        </button>
      </div>
    </div>
  </div>
</template>
