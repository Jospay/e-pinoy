<script setup lang="ts">
import DataTable from '@/components/DataTable.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useDetailsModal } from '@/composables/useDetailsModal';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { type ColumnDef } from '@tanstack/vue-table';
import {
  AlertCircleIcon,
  BanknoteArrowDownIcon,
  BanknoteArrowUpIcon,
  LandmarkIcon,
  MoreHorizontal,
  PlusIcon,
  UsersRoundIcon,
} from 'lucide-vue-next';
import { computed, h, onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const page = usePage<{
  flash?: {
    success?: string;
    error?: string;
  };
}>();

onMounted(() => {
  if (page.props.flash?.success) {
    toast.success(page.props.flash.success);
  }

  if (page.props.flash?.error) {
    toast.error(page.props.flash.error);
  }
});

interface FranchiseRow {
  id: number;
  name: string;
  email: string;
  phone: string;
  contract_attachment: string;
  status_name: string;
  owner_username: string;
  owner_id: number;
}

interface Stats {
  total_revenue: number;
  total_expenses: number;
  total_franchises: number;
  total_drivers: number;
}

const props = defineProps<{
  franchises: {
    data: FranchiseRow[];
  };
  stats: Stats;
}>();

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount);
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: superAdmin.dashboard().url,
  },
];

interface FranchiseModal {
  id: number;
  status: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  region: string;
  city: string;
  barangay: string;
  province?: string;
  postal_code: string;
  created_at: string;
  dti_registration_attachment?: string;
  mayor_permit_attachment?: string;
  proof_agreement_attachment?: string;
}
const franchiseDetails = computed(() => {
  const data = franchiseModal.data.value;
  if (!data) return [];

  return [
    { label: 'Name', value: data.name, type: 'text' },
    { label: 'Email', value: data.email, type: 'text' },
    { label: 'Phone', value: data.phone, type: 'text' },
    { label: 'Status', value: data.status, type: 'text' },
    { label: 'Region', value: data.region, type: 'text' },
    { label: 'Province', value: data.province, type: 'text' },
    { label: 'City', value: data.city, type: 'text' },
    { label: 'Barangay', value: data.barangay, type: 'text' },
    { label: 'Postal Code', value: data.postal_code, type: 'text' },
    { label: 'Address', value: data.address, type: 'text' },
    { label: 'Registered At', value: data.created_at, type: 'text' },
    {
      label: 'DTI Registration',
      value: data.dti_registration_attachment,
      type: 'link',
    },
    {
      label: "Mayor's Permit",
      value: data.mayor_permit_attachment,
      type: 'link',
    },
    {
      label: 'Proof of Agreement',
      value: data.proof_agreement_attachment,
      type: 'link',
    },
  ].filter((item) => item.value);
});

interface OwnerModal {
  id: number;
  status: string;
  username: string;
  name: string;
  email: string;
  phone: string;
  address: string;
  region: string;
  city: string;
  barangay: string;
  province?: string;
  postal_code: string;
  valid_id_type: string;
  valid_id_number: string;
  front_valid_id_picture?: string;
  back_valid_id_picture?: string;
  created_at?: string;
}
const ownerDetails = computed(() => {
  const data = ownerModal.data.value;
  if (!data) return [];

  return [
    { label: 'Username', value: data.username, type: 'text' },
    { label: 'Name', value: data.name, type: 'text' },
    { label: 'Email', value: data.email, type: 'text' },
    { label: 'Phone', value: data.phone, type: 'text' },
    { label: 'Status', value: data.status, type: 'text' },
    { label: 'Region', value: data.region, type: 'text' },
    { label: 'Province', value: data.province, type: 'text' },
    { label: 'City', value: data.city, type: 'text' },
    { label: 'Barangay', value: data.barangay, type: 'text' },
    { label: 'Postal Code', value: data.postal_code, type: 'text' },
    { label: 'Address', value: data.address, type: 'text' },
    { label: 'Registered At', value: data.created_at, type: 'text' },
    { label: 'Valid ID Type', value: data.valid_id_type, type: 'text' },
    { label: 'Valid ID Number', value: data.valid_id_number, type: 'text' },
    {
      label: 'Front Valid ID Picture',
      value: data.front_valid_id_picture,
      type: 'link',
    },
    {
      label: 'Back Valid ID Picture',
      value: data.back_valid_id_picture,
      type: 'link',
    },
  ].filter((item) => item.value);
});

// --- Modal State ---
const franchiseModal = useDetailsModal<FranchiseModal>({
  baseUrl: '/super-admin/franchise',
});

const ownerModal = useDetailsModal<OwnerModal>({
  baseUrl: '/super-admin/owner',
});

