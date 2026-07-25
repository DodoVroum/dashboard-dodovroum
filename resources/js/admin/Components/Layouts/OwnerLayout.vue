<template>
  <div class="min-h-screen bg-slate-100 text-slate-900 flex min-w-0">
    <Sidebar :items="navItems" :mobile-items="moreNavItems" :open="sidebarOpen" @close="sidebarOpen = false" />
    <div class="flex-1 flex flex-col min-h-screen min-w-0 w-full">
      <Header />
      <main class="flex-1 p-4 sm:p-6 pb-24 lg:pb-6 space-y-4 sm:space-y-6 overflow-x-hidden">
        <slot />
      </main>
    </div>
    <BottomNavBar :items="bottomNavItems" @open-more="sidebarOpen = true" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import Sidebar from '../Sidebar.vue';
import Header from '../Header.vue';
import BottomNavBar from '../BottomNavBar.vue';
import { usePage } from '@inertiajs/vue3';
import { Home, Building2, Truck, Calendar, Package, Wallet } from 'lucide-vue-next';
import { useAutoRefresh } from '../../composables/useAutoRefresh';

const sidebarOpen = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user);
useAutoRefresh();

// Navigation complète : utilisée telle quelle par le rendu statique desktop du Sidebar.
const navItems = computed(() => [
  { label: 'Tableau de bord', href: '/owner/dashboard', icon: Home },
  { label: 'Finances', href: '/owner/revenue', icon: Wallet },
  { label: 'Mes résidences', href: '/owner/residences', icon: Building2 },
  { label: 'Mes véhicules', href: '/owner/vehicles', icon: Truck },
  { label: 'Mes offres', href: '/owner/combo-offers', icon: Package },
  { label: 'Mes réservations', href: '/owner/bookings', icon: Calendar },
]);

// Entrées visibles directement dans la barre du bas sur mobile (max 4 + "Plus").
const bottomNavItems = computed(() => [
  { label: 'Accueil', href: '/owner/dashboard', icon: Home },
  { label: 'Résidences', href: '/owner/residences', icon: Building2 },
  { label: 'Véhicules', href: '/owner/vehicles', icon: Truck },
  { label: 'Réservations', href: '/owner/bookings', icon: Calendar },
]);

// Entrées reléguées dans le tiroir "Plus" sur mobile, pour ne pas dupliquer
// celles déjà visibles dans bottomNavItems.
const moreNavItems = computed(() => [
  { label: 'Finances', href: '/owner/revenue', icon: Wallet },
  { label: 'Mes offres', href: '/owner/combo-offers', icon: Package },
]);
</script>
