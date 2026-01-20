<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { usePageTheme } from '@/composables/usePageTheme';
import { login } from '@/routes';
import { Link } from '@inertiajs/vue3';
// Props type
interface UserType {
  encrypted_id: string;
  name: string;
}

const props = defineProps<{
  userTypes: UserType[];
}>();

// Force light mode on this page only
usePageTheme('light');
</script>

<template>
  <div
    class="flex h-screen w-full items-center justify-center bg-muted bg-[url(@/assets/auth/loginbg.jpg)] bg-cover bg-center bg-no-repeat p-2 sm:p-6"
  >
    <div
      class="relative w-full max-w-md rounded-2xl bg-white p-4 shadow-2xl sm:p-8"
    >
      <img
        src="@/assets/epinoy/nav_logo.png"
        alt=""
        class="mx-auto h-15 w-auto pb-5"
      />

      <h1 class="text-center text-2xl font-bold text-auth-blue">
        ACCOUNT REGISTER
      </h1>
      <h1 class="text-md pb-5 text-center text-gray-700">
        What do you want to register?
      </h1>

      <div class="space-y-3 pt-2">
        <div v-for="type in props.userTypes" :key="type.encrypted_id">
          <Link :href="`/register/${type.encrypted_id}`">
            <div
              class="flex cursor-pointer items-center space-x-3 rounded-md bg-auth-blue p-3 text-white hover:bg-brand-blue"
            >
              <span class="text-center text-lg font-medium uppercase">{{
                type.name
              }}</span>
            </div>
          </Link>

          <!-- Conditionally show "Apply a Franchised" for type.name === 'Owner' -->
          <div v-if="type.name === 'Owner'" class="mt-2 text-sm text-gray-600">
            Apply a Franchised
          </div>
        </div>
      </div>

      <div class="pt-4 text-center text-sm">
        Already have an account?
        <TextLink
          :href="login()"
          class="font-bold text-auth-blue underline underline-offset-4 hover:text-brand-blue"
        >
          Log in
        </TextLink>
      </div>
    </div>
  </div>
</template>