// --- Accept Modal State ---
const isAcceptModalOpen = ref(false);
const selectedFranchise = ref<Partial<FranchiseRow>>({});
const isAcceptingFranchise = ref(false);

const openAcceptModal = (franchise: FranchiseRow) => {
  selectedFranchise.value = franchise;
  isAcceptModalOpen.value = true;
};

const handleAcceptFranchise = () => {
  if (!selectedFranchise.value?.id) return;
  isAcceptingFranchise.value = true;
  router.patch(
    superAdmin.franchise.accept(selectedFranchise.value.id).url,
    {},
    {
      onSuccess: () => {
        isAcceptModalOpen.value = false;
        toast.success('Franchise accepted successfully!');
      },
      onFinish: () => {
        setTimeout(() => {
          isAcceptingFranchise.value = false;
        }, 500);
      },
    },
  );
};

// --- Delete Modal State ---
const isDeleteModalOpen = ref(false);
const isDeletingFranchise = ref(false);

const openDeleteModal = (franchise: FranchiseRow) => {
  selectedFranchise.value = franchise;
  isDeleteModalOpen.value = true;
};

const handleDeleteFranchise = () => {
  if (!selectedFranchise.value?.id) return;

  isDeletingFranchise.value = true;

  router.delete(superAdmin.franchise.destroy(selectedFranchise.value.id).url, {
    preserveScroll: true,

    onSuccess: () => {
      isDeleteModalOpen.value = false;
      selectedFranchise.value = {};
      toast.success('Franchise deleted successfully!');
    },

    onFinish: () => {
      isDeletingFranchise.value = false;
    },
  });
};

// const isOtpModalOpen = ref(false);
// const otpCode = ref('');
// const isVerifyingOtp = ref(false);
// const isResending = ref(false);
// const resendCooldown = ref(0);

// let resendInterval = null;

// const handleDeleteFranchise = async () => {
//   if (!selectedFranchise.value?.id) return;

//   isDeletingFranchise.value = true;

//   try {
//     // Request OTP to be sent
//     await axios.post('/super-admin/franchises/send-action-code', { action: 'delete_franchise', });

//     // Open OTP modal
//     isOtpModalOpen.value = true;
//     otpCode.value = '';
//     isDeleteModalOpen.value = false;

//   } catch (e) {
//     toast.error('Failed to send verification code.');
//     isDeleteModalOpen.value = false;
//     selectedFranchise.value = {};
//   } finally {
//     isDeletingFranchise.value = false;
//     isDeleteModalOpen.value = false;
//   }
// };

// // Function to verify OTP
// const handleVerifyOtp = () => {
//   if (otpCode.value.length !== 6) return;

//   isVerifyingOtp.value = true;

//   router.delete(
//     superAdmin.franchise.destroy(selectedFranchise.value.id).url,
//     {
//       data: {
//         code: otpCode.value,
//       },
//       preserveScroll: true,

//       onSuccess: () => {
//         isOtpModalOpen.value = false;
//         isDeleteModalOpen.value = false;
//         selectedFranchise.value = {};
//         otpCode.value = '';

//         toast.success('Franchise deleted successfully!');
//       },

//       onError: (errors) => {
//         toast.error(
//           errors?.message || 'Invalid or expired verification code.'
//         );
//       },

//       onFinish: () => {
//         isVerifyingOtp.value = false;
//       },
//     }
//   );
// };

// Function to resend OTP
// const handleResendOtp = async () => {
//   if (resendCooldown.value > 0) return;

//   isResending.value = true;
//   try {
//     await axios.post('/super-admin/franchises/send-action-code', {
//       franchise_id: selectedFranchise.value.id,
//       action: 'delete_franchise',
//     });
//     toast.success('OTP resent successfully!');

//     // Start cooldown
//     resendCooldown.value = 30;
//     resendInterval = setInterval(() => {
//       resendCooldown.value -= 1;
//       if (resendCooldown.value <= 0) clearInterval(resendInterval);
//     }, 1000);
//   } catch (error) {
//     toast.error('Failed to resend OTP.');
//   } finally {
//     isResending.value = false;
//   }
// };

// Upload Contract Modal State
const uploadContractModal = ref(false);
const selectedFranchiseId = ref<number | null>(null);
const contractFile = ref<File | null>(null);

function openUploadContractModal(franchise: { id: number }) {
  selectedFranchiseId.value = franchise.id;
  uploadContractModal.value = true;
}

function handleContractFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  contractFile.value = target.files?.[0] || null;
}

const isUploadingContract = ref(false);

