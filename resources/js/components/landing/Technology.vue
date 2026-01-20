<template>
  <div
    id="technology"
    class="scroll-mt-13 bg-[url('@/assets/epinoy/technology-main-bg.jpg')] bg-cover bg-center bg-no-repeat"
  >
    <div class="mx-auto w-full max-w-[1200px] px-5 py-12">
      <div class="grid grid-cols-12 items-center gap-5">
        <div class="order-2 col-span-12 lg:order-1 lg:col-span-5">
          <h1 class="text-4xl font-bold text-white">TECHNOLOGY</h1>

          <p class="pt-2 text-lg font-semibold text-white">
            Key Technology Features
          </p>
          <p class="text-sm text-gray-200">
            EMSi uses digital platforms to manage electric transport operations
            efficiently.
          </p>

          <p class="pt-3 text-lg font-semibold text-white">
            Key Technology Features
          </p>
          <ul class="list-disc ps-4 text-sm text-gray-200">
            <li>Web and mobile applications</li>
            <li>Real-time fleet monitoring</li>
            <li>Operations dashboard and analytics</li>
            <li>System updates and enhancements</li>
            <li>Secure data handling and privacy compliance</li>
          </ul>
        </div>

        <div class="order-1 col-span-12 lg:order-2 lg:col-span-7">
          <div class="grid grid-cols-12 gap-2 sm:gap-5.5">
            <div class="col-span-4">
              <img
                src="@/assets/epinoy/tech-pic-1.jpg"
                class="mb-2 w-full rounded-2xl sm:mb-4"
                alt=""
              />
              <img
                src="@/assets/epinoy/tech-pic-2.jpg"
                class="w-full rounded-2xl"
                alt=""
              />
            </div>
            <div class="col-span-8">
              <img
                src="@/assets/epinoy/tech-pic-3.jpg"
                class="w-full rounded-2xl"
                alt=""
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mx-auto w-full max-w-[1000px] px-7 py-12">
    <div
      class="grid items-center justify-center gap-4 md:flex md:justify-between md:gap-0"
    >
      <div>
        <h1
          class="pt-4 text-center text-3xl font-bold whitespace-nowrap text-brand-blue sm:text-4xl"
        >
          DOWNLOAD APP:
        </h1>
      </div>

      <div class="flex flex-wrap items-center justify-center gap-4">
        <a href="" target="_blank">
          <img
            src="@/assets/epinoy/playstore.png"
            class="h-[50px] w-auto sm:h-[80px]"
            alt="Play Store"
          />
        </a>
        <img
          src="@/assets/epinoy/appstore.png"
          class="h-[50px] w-auto sm:h-[80px]"
          alt="App Store"
        />
      </div>
    </div>
  </div>
</template>
