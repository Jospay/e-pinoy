<script setup lang="ts">
import { usePageTheme } from '@/composables/usePageTheme';
import HomeLayout from '@/layouts/HomeLayout.vue';
import { Head } from '@inertiajs/vue3';

// landing page import
import About from '@/components/landing/About.vue';
import Banner from '@/components/landing/Banner.vue';
import Contact from '@/components/landing/Contact.vue';
import Services from '@/components/landing/Services.vue';
import Careers from '@/components/landing/Careers.vue';
import Franchise from '@/components/landing/Franchise.vue';
import News from '@/components/landing/News.vue';
import Sustainability from '@/components/landing/Sustainability.vue';
import Technology from '@/components/landing/Technology.vue';
import Testimonials from '@/components/landing/Testimonials.vue';
import Partners from '@/components/landing/Partners.vue';
import Solutions from '@/components/landing/Solutions.vue';

// 1. Define the data structure
interface FranchiseData {
  id: number;
  name: string;
  region: string;
  province: string;
  city: string;
}

interface UserTypes {
  name: string;
  encrypted_id: string;
}

interface UserTypeFeedback {
  id: number;
  name: string;
}

interface Feedback {
  id: number;
  name: string;
  avatar?: string | null;
  rating: number;
  description: string;
  user_type: UserTypeFeedback | null;
  created_at: string;
}

// 2. Define a SINGLE Props interface
interface Props {
  userTypes: UserTypes[];
  franchises: FranchiseData[];
  feedbacks: Feedback[];
}

// 3. Destructure BOTH variables so they are available to your template
const { userTypes, franchises, feedbacks } = defineProps<Props>();

// Force light mode
usePageTheme('light');
</script>

<template>
  <Head title=" | Home" />

  <HomeLayout>
    <!-- Banner Start -->
    <Banner :userTypes="userTypes" />
    <!-- Banner End -->

    <!-- About Us Start -->
    <About />
    <!-- About Us End -->

    <!-- How It Solutions Start -->
    <Solutions />
    <!-- How It Solutions End -->

    <!-- Franchise Start -->
    <Franchise :userTypes="userTypes" :franchises="franchises" />
    <!-- Franchise End -->

    <!-- Services Start -->
    <Services :userTypes="userTypes" />
    <!-- Services End -->

    <!-- Technology Start -->
    <Technology :userTypes="userTypes" />
    <!-- Technology End -->

    <!-- Sustainability Start -->
    <Sustainability />
    <!-- Sustainability End -->

    <!-- Partners Start -->
    <Partners :feedbacks="feedbacks" :userTypes="userTypes" />
    <!-- Partners End -->

    <!-- Join Start -->
    <News />
    <!-- Join End -->

    <!-- Careers Start -->
    <Careers />
    <!-- Careers End -->

    <!-- Testimonials Start -->
    <Testimonials />
    <!-- Testimonials End -->

    <!-- Contact Start -->
    <Contact />
    <!-- Contact End-->
  </HomeLayout>
</template>
