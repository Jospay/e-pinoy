<template>
  <Head title="My Contract" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-1 flex-col gap-6 p-6 min-h-[calc(100vh-64px)]">
      
      <!-- Page Title -->
      <h1 class="text-2xl font-semibold text-gray-900">My Contract</h1>

      <!-- Intro Paragraph -->
      <p class="text-gray-600 text-sm leading-relaxed">
        This section provides an overview of your franchise contract. 
        If a contract has already been uploaded, you can view it below. 
        Otherwise, a notice will indicate that no contract is currently available.
      </p>

      <!-- Contract Status -->
      <div
        v-if="!franchise || !franchise.contract_attachment"
        class="flex flex-col items-center justify-center gap-2 rounded border border-gray-300 bg-white p-6 text-center"
      >
        <p class="text-gray-500 italic">No contract available yet.</p>
        <p class="text-gray-400 text-sm">
          Once your contract is uploaded, it will appear here for easy access.
        </p>
      </div>

      <div v-else class="flex flex-col items-center gap-3">
        <p class="text-gray-700 text-sm text-center">
          Your contract has been successfully uploaded. You can review the details by opening the document below:
        </p>
        <a
          :href="franchise.contract_attachment"
          target="_blank"
          rel="noopener"
          class="px-4 py-2 rounded border border-blue-500 text-blue-600 font-medium hover:bg-blue-50 transition"
        >
          View Contract
        </a>
      </div>

      <!-- Helpful Note -->
      <p class="mt-6 text-gray-400 text-xs text-center">
        💡 Keep a copy of your contract for your records. To update or replace it, contact your administrator.
      </p>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, PageProps, usePage } from '@inertiajs/vue3';

interface Franchise {
  id: number;
  name: string;
  contract_attachment: string | null;
}

interface Props extends PageProps {
  franchise: Franchise | null;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Contract',
    href: owner.franchise.myContract(),
  },
];

const page = usePage<Props>();
const franchise = page.props.franchise;
</script>
