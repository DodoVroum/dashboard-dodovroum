<template>
  <div class="border border-slate-200 rounded-xl overflow-hidden">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 px-4 py-3.5 min-h-[44px] text-left touch-manipulation"
      :class="isDesktop ? 'lg:cursor-default' : ''"
      @click="toggle"
    >
      <span class="flex items-center gap-2.5 min-w-0">
        <component :is="icon" v-if="icon" class="w-[18px] h-[18px] text-slate-500 flex-shrink-0" aria-hidden="true" />
        <span class="text-base font-semibold text-slate-900 truncate">{{ title }}</span>
      </span>
      <ChevronDown
        v-if="!isDesktop"
        class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform duration-200"
        :class="isOpen ? 'rotate-180' : ''"
        aria-hidden="true"
      />
    </button>

    <!-- Visibilité pilotée en JS (isDesktop || isOpen), pas par une classe CSS !important
    en concurrence avec le style inline de v-show : plus fiable quel que soit l'ordre/la
    purge des feuilles de style en production. -->
    <div v-show="isDesktop || isOpen" class="px-4 pb-4 pt-1 border-t border-slate-100">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import type { Component } from 'vue';

const props = withDefaults(
  defineProps<{
    title: string;
    defaultOpen?: boolean;
    icon?: Component;
  }>(),
  { defaultOpen: false }
);

const emit = defineEmits<{
  toggle: [open: boolean];
}>();

const isOpen = ref(props.defaultOpen);

const isDesktop = ref(false);
function updateIsDesktop() {
  isDesktop.value = typeof window !== 'undefined' && window.innerWidth >= 1024;
}

onMounted(() => {
  updateIsDesktop();
  window.addEventListener('resize', updateIsDesktop);
});

onUnmounted(() => {
  window.removeEventListener('resize', updateIsDesktop);
});

function toggle() {
  if (isDesktop.value) return;
  isOpen.value = !isOpen.value;
  emit('toggle', isOpen.value);
}
</script>
