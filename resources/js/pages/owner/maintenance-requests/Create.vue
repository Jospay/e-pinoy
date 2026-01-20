<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import { Button } from '@/components/ui/button';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import owner from '@/routes/owner';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { toast } from 'vue-sonner';

interface Vehicle {
	id: number;
	plate_number: string;
	brand: string;
	model: string;
}

interface Technicians {
	id: number;
	name: string;
	email: string;
	phone: string;
}

interface Inventories {
	id: number;
	code_no: string;
	name: string;
	category: string;
	specification: string;
	quantity: number;
	unit_price: number;
}

interface Props {
	vehicles: Vehicle[];
	technicians: Technicians[];
	inventories: Inventories[];
}

const { vehicles, technicians, inventories } = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: 'Maintenance Requests',
		href: owner.maintenanceRequests.index().url,
	},
	{
		title: 'Create Maintenance Requests',
		href: owner.maintenanceRequests.create().url,
	},
];

const form = useForm({
	vehicle: '',
	technician: '',
	inventory: '',
	description: '',
	maintenance_date: '',
	next_maintenance_date: '',
});

const disableSubmit = computed(() => {
	const areDetailsComplete =
		!!form.vehicle &&
		!!form.technician &&
		!!form.inventory &&
		!!form.description &&
		!!form.maintenance_date &&
		!!form.next_maintenance_date;

	return !areDetailsComplete;
});

const submit = () => {
	form.post(owner.maintenanceRequests.store().url, {
		onSuccess: () => {
			form.reset();
			toast.success('Maintenance Request created successfully!');
		},
		onError: (errors) => {
			const firstError = Object.values(errors)[0] as string;
			toast.error(firstError);
		},
	});
};
</script>

<template>
	<Head title="Maintenance Requests" />

	<AppLayout :breadcrumbs="breadcrumbs">
		<div class="m-6 max-w-full rounded-xl border p-6 shadow-sm">
			<h2 class="mb-6 font-mono text-2xl font-bold">
				Create New Maintenance Request
			</h2>

			<form @submit.prevent="submit" class="flex flex-col gap-6">
				<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
					<div class="grid gap-2">
						<Label for="vehicle">Vehicle</Label>
						<Select
							v-model="form.vehicle"
							@update:model-value="form.errors.vehicle = ''"
						>
							<SelectTrigger
								:class="{
									'border-red-500': form.errors.vehicle,
								}"
							>
								<SelectValue placeholder="Select Vehicle" />
							</SelectTrigger>

							<SelectContent>
								<div
									v-if="vehicles.length === 0"
									class="p-2 text-sm text-gray-500"
								>
									<p>No available vehicles found</p>
								</div>
								<SelectItem
									v-else
									v-for="vehicle in vehicles"
									:key="vehicle.id"
									:value="vehicle.id"
								>
									{{ vehicle.plate_number }} - {{ vehicle.brand }}
									{{ vehicle.model }}
								</SelectItem>
							</SelectContent>
						</Select>
						<InputError :message="form.errors.vehicle" />
					</div>

					<div class="grid gap-2">
						<Label for="technician">Technicians</Label>
						<Select
							v-model="form.technician"
							@update:model-value="form.errors.technician = ''"
						>
							<SelectTrigger
								:class="{
									'border-red-500': form.errors.technician,
								}"
							>
								<SelectValue placeholder="Select Technician" />
							</SelectTrigger>

							<SelectContent>
								<div
									v-if="technicians.length === 0"
									class="p-2 text-sm text-gray-500"
								>
									<p>No available technicians found</p>
								</div>
								<SelectItem
									v-else
									v-for="technician in technicians"
									:key="technician.id"
									:value="technician.id"
								>
									{{ technician.name }}
								</SelectItem>
							</SelectContent>
						</Select>
						<InputError :message="form.errors.technician" />
					</div>

					<div class="grid gap-2">
						<Label for="inventory">Inventories</Label>
						<Select
							v-model="form.inventory"
							@update:model-value="form.errors.inventory = ''"
						>
							<SelectTrigger
								:class="{
									'border-red-500': form.errors.inventory,
								}"
							>
								<SelectValue placeholder="Select Item from Inventories" />
							</SelectTrigger>

							<SelectContent>
								<div
									v-if="inventories.length === 0"
									class="p-2 text-sm text-gray-500"
								>
									<p>No available inventories found</p>
								</div>
								<SelectItem
									v-else
									v-for="inventory in inventories"
									:key="inventory.id"
									:value="inventory.id"
								>
									{{ inventory.name }} - {{ inventory.category }}
								</SelectItem>
							</SelectContent>
						</Select>
						<InputError :message="form.errors.inventory" />
					</div>
				</div>

				<div class="my-4 border-t" />

				<div class="grid gap-2">
					<Label>Description</Label>
					<Textarea
						v-model="form.description"
						placeholder="Define the operational area..."
						:class="{ 'border-red-500': form.errors.description }"
						@change="form.errors.description = ''"
					/>
					<InputError :message="form.errors.description" />
				</div>

				<div class="grid grid-cols-1 gap-6 md:grid-cols-2">
					<div class="grid gap-2">
						<Label>Maintenance Date</Label>
						<DatePicker
							v-model="form.maintenance_date"
							placeholder="Pick maintenance date"
							:class="{ 'border-red-500': form.errors.maintenance_date }"
							@update:model-value="form.errors.maintenance_date = ''"
						/>
						<InputError :message="form.errors.maintenance_date" />
					</div>

					<div class="grid gap-2">
						<Label>Next Maintenance Date</Label>
						<DatePicker
							v-model="form.next_maintenance_date"
							:min-date="form.maintenance_date"
							placeholder="Pick next maintenance date"
							:class="{ 'border-red-500': form.errors.next_maintenance_date }"
							@update:model-value="form.errors.next_maintenance_date = ''"
						/>
						<InputError :message="form.errors.next_maintenance_date" />
					</div>
				</div>

				<div class="flex justify-end gap-4">
					<Button type="button" variant="outline" @click="form.reset()"
						>Reset</Button
					>
					<Button type="submit" :disabled="form.processing || disableSubmit">
						{{ form.processing ? 'Saving...' : 'Create Maintenance Request' }}
					</Button>
				</div>
			</form>
		</div>
	</AppLayout>
</template>
