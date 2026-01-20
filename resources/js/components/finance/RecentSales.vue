<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center">
      <Avatar class="h-9 w-9">
        <AvatarImage src="/avatars/01.png" alt="Driver Avatar" />
        <AvatarFallback>DM</AvatarFallback>
      </Avatar>
      <div class="ml-4 space-y-1">
        <p class="text-sm leading-none font-medium">David Moore</p>
        <p class="text-sm text-muted-foreground">david.moore@evtaxi.com</p>
      </div>
      <div class="ml-auto font-medium">EV Taxi Boundaries: Zone A</div>
    </div>
    <div class="flex items-center">
      <Avatar class="flex h-9 w-9 items-center justify-center space-y-0 border">
        <AvatarImage src="/avatars/02.png" alt="Driver Avatar" />
        <AvatarFallback>JL</AvatarFallback>
      </Avatar>
      <div class="ml-4 space-y-1">
        <p class="text-sm leading-none font-medium">James Lee</p>
        <p class="text-sm text-muted-foreground">james.lee@evtaxi.com</p>
      </div>
      <div class="ml-auto font-medium">EV Taxi Boundaries: Zone B</div>
    </div>
    <div class="flex items-center">
      <Avatar class="h-9 w-9">
        <AvatarImage src="/avatars/03.png" alt="Driver Avatar" />
        <AvatarFallback>IN</AvatarFallback>
      </Avatar>
      <div class="ml-4 space-y-1">
        <p class="text-sm leading-none font-medium">Isabella Nguyen</p>
        <p class="text-sm text-muted-foreground">isabella.nguyen@evtaxi.com</p>
      </div>
      <div class="ml-auto font-medium">EV Taxi Boundaries: Zone C</div>
    </div>
    <div class="flex items-center">
      <Avatar class="h-9 w-9">
        <AvatarImage src="/avatars/04.png" alt="Driver Avatar" />
        <AvatarFallback>WK</AvatarFallback>
      </Avatar>
      <div class="ml-4 space-y-1">
        <p class="text-sm leading-none font-medium">William Kim</p>
        <p class="text-sm text-muted-foreground">will.kim@evtaxi.com</p>
      </div>
      <div class="ml-auto font-medium">EV Taxi Boundaries: Zone D</div>
    </div>
    <div class="flex items-center">
      <Avatar class="h-9 w-9">
        <AvatarImage src="/avatars/05.png" alt="Driver Avatar" />
        <AvatarFallback>SD</AvatarFallback>
      </Avatar>
      <div class="ml-4 space-y-1">
        <p class="text-sm leading-none font-medium">Sofia Davis</p>
        <p class="text-sm text-muted-foreground">sofia.davis@evtaxi.com</p>
      </div>
      <div class="ml-auto font-medium">EV Taxi Boundaries: Zone E</div>
    </div>
  </div>
</template>
