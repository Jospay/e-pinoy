<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import { useAddress } from '@/composables/useAddress';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

const { props } = usePage<{
	franchise: any;
	idTypes: { value: string; label: string }[];
}>();

const breadcrumbs = [
	{ title: 'Franchise Management', href: superAdmin.dashboard().url },
	{ title: 'Edit Franchise' },
];

const formData = ref({
	name: props.franchise.name,
	email: props.franchise.email,
	phone: props.franchise.phone,
	region: props.franchise.region,
	province: props.franchise.province,
	city: props.franchise.city,
	barangay: props.franchise.barangay,
	postal_code: props.franchise.postal_code,
	address: props.franchise.address,
});

const selectedFiles = ref<{
	dti_registration_attachment: File | null;
	mayor_permit_attachment: File | null;
	proof_agreement_attachment: File | null;
}>({
	dti_registration_attachment: null,
	mayor_permit_attachment: null,
	proof_agreement_attachment: null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleDTIChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files.length > 0) {
		selectedFiles.value.dti_registration_attachment = target.files[0];
	}
};

const handleMayorChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files.length > 0) {
		selectedFiles.value.mayor_permit_attachment = target.files[0];
	}
};

const handleProofChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files.length > 0) {
		selectedFiles.value.proof_agreement_attachment = target.files[0];
	}
};

const {
	regions,
	provinces,
	cities,
	barangays,
	selectedRegion,
	selectedProvince,
	selectedCity,
	selectedBarangay,
	isNcr,
	isLoadingRegions,
	isLoadingProvinces,
	isLoadingCities,
	isLoadingBarangays,
} = useAddress();

selectedRegion.value = props.franchise.region || '';
selectedProvince.value = props.franchise.province || '';
selectedCity.value = props.franchise.city || '';
selectedBarangay.value = props.franchise.barangay || '';

watch(selectedRegion, (v) => (formData.value.region = v));
watch(selectedProvince, (v) => (formData.value.province = v));
watch(selectedCity, (v) => (formData.value.city = v));
watch(selectedBarangay, (v) => (formData.value.barangay = v));

const disableSubmit = computed(() => {
	return !(
		formData.value.name &&
		formData.value.email &&
		formData.value.phone &&
		formData.value.region &&
		formData.value.city &&
		formData.value.barangay &&
		formData.value.postal_code &&
		formData.value.address
	);
});

const submit = () => {
	processing.value = true;

	const data = new FormData();

	Object.entries(formData.value).forEach(([key, value]) => {
		if (value !== null && value !== undefined) {
			data.append(key, String(value));
		}
	});

	if (selectedFiles.value.dti_registration_attachment) {
		data.append(
			'dti_registration_attachment',
			selectedFiles.value.dti_registration_attachment,
		);
	}
	if (selectedFiles.value.mayor_permit_attachment) {
		data.append(
			'mayor_permit_attachment',
			selectedFiles.value.mayor_permit_attachment,
		);
	}
	if (selectedFiles.value.proof_agreement_attachment) {
		data.append(
			'proof_agreement_attachment',
			selectedFiles.value.proof_agreement_attachment,
		);
	}

	router.post(superAdmin.franchise.update(props.franchise.id).url, data, {
		preserveState: true,
		onSuccess: () => {
			toast.success('Franchise updated successfully!');
			processing.value = false;
			selectedFiles.value = {
				dti_registration_attachment: null,
				mayor_permit_attachment: null,
				proof_agreement_attachment: null,
			};
		},
		onError: (err) => {
			errors.value = err;
			processing.value = false;
		},
		onFinish: () => {
			processing.value = false;
		},
	});
};

const reset = () => {
	formData.value = {
		name: props.franchise.name,
		email: props.franchise.email,
		phone: props.franchise.phone,
		region: props.franchise.region,
		province: props.franchise.province,
		city: props.franchise.city,
		barangay: props.franchise.barangay,
		postal_code: props.franchise.postal_code,
		address: props.franchise.address,
	};
	selectedFiles.value = {
		dti_registration_attachment: null,
		mayor_permit_attachment: null,
		proof_agreement_attachment: null,
	};
};
</script>

