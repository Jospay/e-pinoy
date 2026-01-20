<script setup lang="ts">
import axios from 'axios';
import { computed, nextTick, onMounted, ref } from 'vue';

declare const $: any;

interface UserTypeFeedback {
  id: number;
  name: string;
}

interface Feedback {
  id: number;
  name: string;
  avatar?: string | null;
  rating: number | string;
  description: string;
  user_type: UserTypeFeedback | null;
  created_at: string;
}

interface UserType {
  name: string;
  encrypted_id: string;
}

const props = defineProps<{
  feedbacks: Feedback[];
  userTypes?: UserType[];
}>();

// Local reactive copy of feedbacks
const feedbacks = ref<Feedback[]>([...props.feedbacks]);

const filteredUserTypes = computed(() =>
  (props.userTypes || []).filter((t) =>
    ['driver', 'passenger'].includes(t.name.toLowerCase()),
  ),
);

// Modal and form state
const showModal = ref(false);

const form = ref<{
  name: string;
  user_type: string;
  rating: number;
  description: string;
  avatar: File | null;
}>({
  name: '',
  user_type: '',
  rating: 5,
  description: '',
  avatar: null,
});

// Form validation errors
const errors = ref<Record<string, string[]>>({});

// Submission status
const submitting = ref(false);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

