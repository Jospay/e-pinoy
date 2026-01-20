<script setup lang="ts">
import FranchiseCreateForm from '@/components/owner/franchise/FranchiseCreateForm.vue';
import { usePageTheme } from '@/composables/usePageTheme';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
	genderOptions: { value: string; label: string }[];
	idTypes: { value: string; label: string }[];

	paymentOptions: {
		id: string;
		label: string;
		color: string;
	}[];

	userType: {
		encrypted_id: string;
		name: string;
	};
}>();

usePageTheme('light');
</script>
<template>
	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="max-w-full px-6 py-3">
			<h2 class="font-mono text-2xl font-bold">Create New Franchise</h2>
		</div>
		<div class="mx-6 mb-6 max-w-2xl rounded-xl border p-6 shadow-sm">
			<FranchiseCreateForm :id-types="idTypes" />
		</div>
	</AppLayout>
</template>