function submitContract() {
  if (!selectedFranchiseId.value || !contractFile.value) return;

  isUploadingContract.value = true;

  const formData = new FormData();
  formData.append('contract_attachment', contractFile.value);

  router.post(
    superAdmin.franchise.uploadContract(selectedFranchiseId.value).url,
    formData,
    {
      forceFormData: true,
      onSuccess: () => {
        uploadContractModal.value = false;
        contractFile.value = null;
        toast.success('Contract uploaded successfully!');
      },
      onFinish: () => {
        setTimeout(() => {
          isUploadingContract.value = false;
        }, 500);
      },
    },
  );
}

const createFranchise = () => {
  router.get(superAdmin.franchise.create().url);
};

const editFranchise = (franchiseId: number) => {
  router.get(superAdmin.franchise.edit(franchiseId).url);
};

const editOwner = (franchiseId: number) => {
  router.get(superAdmin.owner.edit(franchiseId).url);
};

const franchiseColumns: ColumnDef<FranchiseRow>[] = [
  {
    accessorKey: 'name',
    header: () => h('div', { class: 'text-center' }, 'Franchise'),
    cell: ({ row }) => h('div', { class: 'text-center' }, row.getValue('name')),
  },
  {
    accessorKey: 'owner_username',
    header: () => h('div', { class: 'text-center' }, 'Owner'),
    cell: ({ row }) =>
      h(
        'div',
        { class: 'text-center' },
        row.getValue('owner_username') || 'N/A',
      ),
  },
  {
    accessorKey: 'email',
    header: () => h('div', { class: 'text-center' }, 'Email'),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, row.getValue('email')),
  },
  {
    accessorKey: 'phone',
    header: () => h('div', { class: 'text-center' }, 'Phone'),
    cell: ({ row }) =>
      h('div', { class: 'text-center' }, row.getValue('phone')),
  },
  {
    accessorKey: 'contract_attachment',
    header: () => h('div', { class: 'text-center' }, 'Contract'),
    cell: ({ row }) => {
      const contract = row.original.contract_attachment;

      if (!contract) {
        return h(
          'div',
          { class: 'text-center text-gray-400 italic' },
          'Not yet available',
        );
      }

      return h(
        'div',
        { class: 'flex justify-center' },
        h(
          'a',
          {
            href: contract,
            target: '_blank',
            rel: 'noopener',
            class: 'text-blue-500 hover:underline',
          },
          'View Contract',
        ),
      );
    },
  },
  {
    accessorKey: 'status_name',
    header: () => h('div', { class: 'text-center' }, 'Status'),
    cell: ({ row }) => {
      const status = row.getValue('status_name') as string;
      const badgeClass = {
        'bg-blue-500 hover:bg-blue-600': status === 'active',
        'bg-amber-500 hover:bg-amber-600': status === 'pending',
      };
      return h('div', { class: 'text-center' }, [
        h(Badge, { class: [badgeClass, 'text-white'] }, () => status || 'N/A'),
      ]);
    },
  },
  {
    id: 'actions',
    header: () => h('div', { class: 'text-center' }, 'Actions'),
    cell: ({ row }) => {
      const franchise = row.original;

      return h('div', { class: 'relative text-center' }, [
        h(DropdownMenu, null, () => [
          h(
            DropdownMenuTrigger,
            { asChild: true, class: 'cursor-pointer' },
            () =>
              h(Button, { variant: 'ghost', class: 'h-8 w-8 p-0' }, () => [
                h('span', { class: 'sr-only' }, 'Open menu'),
                h(MoreHorizontal, { class: 'h-4 w-4' }),
              ]),
          ),
          h(DropdownMenuContent, { align: 'end', class: 'border-2' }, () => [
            h(DropdownMenuLabel, null, () => 'Actions'),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer',
                onClick: () => franchiseModal.open(franchise.id),
              },
              () => 'View Franchise Details',
            ),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer',
                onClick: () => ownerModal.open(franchise.owner_id),
              },
              () => 'View Owner Details',
            ),
            franchise.status_name === 'pending'
              ? [
                  h(DropdownMenuSeparator),
                  h(
                    DropdownMenuItem,
                    {
                      class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                      onClick: () => openAcceptModal(franchise),
                    },
                    () => 'Accept Franchise',
                  ),
                ]
              : null,

            franchise.contract_attachment === null
              ? [
                  // h(DropdownMenuSeparator),
                  h(
                    DropdownMenuItem,
                    {
                      class: 'cursor-pointer text-blue-500 focus:text-blue-600',
                      onClick: () => openUploadContractModal(franchise),
                    },
                    () => 'Upload Contract',
                  ),
                ]
              : null,

            h(DropdownMenuSeparator),
            h(
              DropdownMenuItem,
              {
                class: 'cursor-pointer text-red-500 focus:text-red-600',
                onClick: () => openDeleteModal(franchise),
              },
              () => 'Delete Franchise',
            ),
          ]),
        ]),
      ]);
    },
  },
];

