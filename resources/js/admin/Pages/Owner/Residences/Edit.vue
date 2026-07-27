<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Modifier la résidence</h1>
        <p class="text-sm text-slate-500 mt-1">{{ residence.title || residence.name }}</p>
      </div>
      <Link
        href="/owner/residences"
        class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50"
      >
        Annuler
      </Link>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <!-- Informations de base -->
      <CollapsibleSection title="Informations de base" :default-open="true">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Titre *
            </label>
            <input
              v-model="form.title"
              type="text"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="ex: Villa de luxe à Cocody"
            />
            <p v-if="errors.title" class="text-red-600 text-sm mt-1">{{ errors.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Type *
            </label>
            <select
              v-model="form.typeResidence"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            >
              <option value="">Sélectionner un type</option>
              <option value="villa">Villa</option>
              <option value="appartement">Appartement</option>
              <option value="studio">Studio</option>
              <option value="duplex">Duplex</option>
              <option value="maison_basse">Maison basse</option>
              <option value="chambre_meublee">Chambre meublée</option>
              <option value="residence_hoteliere">Résidence hôtelière</option>
              <option value="villa_avec_piscine">Villa avec piscine</option>
              <option value="penthouse">Penthouse</option>
              <option value="bungalow">Bungalow</option>
            </select>
            <p v-if="errors.typeResidence" class="text-red-600 text-sm mt-1">{{ errors.typeResidence }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Adresse *
            </label>
            <input
              v-model="form.address"
              type="text"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="123 Rue de la Paix"
            />
            <p v-if="errors.address" class="text-red-600 text-sm mt-1">{{ errors.address }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Ville *
            </label>
            <input
              v-model="form.city"
              type="text"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Abidjan"
            />
            <p v-if="errors.city" class="text-red-600 text-sm mt-1">{{ errors.city }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Pays *
            </label>
            <input
              v-model="form.country"
              type="text"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="Côte d'Ivoire"
            />
            <p v-if="errors.country" class="text-red-600 text-sm mt-1">{{ errors.country }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
              Prix par nuit (CFA) *
            </label>
            <input
              v-model.number="form.pricePerNight"
              type="number"
              required
              min="0"
              step="0.01"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              placeholder="50000"
            />
            <p v-if="errors.pricePerNight" class="text-red-600 text-sm mt-1">{{ errors.pricePerNight }}</p>
          </div>
        </div>
      </CollapsibleSection>

      <!-- Équipements -->
      <CollapsibleSection title="Équipements">
        <div class="space-y-2">
          <div class="flex flex-wrap gap-2">
            <label
              v-for="amenity in availableAmenities"
              :key="amenity"
              class="flex items-center px-3 py-2 border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50"
              :class="form.amenities.includes(amenity) ? 'bg-blue-50 border-blue-500' : ''"
            >
              <input
                type="checkbox"
                :value="amenity"
                v-model="form.amenities"
                class="mr-2"
              />
              <span>{{ amenity }}</span>
            </label>
          </div>

          <!-- Équipements personnalisés ajoutés (absents de la liste prédéfinie ci-dessus) -->
          <div v-if="customAmenities.length > 0" class="flex flex-wrap gap-2">
            <span
              v-for="amenity in customAmenities"
              :key="amenity"
              class="inline-flex items-center gap-1.5 pl-3 pr-2 py-2 bg-blue-50 border border-blue-500 text-blue-700 rounded-lg text-sm"
            >
              {{ amenity }}
              <button
                type="button"
                @click="removeAmenity(amenity)"
                class="p-0.5 rounded-full hover:bg-blue-100 min-h-[20px] min-w-[20px] flex items-center justify-center"
                :aria-label="`Retirer ${amenity}`"
              >
                ×
              </button>
            </span>
          </div>

          <div class="flex gap-2">
            <input
              type="text"
              v-model="newAmenity"
              @keyup.enter="addAmenity"
              placeholder="Ajouter un équipement"
              class="flex-1 min-w-0 px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
            />
            <button
              type="button"
              @click="addAmenity"
              :disabled="!newAmenity.trim()"
              class="px-4 py-2 min-h-[44px] bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 disabled:opacity-50 disabled:cursor-not-allowed font-medium shrink-0"
            >
              Ajouter
            </button>
          </div>
        </div>
      </CollapsibleSection>

      <!-- Images -->
      <CollapsibleSection title="Images">
        <div class="space-y-4">
          <!-- Upload de fichier -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Uploader une image depuis votre PC
            </label>
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              @change="handleFileUpload"
              class="hidden"
            />
            <button
              type="button"
              @click="$refs.fileInput.click()"
              :disabled="uploading"
              class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="!uploading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              <svg v-else class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ uploading ? 'Upload en cours...' : 'Choisir un fichier' }}
            </button>
          </div>

          <!-- Prévisualisation des images -->
          <div v-if="form.images && form.images.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="(image, index) in form.images" :key="index" class="relative group">
              <div class="w-full h-32 rounded-lg border border-slate-300 overflow-hidden bg-slate-100 flex items-center justify-center">
                <img
                  v-if="!imageErrors[index]"
                  :src="getStorageImageUrl(image, 'residences')"
                  :alt="`Image ${index + 1}`"
                  class="w-full h-full object-cover"
                  @error="() => handleImageError(index)"
                  @load="() => imageErrors[index] = false"
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
      </CollapsibleSection>

      <!-- Statuts -->
      <CollapsibleSection title="Statuts">
        <div class="space-y-3">
          <label class="flex items-center">
            <input
              type="checkbox"
              v-model="form.isActive"
              class="mr-2"
            />
            <span class="text-sm font-medium text-slate-700">Résidence active</span>
          </label>
        </div>
      </CollapsibleSection>

      <!-- Caractéristiques -->
      <CollapsibleSection title="Caractéristiques">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <NumberStepper
              v-model="form.bedrooms"
              label="Nombre de chambres *"
              :min="0"
            />
            <p v-if="errors.bedrooms" class="text-red-600 text-sm mt-1">{{ errors.bedrooms }}</p>
          </div>

          <div>
            <NumberStepper
              v-model="form.bathrooms"
              label="Nombre de salles de bain *"
              :min="0"
            />
            <p v-if="errors.bathrooms" class="text-red-600 text-sm mt-1">{{ errors.bathrooms }}</p>
          </div>

          <div>
            <NumberStepper
              v-model="form.capacity"
              label="Capacité (personnes) *"
              :min="1"
            />
            <p v-if="errors.capacity" class="text-red-600 text-sm mt-1">{{ errors.capacity }}</p>
          </div>
        </div>
      </CollapsibleSection>

      <!-- Description -->
      <CollapsibleSection title="Description">
        <textarea
          v-model="form.description"
          rows="4"
          maxlength="500"
          class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
          placeholder="Description de la résidence..."
        ></textarea>
        <div class="flex justify-between items-center mt-1">
          <p v-if="errors.description" class="text-red-600 text-sm">{{ errors.description }}</p>
          <p class="text-xs text-slate-500 ml-auto">{{ form.description?.length || 0 }}/500 caractères</p>
        </div>
      </CollapsibleSection>

      <!-- Actions -->
      <div class="flex justify-end gap-3 bg-white border border-slate-200 rounded-xl p-4">
        <Link
          href="/owner/residences"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50"
        >
          Annuler
        </Link>
        <button
          type="submit"
          :disabled="processing"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {{ processing ? 'Mise à jour...' : 'Mettre à jour' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { getStorageImageUrl } from '../../../utils/imageUrl';
import OwnerLayout from '../../../Components/Layouts/OwnerLayout.vue';
import CollapsibleSection from '../../../Components/CollapsibleSection.vue';
import NumberStepper from '../../../Components/NumberStepper.vue';

defineOptions({
  layout: OwnerLayout,
});

const props = defineProps<{
  residence: {
    id: number | string;
    title?: string;
    name?: string;
    nom?: string;
    typeResidence?: string;
    type?: string;
    address?: string;
    adresse?: string;
    city?: string;
    ville?: string;
    country?: string;
    pays?: string;
    pricePerNight?: number;
    price?: number;
    prixParNuit?: number;
    bedrooms?: number;
    nombreChambres?: number;
    bathrooms?: number;
    nombreSallesBain?: number;
    capacity?: number;
    capacite?: number;
    description?: string;
    images?: string[];
    amenities?: string[];
    commodites?: string[];
    localisation?: {};
    isActive?: boolean;
    available?: boolean;
  };
}>();

const form = useForm({
  title: props.residence.title || props.residence.name || props.residence.nom || '',
  typeResidence: props.residence.typeResidence || props.residence.type || '',
  address: props.residence.address || props.residence.adresse || '',
  city: props.residence.city || props.residence.ville || '',
  country: props.residence.country || props.residence.pays || '',
  pricePerNight: props.residence.pricePerNight || props.residence.price || props.residence.prixParNuit || 0,
  bedrooms: props.residence.bedrooms || props.residence.nombreChambres || 0,
  bathrooms: props.residence.bathrooms || props.residence.nombreSallesBain || 0,
  capacity: props.residence.capacity || props.residence.capacite || 1,
  description: props.residence.description || '',
  images: props.residence.images || [],
  amenities: props.residence.amenities || props.residence.commodites || [],
  isActive: props.residence.isActive ?? props.residence.available ?? true,
});

const availableAmenities = [
  'WiFi',
  'Piscine',
  'Parking',
  'Climatisation',
  'Chauffage',
  'Cuisine équipée',
  'Lave-linge',
  'Télévision',
  'Jardin',
  'Terrasse',
  'Balcon',
  'Ascenseur',
  'Salle de sport',
  'Spa',
];

const newAmenity = ref('');
const uploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const imageErrors = ref<Record<number, boolean>>({});

const addAmenity = () => {
  if (newAmenity.value.trim() && !form.amenities.includes(newAmenity.value.trim())) {
    form.amenities.push(newAmenity.value.trim());
    newAmenity.value = '';
  }
};

// Équipements ajoutés via le champ texte, absents de la liste de cases à cocher
// prédéfinie ci-dessus : sans ça, ils étaient bien envoyés au serveur mais
// invisibles à l'écran.
const customAmenities = computed(() =>
  form.amenities.filter((amenity) => !availableAmenities.includes(amenity))
);

const removeAmenity = (amenity: string) => {
  form.amenities = form.amenities.filter((a) => a !== amenity);
};

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (!file) return;

  // Vérifier la taille (5MB max)
  if (file.size > 5 * 1024 * 1024) {
    alert('Le fichier est trop volumineux. Taille maximale : 5MB');
    return;
  }

  uploading.value = true;

  try {
    const formData = new FormData();
    formData.append('image', file);

    const response = await axios.post('/owner/images/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.success && response.data.url) {
      form.images.push(response.data.url);
    } else {
      alert('Erreur lors de l\'upload de l\'image');
    }
  } catch (error: any) {
    console.error('Erreur upload:', error);
    alert('Erreur lors de l\'upload de l\'image: ' + (error.response?.data?.message || error.message));
  } finally {
    uploading.value = false;
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};


const handleImageError = (index: number) => {
  // Marquer l'image comme ayant une erreur de chargement
  imageErrors.value[index] = true;
  console.warn(`Image ${index} ne peut pas être chargée:`, form.images[index]);
};

const removeImage = (index: number) => {
  if (!form.images || index < 0 || index >= form.images.length) return;
  
  // Créer un nouveau tableau pour déclencher la réactivité
  const newImages = [...form.images];
  newImages.splice(index, 1);
  form.images = newImages;
  
  // Supprimer l'erreur associée si elle existe
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

const submit = () => {
  const payload = form.data();
  console.log('DEBUG_PAYLOAD_ENVOYE:', JSON.stringify(payload, null, 2));
  form.put(`/owner/residences/${props.residence.id}`, {
    onSuccess: () => {
      // Redirection gérée par le contrôleur
    },
  });
};

const errors = form.errors;
const processing = form.processing;

</script>

