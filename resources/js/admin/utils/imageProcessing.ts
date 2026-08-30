/**
 * Prépare une photo côté navigateur avant upload : rejet immédiat des formats
 * RAW/DNG (jamais pris en charge, ni ici ni côté serveur), conversion HEIC → JPEG,
 * puis redimensionnement/compression via Canvas.
 *
 * Le serveur (ImageProcessingService, Laravel) ré-applique un redimensionnement/
 * compression WebP en filet de sécurité : ce module n'a pas besoin d'être parfait,
 * juste de réduire la charge réseau et d'écarter tôt ce que le serveur ne peut de
 * toute façon pas traiter (RAW, HEIC non converti).
 */

export const MAX_OUTPUT_WIDTH = 1920;
export const OUTPUT_QUALITY = 0.82;
export const MAX_ORIGINAL_FILE_SIZE = 20 * 1024 * 1024; // 20MB, aligné sur la limite serveur

const RAW_EXTENSIONS = [
  'dng', 'raw', 'cr2', 'cr3', 'nef', 'arw', 'raf', 'orf', 'rw2', 'pef', 'srw', 'tif', 'tiff',
];

const HEIC_EXTENSIONS = ['heic', 'heif'];

const ACCEPTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', ...HEIC_EXTENSIONS];

export class ImageProcessingError extends Error {}

function getExtension(filename: string): string {
  const idx = filename.lastIndexOf('.');
  return idx === -1 ? '' : filename.slice(idx + 1).toLowerCase();
}

function baseName(filename: string): string {
  const idx = filename.lastIndexOf('.');
  return idx === -1 ? filename : filename.slice(0, idx);
}

export function isRawFile(file: File): boolean {
  return RAW_EXTENSIONS.includes(getExtension(file.name));
}

export function isHeicFile(file: File): boolean {
  const ext = getExtension(file.name);
  return HEIC_EXTENSIONS.includes(ext) || file.type === 'image/heic' || file.type === 'image/heif';
}

let webpSupportCache: boolean | null = null;

function supportsWebpEncoding(): boolean {
  if (webpSupportCache !== null) return webpSupportCache;
  const canvas = document.createElement('canvas');
  canvas.width = 1;
  canvas.height = 1;
  webpSupportCache = canvas.toDataURL('image/webp').startsWith('data:image/webp');
  return webpSupportCache;
}

/**
 * heic2any embarque un décodeur WASM (~1.7MB) : chargé en dynamic import pour ne
 * jamais alourdir le bundle initial du dashboard, seulement quand un HEIC est
 * réellement sélectionné.
 */
async function convertHeicToJpeg(file: File): Promise<Blob> {
  const heic2any = (await import('heic2any')).default;
  const result = await heic2any({ blob: file, toType: 'image/jpeg', quality: 0.9 });
  return Array.isArray(result) ? result[0] : result;
}

async function resizeAndCompress(blob: Blob, maxWidth: number, quality: number): Promise<Blob> {
  const bitmap = await createImageBitmap(blob);
  const scale = bitmap.width > maxWidth ? maxWidth / bitmap.width : 1;
  const targetWidth = Math.round(bitmap.width * scale);
  const targetHeight = Math.round(bitmap.height * scale);

  const canvas = document.createElement('canvas');
  canvas.width = targetWidth;
  canvas.height = targetHeight;
  const ctx = canvas.getContext('2d');
  if (!ctx) {
    bitmap.close();
    throw new ImageProcessingError("Votre navigateur ne permet pas de traiter cette image.");
  }
  ctx.drawImage(bitmap, 0, 0, targetWidth, targetHeight);
  bitmap.close();

  const outputType = supportsWebpEncoding() ? 'image/webp' : 'image/jpeg';
  const output = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, outputType, quality));

  if (!output) {
    throw new ImageProcessingError("Impossible de compresser cette image.");
  }

  return output;
}

export interface PreparedImage {
  blob: Blob;
  filename: string;
}

/**
 * Point d'entrée unique utilisé par la file d'upload. Lève une ImageProcessingError
 * avec un message déjà prêt à afficher à l'utilisateur pour tout rejet.
 */
export async function prepareImageForUpload(file: File): Promise<PreparedImage> {
  if (isRawFile(file)) {
    throw new ImageProcessingError(
      "Ce format RAW n'est pas pris en charge. Veuillez convertir votre photo en JPG avant de l'importer."
    );
  }

  const extension = getExtension(file.name);
  if (!ACCEPTED_EXTENSIONS.includes(extension)) {
    throw new ImageProcessingError(
      "Format non pris en charge. Formats acceptés : JPG, PNG, HEIC."
    );
  }

  if (file.size > MAX_ORIGINAL_FILE_SIZE) {
    const maxMb = Math.round(MAX_ORIGINAL_FILE_SIZE / 1024 / 1024);
    throw new ImageProcessingError(
      `Le fichier est trop volumineux (maximum ${maxMb} Mo). Réduisez sa taille avant de l'importer.`
    );
  }

  let workingBlob: Blob = file;

  if (isHeicFile(file)) {
    try {
      workingBlob = await convertHeicToJpeg(file);
    } catch {
      throw new ImageProcessingError(
        "Impossible de convertir cette photo HEIC automatiquement. Réessayez, ou convertissez-la en JPG avant de l'importer."
      );
    }
  }

  try {
    const processed = await resizeAndCompress(workingBlob, MAX_OUTPUT_WIDTH, OUTPUT_QUALITY);
    const outputExtension = processed.type === 'image/webp' ? 'webp' : 'jpg';
    return { blob: processed, filename: `${baseName(file.name)}.${outputExtension}` };
  } catch (e) {
    if (e instanceof ImageProcessingError) throw e;
    throw new ImageProcessingError("Impossible de traiter cette image. Réessayez avec un autre fichier.");
  }
}
