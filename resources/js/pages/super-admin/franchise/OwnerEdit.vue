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
	owner: {
		id: number;
		valid_id_type: string;
		valid_id_number: string;
		front_valid_id_picture: string | null;
		back_valid_id_picture: string | null;
		status: {
			id: number;
			name: string;
		} | null;
		user: {
			id: number;
			username: string;
			name: string;
			email: string;
			phone: string;
			address: string;
			region: string;
			province: string;
			city: string;
			barangay: string;
			postal_code: string;
		} | null;
	};
	franchises: {
		id: number;
		name: string;
		email: string;
	}[];
	idTypes: { value: string; label: string }[];
}>();

const breadcrumbs = [
	{ title: 'Franchise Management', href: superAdmin.dashboard().url },
	{ title: 'Edit Owner' },
];

const formData = ref({
	// User info
	username: props.owner?.user?.username || '',
	name: props.owner?.user?.name || '',
	email: props.owner?.user?.email || '',
	phone: props.owner?.user?.phone || '',
	
	// Address
	region: props.owner?.user?.region || '',
	province: props.owner?.user?.province || '',
	city: props.owner?.user?.city || '',
	barangay: props.owner?.user?.barangay || '',
	postal_code: props.owner?.user?.postal_code || '',
	address: props.owner?.user?.address || '',
	
	// Owner specific
	valid_id_type: props.owner?.valid_id_type || '',
	valid_id_number: props.owner?.valid_id_number || '',
});

const selectedFiles = ref<{
	front_valid_id_picture: File | null;
	back_valid_id_picture: File | null;
}>({
	front_valid_id_picture: null,
	back_valid_id_picture: null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const handleFrontIdChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files.length > 0) {
		selectedFiles.value.front_valid_id_picture = target.files[0];
	}
};