<template>
	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="max-w-full px-6 py-3">
			<h2 class="font-mono text-2xl font-bold">
				Edit Franchise —
				<span class="font-normal">
					{{ props.franchise.name }}
					<span v-if="props.franchise.owner?.user">
						— Owned by {{ props.franchise.owner.user.name }}
						<span class="text-sm text-gray-500">
							({{ props.franchise.owner.user.email }})
						</span>
					</span>
				</span>
			</h2>
		</div>

		<div class="mx-6 mb-6 max-w-full rounded-xl border p-6 shadow-sm">
			<form @submit.prevent="submit" class="space-y-8">
				<!-- Franchise Info -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">Franchise Information</h2>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div class="space-y-2">
							<Label>Name</Label>
							<Input v-model="formData.name" />
							<InputError :message="errors.name" />
						</div>
						<div class="space-y-2">
							<Label>Email</Label>
							<Input v-model="formData.email" />
							<InputError :message="errors.email" />
						</div>
						<div class="space-y-2">
							<Label>Phone</Label>
							<Input v-model="formData.phone" />
							<InputError :message="errors.phone" />
						</div>
					</div>
				</div>

				<div class="my-4 border-t" />

				<!-- Address -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">Franchise Address</h2>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div class="space-y-2">
							<Label>Region</Label>
							<Select v-model="selectedRegion" :disabled="isLoadingRegions">
								<SelectTrigger>
									<SelectValue placeholder="Select region" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem
										v-for="region in regions"
										:key="region.code"
										:value="region.name"
									>
										{{ region.name }}
									</SelectItem>
								</SelectContent>
							</Select>
							<InputError :message="errors.region" />
						</div>

						<div v-if="!isNcr" class="space-y-2">
							<Label>Province</Label>
							<Select
								v-model="selectedProvince"
								:disabled="!selectedRegion || isLoadingProvinces"
							>
								<SelectTrigger>
									<SelectValue placeholder="Select province" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem
										v-for="province in provinces"
										:key="province.code"
										:value="province.name"
									>
										{{ province.name }}
									</SelectItem>
								</SelectContent>
							</Select>
							<InputError :message="errors.province" />
						</div>

						<div class="space-y-2">
							<Label>City / Municipality</Label>
							<Select
								v-model="selectedCity"
								:disabled="!selectedRegion || isLoadingCities"
							>
								<SelectTrigger>
									<SelectValue placeholder="Select city / municipality" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem
										v-for="city in cities"
										:key="city.code"
										:value="city.name"
									>
										{{ city.name }}
									</SelectItem>
								</SelectContent>
							</Select>
							<InputError :message="errors.city" />
						</div>

						<div class="space-y-2">
							<Label>Barangay</Label>
							<Select
								v-model="selectedBarangay"
								:disabled="!selectedCity || isLoadingBarangays"
							>
								<SelectTrigger>
									<SelectValue placeholder="Select barangay" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem
										v-for="barangay in barangays"
										:key="barangay.code"
										:value="barangay.name"
									>
										{{ barangay.name }}
									</SelectItem>
								</SelectContent>
							</Select>
							<InputError :message="errors.barangay" />
						</div>

						<div class="space-y-2">
							<Label>Postal Code</Label>
							<Input v-model="formData.postal_code" />
							<InputError :message="errors.postal_code" />
						</div>

						<div class="space-y-2">
							<Label>Address</Label>
							<Input v-model="formData.address" />
							<InputError :message="errors.address" />
						</div>
					</div>
				</div>

				<div class="my-4 border-t" />

				<!-- Supporting Documents -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">Supporting Documents</h2>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div class="space-y-2">
							<Label>DTI Certificate</Label>
							<input
								type="file"
								@change="handleDTIChange"
								accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
								class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
							/>
							<InputError :message="errors.dti_registration_attachment" />
							<p
								v-if="selectedFiles.dti_registration_attachment"
								class="text-sm text-green-600"
							>
								✓ {{ selectedFiles.dti_registration_attachment.name }}
							</p>
						</div>
						<div class="space-y-2">
							<Label>Mayor Permit</Label>
							<input
								type="file"
								@change="handleMayorChange"
								accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
								class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
							/>
							<InputError :message="errors.mayor_permit_attachment" />
							<p
								v-if="selectedFiles.mayor_permit_attachment"
								class="text-sm text-green-600"
							>
								✓ {{ selectedFiles.mayor_permit_attachment.name }}
							</p>
						</div>
						<div class="space-y-2">
							<Label>Proof of Capital</Label>
							<input
								type="file"
								@change="handleProofChange"
								accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
								class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
							/>
							<InputError :message="errors.proof_agreement_attachment" />
							<p
								v-if="selectedFiles.proof_agreement_attachment"
								class="text-sm text-green-600"
							>
								✓ {{ selectedFiles.proof_agreement_attachment.name }}
							</p>
						</div>
					</div>
				</div>

				<!-- Buttons -->
				<div class="flex justify-end gap-4">
					<Button type="button" variant="outline" @click="reset">Reset</Button>
					<Button type="submit" :disabled="processing || disableSubmit">
						{{ processing ? 'Saving...' : 'Update Franchise' }}
					</Button>
				</div>
			</form>
		</div>
	</AppLayout>
</template>
