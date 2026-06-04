<template>
  <div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0">
        <div class="flex items-center gap-2 mb-1">
          <a href="/owner/combo-offers" class="text-sm text-slate-500 hover:text-slate-700 flex items-center gap-1 transition-colors">
            <ChevronLeft class="w-4 h-4" />
            Mes offres combinées
          </a>
        </div>
        <h1 class="text-xl sm:text-2xl font-bold text-slate-900">Offres combinées archivées</h1>
        <p class="text-sm text-slate-500 mt-0.5">Offres désactivées — réactivez-les pour les remettre en ligne</p>
      </div>
    </div>

    <!-- Flash messages -->
    <div v-if="$page.props.flash?.success" class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ error }}
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
      <!-- État vide -->
      <div v-if="comboOffers.length === 0 && !error" class="p-12 text-center">
        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
          <Archive class="w-8 h-8 text-slate-400" />
        </div>
        <h3 class="text-lg font-medium text-slate-900 mb-2">Aucune offre archivée</h3>
        <p class="text-slate-500 mb-4">Vos offres désactivées apparaîtront ici.</p>
        <a href="/owner/combo-offers" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition inline-block text-sm font-medium">
          Voir mes offres actives
        </a>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Offre</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Résidence</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Véhicule</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Prix</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Statut</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr
              v-for="offer in comboOffers"
              :key="offer.id"
              class="hover:bg-slate-50 transition-colors opacity-80"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center flex-shrink-0">
                    <Package class="w-5 h-5 text-slate-400" />
                  </div>
                  <span class="text-sm font-medium text-slate-900">{{ offer.title || 'Offre sans nom' }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ offer.residenceName }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ offer.vehicleName }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-700">{{ formatPrice(offer.price || 0) }} CFA</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">
                  <Archive class="w-3 h-3" />
                  Archivé
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right" @click.stop>
                <div class="flex items-center justify-end gap-2">
                  <a
                    :href="`/owner/combo-offers/${offer.id}`"
                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors inline-flex"
                    title="Voir"
                  >
                    <Eye class="w-4 h-4" />
                  </a>
                  <form :action="`/owner/combo-offers/${offer.id}/reactivate`" method="POST">
                    <input type="hidden" name="_token" :value="csrfToken()" />
                    <input type="hidden" name="_method" value="PATCH" />
                    <button
                      type="submit"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors"
                    >
                      <RotateCcw class="w-3.5 h-3.5" />
                      Réactiver
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination
        v-if="pagination && comboOffers.length > 0"
        :pagination="pagination"
        route-name="owner.combo-offers.archived"
        :filters="{}"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Package, Eye, Archive, RotateCcw, ChevronLeft } from 'lucide-vue-next';
import Pagination from '../../../Components/Pagination.vue';
import OwnerLayout from '../../../Components/Layouts/OwnerLayout.vue';

defineOptions({ layout: OwnerLayout });

defineProps<{
  comboOffers: Array<{
    id: number | string;
    title?: string;
    residenceName?: string;
    vehicleName?: string;
    price?: number;
  }>;
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  } | null;
  error?: string;
}>();

const page = usePage();
const csrfToken = () => (page.props as any).csrf_token as string;

const formatPrice = (price: number): string => new Intl.NumberFormat('fr-FR').format(price);
</script>
