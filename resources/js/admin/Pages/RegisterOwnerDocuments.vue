<template>
  <div class="auth-root">
    <div class="bg-base" />
    <div class="bg-blob  bg-blob--1" />
    <div class="bg-blob  bg-blob--2" />
    <div class="bg-blob  bg-blob--3" />
    <div class="bg-grid" />

    <aside class="brand-panel">
      <div class="brand-inner">
        <div class="logo-wrap anim-in">
          <div class="logo-halo" />
          <div class="logo-card">
            <img :src="logoUrl" alt="DodoVroum" class="logo-img" />
          </div>
        </div>

        <div class="brand-copy anim-in">
          <p class="brand-eyebrow">Étape 2 / 2</p>
          <h1 class="brand-title">
            Vérifiez votre identité<br/>
            <span class="brand-title__hl">pour sécuriser vos réservations.</span>
          </h1>
          <p class="brand-desc">
            Vos documents servent uniquement à confirmer votre identité de propriétaire et à protéger la plateforme contre la fraude. Ils ne sont jamais partagés avec les clients.
          </p>
        </div>
      </div>
    </aside>

    <main class="form-panel">
      <div class="glass-card anim-in">
        <div class="mobile-logo">
          <img :src="logoUrl" alt="DodoVroum" class="mobile-logo__img" />
        </div>

        <header class="card-head">
          <div class="card-badge">
            <span class="card-badge__dot" />
            Vérification d'identité
          </div>
          <h2 class="card-title">Votre pièce d'identité</h2>
          <p class="card-sub">Recto et verso, format image (JPG/PNG), 5&nbsp;Mo max par fichier</p>
        </header>

        <transition name="shake">
          <div v-if="flashSuccess" class="ok-banner" role="status">
            {{ flashSuccess }}
          </div>
        </transition>

        <form @submit.prevent="submitIdentityForm" class="form">
          <div class="field-row">
            <div class="field">
              <label for="identityType" class="field__lbl">Type de document</label>
              <select id="identityType" v-model="identityForm.identityType" class="field__inp field__inp--noicon field__select">
                <option value="CNI">Carte nationale d'identité</option>
                <option value="PASSPORT">Passeport</option>
                <option value="PERMIT">Permis de conduire (pièce)</option>
                <option value="DRIVER_LICENSE">Permis de conduire</option>
                <option value="OTHER">Autre</option>
              </select>
              <p v-if="identityForm.errors.identityType" class="field__msg">{{ identityForm.errors.identityType }}</p>
            </div>

            <div class="field">
              <label for="identityNumber" class="field__lbl">Numéro du document</label>
              <input
                id="identityNumber" v-model="identityForm.identityNumber" type="text"
                placeholder="Ex : CI0123456789" class="field__inp field__inp--noicon"
              />
              <p v-if="identityForm.errors.identityNumber" class="field__msg">{{ identityForm.errors.identityNumber }}</p>
            </div>
          </div>

          <div class="photo-row">
            <div v-for="slot in photoSlots" :key="slot.key" class="photo-slot">
              <label class="field__lbl">{{ slot.label }}</label>

              <div v-if="identityForm[slot.key]" class="photo-preview">
                <img :src="identityForm[slot.key]" :alt="slot.label" />
                <button type="button" class="photo-remove" @click="identityForm[slot.key] = ''" title="Retirer">✕</button>
              </div>
              <label v-else class="photo-drop" :class="{ 'photo-drop--busy': uploadingSlot === slot.key }">
                <input type="file" accept="image/*" :disabled="uploadingSlot === slot.key" @change="(e) => handlePhotoUpload(e, slot.key)" />
                <span v-if="uploadingSlot === slot.key">Envoi…</span>
                <span v-else>Choisir un fichier</span>
              </label>
              <p v-if="identityForm.errors[slot.key]" class="field__msg">{{ identityForm.errors[slot.key] }}</p>
            </div>
          </div>

          <button type="submit" :disabled="identityForm.processing || !!uploadingSlot" class="cta-btn">
            <span v-if="!identityForm.processing" class="cta-btn__inner">Envoyer mes documents</span>
            <span v-else class="cta-btn__spin">
              <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="45"/></svg>
              Envoi…
            </span>
          </button>

          <p class="switch-link">
            <a href="/owner/dashboard">Plus tard, aller à mon espace →</a>
          </p>
        </form>

        <p class="card-footer">© {{ new Date().getFullYear() }} DodoVroum · Plateforme de gestion locative</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthLayout from '../Components/Layouts/AuthLayout.vue';
import logoUrl from '../assets/logo.png';

defineOptions({ layout: AuthLayout });

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

