<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-slate-700 mb-1">{{ label }}</label>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="w-11 h-11 flex-shrink-0 flex items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation"
        :disabled="min !== undefined && modelValue <= min"
        aria-label="Diminuer"
        @click="setValue(modelValue - step)"
      >
        <Minus class="w-4 h-4" />
      </button>

      <input
        type="number"
        :value="modelValue"
        :min="min"
        :max="max"
        :step="step"
        class="w-full text-center px-2 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
        @input="onInput"
      />

      <button
        type="button"
        class="w-11 h-11 flex-shrink-0 flex items-center justify-center rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed touch-manipulation"
        :disabled="max !== undefined && modelValue >= max"
        aria-label="Augmenter"
        @click="setValue(modelValue + step)"
      >
        <Plus class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Minus, Plus } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    modelValue: number;
    min?: number;
    max?: number;
    step?: number;
    label?: string;
  }>(),
  { min: 0, step: 1 }
);

const emit = defineEmits<{
  'update:modelValue': [value: number];
}>();

function clamp(value: number): number {
  let v = value;
  if (props.min !== undefined && v < props.min) v = props.min;
  if (props.max !== undefined && v > props.max) v = props.max;
  return v;
}

function setValue(value: number) {
  emit('update:modelValue', clamp(value));
}

function onInput(event: Event) {
  const raw = (event.target as HTMLInputElement).value;
  const parsed = raw === '' ? (props.min ?? 0) : Number(raw);
  if (!Number.isNaN(parsed)) {
    setValue(parsed);
  }
}
</script>
