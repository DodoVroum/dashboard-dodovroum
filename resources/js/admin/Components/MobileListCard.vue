<template>
  <div
    class="bg-white border border-slate-200/80 rounded-2xl p-4 min-w-0
           shadow-[0_1px_3px_rgba(0,0,0,0.04),0_4px_12px_rgba(0,0,0,0.04)]
           transition-all duration-200"
    :class="clickable && !loading ? 'active:bg-slate-50 cursor-pointer' : ''"
    :role="clickable && !loading ? 'button' : undefined"
    :tabindex="clickable && !loading ? 0 : undefined"
    @click="onRootClick"
    @keydown.enter="onRootClick"
    @keydown.space.prevent="onRootClick"
  >
    <!-- Skeleton -->
    <div v-if="loading" class="flex items-center gap-3 animate-pulse">
      <div class="w-12 h-12 rounded-lg bg-slate-200 flex-shrink-0" />
      <div class="flex-1 min-w-0 space-y-2">
        <div class="h-3.5 bg-slate-200 rounded w-2/3" />
        <div class="h-3 bg-slate-100 rounded w-1/3" />
      </div>
    </div>

    <!-- Contenu réel -->
    <div v-else class="flex items-start gap-3">
      <div v-if="$slots.media" class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 bg-slate-100">
        <slot name="media" />
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0">
            <div class="text-sm font-medium text-slate-900 truncate">
              <slot name="title" />
            </div>
            <div v-if="$slots.subtitle" class="text-xs text-slate-500 mt-0.5 truncate">
              <slot name="subtitle" />
            </div>
          </div>

          <!-- Le contenu du slot actions gère lui-même @click.stop pour ne pas déclencher la navigation de la carte -->
          <div v-if="$slots.actions" class="flex-shrink-0 -mt-1 -mr-1">
            <slot name="actions" />
          </div>
        </div>

        <div v-if="$slots.badge || $slots.metric" class="flex items-center justify-between gap-2 mt-2" :class="dense ? 'mt-1.5' : ''">
          <div v-if="$slots.badge">
            <slot name="badge" />
          </div>
          <div v-if="$slots.metric" class="text-sm font-semibold text-slate-900 ml-auto">
            <slot name="metric" />
          </div>
        </div>

        <div v-if="$slots.default" :class="dense ? 'mt-1.5' : 'mt-2'">
          <slot />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    dense?: boolean;
    clickable?: boolean;
    loading?: boolean;
  }>(),
  {
    dense: false,
    clickable: true,
    loading: false,
  }
);

const emit = defineEmits<{
  click: [event: MouseEvent | KeyboardEvent];
}>();

function onRootClick(event: MouseEvent | KeyboardEvent) {
  emit('click', event);
}
</script>