const flashSuccess = computed(() => {
  const flash = (usePage().props as any).flash ?? {};
  return flash.success as string | undefined;
});

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
      alert("Erreur lors de l'upload de l'image");
    }
  } catch (error: any) {
    alert("Erreur lors de l'upload de l'image : " + (error.response?.data?.message || error.message));
  } finally {
    uploadingSlot.value = null;
    target.value = '';
  }
};

const submitIdentityForm = () => {
  identityForm.post('/profile/identity-verification', {
    preserveScroll: true,
    onSuccess: () => router.visit('/owner/dashboard'),
  });
};
</script>

<style scoped>
.auth-root { position: relative; display: flex; min-height: 100dvh; font-family: 'Inter', system-ui, -apple-system, sans-serif; overflow: hidden; }
.bg-base { position: fixed; inset: 0; z-index: 0; background: linear-gradient(135deg, #0a1628 0%, #0d2855 45%, #1a4a9e 100%); background-size: 250% 250%; animation: bgDrift 20s ease infinite; }
@keyframes bgDrift { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.bg-blob { position: fixed; border-radius: 50%; filter: blur(88px); pointer-events: none; z-index: 1; }
.bg-blob--1 { width: 520px; height: 520px; background: radial-gradient(circle, rgba(29,111,212,.5) 0%, transparent 70%); top: -100px; left: -80px; animation: blob1 15s ease-in-out infinite; }
.bg-blob--2 { width: 380px; height: 380px; background: radial-gradient(circle, rgba(249,115,22,.28) 0%, transparent 70%); bottom: 10%; right: -40px; animation: blob2 11s ease-in-out infinite; }
.bg-blob--3 { width: 300px; height: 300px; background: radial-gradient(circle, rgba(29,111,212,.22) 0%, transparent 70%); bottom: -60px; left: 28%; animation: blob3 18s ease-in-out infinite; }
@keyframes blob1 { 0%,100%{transform:translate(0,0)}40%{transform:translate(28px,-22px)}70%{transform:translate(-12px,18px)} }
@keyframes blob2 { 0%,100%{transform:translate(0,0)}50%{transform:translate(-20px,24px)} }
@keyframes blob3 { 0%,100%{transform:translate(0,0)}45%{transform:translate(18px,-14px)} }
.bg-grid { position: fixed; inset: 0; z-index: 2; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size: 52px 52px; }

.logo-wrap, .brand-copy { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .65s cubic-bezier(.34,1.4,.64,1); }
.brand-copy { transition-delay: .1s; }
.anim-in { opacity: 1 !important; transform: translateY(0) !important; }

.brand-panel { display: none; position: relative; z-index: 10; flex: 0 0 54%; align-items: center; justify-content: center; padding: 56px 48px; }
@media (min-width: 1024px) { .brand-panel { display: flex; } }
.brand-inner { position: relative; width: 100%; max-width: 470px; display: flex; flex-direction: column; gap: 32px; }
.logo-wrap { position: relative; align-self: flex-start; }
.logo-halo { position: absolute; inset: -24px; background: radial-gradient(ellipse, rgba(29,111,212,.35) 0%, rgba(249,115,22,.12) 50%, transparent 70%); filter: blur(20px); border-radius: 50%; pointer-events: none; }
.logo-card { position: relative; background: rgba(255,255,255,.97); border-radius: 22px; padding: 22px 34px; box-shadow: 0 0 0 1px rgba(255,255,255,.12), 0 12px 40px rgba(0,0,0,.35), 0 0 56px rgba(29,111,212,.22); }
.logo-img { display: block; width: 196px; height: auto; }

.brand-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: .68rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: #f97316; margin: 0 0 12px; }
.brand-eyebrow::before { content: ''; display: block; width: 18px; height: 1.5px; background: #f97316; border-radius: 2px; }
.brand-title { font-size: clamp(1.55rem, 2.4vw, 2rem); font-weight: 800; line-height: 1.22; letter-spacing: -.035em; color: rgba(255,255,255,.94); margin: 0 0 14px; }
.brand-title__hl { background: linear-gradient(90deg, #f97316 30%, #1d6fd4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.brand-desc { font-size: .9rem; line-height: 1.65; color: rgba(255,255,255,.42); margin: 0; }

.form-panel { flex: 1; position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
.glass-card { position: relative; width: 100%; max-width: 520px; background: rgba(255,255,255,.06); backdrop-filter: blur(22px) saturate(1.3); -webkit-backdrop-filter: blur(22px) saturate(1.3); border: 1px solid rgba(255,255,255,.1); border-radius: 28px; padding: 40px 36px; box-shadow: inset 0 0 0 1px rgba(255,255,255,.05), 0 32px 72px rgba(0,0,0,.45), 0 8px 24px rgba(0,0,0,.3); opacity: 0; transform: translateY(32px) scale(.985); transition: opacity .55s ease, transform .7s cubic-bezier(.34,1.35,.64,1); }
.glass-card.anim-in { opacity: 1; transform: translateY(0) scale(1); }
.glass-card::before { content: ''; position: absolute; top: 0; left: 12%; right: 12%; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent); }

.mobile-logo { display: flex; justify-content: center; margin-bottom: 24px; }
.mobile-logo__img { width: 148px; height: auto; filter: drop-shadow(0 0 14px rgba(29,111,212,.35)); }
@media (min-width: 1024px) { .mobile-logo { display: none; } }

.card-head { margin-bottom: 22px; }
.card-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 11px; background: rgba(249,115,22,.12); border: 1px solid rgba(249,115,22,.25); border-radius: 100px; font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #f97316; margin-bottom: 14px; }
.card-badge__dot { width: 6px; height: 6px; background: #f97316; border-radius: 50%; box-shadow: 0 0 6px rgba(249,115,22,.6); animation: dotPulse 2.4s ease-in-out infinite; }
@keyframes dotPulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.55; transform:scale(1.35); } }
.card-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -.04em; color: rgba(255,255,255,.95); margin: 0 0 6px; }
.card-sub { font-size: .85rem; color: rgba(255,255,255,.4); margin: 0; }

.ok-banner { padding: 11px 15px; background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); border-radius: 12px; color: #6ee7b7; font-size: .85rem; font-weight: 500; margin-bottom: 18px; }

.form { display: flex; flex-direction: column; gap: 18px; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field__lbl { font-size: .72rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: rgba(255,255,255,.5); }
.field__inp { width: 100%; padding: 12px 13px; background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.1); border-radius: 12px; font-size: .875rem; font-family: inherit; color: rgba(255,255,255,.92); outline: none; transition: border-color .18s, background .18s; }
.field__select { appearance: none; }
.field__select option { color: #0d2855; }
.field__inp:focus { background: rgba(255,255,255,.1); border-color: rgba(249,115,22,.65); }
.field__msg { font-size: .76rem; color: #fca5a5; font-weight: 500; margin: 0; }

.photo-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
.photo-slot { display: flex; flex-direction: column; gap: 6px; }
.photo-drop { display: flex; align-items: center; justify-content: center; text-align: center; padding: 18px 8px; border: 1.5px dashed rgba(255,255,255,.25); border-radius: 12px; font-size: .78rem; color: rgba(255,255,255,.5); cursor: pointer; transition: border-color .18s, background .18s; min-height: 84px; }
.photo-drop:hover { border-color: rgba(249,115,22,.6); background: rgba(249,115,22,.06); }
.photo-drop--busy { opacity: .6; pointer-events: none; }
.photo-drop input { display: none; }
.photo-preview { position: relative; }
.photo-preview img { width: 100%; height: 84px; object-fit: cover; border-radius: 12px; border: 1px solid rgba(255,255,255,.15); }
.photo-remove { position: absolute; top: 4px; right: 4px; width: 22px; height: 22px; border-radius: 50%; border: none; background: rgba(0,0,0,.55); color: #fff; font-size: .7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.photo-remove:hover { background: rgba(220,38,38,.85); }

.cta-btn { width: 100%; padding: 14px; margin-top: 4px; border: none; border-radius: 13px; cursor: pointer; font-family: inherit; font-size: .92rem; font-weight: 700; letter-spacing: .01em; color: #fff; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 4px 16px rgba(249,115,22,.32), 0 1px 3px rgba(0,0,0,.2); transition: transform .14s ease, filter .18s; }
.cta-btn:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.06); }
.cta-btn:disabled { opacity: .65; cursor: not-allowed; }
.cta-btn__inner, .cta-btn__spin { display: flex; align-items: center; justify-content: center; gap: 9px; }
.cta-btn__spin svg { width: 18px; height: 18px; animation: spin .7s linear infinite; stroke: white; }
@keyframes spin { to { transform: rotate(360deg); } }

.switch-link { text-align: center; font-size: .82rem; margin: 2px 0 0; }
.switch-link a { color: rgba(255,255,255,.45); font-weight: 600; text-decoration: none; }
.switch-link a:hover { color: #f97316; text-decoration: underline; }

.card-footer { text-align: center; font-size: .71rem; color: rgba(255,255,255,.18); margin: 22px 0 0; }

.shake-enter-active { transition: opacity .3s ease; }
.shake-leave-active { transition: opacity .18s; }
.shake-leave-to, .shake-enter-from { opacity: 0; }

@media (max-width: 500px) {
  .glass-card { padding: 28px 20px; border-radius: 22px; }
  .card-title { font-size: 1.4rem; }
  .field-row { grid-template-columns: 1fr; }
  .photo-row { grid-template-columns: 1fr 1fr; }
}
</style>