const handleBackIdChange = (event: Event) => {
	const target = event.target as HTMLInputElement;
	if (target.files && target.files.length > 0) {
		selectedFiles.value.back_valid_id_picture = target.files[0];
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

selectedRegion.value = props.owner?.user?.region || '';
selectedProvince.value = props.owner?.user?.province || '';
selectedCity.value = props.owner?.user?.city || '';
selectedBarangay.value = props.owner?.user?.barangay || '';

watch(selectedRegion, (v) => (formData.value.region = v));
watch(selectedProvince, (v) => (formData.value.province = v));
watch(selectedCity, (v) => (formData.value.city = v));
watch(selectedBarangay, (v) => (formData.value.barangay = v));

const disableSubmit = computed(() => {
	return !(
		formData.value.username &&
		formData.value.name &&
		formData.value.email &&
		formData.value.phone &&
		formData.value.region &&
		formData.value.city &&
		formData.value.barangay &&
		formData.value.postal_code &&
		formData.value.address &&
		formData.value.valid_id_type &&
		formData.value.valid_id_number
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

	if (selectedFiles.value.front_valid_id_picture) {
		data.append('front_valid_id_picture', selectedFiles.value.front_valid_id_picture);
	}
	if (selectedFiles.value.back_valid_id_picture) {
		data.append('back_valid_id_picture', selectedFiles.value.back_valid_id_picture);
	}

	router.post(superAdmin.owner.update(props.owner.id).url, data, {
		preserveState: true,
		onSuccess: () => {
			toast.success('Owner updated successfully!');
			processing.value = false;
			selectedFiles.value = {
				front_valid_id_picture: null,
				back_valid_id_picture: null,
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
		username: props.owner?.user?.username || '',
		name: props.owner?.user?.name || '',
		email: props.owner?.user?.email || '',
		phone: props.owner?.user?.phone || '',
		region: props.owner?.user?.region || '',
		province: props.owner?.user?.province || '',
		city: props.owner?.user?.city || '',
		barangay: props.owner?.user?.barangay || '',
		postal_code: props.owner?.user?.postal_code || '',
		address: props.owner?.user?.address || '',
		valid_id_type: props.owner?.valid_id_type || '',
		valid_id_number: props.owner?.valid_id_number || '',
	};
	selectedFiles.value = {
		front_valid_id_picture: null,
		back_valid_id_picture: null,
	};
};
</script>

<template>
	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="max-w-full px-6 py-3">
			<h2 class="font-mono text-2xl font-bold">
				Edit Owner
				<span v-if="props.owner?.user" class="font-normal">
					— {{ props.owner.user.name }}
					<span class="text-sm text-gray-500">
						({{ props.owner.user.email }})
					</span>
				</span>
				<span v-if="props.franchises && props.franchises.length > 0" class="block mt-1 text-base font-normal text-gray-600">
					Franchises: {{ props.franchises.map(f => f.name).join(', ') }}
				</span>
			</h2>
		</div>

		<div class="mx-6 mb-6 max-w-full rounded-xl border p-6 shadow-sm">
			<form @submit.prevent="submit" class="space-y-8">
				<!-- User Information -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">User Information</h2>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div class="space-y-2">
							<Label>Username</Label>
							<Input v-model="formData.username" />
							<InputError :message="errors.username" />
						</div>
						<div class="space-y-2">
							<Label>Name</Label>
							<Input v-model="formData.name" />
							<InputError :message="errors.name" />
						</div>
						<div class="space-y-2">
							<Label>Email</Label>
							<Input v-model="formData.email" type="email" />
							<InputError :message="errors.email" />
						</div>
						<div class="space-y-2">
							<Label>Phone</Label>
							<Input type="tel" v-model="formData.phone" />
							<InputError :message="errors.phone" />
						</div>
					</div>
				</div>

				<div class="my-4 border-t" />

				<!-- Address -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">Address</h2>
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

				<!-- Valid ID Information -->
				<div class="space-y-4">
					<h2 class="text-xl font-bold text-gray-800">Valid ID Information</h2>
					<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
						<div class="space-y-2">
							<Label>ID Type</Label>
							<Select v-model="formData.valid_id_type">
								<SelectTrigger>
									<SelectValue placeholder="Select ID type" />
								</SelectTrigger>
								<SelectContent>
									<SelectItem
										v-for="idType in props.idTypes"
										:key="idType.value"
										:value="idType.value"
									>
										{{ idType.label }}
									</SelectItem>
								</SelectContent>
							</Select>
							<InputError :message="errors.valid_id_type" />
						</div>
						<div class="space-y-2">
							<Label>ID Number</Label>
							<Input v-model="formData.valid_id_number" />
							<InputError :message="errors.valid_id_number" />
						</div>
						<div class="space-y-2">
							<Label>Front ID Picture</Label>
							<input
								type="file"
								@change="handleFrontIdChange"
								accept=".jpg,.jpeg,.png,.pdf"
								class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
							/>
							<InputError :message="errors.front_valid_id_picture" />
							<p
								v-if="selectedFiles.front_valid_id_picture"
								class="text-sm text-green-600"
							>
								✓ {{ selectedFiles.front_valid_id_picture.name }}
							</p>
							<p
								v-else-if="props.owner?.front_valid_id_picture"
								class="text-sm text-gray-500"
							>
								Current: {{ props.owner.front_valid_id_picture }}
							</p>
						</div>
						<div class="space-y-2">
							<Label>Back ID Picture</Label>
							<input
								type="file"
								@change="handleBackIdChange"
								accept=".jpg,.jpeg,.png,.pdf"
								class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
							/>
							<InputError :message="errors.back_valid_id_picture" />
							<p
								v-if="selectedFiles.back_valid_id_picture"
								class="text-sm text-green-600"
							>
								✓ {{ selectedFiles.back_valid_id_picture.name }}
							</p>
							<p
								v-else-if="props.owner?.back_valid_id_picture"
								class="text-sm text-gray-500"
							>
								Current: {{ props.owner.back_valid_id_picture }}
							</p>
						</div>
					</div>
				</div>

				<!-- Buttons -->
				<div class="flex justify-end gap-4">
					<Button type="button" variant="outline" @click="reset">Reset</Button>
					<Button type="submit" :disabled="processing || disableSubmit">
						{{ processing ? 'Saving...' : 'Update Owner' }}
					</Button>
				</div>
			</form>
		</div>
	</AppLayout>
</template>