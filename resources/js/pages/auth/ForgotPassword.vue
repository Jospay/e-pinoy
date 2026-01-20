<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { usePageTheme } from '@/composables/usePageTheme';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { User } from 'lucide-vue-next';

defineProps<{
  status?: string;
}>();

// Force light mode on this page only
usePageTheme('light');
</script>

<template>
  <AuthLayout
    text-overlay="Recover Your Account"
    title="Forgot password"
    description="Enter your email to receive a password reset link"
  >
    <Head title="Forgot password" />

    <div
      v-if="status"
      class="mb-4 text-center text-sm font-medium text-green-600"
    >
      {{ status }}
    </div>

    <div>
      <Form v-bind="email.form()" v-slot="{ errors, processing }">
        <div class="grid gap-2">
          <Label for="email" class="text-auth-blue">Email address</Label>
          <div
            class="flex w-full max-w-sm overflow-hidden rounded-md border border-gray-300"
          >
            <div class="flex items-center justify-center bg-auth-blue px-3">
              <User class="h-5 w-5 text-white" />
            </div>
            <Input
              id="email"
              type="email"
              name="email"
              required
              autofocus
              :tabindex="1"
              autocomplete="email"
              placeholder="email@example.com"
              class="flex-1 border-0 focus-visible:ring-0"
            />
          </div>

          <InputError :message="errors.email" />
        </div>

        <div class="mt-3 flex items-center justify-start">
          <Button
            class="text-md mt-2 w-full cursor-pointer bg-brand-green font-bold hover:bg-brand-green hover:opacity-80"
            :disabled="processing"
            data-test="email-password-reset-link-button"
          >
            <Spinner v-if="processing" />
            Email password reset link
          </Button>
        </div>
      </Form>

      <div class="pt-2 pb-4 text-center text-sm">
        Or, return to
        <TextLink
          :href="login()"
          class="font-bold text-auth-blue underline underline-offset-1"
        >
          log in
        </TextLink>
      </div>
    </div>
  </AuthLayout>
</template>