const selectedStatus = ref<
  'all' | 'active' | 'pending' | 'available' | 'maintenance'
>('all');

const filteredFranchises = computed(() => {
  if (selectedStatus.value === 'all') return props.franchises.data;

  return props.franchises.data.filter(
    (franchise) => franchise.status_name === selectedStatus.value,
  );
});
</script>

<template>
  <Head title="Super Admin Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div
      class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="font-mono text-lg"
              >Total Revenue
              <span class="font-mono text-sm text-muted-foreground"
                >(Today)</span
              >
            </CardTitle>
            <BanknoteArrowUpIcon class="h-6 w-6 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="font-mono text-2xl font-semibold">
              {{ formatCurrency(stats.total_revenue) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="font-mono text-lg"
              >Total Expense
              <span class="font-mono text-sm text-muted-foreground"
                >(Today)</span
              >
            </CardTitle>
            <BanknoteArrowDownIcon class="h-6 w-6 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="font-mono text-2xl font-semibold">
              {{ formatCurrency(stats.total_expenses) }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="font-mono text-lg">Total Franchise </CardTitle>
            <LandmarkIcon class="h-6 w-6 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="font-mono text-2xl font-semibold">
              {{ stats.total_franchises }}
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader
            class="flex flex-row items-center justify-between space-y-0 pb-2"
          >
            <CardTitle class="font-mono text-lg">Total Driver </CardTitle>
            <UsersRoundIcon class="h-6 w-6 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="font-mono text-2xl font-semibold">
              {{ stats.total_drivers }}
            </div>
          </CardContent>
        </Card>
      </div>
      <div
        class="relative rounded-xl border border-sidebar-border/70 p-4 md:min-h-min dark:border-sidebar-border"
      >
        <h2 class="mb-4 font-mono text-xl font-semibold">
          Franchise Management
        </h2>
        <DataTable
          :columns="franchiseColumns"
          :data="filteredFranchises"
          search-placeholder="Search franchises..."
        >
          <template #custom-actions>
            <div class="mb-4 flex items-center gap-4">
              <Select v-model="selectedStatus">
                <SelectTrigger class="w-48">
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All</SelectItem>
                  <SelectItem value="active">Active</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                </SelectContent>
              </Select>

              <!-- You can keep Add Franchise button here -->
              <Button class="ms-auto" @click="createFranchise">
                <PlusIcon /> Add Franchise
              </Button>
            </div>
          </template>
        </DataTable>
      </div>
    </div>
  </AppLayout>

  <Dialog v-model:open="isAcceptModalOpen">
    <DialogContent class="max-w-md font-mono">
      <DialogHeader>
        <DialogTitle class="text-2xl">Accept Franchise?</DialogTitle>
        <DialogDescription class="text-md font-semibold">
          Are you sure you want to accept the franchise
          <strong class="text-blue-500">{{ selectedFranchise.name }}</strong
          >? This will also activate the owner's account.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline" @click="isAcceptModalOpen = false"
          >Cancel</Button
        >
        <Button
          variant="default"
          @click="handleAcceptFranchise"
          :disabled="isAcceptingFranchise"
        >
          {{ isAcceptingFranchise ? 'Accepting...' : 'Yes, Accept' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="isDeleteModalOpen">
    <DialogContent class="max-w-md font-mono">
      <DialogHeader>
        <DialogTitle class="text-2xl">Delete Franchise?</DialogTitle>
        <DialogDescription class="text-md font-semibold">
          Are you sure you want to delete the franchise
          <strong class="text-red-500">{{ selectedFranchise.name }}</strong
          >? This will also delete the owner's account.
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <Button variant="outline" @click="isDeleteModalOpen = false"
          >Cancel</Button
        >
        <Button
          variant="destructive"
          @click="handleDeleteFranchise"
          :disabled="isDeletingFranchise"
        >
          {{ isDeletingFranchise ? 'Deleting...' : 'Yes, Delete' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- <Dialog v-model:open="isOtpModalOpen">
      <DialogContent class="max-w-md font-mono">
        <DialogHeader>
          <DialogTitle class="text-2xl">Enter Verification Code</DialogTitle>
          <DialogDescription class="text-md font-semibold">
            A 6-digit verification code has been sent to the franchise owner's phone.
            Enter the code below to confirm deleting
            <strong class="text-red-500">{{ selectedFranchise.name }}</strong>.
          </DialogDescription>
        </DialogHeader>

        <div class="mt-4">
          <input
            type="text"
            v-model="otpCode"
            maxlength="6"
            placeholder="Enter 6-digit code"
            class="w-full border rounded p-2 text-center"
          />
        </div>

        <div class="mt-2 flex justify-between items-center text-sm">
          <span>
            Didn't receive it?
          </span>
          <Button
            variant="outline"
            size="sm"
            @click="handleResendOtp"
            :disabled="isResending || resendCooldown > 0"
          >
            {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend' }}
          </Button>
        </div>

        <DialogFooter class="mt-4">
          <Button variant="outline" @click="isOtpModalOpen = false">Cancel</Button>
          <Button
            variant="destructive"
            @click="handleVerifyOtp"
            :disabled="isVerifyingOtp || otpCode.length !== 6"
          >
            {{ isVerifyingOtp ? 'Verifying...' : 'Confirm Delete' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog> -->


  <Dialog v-model:open="franchiseModal.isOpen.value">
    <DialogContent class="max-w-2xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Franchise Details</DialogTitle>
      </DialogHeader>
      <DialogDescription>
        <div
          v-if="franchiseModal.isLoading.value"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div
          v-else-if="franchiseDetails.length > 0"
          class="grid grid-cols-2 gap-4"
        >
          <template v-for="item in franchiseDetails" :key="item.label">
            <div class="font-medium">{{ item.label }}:</div>

            <div v-if="item.type === 'link'">
              <a
                :href="item.value"
                target="_blank"
                class="text-blue-500 hover:underline"
                >View</a
              >
            </div>

            <div v-else>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="franchiseModal.isError.value">
          <Alert
            variant="destructive"
            class="border-2 border-red-500 shadow-lg"
          >
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load franchise details.
            </AlertDescription>
          </Alert>
        </div>
      </DialogDescription>
      <DialogFooter class="mt-5 flex justify-between">
        <!-- Edit button -->
        <Button
          variant="default"
          v-if="franchiseModal.data.value"
          @click="editFranchise(franchiseModal.data.value.id)"
        >
          Edit Franchise
        </Button>

        <!-- Close button -->
        <Button variant="outline" @click="franchiseModal.close"> Close </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="ownerModal.isOpen.value">
    <DialogContent class="max-w-3xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>Owner Details</DialogTitle>
      </DialogHeader>
      <DialogDescription>
        <div v-if="ownerModal.isLoading.value" class="grid grid-cols-2 gap-4">
          <template v-for="item in 10" :key="item">
            <Skeleton class="h-5 w-24" />
            <Skeleton class="h-5 w-3/4" />
          </template>
        </div>

        <div v-else-if="ownerDetails.length > 0" class="grid grid-cols-2 gap-4">
          <template v-for="item in ownerDetails" :key="item.label">
            <div class="font-medium">{{ item.label }}:</div>

            <div v-if="item.type === 'link'">
              <a
                :href="item.value"
                target="_blank"
                class="text-blue-500 hover:underline"
                >View</a
              >
            </div>

            <div v-else>
              {{ item.value }}
            </div>
          </template>
        </div>

        <div v-else-if="ownerModal.isError.value">
          <Alert
            variant="destructive"
            class="border-2 border-red-500 shadow-lg"
          >
            <AlertCircleIcon class="h-4 w-4" />
            <AlertTitle class="font-bold">Error</AlertTitle>
            <AlertDescription class="font-semibold">
              Failed to load owner details.
            </AlertDescription>
          </Alert>
        </div>
      </DialogDescription>

      <DialogFooter class="mt-5 flex justify-between">
        <!-- Edit button -->
        <Button
          variant="default"
          v-if="ownerModal.data.value"
          @click="editOwner(ownerModal.data.value.id)"
        >
          Edit Owner
        </Button>

        <Button variant="outline" @click="ownerModal.close">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <Dialog v-model:open="uploadContractModal">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Upload Contract</DialogTitle>
        <DialogDescription>
          Upload the signed contract for this franchise
        </DialogDescription>
      </DialogHeader>

      <div class="mt-4 grid w-full gap-4">
        <div class="grid w-full items-center gap-1.5">
          <Label for="contract">Contract File</Label>
          <Input id="contract" type="file" @change="handleContractFileChange" />
        </div>

        <div class="mt-4 flex justify-end gap-2">
          <Button variant="outline" @click="isOpen = false">Cancel</Button>
          <Button @click="submitContract" :disabled="!contractFile"
            >Upload</Button
          >
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>
