<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import superAdmin from '@/routes/super-admin';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Eye, Power, Search, Trash } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface UserType {
  id: number;
  name: string;
}

interface Feedback {
  id: number;
  name: string;
  avatar: string | null;
  rating: number;
  is_active: boolean;
  description: string;
  user_type: UserType | null;
  created_at: string;
}

interface FeedbackPaginator {
  current_page: number;
  data: Feedback[];
  first_page_url: string | null;
  from: number | null;
  last_page: number;
  last_page_url: string | null;
  links: Array<{ url: string | null; label: string; active: boolean }>;
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

interface Props {
  feedbacks: FeedbackPaginator;
}

const { feedbacks } = defineProps<Props>();
const paginator = ref(feedbacks);

// -------------------------
// Watcher: update paginator when props change
// -------------------------
watch(
  () => feedbacks,
  (newFeedbacks) => {
    paginator.value = newFeedbacks;
  },
  { deep: true },
);

// -------------------------
// Breadcrumbs
// -------------------------
const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Feedback Management', href: superAdmin.feedbacks.index().url },
];

// -------------------------
// Filters / Search
// -------------------------
const globalFilter = ref('');

// -------------------------
// Dialog
// -------------------------
const selectedFeedback = ref<Feedback | null>(null);
const showDialog = ref(false);

const openDialog = (feedback: Feedback) => {
  selectedFeedback.value = feedback;
  showDialog.value = true;
};

// -------------------------
// Computed: Filtered data for client-side search
// -------------------------
const filteredData = computed(() => {
  if (!globalFilter.value) return paginator.value.data;
  const search = globalFilter.value.toLowerCase();
  return paginator.value.data.filter((item) =>
    Object.values(item)
      .filter((v) => v !== null && v !== undefined)
      .some((v) => v.toString().toLowerCase().includes(search)),
  );
});

// -------------------------
// Pagination links without Previous/Next
// -------------------------
const paginationLinks = computed(() =>
  paginator.value.links.filter(
    (link) => link.label !== 'Previous' && link.label !== 'Next',
  ),
);

// -------------------------
// Pagination / server-side navigation
// -------------------------
const goToPage = (url: string | null) => {
  if (!url) return;
  router.get(
    url,
    {
      search: globalFilter.value || undefined,
    },
    { preserveState: true, preserveScroll: true },
  );
};

// -------------------------
// Toggle feedback status
// -------------------------
const updatingId = ref<number | null>(null);

const toggleFeedbackStatus = (feedback: Feedback) => {
  updatingId.value = feedback.id;
  const toastId = toast.loading('Updating feedback status...');

  router.patch(
    `/super-admin/feedback/${feedback.id}/toggle`,
    {},
    {
      onSuccess: () => {
        toast.success('Feedback status updated!', { id: toastId });
        feedback.is_active = !feedback.is_active;
      },
      onError: () =>
        toast.error('Failed to update feedback status.', { id: toastId }),
      onFinish: () => (updatingId.value = null),
    },
  );
};

// -------------------------
// Delete feedback
// -------------------------
const showDeleteDialog = ref(false);
const feedbackToDelete = ref<Feedback | null>(null);
const deletingId = ref<number | null>(null);

const confirmDelete = (feedback: Feedback) => {
  feedbackToDelete.value = feedback;
  showDeleteDialog.value = true;
};

const deleteFeedback = () => {
  if (!feedbackToDelete.value) return;

  const toastId = toast.loading('Deleting feedback...');
  const idToRemove = feedbackToDelete.value.id;

  router.delete(`/super-admin/feedbacks/${idToRemove}`, {
    onSuccess: () =>
      toast.success('Feedback deleted!', { id: toastId }),
    onError: () => toast.error('Failed to delete feedback.', { id: toastId }),
    onFinish: () => {
      showDeleteDialog.value = null;
      feedbackToDelete.value = false;
    },
  });
};
</script>

