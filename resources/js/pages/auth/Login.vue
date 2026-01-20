<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePageTheme } from '@/composables/usePageTheme';
import AuthBase from '@/layouts/AuthLayout.vue';
import { home, selectUserType } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Form, Head, Link } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, User } from 'lucide-vue-next';
import { ref } from 'vue';

// Define types to match your AuthLayout
interface FranchiseData {
  id: number;
  name: string;
  region: string;
  province: string;
  city: string;
  latitude: string;
  longitude: string;
}

// UPDATE: Added franchises and userTypes to props
defineProps<{
  status?: string;
  canResetPassword: boolean;
  canRegister: boolean;
  franchises: FranchiseData[];
  userTypes: any[];
}>();

const showPassword = ref(false);

// Force light mode on this page only
usePageTheme('light');
</script>

<template>
  <!-- Log in to your account -->
  <AuthBase
    :franchises="franchises"
    text-overlay=""
    title="ACCOUNT LOGIN"
    description="Enter your email and password to log in "
  >
    <Head title="Log in" />

    <div
      v-if="status"
      class="mb-4 text-center text-sm font-medium text-green-600"
    >
      {{ status }}
    </div>

    <Form
      v-bind="store.form()"
      :reset-on-success="['password']"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-2"
    >
      <div class="grid gap-2">
        <Label for="email" class="text-auth-blue"
          >Email address or Phone Number</Label
        >
        <div
          class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
        >
          <div class="flex items-center justify-center bg-auth-blue px-3">
            <User class="h-5 w-5 text-white" />
          </div>
          <Input
            id="email"
            type="text"
            name="email"
            required
            autofocus
            :tabindex="1"
            autocomplete="email"
            placeholder="Email or Phone Number"
            class="flex-1 border-0 focus-visible:ring-0"
          />
        </div>

        <InputError :message="errors.email" />
      </div>

      <div class="grid gap-2">
        <Label for="password" class="text-auth-blue">Password</Label>
        <div
          class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
        >
          <div
            class="flex items-center justify-center rounded-l-md bg-auth-blue px-3"
          >
            <Lock class="h-5 w-5 text-white" />
          </div>

          <div class="relative w-full items-center">
            <Input
              id="password"
              :type="showPassword ? 'text' : 'password'"
              name="password"
              required
              :tabindex="2"
              autocomplete="current-password"
              placeholder="Password"
              class="flex-1 border-0 pr-10 focus-visible:ring-0"
            />

            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
              @click="showPassword = !showPassword"
            >
              <Eye v-if="!showPassword" class="h-5 w-5" />
              <EyeOff v-else class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div class="text-end">
          <TextLink
            v-if="canResetPassword"
            :href="request()"
            class="text-end text-sm text-auth-blue"
            :tabindex="5"
          >
            Forgot password?
          </TextLink>
        </div>
      </div>

      <!-- <div class="flex items-center justify-between">
        <Label for="remember" class="flex items-center">
          <Checkbox id="remember" name="remember" :tabindex="3" />
          <span>Remember me</span>
        </Label>
      </div> -->

      <div class="flex flex-col gap-2">
        <Button
          type="submit"
          class="text-md mt-2 w-full cursor-pointer bg-auth-blue font-bold text-white hover:bg-auth-blue hover:opacity-80"
          :tabindex="4"
          :disabled="processing"
          data-test="login-button"
        >
          <Spinner v-if="processing" />
          LOG IN
        </Button>

        <Button
          asChild
          class="text-md w-full cursor-pointer bg-brand-red font-bold text-white hover:bg-brand-red hover:opacity-80"
          :tabindex="5"
          :disabled="processing"
        >
          <Link :href="home()"> RETURN HOME </Link>
        </Button>
      </div>

      <div class="pt-1 pb-5 text-center text-sm" v-if="canRegister">
        Don't have an account?
        <TextLink
          :href="selectUserType()"
          :tabindex="6"
          class="font-bold text-auth-blue underline underline-offset-1"
        >
          Register Here
        </TextLink>
      </div>
    </Form>
  </AuthBase>
</template>
