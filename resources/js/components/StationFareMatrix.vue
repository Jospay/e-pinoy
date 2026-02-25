<script setup lang="ts">
interface Fare {
  from_id: number;
  from_code: string;
  to_id: number;
  to_code: string;
  amount: string;
}

defineProps<{
  fares: Fare[];
}>();
</script>

<template>
  <div
    v-if="fares.length"
    class="overflow-x-auto rounded-lg border border-slate-200"
  >
    <table class="w-full text-sm">
      <thead>
        <tr>
          <th
            class="border-r border-b border-slate-200 p-2 text-left font-black text-slate-400 uppercase"
          >
            From \ To
          </th>
          <th
            class="border-r border-b border-slate-200 p-2 text-left font-black text-slate-400 uppercase"
          >
            To \ From
          </th>
          <th
            class="border-b border-slate-200 p-2 text-center font-black text-slate-400 uppercase"
          >
            Price
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="fare in fares"
          :key="`${fare.from_id}-${fare.to_id}`"
          class="border-b border-slate-100 last:border-b-0"
        >
          <td class="border-r border-slate-200 p-2 font-bold">
            {{ fare.from_code }}
          </td>
          <td class="border-r border-slate-200 p-2 font-bold">
            {{ fare.to_code }}
          </td>
          <td class="p-2 text-center font-semibold text-emerald-600">
            ₱{{ fare.amount }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div
    v-else
    class="flex items-center gap-3 rounded-lg border border-dashed border-amber-200 bg-amber-50 p-3"
  >
    <MapPin class="h-3.5 w-3.5 text-amber-600" />
    <p class="text-[11px] font-medium text-amber-800 italic">
      No fare data configured for this franchise yet.
    </p>
  </div>
</template>