<template>
  <Head title="Feedback Management" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div>
        <h1 class="mb-1 text-3xl font-bold">Active Feedbacks</h1>
        <p class="text-gray-600">
          Manage all active feedbacks submitted by users.
        </p>
      </div>

      <!-- Search -->
      <div class="flex items-center gap-4">
        <div class="relative flex-1">
          <Search
            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
          />
          <input
            v-model="globalFilter"
            placeholder="Search feedbacks..."
            class="w-full rounded-md border px-10 py-2"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-lg border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Avatar</TableHead>
              <TableHead>Rating</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>User Type</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Created At</TableHead>
              <TableHead>Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-if="filteredData.length === 0">
              <TableCell colspan="7" class="py-6 text-center text-gray-500">
                No results found.
              </TableCell>
            </TableRow>

            <TableRow v-for="fb in filteredData" :key="fb.id">
              <TableCell>{{ fb.name }}</TableCell>
              <TableCell>
                <img
                  v-if="fb.avatar"
                  :src="fb.avatar"
                  class="h-8 w-8 rounded-full"
                />
                <span v-else>—</span>
              </TableCell>
              <TableCell>{{ fb.rating }}</TableCell>
              <TableCell class="max-w-xs truncate">{{
                fb.description
              }}</TableCell>
              <TableCell class="capitalize">{{
                fb.user_type?.name ?? '—'
              }}</TableCell>
              <TableCell>
                <Badge :variant="fb.is_active ? 'default' : 'destructive'">
                  {{ fb.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </TableCell>
              <TableCell>{{ fb.created_at }}</TableCell>
              <TableCell class="flex gap-2">
                <!-- View button -->
                <Button size="sm" variant="outline" @click="openDialog(fb)">
                  <Eye />
                </Button>

                <!-- Toggle status button with icon and tooltip -->
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Button
                        size="sm"
                        :variant="fb.is_active ? 'destructive' : 'default'"
                        @click="toggleFeedbackStatus(fb)"
                        :disabled="updatingId === fb.id"
                      >
                        <Power class="h-4 w-4" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="top">
                      {{ fb.is_active ? 'Deactivate' : 'Activate' }}
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>

                <!-- Delete button -->
                <Button
                  size="sm"
                  variant="destructive"
                  @click="confirmDelete(fb)"
                  :disabled="deletingId === fb.id"
                >
                  <Trash />
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between pt-4">
        <span class="text-sm text-gray-600">
          Showing {{ paginator.from || 0 }} to {{ paginator.to || 0 }} of
          {{ paginator.total }} entries
        </span>

        <Pagination
          :items-per-page="paginator.per_page"
          :total="paginator.total"
          :default-page="paginator.current_page"
        >
          <PaginationContent>
            <PaginationPrevious
              :disabled="!paginator.prev_page_url"
              @click="goToPage(paginator.prev_page_url)"
            />
            <template v-for="link in paginationLinks" :key="link.label">
              <PaginationItem
                v-if="!isNaN(Number(link.label))"
                :value="Number(link.label)"
              >
                <Button
                  variant="ghost"
                  size="sm"
                  :class="{ 'bg-gray-100': link.active }"
                  :disabled="!link.url"
                  @click="goToPage(link.url)"
                >
                  {{ link.label }}
                </Button>
              </PaginationItem>
              <PaginationEllipsis v-else-if="link.label.includes('...')" />
            </template>
            <PaginationNext
              :disabled="!paginator.next_page_url"
              @click="goToPage(paginator.next_page_url)"
            />
          </PaginationContent>
        </Pagination>
      </div>

      <!-- Feedback Dialog -->
      <Dialog v-model:open="showDialog">
        <DialogContent class="max-w-3xl overflow-y-auto">
          <DialogHeader>
            <DialogTitle>Feedback Details</DialogTitle>
          </DialogHeader>
          <DialogDescription>
            <div v-if="selectedFeedback" class="grid grid-cols-2 gap-4">
              <div class="font-medium">Name:</div>
              <div>{{ selectedFeedback.name }}</div>

              <div class="font-medium">Avatar:</div>
              <div>
                <img
                  v-if="selectedFeedback.avatar"
                  :src="selectedFeedback.avatar"
                  class="h-12 w-12 rounded-full"
                />
                <span v-else>—</span>
              </div>

              <div class="font-medium">Rating:</div>
              <div>{{ selectedFeedback.rating }}</div>

              <div class="font-medium">Description:</div>
              <div>{{ selectedFeedback.description }}</div>

              <div class="font-medium">User Type:</div>
              <div>{{ selectedFeedback.user_type?.name ?? '—' }}</div>

              <div class="font-medium">Status:</div>
              <div>
                <Badge
                  :variant="
                    selectedFeedback.is_active ? 'default' : 'destructive'
                  "
                >
                  {{ selectedFeedback.is_active ? 'Active' : 'Inactive' }}
                </Badge>
              </div>

              <div class="font-medium">Created At:</div>
              <div>{{ selectedFeedback.created_at }}</div>
            </div>
          </DialogDescription>
          <DialogFooter>
            <Button variant="outline" @click="showDialog = false">Close</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <Dialog v-model:open="showDeleteDialog">
        <DialogContent class="max-w-md">
          <DialogHeader>
            <DialogTitle>Delete Feedback</DialogTitle>
          </DialogHeader>
          <DialogDescription>
            Are you sure you want to delete
            <span class="font-semibold">{{ feedbackToDelete?.name }}</span
            >? This action cannot be undone.
          </DialogDescription>
          <DialogFooter class="flex gap-2">
            <Button variant="outline" @click="showDeleteDialog = false">
              Cancel
            </Button>
            <Button
              variant="destructive"
              @click="deleteFeedback"
              :disabled="deletingId === feedbackToDelete?.id"
            >
              Delete
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
