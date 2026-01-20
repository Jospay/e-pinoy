<script setup lang="ts">
import { nextTick, onMounted } from 'vue';

declare const $: any;

// Owl Carousel Init
onMounted(async () => {
  await nextTick();

  const $carousel = $('.owl-2');

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
        550: { margin: 20, nav: false, items: 2 },
        800: { margin: 20, stagePadding: 0, nav: false, items: 3 },
        1280: { margin: 20, stagePadding: 0, nav: false, items: 4 },
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
  <div
    id="news"
    class="scroll-mt-12 bg-[url('@/assets/epinoy/new-bg.jpg')] bg-cover bg-center bg-no-repeat px-5 py-10 text-center text-white"
  >
    <h1 class="text-4xl font-bold">NEWS & UPDATES</h1>
    <p class="pt-3 text-xl font-bold">Stay Informed</p>
    <P class="text-md text-gray-300"
      >Latest announcements and updates from EMSi.</P
    >
  </div>

  <div class="mx-auto w-full max-w-[1320px] px-5 py-12">
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

      <div class="absolute end-3 top-1/2 z-10 -translate-y-1/2 2xl:end-[-60px]">
        <button
          class="bg-right-news h-[40px] w-[40px] rounded-[50%] bg-white/50 font-bold text-brand-blue shadow-blue 2xl:bg-white"
        >
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>

      <div class="owl-carousel owl-2">
        <div class="p-1">
          <div class="overflow-hidden rounded-2xl bg-white shadow-brand-shadow">
            <!-- Image wrapper -->
            <div class="h-70 w-full">
              <img
                src="@/assets/epinoy/news-pic-1.jpg"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="p-3">
              <p class="text-md font-bold text-brand-blue">
                New route launches
              </p>
              <div
                class="mt-5 w-full rounded-2xl bg-brand-red py-3 text-center text-xl font-bold text-white"
              >
                See more
              </div>
            </div>
          </div>
        </div>

        <div class="p-1">
          <div class="overflow-hidden rounded-2xl bg-white shadow-brand-shadow">
            <!-- Image wrapper -->
            <div class="h-70 w-full">
              <img
                src="@/assets/epinoy/news-pic-2.jpg"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="p-3">
              <p class="text-md font-bold text-brand-blue">
                System improvements
              </p>

              <div
                class="mt-5 w-full rounded-2xl bg-brand-red py-3 text-center text-xl font-bold text-white"
              >
                See more
              </div>
            </div>
          </div>
        </div>

        <div class="p-1">
          <div class="overflow-hidden rounded-2xl bg-white shadow-brand-shadow">
            <!-- Image wrapper -->
            <div class="h-70 w-full">
              <img
                src="@/assets/epinoy/news-pic-3.jpg"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="p-3">
              <p class="text-md font-bold text-brand-blue">
                Company announcements
              </p>

              <div
                class="mt-5 w-full rounded-2xl bg-brand-red py-3 text-center text-xl font-bold text-white"
              >
                See more
              </div>
            </div>
          </div>
        </div>

        <div class="p-1">
          <div class="overflow-hidden rounded-2xl bg-white shadow-brand-shadow">
            <!-- Image wrapper -->
            <div class="h-70 w-full">
              <img
                src="@/assets/epinoy/news-pic-4.jpg"
                class="h-full w-full object-cover"
              />
            </div>

            <div class="p-3">
              <p class="text-md font-bold text-brand-blue">Public advisories</p>

              <div
                class="mt-5 w-full rounded-2xl bg-brand-red py-3 text-center text-xl font-bold text-white"
              >
                See more
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- <div
      class="mx-auto mt-14 w-fit rounded-2xl bg-brand-red px-16 py-3 text-center text-xl font-bold text-white sm:px-20"
    >
      View more <i class="fa-solid fa-arrow-right ps-3"></i>
    </div> -->
  </div>
</template>
