<script setup lang="ts">
import axios from 'axios';
import { computed, nextTick, onMounted, ref, onBeforeUnmount } from 'vue';

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

const feedbacks = ref<Feedback[]>([...props.feedbacks]);

const filteredUserTypes = computed(() =>
  (props.userTypes || []).filter((t) =>
    ['driver', 'passenger'].includes(t.name.toLowerCase()),
  ),
);

const showModal = ref(false);
const showDetailModal = ref(false);
const selectedFeedback = ref<Feedback | null>(null);

// Fixed: Helper function to find feedback by ID (needed for Owl Carousel clones)
const handleSeeMoreClick = (id: number) => {
  const fb =
    feedbacks.value.find((f) => f.id === id) ||
    props.feedbacks.find((f) => f.id === id);
  if (fb) {
    selectedFeedback.value = fb;
    showDetailModal.value = true;
  }
};

const form = ref({
  name: '',
  user_type: '',
  rating: 5,
  description: '',
  avatar: null as File | null,
});

const errors = ref<Record<string, string[]>>({});
const submitting = ref(false);
const successMessage = ref<string | null>(null);
const errorMessage = ref<string | null>(null);

onMounted(async () => {
  await nextTick();

  const $carousel = $('.owl-3');

  if ($carousel.length > 0) {
    const owl2 = $carousel.owlCarousel({
      center: false,
      items: 1,
      loop: true,
      margin: 20,
      smartSpeed: 1000,
      responsive: {
        700: { items: 2 },
        1280: { items: 3 },
      },
    });

    $('.bg-left-news').on('click', () =>
      owl2.trigger('prev.owl.carousel', [1000]),
    );
    $('.bg-right-news').on('click', () =>
      owl2.trigger('next.owl.carousel', [1000]),
    );

    // FIXED: Event Delegation for "See More"
    // This ensures buttons work even on CLONED carousel items
    $(document).on('click', '.see-more-trigger', function (this: HTMLElement) {
      const fbId = $(this).data('id');
      handleSeeMoreClick(Number(fbId));
    });
  }
});

onBeforeUnmount(() => {
  $(document).off('click', '.see-more-trigger');
});

const onFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    form.value.avatar = target.files[0];
  }
};

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
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    feedbacks.value.push(data.feedback);
    form.value = {
      name: '',
      user_type: '',
      rating: 5,
      description: '',
      avatar: null,
    };
    showModal.value = false;
    successMessage.value = data.message || 'Feedback submitted successfully!';

    // Refresh carousel if needed (optional)
    setTimeout(() => {
      successMessage.value = '';
      window.location.reload();
    }, 2000);
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      errorMessage.value = 'Please fix the errors and try again.';
    } else {
      errorMessage.value = 'An unexpected error occurred.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <div id="testimonials" class="relative scroll-mt-10 bg-[#e3f0fa]">
    <div class="h-18 w-full"></div>
    <div class="h-7 w-full bg-gradient-to-r from-brand-blue to-[#05AEE9]"></div>

    <div
      class="absolute top-11 right-0 left-0 mx-auto w-fit bg-[#e3f0fa] px-3 py-1 text-center font-bold text-brand-blue sm:px-20"
    >
      <h1 class="text-4xl uppercase">Testimonials</h1>
      <p class="pt-3 text-xl">What People Say About EMSi</p>
    </div>

    <div class="mx-auto w-full max-w-[1320px] px-5 pt-16 pb-10">
      <div class="relative">
        <div
          class="absolute start-3 top-1/2 z-10 -translate-y-1/2 2xl:start-[-60px]"
        >
          <button
            class="bg-left-news h-[40px] w-[40px] rounded-full bg-white/50 font-bold text-brand-blue shadow-md 2xl:bg-white"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>
        </div>
        <div
          class="absolute end-3 top-1/2 z-10 -translate-y-1/2 2xl:end-[-60px]"
        >
          <button
            class="bg-right-news h-[40px] w-[40px] rounded-full bg-white/50 font-bold text-brand-blue shadow-md 2xl:bg-white"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>

        <div
          class="owl-carousel owl-3"
          v-if="props.feedbacks && props.feedbacks.length"
        >
          <div
            v-for="fb in props.feedbacks"
            :key="fb.id"
            class="px-2 pt-16 pb-1"
          >
            <div
              class="relative flex min-h-[300px] flex-col rounded-2xl bg-white pt-20 shadow-lg"
            >
              <div
                class="absolute top-[-60px] right-0 left-0 mx-auto w-fit rounded-full bg-white p-2 shadow-md"
              >
                <div class="h-25 w-25 overflow-hidden rounded-full">
                  <img
                    :src="
                      fb.avatar ? `/storage/${fb.avatar}` : '/images/avatar.svg'
                    "
                    class="h-full w-full object-cover"
                  />
                </div>
              </div>

              <div class="flex flex-1 flex-col justify-between p-5 text-center">
                <div>
                  <p class="text-xl font-bold text-brand-blue">{{ fb.name }}</p>
                  <p class="text-xs text-blue-900">
                    {{ fb.user_type?.name || 'User' }}
                  </p>

                  <div class="pt-5 text-gray-700">
                    <p>
                      "{{
                        fb.description.length > 100
                          ? fb.description.substring(0, 100) + '...'
                          : fb.description
                      }}"
                    </p>
                    <button
                      v-if="fb.description.length > 100"
                      :data-id="fb.id"
                      class="see-more-trigger mt-2 text-xs font-bold text-brand-blue underline"
                    >
                      See More
                    </button>
                  </div>
                </div>

                <div class="flex items-center justify-center gap-2 pt-4">
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
                  <div class="ml-1 font-bold text-brand-blue">
                    {{ (+fb.rating).toFixed(1) }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="py-10 text-center text-gray-500">
          No feedbacks available yet.
        </div>
      </div>

      <div
        class="mx-auto mt-10 flex w-full max-w-[500px] flex-col items-center gap-3 rounded-xl bg-white p-2 shadow-sm lg:flex-row lg:justify-between"
      >
        <p class="w-full text-center text-xl font-bold text-brand-blue lg:ps-4">
          Add Testimonials
        </p>
        <button
          class="w-full rounded-xl bg-red-600 py-3 text-xl font-bold whitespace-nowrap text-white sm:w-fit sm:px-16"
          @click="showModal = true"
        >
          Create <i class="fa-solid fa-arrow-right ps-3"></i>
        </button>
      </div>

      <div
        v-if="successMessage"
        class="my-2 rounded bg-green-100 p-2 text-center text-green-800"
      >
        {{ successMessage }}
      </div>
      <div
        v-if="errorMessage"
        class="my-2 rounded bg-red-100 p-2 text-center text-red-800"
      >
        {{ errorMessage }}
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      >
        <div class="relative w-full max-w-lg rounded-xl bg-white p-8 shadow-lg">
          <h2 class="mb-6 text-2xl font-bold text-brand-blue">
            Submit Your Feedback
          </h2>
          <button
            class="absolute top-4 right-4 text-xl text-gray-500 hover:text-black"
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
                required
              />
            </div>
            <div>
              <label class="mb-1 block font-semibold">User Type</label>
              <select
                v-model="form.user_type"
                class="w-full rounded border px-3 py-2"
                required
              >
                <option value="" disabled>Select Type</option>
                <option
                  v-for="type in filteredUserTypes"
                  :key="type.encrypted_id"
                  :value="type.encrypted_id"
                >
                  {{ type.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="mb-1 block font-semibold">Rating (1-5)</label>
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
                required
              ></textarea>
            </div>
            <div>
              <label class="mb-1 block font-semibold">Avatar</label>
              <input type="file" @change="onFileChange" class="text-sm" />
            </div>
            <button
              type="submit"
              class="w-full rounded-xl bg-brand-blue py-3 text-xl font-bold text-white"
              :disabled="submitting"
            >
              {{ submitting ? 'Submitting...' : 'Submit' }}
            </button>
          </form>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="showDetailModal && selectedFeedback"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 px-4 backdrop-blur-sm"
        @click.self="showDetailModal = false"
      >
        <div
          class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl"
        >
          <button
            class="absolute top-4 right-4 text-gray-400 hover:text-black"
            @click="showDetailModal = false"
          >
            <i class="fa-solid fa-xmark text-2xl"></i>
          </button>
          <div class="flex flex-col items-center text-center">
            <div
              class="mb-4 h-24 w-24 overflow-hidden rounded-full border-4 border-brand-blue/10"
            >
              <img
                :src="
                  selectedFeedback.avatar
                    ? `/storage/${selectedFeedback.avatar}`
                    : '/images/avatar.svg'
                "
                class="h-full w-full object-cover"
              />
            </div>
            <h3 class="text-2xl font-bold text-brand-blue">
              {{ selectedFeedback.name }}
            </h3>
            <span
              class="mb-4 text-sm font-medium tracking-widest text-gray-400 uppercase"
              >{{ selectedFeedback.user_type?.name || 'User' }}</span
            >

            <div class="mb-6 flex gap-1">
              <template v-for="i in 5" :key="i">
                <i
                  class="fa-solid fa-star text-xl"
                  :class="
                    i <= Math.round(+selectedFeedback.rating)
                      ? 'text-yellow-400'
                      : 'text-gray-200'
                  "
                ></i>
              </template>
            </div>

            <div class="max-h-[300px] overflow-y-auto px-4">
              <p class="text-lg leading-relaxed text-gray-600 italic">
                "{{ selectedFeedback.description }}"
              </p>
            </div>

            <button
              @click="showDetailModal = false"
              class="mt-8 w-full rounded-xl bg-brand-blue py-4 font-bold text-white shadow-lg hover:brightness-110"
            >
              Back to Testimonials
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.shadow-brand-shadow {
  box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
}
</style>
