<template>
  <div class="legal-root">
    <div class="legal-card">
      <div class="legal-notice">
        ⚠️ Contenu provisoire — ce texte n'a pas encore été validé par un juriste. Il sera remplacé avant la mise en production.
      </div>

      <h1>Conditions générales d'utilisation</h1>
      <p class="legal-updated">Version provisoire</p>

      <section>
        <h2>1. Objet</h2>
        <p>Les présentes conditions générales d'utilisation régissent l'accès et l'usage de la plateforme DodoVroum par ses utilisateurs (clients et propriétaires).</p>
      </section>

      <section>
        <h2>2. Acceptation</h2>
        <p>L'utilisation de la plateforme implique l'acceptation pleine et entière des présentes conditions.</p>
      </section>

      <section>
        <h2>3. Comptes utilisateurs</h2>
        <p>Chaque utilisateur est responsable de la confidentialité de ses identifiants et de l'exactitude des informations fournies lors de son inscription.</p>
      </section>

      <section>
        <h2>4. Réservations et paiements</h2>
        <p>Les modalités de réservation, de paiement et de commission sont définies dans le Contrat de partenariat propriétaire pour les propriétaires, et communiquées aux clients lors de chaque réservation.</p>
      </section>

      <section>
        <h2>5. Données personnelles</h2>
        <p>Le traitement des données personnelles est effectué conformément à la réglementation ivoirienne applicable en matière de protection des données à caractère personnel.</p>
      </section>

      <a href="/login" class="legal-back">← Retour</a>
    </div>
  </div>
</template>

<script setup lang="ts">
import AuthLayout from '../Components/Layouts/AuthLayout.vue';
defineOptions({ layout: AuthLayout });
</script>

<style scoped>
.legal-root {
  min-height: 100dvh;
  display: flex;
  justify-content: center;
  padding: 48px 20px;
  background: #0d2855;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
.legal-card {
  width: 100%;
  max-width: 720px;
  background: rgba(255,255,255,.97);
  border-radius: 20px;
  padding: 40px 44px;
  box-shadow: 0 24px 60px rgba(0,0,0,.35);
}
.legal-notice {
  background: #fff7ed;
  border: 1px solid #fdba74;
  color: #9a3412;
  font-size: .85rem;
  font-weight: 600;
  padding: 12px 16px;
  border-radius: 10px;
  margin-bottom: 28px;
}
h1 { font-size: 1.6rem; font-weight: 800; color: #0d2855; margin: 0 0 4px; }
.legal-updated { color: #64748b; font-size: .82rem; margin: 0 0 28px; }
section { margin-bottom: 22px; }
h2 { font-size: 1rem; font-weight: 700; color: #0d2855; margin: 0 0 8px; }
p { font-size: .9rem; line-height: 1.65; color: #334155; margin: 0; }
.legal-back { display: inline-block; margin-top: 12px; color: #1d6fd4; font-weight: 600; text-decoration: none; font-size: .875rem; }
.legal-back:hover { text-decoration: underline; }
</style>
