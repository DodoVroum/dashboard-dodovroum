<template>
  <div class="border border-slate-200 rounded-xl overflow-hidden">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-3 px-4 py-3.5 min-h-[44px] text-left touch-manipulation lg:cursor-default"
      @click="toggle"
    >
      <span class="flex items-center gap-2.5 min-w-0">
        <component :is="icon" v-if="icon" class="w-[18px] h-[18px] text-slate-500 flex-shrink-0" aria-hidden="true" />
        <span class="text-base font-semibold text-slate-900 truncate">{{ title }}</span>
      </span>
      <ChevronDown
        class="w-5 h-5 text-slate-400 flex-shrink-0 transition-transform duration-200 lg:hidden"
        :class="isOpen ? 'rotate-180' : ''"
        aria-hidden="true"
      />
    </button>

    <!-- v-show pilote l'état mobile ; lg:!block force l'ouverture permanente en desktop quel que soit cet état -->
    <div v-show="isOpen" class="lg:!block px-4 pb-4 pt-1 border-t border-slate-100">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
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

function toggle() {
  isOpen.value = !isOpen.value;
  emit('toggle', isOpen.value);
}
</script>
