<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ShieldCheck, ArrowLeft, RefreshCw } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({ errors: Object });
const formVerify = useForm({
  code: '',
  latitude: null as number | null,
  longitude: null as number | null,
});

// Cooldown Logic (120 seconds = 2 minutes)
const cooldown = ref(0);
let timerInterval: any = null;

const startTimer = (seconds: number) => {
  cooldown.value = seconds;
  const expiry = Date.now() + seconds * 1000;
  localStorage.setItem('otp_cooldown_expiry', expiry.toString());

  timerInterval = setInterval(() => {
    const remaining = Math.round((expiry - Date.now()) / 1000);
    if (remaining <= 0) {
      cooldown.value = 0;
      clearInterval(timerInterval);
      localStorage.removeItem('otp_cooldown_expiry');
    } else {
      cooldown.value = remaining;
    }
  }, 1000);
};

onMounted(() => {
  const savedExpiry = localStorage.getItem('otp_cooldown_expiry');
  if (savedExpiry) {
    const remaining = Math.round((parseInt(savedExpiry) - Date.now()) / 1000);
    if (remaining > 0) startTimer(remaining);
  }
});

onUnmounted(() => clearInterval(timerInterval));

const resendOtp = () => {
  if (cooldown.value > 0) return;

  router.post(
    '/passenger/send-otp',
    {},
    {
      onBefore: () => toast.loading('Sending code...', { id: 'otp-send' }),
      onSuccess: () => {
        toast.success('A new OTP has been sent!', { id: 'otp-send' });
        startTimer(120);
      },
      onError: () => toast.error('Failed to send OTP.', { id: 'otp-send' }),
    },
  );
};

const verifyOtp = () => {
  // Capture location during verification for security logs
  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        formVerify.latitude = position.coords.latitude;
        formVerify.longitude = position.coords.longitude;
        processVerification();
      },
      () => processVerification(), // Proceed even if location is denied
    );
  } else {
    processVerification();
  }
};

const processVerification = () => {
  formVerify.post('/passenger/verify-otp', {
    onBefore: () =>
      toast.loading('Verifying security code...', { id: 'verify-toast' }),
    onSuccess: () => {
      // Success redirect handled by Controller
    },
    onError: () => {
      toast.error('Verification failed. Check your code.', {
        id: 'verify-toast',
      });
      formVerify.code = '';
    },
  });
};

const breadcrumbs = [{ title: 'Security Verification', href: '#' }];
</script>

<template>
  <Head title="Verify Identity" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex min-h-[calc(100vh-64px)] items-center justify-center bg-slate-50/50 p-4"
    >
      <div class="w-full max-w-md space-y-6">
        <div class="text-center">
          <div
            class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl bg-brand-blue/10 text-brand-blue"
          >
            <ShieldCheck class="h-12 w-12" />
          </div>
          <h1 class="text-2xl font-black text-slate-900">Security Check</h1>
          <p class="mt-2 text-sm font-medium text-slate-500">
            Enter the 6-digit code sent to your phone.
          </p>
        </div>

        <div
          class="rounded-[32px] border border-slate-200 bg-white p-8 shadow-2xl"
        >
          <form @submit.prevent="verifyOtp" class="space-y-6">
            <input
              v-model="formVerify.code"
              type="text"
              maxlength="6"
              autofocus
              placeholder="000000"
              class="h-16 w-full rounded-2xl border-2 border-slate-100 bg-slate-50 text-center text-3xl font-black tracking-[0.5em] transition-all focus:border-brand-blue"
            />

            <div
              v-if="errors.code"
              class="mt-2 text-center text-xs font-bold text-red-500"
            >
              {{ errors.code }}
            </div>

            <Button
              type="submit"
              :disabled="formVerify.processing || formVerify.code.length < 6"
              class="h-14 w-full bg-brand-blue font-bold text-white"
            >
              Verify & Continue
            </Button>
          </form>

          <div class="mt-8 border-t border-slate-100 pt-6 text-center">
            <p class="text-xs font-bold text-slate-400">
              Didn't receive the code?
            </p>
            <button
              @click="resendOtp"
              :disabled="cooldown > 0"
              class="mx-auto mt-2 flex items-center justify-center gap-2 text-sm font-black text-brand-blue disabled:opacity-50"
            >
              <RefreshCw
                class="h-4 w-4"
                :class="{ 'animate-spin': cooldown > 0 }"
              />
              {{
                cooldown > 0
                  ? `Retry in ${Math.floor(cooldown / 60)}:${(cooldown % 60).toString().padStart(2, '0')}`
                  : 'Resend OTP'
              }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