// Owl Carousel Init
onMounted(async () => {
  await nextTick();

  const $carousel = $('.owl-1');

  if ($carousel.length > 0) {
    const owl1 = $carousel.owlCarousel({
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
        850: { margin: 20, nav: false, items: 1 },
        1000: { margin: 20, stagePadding: 0, nav: false, items: 1 },
        1280: { margin: 20, stagePadding: 0, nav: false, items: 2 },
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

// Handle file upload
const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.value.avatar = target.files[0];
  }
};

// Submit feedback
const submitFeedback = async () => {
  submitting.value = true;
  successMessage.value = null;
  errorMessage.value = null;
  errors.value = {};

  try {
    const formData = new FormData();
    formData.append('name', form.value.name);
    formData.append('user_type', form.value.user_type);
    formData.append('rating', form.value.rating.toString());
    formData.append('description', form.value.description);

    if (form.value.avatar) {
      formData.append('avatar', form.value.avatar);
    }

    const { data } = await axios.post('/feedback', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    // Update local feedbacks list
    feedbacks.value.push(data.feedback);

    // Reset form and close modal
    form.value = {
      name: '',
      user_type: '',
      rating: 5,
      description: '',
      avatar: null,
    };
    showModal.value = false;
    successMessage.value = data.message || 'Feedback submitted successfully!';
    setTimeout(() => {
      successMessage.value = '';
    }, 3000);
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      errorMessage.value = 'Please fix the errors and try again.';
    } else if (err.response?.data?.error) {
      errorMessage.value = err.response.data.error;
    } else {
      errorMessage.value = 'An unexpected error occurred. Please try again.';
    }
    console.error('Feedback submission error:', err);
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <div id="testi" class="scroll-mt-12 px-5 py-10">
    <div class="mx-auto w-full max-w-[1320px]">
      <div class="flex items-center gap-5">
        <h1 class="text-3xl font-bold whitespace-nowrap text-brand-blue">
          TESTIMONIALS
        </h1>
        <div class="h-1 w-full bg-brand-blue"></div>
      </div>

      <div class="grid grid-cols-12 items-center gap-5 pt-14 lg:gap-0">
        <div class="col-span-12 lg:col-span-5">
          <h1 class="text-3xl font-bold text-brand-blue">
            REAL STORIES FROM OUR <br />
            DRIVERS, FRANCHISEES, <br />
            AND PASSENGERS
          </h1>

          <p class="pt-6 text-lg/[22px]">
            Hear how ERP System for Car Boundary <br />Service- Philippines is
            transforming the way <br />
            Filipinos drive, manage, and ride every day.
          </p>
          <p class="pt-6 text-lg/[22px]">
            From franchise owner to daily commuters, <br />
            our system empowers everyone in the <br />
            boundary ecosystem.
          </p>

          <div class="pt-10">
            <button
              class="rounded-xl bg-brand-green px-12 py-3 text-2xl text-white md:text-3xl"
              @click="showModal = true"
            >
              FEEDBACK
            </button>
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
          </div>
        </div>

        <div class="col-span-12 lg:col-span-7">
          <div class="relative">
            <div class="absolute start-[-20px] top-1/2 z-10 -translate-y-1/2">
              <button
                class="bg-left-half123 h-[40px] w-[40px] rounded-[50%] border-2 border-white bg-brand-blue text-white"
              >
                <i class="fa-solid fa-chevron-left"></i>
              </button>
            </div>

            <div class="absolute end-[-20px] top-1/2 z-10 -translate-y-1/2">
              <button
                class="bg-right-half h-[40px] w-[40px] rounded-[50%] border-2 border-white bg-brand-blue text-white"
              >
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

            <div
              class="owl-carousel owl-1"
              v-if="props.feedbacks && props.feedbacks.length"
            >
              <!-- Dynamic Feedback Loop -->
              <div
                v-for="fb in props.feedbacks"
                :key="fb.id"
                class="rounded-xl border-4 border-brand-blue p-5"
              >
                <div
                  class="grid items-center justify-center gap-5 sm:flex sm:justify-start"
                >
                  <div class="flex justify-center sm:justify-start">
                    <div
                      class="h-[120px] w-[120px] overflow-hidden rounded-full border-4 border-brand-blue"
                    >
                      <img
                        :src="
                          fb.avatar
                            ? `/storage/${fb.avatar}`
                            : '/images/avatar.svg'
                        "
                        alt=""
                        class="h-full w-full object-contain"
                      />
                    </div>
                  </div>

                  <div class="text-center sm:text-start">
                    <h1 class="text-xl font-bold text-brand-blue">
                      {{ fb.name }}
                    </h1>
                    <p>{{ fb.user_type?.name || 'User' }}</p>
                    <div class="flex gap-2 pt-4">
                      <template v-for="i in 5" :key="i">
                        <i
                          class="fa-solid fa-star text-2xl"
                          :class="
                            i <= Math.round(+fb.rating)
                              ? 'text-yellow-400'
                              : 'text-gray-300'
                          "
                        ></i>
                      </template>
                      <div>{{ (+fb.rating).toFixed(1) }}</div>
                    </div>
                  </div>
                </div>
                <p class="pt-4 text-lg/[22px]">{{ fb.description }}</p>
              </div>
              <!-- End Dynamic Feedback Loop -->
            </div>
            <div v-else class="py-10 text-center text-gray-500">
              No feedbacks available yet.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback Modal -->
    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
      >
        <div class="relative w-full max-w-lg rounded-xl bg-white p-8 shadow-lg">
          <h2 class="mb-6 text-2xl font-bold text-brand-blue">
            Submit Your Feedback
          </h2>
          <button
            class="absolute top-3 right-3 text-gray-500 hover:text-gray-800"
            @click="showModal = false"
          >
            ✕
          </button>

          <form @submit.prevent="submitFeedback" class="space-y-4">
            <div>
              <label class="mb-1 block font-semibold">Name</label>
              <input
                type="text"
                v-model="form.name"
                class="w-full rounded border px-3 py-2"
                placeholder="Juan Dela Cruz"
                required
              />
            </div>

            <div>
              <label class="mb-1 block font-semibold">User Type</label>
              <select
                v-model="form.user_type"
                class="w-full rounded border px-3 py-2 capitalize"
                required
                placeholder="Select user type"
              >
                <option value="" disabled>Select Type</option>
                <option
                  v-for="type in filteredUserTypes"
                  class="capitalize"
                  :key="type.encrypted_id"
                  :value="type.encrypted_id"
                >
                  {{ type.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1 block font-semibold">Rating</label>
              <input
                type="number"
                v-model.number="form.rating"
                min="1"
                max="5"
                step="0.1"
                class="w-full rounded border px-3 py-2"
                required
              />
            </div>

            <div>
              <label class="mb-1 block font-semibold">Description</label>
              <textarea
                v-model="form.description"
                class="w-full rounded border px-3 py-2"
                rows="4"
                placeholder="What is on your mind?"
                required
              ></textarea>
            </div>

            <div>
              <label class="mb-1 block font-semibold">Avatar (optional)</label>
              <input type="file" @change="onFileChange" />
            </div>

            <div class="pt-4">
              <button
                type="submit"
                class="w-full rounded-xl bg-brand-blue py-3 text-2xl text-white"
                :disabled="submitting"
              >
                {{ submitting ? 'Submitting...' : 'Submit' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </div>

  <div class="w-full bg-brand-blue py-2"></div>
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
