<script setup lang="ts">
const scrollToCareers = () => {
  const section = document.getElementById('careers');
  if (section) {
    section.scrollIntoView({ behavior: 'smooth' });
  }
};
</script>

<template>
  <div id="services" class="scroll-mt-22 bg-white">
    <div class="mx-auto w-full max-w-[1050px] px-5 pt-2 pb-10">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-brand-blue">SERVICES</h1>
        <p class="pt-6 text-xl font-bold text-brand-blue">
          Operational Excellence
        </p>
        <p class="text-md text-gray-700">
          EMSi supports end-to-end electric transport operations.
        </p>
      </div>

      <div class="grid grid-cols-12 pt-10 sm:gap-8">
        <div
          class="col-span-12 mb-5 overflow-hidden rounded-2xl shadow-brand-shadow sm:mb-0 lg:col-span-6"
        >
          <div class="grid grid-cols-12 sm:gap-5">
            <div class="col-span-4 h-full w-full overflow-hidden">
              <img
                src="@/assets/epinoy/services pic 1.jpg"
                alt=""
                class="h-full w-full object-cover"
              />
            </div>

            <div class="col-span-8 p-4">
              <p class="text-lg font-semibold text-brand-blue">
                Passenger Services
              </p>

              <ul class="list-disc ps-7 text-sm text-gray-600">
                <li>Route access and ride booking</li>
                <li>Real-time vehicle tracking</li>
                <li>Cashless payments</li>
                <li>Customer support</li>
              </ul>
            </div>
          </div>
        </div>

        <div
          class="col-span-12 mb-5 overflow-hidden rounded-2xl shadow-brand-shadow sm:mb-0 lg:col-span-6"
        >
          <div class="grid grid-cols-12 sm:gap-5">
            <div class="col-span-4 h-full w-full overflow-hidden">
              <img
                src="@/assets/epinoy/services pic 2.jpg"
                alt=""
                class="h-full w-full object-cover"
              />
            </div>

            <div class="col-span-8 p-4">
              <p class="text-lg font-semibold text-brand-blue">
                Driver & Workforce Management
              </p>

              <ul class="list-disc ps-7 text-sm text-gray-600">
                <li>Digital registration and verification</li>
                <li>Scheduling and assignments</li>
                <li>Performance monitoring</li>
                <li>Training programs</li>
              </ul>
            </div>
          </div>
        </div>

        <div
          class="col-span-12 mb-5 overflow-hidden rounded-2xl shadow-brand-shadow sm:mb-0 lg:col-span-6"
        >
          <div class="grid grid-cols-12 sm:gap-5">
            <div class="col-span-4 h-full w-full overflow-hidden">
              <img
                src="@/assets/epinoy/services pic 3.jpg"
                alt=""
                class="h-full w-full object-cover"
              />
            </div>

            <div class="col-span-8 p-4">
              <p class="text-lg font-semibold text-brand-blue">
                Vehicle Maintenance
              </p>

              <ul class="list-disc ps-7 text-sm text-gray-600">
                <li>Preventive maintenance scheduling</li>
                <li>Digital technician assignments</li>
                <li>Maintenance records and compliance</li>
              </ul>
            </div>
          </div>
        </div>

        <div
          class="perspective col-span-12 h-full min-h-[180px] cursor-pointer lg:col-span-6"
          @click="scrollToCareers"
        >
          <div
            class="preserve-3d group relative h-full transition-transform duration-700 hover:rotate-y-180"
          >
            <!-- Front side -->
            <div
              class="absolute inset-0 grid h-full place-items-center rounded-xl border-3 border-white bg-brand-blue p-6 backface-hidden"
            >
              <h1 class="text-center text-3xl font-bold text-white">
                MORE <br />
                SERVICES
              </h1>
            </div>
            <!-- Back side -->
            <div
              class="absolute inset-0 grid h-full rotate-y-180 place-items-center rounded-xl border-3 border-brand-blue bg-white p-6 backface-hidden"
            >
              <h1 class="text-center text-3xl font-bold text-brand-blue">
                CAREERS
              </h1>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.perspective {
  perspective: 1000px;
}
.preserve-3d {
  transform-style: preserve-3d;
}
.backface-hidden {
  backface-visibility: hidden;
}
.rotate-y-180 {
  transform: rotateY(180deg);
}
</style>
