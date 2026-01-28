<script lang="ts" setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
});

const submitting = ref(false);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

function submit() {
  submitting.value = true;
  successMessage.value = null;
  errorMessage.value = null;

  form.post('/contact', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      successMessage.value = 'Submitted successfully!';
      submitting.value = false;
      setTimeout(() => {
        successMessage.value = '';
      }, 3000);
    },
  });
}
</script>

<template>
  <div id="contact" class="bg-[#e3f0fa]">
    <div class="mx-auto w-full max-w-[1320px] py-10 sm:px-5">
      <div class="bg-white sm:rounded-2xl">
        <div class="grid grid-cols-12 items-center pt-8 pb-12 lg:pt-12">
          <div class="col-span-12 p-4 sm:p-10 lg:col-span-6">
            <h3 class="text-2xl font-bold text-brand-blue">CONTACT US</h3>
            <p class="pt-3 text-xl font-bold text-brand-blue">Let's Connect</p>
            <p class="pb-8 text-lg">For inquiries, partnerships, or support.</p>

            <div
              v-if="successMessage"
              class="my-2 rounded bg-green-100 p-2 text-green-800"
            >
              {{ successMessage }}
            </div>

            <div
              v-if="errorMessage"
              class="my-2 rounded bg-red-100 p-2 text-red-800"
            >
              {{ errorMessage }}
            </div>
            <form @submit.prevent="submit()">
              <label for="" class="ps-1 text-brand-blue">Full Name</label>
              <input
                type="text"
                v-model="form.name"
                required
                placeholder="Name"
                class="mt-1 mb-3 w-full rounded-3xl border border-gray-300 bg-white px-4 py-2 shadow-black focus:ring-2 focus:ring-blue-400 focus:outline-none"
              />
              <div v-if="form.errors.name" class="text-red-500">
                {{ form.errors.name }}
              </div>

              <label for="" class="ps-1 text-brand-blue">Email</label>
              <input
                type="email"
                v-model="form.email"
                required
                placeholder="Email"
                class="mt-1 mb-3 w-full rounded-3xl border border-gray-300 bg-white px-4 py-2 shadow-black focus:ring-2 focus:ring-blue-400 focus:outline-none"
              />
              <div v-if="form.errors.email" class="text-red-500">
                {{ form.errors.email }}
              </div>

              <label for="" class="ps-1 text-brand-blue">Subject</label>
              <input
                type="text"
                v-model="form.subject"
                required
                placeholder="Subject"
                class="mt-1 mb-3 w-full rounded-3xl border border-gray-300 bg-white px-4 py-2 shadow-black focus:ring-2 focus:ring-blue-400 focus:outline-none"
              />
              <div v-if="form.errors.subject" class="text-red-500">
                {{ form.errors.subject }}
              </div>
              <label for="" class="ps-1 text-brand-blue">Message</label>
              <textarea
                placeholder="Message.."
                v-model="form.message"
                required
                rows="6"
                class="mt-1 w-full rounded-3xl border border-gray-300 bg-white p-4 shadow-black focus:ring-2 focus:ring-blue-400 focus:outline-none"
              ></textarea>
              <div v-if="form.errors.message" class="text-red-500">
                {{ form.errors.message }}
              </div>

              <div class="pt-4 pb-4 text-center lg:pb-0 lg:text-end">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="rounded-md bg-brand-blue px-10 py-2 text-lg font-bold text-white"
                >
                  <span v-if="submitting">Submitting...</span>
                  <span v-else>
                    Send <i class="fa-solid fa-arrow-right ps-3"></i>
                  </span>
                </button>
              </div>
            </form>
          </div>

          <div class="col-span-12 px-5 lg:col-span-6 lg:px-0">
            <div
              class="rounded-2xl bg-gradient-to-r from-brand-blue to-[#05AEE9] p-4 sm:p-10"
            >
              <h3 class="text-xl font-bold text-white">
                Visit our office or drop us a line:
              </h3>

              <div class="mt-8 rounded-xl bg-white/25 p-5 sm:p-8 md:col-span-6">
                <div class="flex items-center gap-4 sm:gap-14">
                  <i class="fa-solid fa-phone-volume text-3xl text-white"></i>

                  <div class="text-white">
                    <h1 class="text-xl font-bold">Mobile Number:</h1>
                    <h1 class="text-md text-gray-200">+63 912 345 6789</h1>
                  </div>
                </div>
              </div>

              <div class="mt-6 rounded-xl bg-white/25 p-5 sm:p-8 md:col-span-6">
                <div class="flex items-center gap-4 sm:gap-14">
                  <i class="fa-solid fa-location-dot text-3xl text-white"></i>

                  <div class="text-white">
                    <h1 class="text-xl font-bold">Email Address:</h1>
                    <h1 class="text-md break-all text-gray-200">
                      info.sample.email@gmail.com
                    </h1>
                  </div>
                </div>
              </div>

              <div class="mt-6 rounded-xl bg-white/25 p-5 sm:p-8 md:col-span-6">
                <div class="flex items-center gap-4 sm:gap-14">
                  <i class="fa-solid fa-location-dot text-3xl text-white"></i>

                  <div class="text-white">
                    <h1 class="text-xl font-bold">Address:</h1>
                    <h1 class="text-md text-gray-200">
                      3rd Floor, EMSi Transport Hub Ortigas Center, Pasig City
                      Metro Manila, Philippines 1605
                    </h1>
                  </div>
                </div>
              </div>

              <h3 class="pt-10 text-xl font-bold text-white">
                Follow us on social media for the latest updates!
              </h3>
              <div class="flex flex-wrap gap-3 pt-4">
                <img src="@/assets/epinoy/fb.png" class="h-10 md:h-18" alt="" />
                <img src="@/assets/epinoy/yt.png" class="h-10 md:h-18" alt="" />
                <img
                  src="@/assets/epinoy/insta.png"
                  class="h-10 md:h-18"
                  alt=""
                />
                <img src="@/assets/epinoy/x.png" class="h-10 md:h-18" alt="" />
                <img
                  src="@/assets/epinoy/tiktok.png"
                  class="h-10 md:h-18"
                  alt=""
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
