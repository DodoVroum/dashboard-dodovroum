import { ref } from 'vue';
import axios from 'axios';
import { prepareImageForUpload, ImageProcessingError } from '../utils/imageProcessing';

export type UploadItemStatus = 'pending' | 'processing' | 'uploading' | 'done' | 'error';

export interface UploadItem {
  id: string;
  name: string;
  status: UploadItemStatus;
  progress: number;
  error?: string;
  url?: string;
}

interface UseImageUploadQueueOptions {
  endpoint?: string;
  category?: string;
  concurrency?: number;
  onUploaded?: (url: string) => void;
}

const DEFAULT_CONCURRENCY = 2;

function makeId(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto
    ? crypto.randomUUID()
    : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

/**
 * File d'upload à concurrence limitée : plusieurs photos peuvent être sélectionnées
 * d'un coup sans geler le dashboard, chacune avec son propre statut/progression/erreur
 * indépendants des autres (l'échec d'une image n'affecte pas les autres).
 */
export function useImageUploadQueue(options: UseImageUploadQueueOptions = {}) {
  const {
    endpoint = '/owner/images/upload',
    category = 'residences',
    concurrency = DEFAULT_CONCURRENCY,
    onUploaded,
  } = options;

  const items = ref<UploadItem[]>([]);
  const filesById = new Map<string, File>();
  let activeCount = 0;

  const hasActiveUploads = () =>
    items.value.some((item) => item.status === 'pending' || item.status === 'processing' || item.status === 'uploading');

  function pump() {
    while (activeCount < concurrency) {
      const next = items.value.find((item) => item.status === 'pending');
      if (!next) break;

      activeCount += 1;
      processItem(next).finally(() => {
        activeCount -= 1;
        pump();
      });
    }
  }

  async function processItem(item: UploadItem) {
    const file = filesById.get(item.id);
    if (!file) return;

    item.status = 'processing';

    let prepared;
    try {
      prepared = await prepareImageForUpload(file);
    } catch (e) {
      item.status = 'error';
      item.error = e instanceof ImageProcessingError ? e.message : "Impossible de traiter cette image.";
      return;
    }

    item.status = 'uploading';
    item.progress = 0;

    try {
      const formData = new FormData();
      formData.append('image', prepared.blob, prepared.filename);
      formData.append('category', category);

      const response = await axios.post(endpoint, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (evt) => {
          if (evt.total) {
            item.progress = Math.round((evt.loaded / evt.total) * 100);
          }
        },
      });

      if (response.data?.success && response.data?.url) {
        item.status = 'done';
        item.progress = 100;
        item.url = response.data.url;
        onUploaded?.(response.data.url);
      } else {
        item.status = 'error';
        item.error = response.data?.message || "Erreur lors de l'upload de l'image.";
      }
    } catch (e: any) {
      item.status = 'error';
      item.error = e?.response?.data?.message || e?.message || "Erreur réseau lors de l'upload.";
    }
  }

  function addFiles(fileList: FileList | File[]) {
    const files = Array.from(fileList);
    const newItems: UploadItem[] = files.map((file) => {
      const id = makeId();
      filesById.set(id, file);
      return { id, name: file.name, status: 'pending', progress: 0 };
    });

    items.value.push(...newItems);
    pump();
  }

  function dismiss(id: string) {
    filesById.delete(id);
    items.value = items.value.filter((item) => item.id !== id);
  }

  function retry(id: string) {
    const item = items.value.find((i) => i.id === id);
    if (!item || !filesById.has(id)) return;
    item.status = 'pending';
    item.error = undefined;
    item.progress = 0;
    pump();
  }

  return { items, addFiles, dismiss, retry, hasActiveUploads };
}
