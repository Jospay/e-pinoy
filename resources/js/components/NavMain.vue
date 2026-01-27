<script setup lang="ts">
import { useSidebar } from '@/components/ui/sidebar';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { urlIsActive } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Lock } from 'lucide-vue-next'; // Import Lock icon

const props = defineProps<{ items: NavItem[] }>();
const { state } = useSidebar();

const page = usePage();

const groupedItems = computed(() => {
  const groups: Record<string, NavItem[]> = {};
  for (const item of props.items) {
    const group = item.group || 'Other';
    if (!groups[group]) groups[group] = [];
    groups[group].push(item);
  }
  return groups;
});
</script>

<template>
  <div>
    <template v-for="(groupItems, groupName) in groupedItems" :key="groupName">
      <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel v-if="state !== 'collapsed'">
          {{ groupName }}
        </SidebarGroupLabel>

        <SidebarMenu>
          <SidebarMenuItem v-for="item in groupItems" :key="item.title">
            <SidebarMenuButton
              :as-child="!item.disabled"
              :is-active="!item.disabled && urlIsActive(item.href, page.url)"
              :tooltip="item.title"
            >
              <Link v-if="!item.disabled" :href="item.href">
                <component :is="item.icon" />
                <span>{{ item.title }}</span>
              </Link>

              <div
                v-else
                class="flex w-full cursor-not-allowed items-center opacity-50"
              >
                <div class="flex w-full items-center gap-2">
                  <component :is="item.icon" class="h-4 w-4 shrink-0" />

                  <template v-if="state !== 'collapsed'">
                    <span class="flex-1 truncate">{{ item.title }}</span>
                    <Lock class="h-3 w-3 shrink-0 text-muted-foreground" />
                  </template>
                </div>
              </div>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>
    </template>
  </div>
</template>
