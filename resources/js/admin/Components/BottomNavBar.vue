<template>
  <nav
    class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-white border-t border-slate-200 flex items-stretch"
    style="padding-bottom: env(safe-area-inset-bottom);"
    aria-label="Navigation principale mobile"
  >
    <Link
      v-for="item in props.items"
      :key="item.href"
      :href="item.href"
      class="flex-1 flex flex-col items-center justify-center gap-1 min-h-[56px] py-1.5 touch-manipulation"
      :class="isActive(item.href) ? 'text-brand' : 'text-slate-400'"
      :aria-current="isActive(item.href) ? 'page' : undefined"
    >
      <component :is="item.icon" class="w-5 h-5 flex-shrink-0" aria-hidden="true" />
      <span class="text-[11px] font-medium leading-none truncate max-w-[64px]">{{ item.label }}</span>
    </Link>

    <button
      type="button"
      class="flex-1 flex flex-col items-center justify-center gap-1 min-h-[56px] py-1.5 touch-manipulation text-slate-400"
      aria-label="Plus d'options"
      @click="emit('open-more')"
    >
      <MoreHorizontal class="w-5 h-5 flex-shrink-0" aria-hidden="true" />
      <span class="text-[11px] font-medium leading-none">Plus</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = defineProps<{
  items: { label: string; href: string; icon: Component }[];
}>();

const emit = defineEmits<{
  'open-more': [];
}>();

const page = usePage();
const currentPath = computed(() => page.url);

function isActive(path: string): boolean {
  if (path.endsWith('/dashboard')) {
    return currentPath.value === path;
  }
  return currentPath.value.startsWith(path);
}
</script>
