<template>
  <div class="space-y-4">
    <!-- Sélection de fichiers -->
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-2">
        Importer des photos depuis votre appareil
      </label>
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.heic,.heif"
        @change="handleFileSelection"
        class="hidden"
      />
      <button
        type="button"
        @click="fileInput?.click()"
        class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        Choisir des photos
      </button>
      <p class="text-xs text-slate-500 mt-1">
        JPG, PNG ou HEIC (photos iPhone). Les formats RAW/DNG ne sont pas pris en charge.
      </p>
    </div>

    <!-- Fichiers en cours de traitement / upload / en erreur -->
    <div v-if="visibleItems.length > 0" class="space-y-2">
      <div
        v-for="item in visibleItems"
        :key="item.id"
        class="flex items-center gap-3 px-3 py-2 border rounded-lg text-sm"
        :class="item.status === 'error' ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50'"
      >
        <svg v-if="item.status !== 'error'" class="animate-spin h-4 w-4 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else class="h-4 w-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>

        <div class="flex-1 min-w-0">
          <p class="truncate text-slate-700" :title="item.name">{{ item.name }}</p>
          <p v-if="item.status === 'error'" class="text-red-600 text-xs mt-0.5">{{ item.error }}</p>
          <p v-else-if="item.status === 'processing'" class="text-xs text-slate-500 mt-0.5">Préparation de l'image...</p>
          <div v-else-if="item.status === 'uploading'" class="w-full bg-slate-200 rounded-full h-1.5 mt-1.5">
            <div class="bg-blue-600 h-1.5 rounded-full transition-all" :style="{ width: item.progress + '%' }"></div>
          </div>
        </div>

        <button
          v-if="item.status === 'error'"
          type="button"
          @click="retry(item.id)"
          class="text-xs px-2 py-1 border border-slate-300 rounded hover:bg-white shrink-0"
        >
          Réessayer
        </button>
        <button
          type="button"
          @click="dismiss(item.id)"
          class="p-1 rounded-full hover:bg-white shrink-0"
          :aria-label="`Retirer ${item.name} de la file d'import`"
        >
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Prévisualisation des images déjà importées -->
    <div v-if="images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <div v-for="(image, index) in images" :key="image + index" class="relative group">
        <div class="w-full h-32 rounded-lg border border-slate-300 overflow-hidden bg-slate-100 flex items-center justify-center">
          <img
            v-if="!imageErrors[index]"
            :src="getStorageImageUrl(image, 'residences')"
            :alt="`Image ${index + 1}`"
            loading="lazy"
            class="w-full h-full object-cover"
            @error="() => handleImageError(index)"
            @load="() => (imageErrors[index] = false)"
          />
          <div v-else class="w-full h-full flex flex-col items-center justify-center p-2 text-center">
            <svg class="w-8 h-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-xs text-slate-500 break-all px-2" :title="image">{{ image.length > 50 ? image.substring(0, 50) + '...' : image }}</p>
            <p class="text-xs text-slate-400 mt-1">Image non accessible</p>
          </div>
        </div>
        <button
          type="button"
          @click.stop.prevent="removeImage(index)"
          class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 opacity-90 hover:opacity-100 transition-opacity hover:bg-red-600 z-50 shadow-lg"
          style="pointer-events: auto !important;"
          title="Supprimer cette image"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { getStorageImageUrl } from '../utils/imageUrl';
import { useImageUploadQueue } from '../composables/useImageUploadQueue';

const props = withDefaults(defineProps<{ category?: string }>(), {
  category: 'residences',
});

const images = defineModel<string[]>({ default: () => [] });

const fileInput = ref<HTMLInputElement | null>(null);
const imageErrors = ref<Record<number, boolean>>({});

const { items, addFiles, dismiss, retry } = useImageUploadQueue({
  endpoint: '/owner/images/upload',
  category: props.category,
  onUploaded: (url) => {
    images.value = [...images.value, url];
  },
});

// Les imports terminés restent visibles via la grille de prévisualisation ci-dessous ;
// inutile de les garder aussi dans la liste de progression.
const visibleItems = computed(() => items.value.filter((item) => item.status !== 'done'));

const handleFileSelection = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    addFiles(target.files);
  }
  target.value = '';
};

const handleImageError = (index: number) => {
  imageErrors.value[index] = true;
};

const removeImage = (index: number) => {
  if (index < 0 || index >= images.value.length) return;

  const newImages = [...images.value];
  newImages.splice(index, 1);
  images.value = newImages;

  if (imageErrors.value[index] !== undefined) {
    const newErrors: Record<number, boolean> = {};
    Object.keys(imageErrors.value).forEach((key) => {
      const keyNum = parseInt(key);
      if (keyNum < index) {
        newErrors[keyNum] = imageErrors.value[keyNum];
      } else if (keyNum > index) {
        newErrors[keyNum - 1] = imageErrors.value[keyNum];
      }
    });
    imageErrors.value = newErrors;
  }
};
</script>
