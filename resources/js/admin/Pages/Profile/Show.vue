<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Mon profil</h1>
        <p class="text-sm text-slate-500 mt-1">Gérez vos informations personnelles</p>
      </div>
      <a
        href="#mot-de-passe"
        class="text-sm font-medium text-blue-600 hover:text-blue-800 whitespace-nowrap shrink-0"
      >
        Mot de passe →
      </a>
    </div>

    <!-- Messages de succès/erreur -->
    <div v-if="$page.props.flash?.success" class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ error }}
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Informations principales -->
      <div class="lg:col-span-2">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
          <h2 class="text-lg font-semibold text-slate-900 mb-6">Informations personnelles</h2>
          
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Prénom -->
              <div>
                <label for="firstName" class="block text-sm font-medium text-slate-700 mb-2">
                  Prénom
                </label>
                <input
                  id="firstName"
                  v-model="form.firstName"
                  type="text"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Votre prénom"
                />
                <div v-if="form.errors.firstName" class="mt-1 text-sm text-red-600">
                  {{ form.errors.firstName }}
                </div>
              </div>

              <!-- Nom -->
              <div>
                <label for="lastName" class="block text-sm font-medium text-slate-700 mb-2">
                  Nom
                </label>
                <input
                  id="lastName"
                  v-model="form.lastName"
                  type="text"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Votre nom"
                />
                <div v-if="form.errors.lastName" class="mt-1 text-sm text-red-600">
                  {{ form.errors.lastName }}
                </div>
              </div>
            </div>

            <!-- Email (lecture seule) -->
            <div>
              <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                Email
              </label>
              <input
                id="email"
                :value="user?.email ?? ''"
                type="email"
                disabled
                class="w-full px-4 py-2 border border-slate-300 rounded-lg bg-slate-50 text-slate-500 cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-slate-500">L'email ne peut pas être modifié</p>
            </div>

            <!-- Téléphone -->
            <div>
              <label for="phone" class="block text-sm font-medium text-slate-700 mb-2">
                Téléphone
              </label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="+33 6 12 34 56 78"
              />
              <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                {{ form.errors.phone }}
              </div>
            </div>

            <!-- Adresse -->
            <div>
              <label for="address" class="block text-sm font-medium text-slate-700 mb-2">
                Adresse
              </label>
              <input
                id="address"
                v-model="form.address"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="123 Rue de la République"
              />
              <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                {{ form.errors.address }}
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Ville -->
              <div>
                <label for="city" class="block text-sm font-medium text-slate-700 mb-2">
                  Ville
                </label>
                <input
                  id="city"
                  v-model="form.city"
                  type="text"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Paris"
                />
                <div v-if="form.errors.city" class="mt-1 text-sm text-red-600">
                  {{ form.errors.city }}
                </div>
              </div>

              <!-- Pays -->
              <div>
                <label for="country" class="block text-sm font-medium text-slate-700 mb-2">
                  Pays
                </label>
                <input
                  id="country"
                  v-model="form.country"
                  type="text"
                  class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="France"
                />
                <div v-if="form.errors.country" class="mt-1 text-sm text-red-600">
                  {{ form.errors.country }}
                </div>
              </div>
            </div>

            <!-- Boutons -->
            <div class="flex gap-3 pt-4">
              <button
                type="submit"
                :disabled="form.processing"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="form.processing">Enregistrement...</span>
                <span v-else>Enregistrer les modifications</span>
              </button>
              <button
                type="button"
                @click="resetForm"
                class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              >
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Colonne droite : résumé + mot de passe (visible sans défiler tout le formulaire) -->
      <div class="space-y-6 lg:col-span-1">
        <!-- Rôle -->
        <div class="bg-white border border-slate-200 rounded-xl p-6">
          <h3 class="text-sm font-medium text-slate-500 mb-2">Rôle</h3>
          <p class="text-lg font-semibold text-slate-900 capitalize">
            {{ roleLabel }}
          </p>
        </div>

        <!-- Date de création -->
        <div v-if="user?.createdAt" class="bg-white border border-slate-200 rounded-xl p-6">
          <h3 class="text-sm font-medium text-slate-500 mb-2">Membre depuis</h3>
          <p class="text-lg font-semibold text-slate-900">
            {{ formatDate(user.createdAt) }}
          </p>
        </div>

        <!-- Mot de passe : même ordre mobile (après infos perso grâce au grid) -->
        <div id="mot-de-passe" class="bg-white border border-slate-200 rounded-xl p-6 scroll-mt-24">
          <h2 class="text-lg font-semibold text-slate-900 mb-2">Mot de passe</h2>
          <p class="text-sm text-slate-500 mb-6">Connexion au tableau de bord (identique à l’app si le compte est partagé).</p>

          <form @submit.prevent="submitPasswordForm" class="space-y-4">
            <div>
              <label for="current_password" class="block text-sm font-medium text-slate-700 mb-2">
                Mot de passe actuel
              </label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                autocomplete="current-password"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">
                {{ passwordForm.errors.current_password }}
              </div>
            </div>

            <div>
              <label for="new_password" class="block text-sm font-medium text-slate-700 mb-2">
                Nouveau mot de passe
              </label>
              <input
                id="new_password"
                v-model="passwordForm.password"
                type="password"
                autocomplete="new-password"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <p class="mt-1 text-xs text-slate-500">Au moins 8 caractères</p>
              <div v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">
                {{ passwordForm.errors.password }}
              </div>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">
                Confirmer le nouveau mot de passe
              </label>
              <input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <div v-if="passwordForm.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                {{ passwordForm.errors.password_confirmation }}
              </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2 pt-2">
              <button
                type="submit"
                :disabled="passwordForm.processing"
                class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="passwordForm.processing">Mise à jour...</span>
                <span v-else>Enregistrer le nouveau mot de passe</span>
              </button>
              <button
                type="button"
                class="px-4 py-2 border border-slate-300 text-sm rounded-lg hover:bg-slate-50 transition-colors"
                @click="resetPasswordForm"
              >
                Effacer
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Vérification d'identité (propriétaires uniquement) -->
    <div v-if="isOwner" class="bg-white border border-slate-200 rounded-xl p-6">
      <div class="flex items-center justify-between mb-2">
        <h2 class="text-lg font-semibold text-slate-900">Vérification d'identité</h2>
        <span
          v-if="verificationStatus"
          :class="statusBadgeClass"
          class="px-3 py-1 rounded-full text-xs font-medium"
        >
          {{ statusLabel }}
        </span>
      </div>
      <p class="text-sm text-slate-500 mb-6">
        Une pièce d'identité valide (CNI, passeport...) est nécessaire pour publier des annonces.
      </p>

      <div v-if="verificationStatus === 'REJECTED' && rejectionReason" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
        <strong>Motif du rejet :</strong> {{ rejectionReason }}
      </div>

      <!-- Statut vérifié : lecture seule -->
      <div v-if="verificationStatus === 'VERIFIED'" class="text-sm text-slate-600">
        Votre identité a été vérifiée{{ identityVerification?.identityType ? ` (${identityVerification.identityType})` : '' }}. Aucune action requise.
      </div>

      <!-- En attente : lecture seule -->
      <div v-else-if="verificationStatus === 'PENDING' || verificationStatus === 'UNDER_REVIEW'" class="text-sm text-slate-600">
        Vos documents sont en cours d'examen par notre équipe.
      </div>

      <!-- Aucune vérification ou rejetée : formulaire de (re)soumission -->
      <form v-else @submit.prevent="submitIdentityForm" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="identityType" class="block text-sm font-medium text-slate-700 mb-2">Type de document</label>
            <select
              id="identityType"
              v-model="identityForm.identityType"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="CNI">Carte nationale d'identité</option>
              <option value="PASSPORT">Passeport</option>
              <option value="PERMIT">Permis de conduire (pièce)</option>
              <option value="DRIVER_LICENSE">Permis de conduire</option>
              <option value="OTHER">Autre</option>
            </select>
            <div v-if="identityForm.errors.identityType" class="mt-1 text-sm text-red-600">{{ identityForm.errors.identityType }}</div>
          </div>
          <div>
            <label for="identityNumber" class="block text-sm font-medium text-slate-700 mb-2">Numéro du document</label>
            <input
              id="identityNumber"
              v-model="identityForm.identityNumber"
              type="text"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Ex : CI0123456789"
            />
            <div v-if="identityForm.errors.identityNumber" class="mt-1 text-sm text-red-600">{{ identityForm.errors.identityNumber }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div v-for="slot in photoSlots" :key="slot.key">
            <label class="block text-sm font-medium text-slate-700 mb-2">{{ slot.label }}</label>
            <div v-if="identityForm[slot.key]" class="relative mb-2">
              <img :src="identityForm[slot.key]" :alt="slot.label" class="w-full h-32 object-cover rounded-lg border border-slate-200" />
              <button
                type="button"
                @click="identityForm[slot.key] = ''"
                class="absolute top-1 right-1 bg-white/90 rounded-full p-1 text-slate-600 hover:text-red-600 shadow"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
            <input
              v-else
              type="file"
              accept="image/*"
              :disabled="uploadingSlot === slot.key"
              @change="(e) => handlePhotoUpload(e, slot.key)"
              class="w-full text-sm border border-slate-300 rounded-lg px-3 py-2"
            />
            <p v-if="uploadingSlot === slot.key" class="mt-1 text-xs text-slate-500">Envoi en cours...</p>
            <div v-if="identityForm.errors[slot.key]" class="mt-1 text-sm text-red-600">{{ identityForm.errors[slot.key] }}</div>
          </div>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="identityForm.processing || !!uploadingSlot"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="identityForm.processing">Envoi...</span>
            <span v-else>{{ verificationStatus === 'REJECTED' ? 'Resoumettre mes documents' : 'Soumettre mes documents' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { X } from 'lucide-vue-next';
import ProfileLayout from '../../Components/Layouts/ProfileLayout.vue';
import { formatDate } from '../../utils/dates';

defineOptions({
  layout: ProfileLayout,
});

const props = withDefaults(
  defineProps<{
    user?: {
      id?: string | number;
      email?: string;
      name?: string;
      firstName?: string;
      lastName?: string;
      phone?: string;
      address?: string;
      city?: string;
      country?: string;
      role?: string;
      createdAt?: string;
      updatedAt?: string;
    };
    identityVerification?: {
      identityType?: string;
      identityNumber?: string;
      identityPhotoFront?: string;
      identityPhotoBack?: string;
      identityPhotoExtra?: string;
      verificationStatus?: string;
      rejectionReason?: string;
    } | null;
    error?: string;
  }>(),
  { user: () => ({}), identityVerification: null }
);

const user = computed(() => props.user ?? {});

const form = useForm({
  firstName: user.value.firstName ?? '',
  lastName: user.value.lastName ?? '',
  phone: user.value.phone ?? '',
  address: user.value.address ?? '',
  city: user.value.city ?? '',
  country: user.value.country ?? '',
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

watch(
  () => props.user,
  (u) => {
    if (!u) return;
    form.firstName = u.firstName ?? '';
    form.lastName = u.lastName ?? '';
    form.phone = u.phone ?? '';
    form.address = u.address ?? '';
    form.city = u.city ?? '';
    form.country = u.country ?? '';
  },
  { deep: true }
);

const error = computed(() => props.error);

const roleLabel = computed(() => {
  const r = (user.value.role ?? '').toLowerCase();
  if (r === 'admin' || r === 'administrator') return 'Administrateur';
  if (r === 'owner' || r === 'proprietaire' || r === 'propriétaire') return 'Propriétaire';
  return 'Client';
});

const submitForm = () => {
  form.put('/profile', {
    preserveScroll: true,
    onSuccess: () => {
      // Le message de succès sera affiché via flash
    },
  });
};

const submitPasswordForm = () => {
  passwordForm.put('/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
      passwordForm.clearErrors();
    },
  });
};

const resetPasswordForm = () => {
  passwordForm.reset();
  passwordForm.clearErrors();
};

const resetForm = () => {
  const u = user.value;
  form.firstName = u.firstName ?? '';
  form.lastName = u.lastName ?? '';
  form.phone = u.phone ?? '';
  form.address = u.address ?? '';
  form.city = u.city ?? '';
  form.country = u.country ?? '';
  form.clearErrors();
};

// formatDate importé depuis utils/dates (timezone CI, fr-FR)

const isOwner = computed(() => {
  const r = (user.value.role ?? '').toLowerCase();
  return r === 'owner' || r === 'proprietaire' || r === 'propriétaire';
});

const identityVerification = computed(() => props.identityVerification ?? null);

const verificationStatus = computed(() => identityVerification.value?.verificationStatus ?? null);

const rejectionReason = computed(() => identityVerification.value?.rejectionReason ?? null);

const statusLabel = computed(() => {
  switch (verificationStatus.value) {
    case 'VERIFIED': return 'Vérifié';
    case 'PENDING': return 'En attente';
    case 'UNDER_REVIEW': return 'En cours d\'examen';
    case 'REJECTED': return 'Rejeté';
    default: return '';
  }
});

const statusBadgeClass = computed(() => {
  switch (verificationStatus.value) {
    case 'VERIFIED': return 'bg-emerald-100 text-emerald-700';
    case 'PENDING':
    case 'UNDER_REVIEW': return 'bg-amber-100 text-amber-700';
    case 'REJECTED': return 'bg-red-100 text-red-700';
    default: return 'bg-slate-100 text-slate-600';
  }
});

type PhotoSlotKey = 'identityPhotoFront' | 'identityPhotoBack' | 'identityPhotoExtra';

const photoSlots: { key: PhotoSlotKey; label: string }[] = [
  { key: 'identityPhotoFront', label: 'Recto' },
  { key: 'identityPhotoBack', label: 'Verso' },
  { key: 'identityPhotoExtra', label: 'Document supplémentaire (optionnel)' },
];

const identityForm = useForm({
  identityType: 'CNI',
  identityNumber: '',
  identityPhotoFront: '',
  identityPhotoBack: '',
  identityPhotoExtra: '',
});

const uploadingSlot = ref<PhotoSlotKey | null>(null);

const handlePhotoUpload = async (event: Event, slot: PhotoSlotKey) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  if (file.size > 5 * 1024 * 1024) {
    alert('Le fichier est trop volumineux. Taille maximale : 5MB');
    return;
  }

  uploadingSlot.value = slot;

  try {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('category', 'users');

    const response = await axios.post('/profile/images/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (response.data.success && response.data.url) {
      identityForm[slot] = response.data.url;
    } else {
      alert('Erreur lors de l\'upload de l\'image');
    }
  } catch (error: any) {
    console.error('Erreur upload:', error);
    alert('Erreur lors de l\'upload de l\'image: ' + (error.response?.data?.message || error.message));
  } finally {
    uploadingSlot.value = null;
    target.value = '';
  }
};

const submitIdentityForm = () => {
  identityForm.post('/profile/identity-verification', {
    preserveScroll: true,
  });
};
</script>

