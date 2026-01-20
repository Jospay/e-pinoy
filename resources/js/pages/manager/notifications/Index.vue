<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import manager from '@/routes/manager';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Notifications',
    href: manager.notifications().url,
  },
];

interface Notification {
  id: number;
  title: string;
  description: string;
  type: 'alert' | 'reminder';
  date: string;
}

const notifications = ref<Notification[]>([
  {
    id: 1,
    title: 'Unpaid boundary alert',
    description:
      'Franchise boundary exceeded for Region A. Please review outstanding payment.',
    type: 'alert',
    date: '2025-11-06 10:30 AM',
  },
  {
    id: 2,
    title: 'Expense approval reminder',
    description:
      'There are 3 pending expense approvals waiting for your review.',
    type: 'reminder',
    date: '2025-11-06 09:10 AM',
  },
  {
    id: 3,
    title: 'New franchise request',
    description:
      'A new franchise applicant in Quezon City submitted requirements.',
    type: 'reminder',
    date: '2025-11-05 05:22 PM',
  },
  {
    id: 4,
    title: 'Inventory threshold alert',
    description:
      'Stock on Franchise #214 is below critical level. Restock needed.',
    type: 'alert',
    date: '2025-11-05 02:45 PM',
  },
  {
    id: 5,
    title: 'Profile update requested',
    description:
      'Owner of Franchise #102 requested changes to company profile.',
    type: 'reminder',
    date: '2025-11-04 11:30 AM',
  },
  {
    id: 6,
    title: 'System maintenance upcoming',
    description: 'Scheduled maintenance on Nov 10, 11:00 PM — Expect downtime.',
    type: 'reminder',
    date: '2025-11-04 09:00 AM',
  },
  {
    id: 7,
    title: 'Unpaid invoice warning',
    description: 'Invoice INV-88932 is overdue for more than 30 days.',
    type: 'alert',
    date: '2025-11-03 01:18 PM',
  },
  {
    id: 8,
    title: 'Financial report ready',
    description: 'October sales analytics are now available for download.',
    type: 'reminder',
    date: '2025-11-03 10:52 AM',
  },
  {
    id: 9,
    title: 'New expense request',
    description: 'A ₱12,800 expense request from Franchise #88 needs approval.',
    type: 'reminder',
    date: '2025-11-02 02:20 PM',
  },
  {
    id: 10,
    title: 'Security alert',
    description:
      'Multiple failed login attempts detected for user admin@corp.ph',
    type: 'alert',
    date: '2025-11-02 08:44 AM',
  },
  {
    id: 11,
    title: 'Expired business permit alert',
    description:
      'Permit for Franchise #309 expired last week. Renewal required.',
    type: 'alert',
    date: '2025-11-01 03:13 PM',
  },
  {
    id: 12,
    title: 'New message received',
    description: 'Support team replied to ticket #7711 — open to view details.',
    type: 'reminder',
    date: '2025-10-31 06:10 PM',
  },
]);

function dismissNotification(id: number) {
  notifications.value = notifications.value.filter((n) => n.id !== id);
}
</script>

<template>
  <Head title="Notifications" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-3xl font-semibold text-gray-900 dark:text-gray-100">
          Notifications
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Stay informed about system updates, alerts, and reminders.
        </p>
      </div>

      <!-- Notification List -->
      <Card v-if="notifications.length" class="py-0">
        <CardContent class="divide-y divide-border p-0">
          <div
            v-for="notif in notifications"
            :key="notif.id"
            class="group flex items-start justify-between p-5 transition-colors hover:bg-gray-50 dark:hover:bg-gray-900/50"
            :class="{
              'border-l-4 border-red-500 bg-red-50/60 dark:bg-red-950/20':
                notif.type === 'alert',
              'border-l-4 border-blue-500 bg-blue-50/60 dark:bg-blue-950/20':
                notif.type === 'reminder',
            }"
          >
            <!-- Notification Content -->
            <div class="flex flex-col gap-1 pr-4">
              <h2
                class="text-sm font-medium tracking-tight"
                :class="
                  notif.type === 'alert'
                    ? 'text-red-700 dark:text-red-400'
                    : 'text-blue-700 dark:text-blue-400'
                "
              >
                {{ notif.title }}
              </h2>
              <p class="text-sm leading-snug text-gray-700 dark:text-gray-300">
                {{ notif.description }}
              </p>
              <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ notif.date }}
              </span>
            </div>

            <!-- Actions -->
            <div
              class="flex gap-2 opacity-0 transition-opacity group-hover:opacity-100"
            >
              <Button size="sm" variant="outline">View</Button>
              <Button
                size="sm"
                variant="ghost"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-gray-100"
                @click="dismissNotification(notif.id)"
              >
                Dismiss
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Empty State -->
      <div
        v-else
        class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 p-12 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="mb-2 h-10 w-10 text-gray-400 dark:text-gray-500"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12h6m2 0a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v3a2 2 0 002 2zm2 3v1a3 3 0 11-6 0v-1m9 0H6"
          />
        </svg>
        <p class="text-base font-medium">You're all caught up!</p>
        <p class="mt-1 text-xs text-gray-400">
          No new notifications at the moment.
        </p>
      </div>
    </div>
  </AppLayout>
</template>
