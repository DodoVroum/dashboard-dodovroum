<template>
  <div class="auth-root">

    <!-- ─── Background : blobs existants + grille ─── -->
    <div class="bg-base" />
    <div class="bg-blob  bg-blob--1" />
    <div class="bg-blob  bg-blob--2" />
    <div class="bg-blob  bg-blob--3" />
    <div class="bg-grid" />

    <!-- ─────────────────────────────────────────
         LEFT PANEL — branding
    ───────────────────────────────────────────── -->
    <aside class="brand-panel">
      <div class="brand-inner">

        <!-- Logo -->
        <div class="logo-wrap" :class="{ 'anim-in': mounted }">
          <div class="logo-halo" />
          <div class="logo-card">
            <img :src="logoUrl" alt="DodoVroum" class="logo-img" />
          </div>
        </div>

        <!-- Accroche -->
        <div class="brand-copy" :class="{ 'anim-in': mounted }">
          <p class="brand-eyebrow">Plateforme de gestion locative</p>
          <h1 class="brand-title">
            Gérez vos locations,<br/>
            véhicules et réservations<br/>
            <span class="brand-title__hl">en temps réel.</span>
          </h1>
          <p class="brand-desc">
            Tout ce dont vous avez besoin pour piloter votre patrimoine locatif depuis un seul endroit.
          </p>
        </div>

        <!-- Features -->
        <ul class="feat-list" :class="{ 'anim-in': mounted }">
          <li class="feat-item">
            <span class="feat-icon feat-icon--blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </span>
            <span class="feat-text">
              <strong>Résidences</strong>
              <span>Suivi des biens et des occupants</span>
            </span>
            <span class="feat-check">
              <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 7l3 3L11.5 4"/></svg>
            </span>
          </li>
          <li class="feat-item">
            <span class="feat-icon feat-icon--orange">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 6h-2l-3 7H3l1 4h14l1-4h-3l-3-7z"/></svg>
            </span>
            <span class="feat-text">
              <strong>Véhicules</strong>
              <span>Flotte et disponibilités en direct</span>
            </span>
            <span class="feat-check">
              <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 7l3 3L11.5 4"/></svg>
            </span>
          </li>
          <li class="feat-item">
            <span class="feat-icon feat-icon--blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <span class="feat-text">
              <strong>Réservations</strong>
              <span>Calendrier centralisé et alertes</span>
            </span>
            <span class="feat-check">
              <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 7l3 3L11.5 4"/></svg>
            </span>
          </li>
        </ul>

        <!-- Stats -->
        <div class="stats" :class="{ 'anim-in': mounted }">
          <div class="stat">
            <span class="stat-n">500<em>+</em></span>
            <span class="stat-l">Propriétaires</span>
          </div>
          <div class="stat-sep" />
          <div class="stat">
            <span class="stat-n">2 400<em>+</em></span>
            <span class="stat-l">Biens gérés</span>
          </div>
          <div class="stat-sep" />
          <div class="stat">
            <span class="stat-n">99.9<em>%</em></span>
            <span class="stat-l">Disponibilité</span>
          </div>
        </div>

        <p class="brand-footer">© {{ new Date().getFullYear() }} DodoVroum — Tous droits réservés</p>
      </div>
    </aside>

    <!-- ─────────────────────────────────────────
         RIGHT PANEL — formulaire
    ───────────────────────────────────────────── -->
    <main class="form-panel">
      <div class="glass-card" :class="{ 'anim-in': mounted }">

        <!-- Logo mobile uniquement -->
        <div class="mobile-logo">
          <img :src="logoUrl" alt="DodoVroum" class="mobile-logo__img" />
        </div>

        <!-- En-tête -->
        <header class="card-head">
          <div class="card-badge">
            <span class="card-badge__dot" />
            Connexion sécurisée
          </div>
          <h2 class="card-title">Bon retour !</h2>
          <p class="card-sub">Accédez à votre espace de gestion</p>
        </header>

        <!-- Restauration en cours (remember me) -->
        <div v-if="isRestoring" class="restoring">
          <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="45"/></svg>
          Restauration de la session…
        </div>

        <!-- Bannière d'erreur globale -->
        <transition name="shake">
          <div v-if="globalError" class="err-banner" role="alert">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span>{{ globalError }}</span>
          </div>
        </transition>

        <!-- Formulaire -->
        <form @submit.prevent="submit" class="form" novalidate>

          <!-- Email -->
          <div class="field" :class="{ 'field--focus': focused === 'email', 'field--err': form.errors.email }">
            <label for="email" class="field__lbl">Adresse email</label>
            <div class="field__row">
              <span class="field__ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              </span>
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                placeholder="votre@email.com"
                class="field__inp"
                @focus="focused = 'email'"
                @blur="focused = null"
              />
            </div>
            <transition name="msg">
              <p v-if="form.errors.email" class="field__msg">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 118 1a7 7 0 010 14zm0-3a.75.75 0 100-1.5.75.75 0 000 1.5zm.75-5.25a.75.75 0 10-1.5 0v2.5a.75.75 0 001.5 0v-2.5z"/></svg>
                {{ form.errors.email }}
              </p>
            </transition>
          </div>

          <!-- Mot de passe -->
          <div class="field" :class="{ 'field--focus': focused === 'password', 'field--err': form.errors.password }">
            <label for="password" class="field__lbl">Mot de passe</label>
            <div class="field__row">
              <span class="field__ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </span>
              <input
                id="password"
                v-model="form.password"
                name="password"
                :type="showPwd ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="field__inp field__inp--pwd"
                @focus="focused = 'password'"
                @blur="focused = null"
              />
              <button
                type="button"
                class="field__eye"
                :title="showPwd ? 'Masquer' : 'Afficher'"
                @click="showPwd = !showPwd"
              >
                <svg v-if="!showPwd" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
              </button>
            </div>
            <transition name="msg">
              <p v-if="form.errors.password" class="field__msg">
                <svg viewBox="0 0 16 16" fill="currentColor"><path d="M8 15A7 7 0 118 1a7 7 0 010 14zm0-3a.75.75 0 100-1.5.75.75 0 000 1.5zm.75-5.25a.75.75 0 10-1.5 0v2.5a.75.75 0 001.5 0v-2.5z"/></svg>
                {{ form.errors.password }}
              </p>
            </transition>
          </div>

          <!-- Se souvenir -->
          <label class="remember">
            <input id="remember" v-model="form.remember" name="remember" type="checkbox" class="remember__native" />
            <span class="remember__box" :class="{ 'remember__box--on': form.remember }">
              <svg v-if="form.remember" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
            </span>
            <span class="remember__lbl">Se souvenir de moi</span>
          </label>

          <!-- Bouton -->
          <button
            type="submit"
            :disabled="form.processing"
            class="cta-btn"
          >
            <span v-if="!form.processing" class="cta-btn__inner">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
              Se connecter
            </span>
            <span v-else class="cta-btn__spin">
              <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="45"/></svg>
              Connexion…
            </span>
          </button>

        </form>

        <p class="card-footer">© {{ new Date().getFullYear() }} DodoVroum · Plateforme de gestion locative</p>
      </div>
    </main>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthLayout from '../Components/Layouts/AuthLayout.vue';
import logoUrl from '../assets/logo.png';

defineOptions({ layout: AuthLayout });

const form         = useForm({ email: '', password: '', remember: false });
const showPwd      = ref(false);
const focused      = ref<string | null>(null);
const mounted      = ref(false);
const isRestoring  = ref(false);

const globalError = computed(() =>
  form.errors.email && !form.email.trim() ? form.errors.email : null
);

onMounted(() => {
    setTimeout(() => { mounted.value = true; }, 60);

    const flash = (usePage().props as any).flash ?? {};

    // Logout : le backend demande de vider le stockage
    if (flash.clearAuthStorage) {
        localStorage.removeItem('dvr_auth');
        sessionStorage.removeItem('dvr_auth');
        delete axios.defaults.headers.common['X-Api-Token'];
        return;
    }

    // Tentative de restauration silencieuse depuis localStorage (remember me)
    try {
        const raw = localStorage.getItem('dvr_auth');
        if (raw) {
            const auth = JSON.parse(raw);
            if (auth?.token) {
                isRestoring.value = true;
                router.post('/auth/restore', { token: auth.token }, {
                    onSuccess: () => { /* le backend redirige vers le dashboard */ },
                    onError: () => {
                        // Token expiré ou invalide → on vide
                        localStorage.removeItem('dvr_auth');
                        delete axios.defaults.headers.common['X-Api-Token'];
                    },
                    onFinish: () => { isRestoring.value = false; },
                });
            }
        }
    } catch {
        localStorage.removeItem('dvr_auth');
    }
});

const submit = () => form.post('/login', {
    onSuccess: (page) => {
        const flash = (page.props as any).flash ?? {};
        const token = flash.token as string | undefined;

        if (token) {
            const authData = JSON.stringify({
                token,
                user: (() => { try { return JSON.parse(flash.user ?? 'null'); } catch { return null; } })(),
            });

            if (form.remember) {
                localStorage.setItem('dvr_auth', authData);
                sessionStorage.removeItem('dvr_auth');
            } else {
                sessionStorage.setItem('dvr_auth', authData);
                localStorage.removeItem('dvr_auth');
            }

            // Mettre à jour le header axios pour les requêtes suivantes
            axios.defaults.headers.common['X-Api-Token'] = token;
        }

        form.reset('password');
    },
});
</script>

<style scoped>
/* ══════════════════════════════════════════
   PALETTE — couleurs existantes du projet
   ──────────────────────────────────────────
   Bleu foncé  : #0a1628  #0d2855  #1a4a9e
   Bleu brand  : #1d6fd4
   Orange brand: #f97316  #ea580c
   Blanc/trans : rgba(255,255,255,…)
   Erreur      : #dc2626  #f87171
   ══════════════════════════════════════════ */

/* ── Root ── */
.auth-root {
  position: relative;
  display: flex;
  min-height: 100dvh;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  overflow: hidden;
}

/* ── Background ── */
.bg-base {
  position: fixed;
  inset: 0;
  z-index: 0;
  /* gradient existant du left-panel, étendu à toute la page */
  background: linear-gradient(135deg, #0a1628 0%, #0d2855 45%, #1a4a9e 100%);
  background-size: 250% 250%;
  animation: bgDrift 20s ease infinite;
}
@keyframes bgDrift {
  0%,100% { background-position: 0% 50%; }
  50%      { background-position: 100% 50%; }
}

/* Blobs — couleurs bleue et orange existantes */
.bg-blob {
  position: fixed;
  border-radius: 50%;
  filter: blur(88px);
  pointer-events: none;
  z-index: 1;
}
.bg-blob--1 {
  width: 520px; height: 520px;
  background: radial-gradient(circle, rgba(29,111,212,.5) 0%, transparent 70%);
  top: -100px; left: -80px;
  animation: blob1 15s ease-in-out infinite;
}
.bg-blob--2 {
  width: 380px; height: 380px;
  background: radial-gradient(circle, rgba(249,115,22,.28) 0%, transparent 70%);
  bottom: 10%; right: -40px;
  animation: blob2 11s ease-in-out infinite;
}
.bg-blob--3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, rgba(29,111,212,.22) 0%, transparent 70%);
  bottom: -60px; left: 28%;
  animation: blob3 18s ease-in-out infinite;
}
@keyframes blob1 { 0%,100%{transform:translate(0,0)}40%{transform:translate(28px,-22px)}70%{transform:translate(-12px,18px)} }
@keyframes blob2 { 0%,100%{transform:translate(0,0)}50%{transform:translate(-20px,24px)} }
@keyframes blob3 { 0%,100%{transform:translate(0,0)}45%{transform:translate(18px,-14px)} }

/* Grille subtile — existante */
.bg-grid {
  position: fixed;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  background-image:
    linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
  background-size: 52px 52px;
}

/* ── Entrée animée ── */
.logo-wrap,
.brand-copy,
.feat-list,
.stats       { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .65s cubic-bezier(.34,1.4,.64,1); }
.brand-copy  { transition-delay: .1s; }
.feat-list   { transition-delay: .2s; }
.stats       { transition-delay: .3s; }
.anim-in     { opacity: 1 !important; transform: translateY(0) !important; }

/* ══════════════════════════════════════════
   LEFT PANEL
   ══════════════════════════════════════════ */
.brand-panel {
  display: none;
  position: relative;
  z-index: 10;
  flex: 0 0 54%;
  align-items: center;
  justify-content: center;
  padding: 56px 48px;
}
@media (min-width: 1024px) { .brand-panel { display: flex; } }

.brand-inner {
  position: relative;
  width: 100%;
  max-width: 470px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Logo */
.logo-wrap { position: relative; align-self: flex-start; }

.logo-halo {
  position: absolute;
  inset: -24px;
  background: radial-gradient(ellipse, rgba(29,111,212,.35) 0%, rgba(249,115,22,.12) 50%, transparent 70%);
  filter: blur(20px);
  border-radius: 50%;
  pointer-events: none;
}

.logo-card {
  position: relative;
  background: rgba(255,255,255,.97);
  border-radius: 22px;
  padding: 22px 34px;
  box-shadow:
    0 0 0 1px rgba(255,255,255,.12),
    0 12px 40px rgba(0,0,0,.35),
    0 0 56px rgba(29,111,212,.22);
  transition: transform .3s ease, box-shadow .3s ease;
}
.logo-card:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow:
    0 0 0 1px rgba(255,255,255,.16),
    0 20px 56px rgba(0,0,0,.4),
    0 0 72px rgba(29,111,212,.28);
}
.logo-img { display: block; width: 196px; height: auto; }

/* Copy */
.brand-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .13em;
  text-transform: uppercase;
  color: #f97316;
  margin: 0 0 12px;
}
.brand-eyebrow::before {
  content: '';
  display: block;
  width: 18px; height: 1.5px;
  background: #f97316;
  border-radius: 2px;
}

.brand-title {
  font-size: clamp(1.55rem, 2.4vw, 2rem);
  font-weight: 800;
  line-height: 1.22;
  letter-spacing: -.035em;
  color: rgba(255,255,255,.94);
  margin: 0 0 14px;
}
.brand-title__hl {
  /* dégradé uniquement avec les couleurs existantes */
  background: linear-gradient(90deg, #f97316 30%, #1d6fd4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-desc {
  font-size: .9rem;
  line-height: 1.65;
  color: rgba(255,255,255,.42);
  margin: 0;
}

/* Feature list */
.feat-list {
  list-style: none;
  margin: 0; padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.feat-item {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 13px 15px;
  border-radius: 14px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
  transition: background .2s, border-color .2s, transform .2s;
}
.feat-item:hover {
  background: rgba(255,255,255,.07);
  border-color: rgba(255,255,255,.11);
  transform: translateX(4px);
}

.feat-icon {
  flex-shrink: 0;
  width: 38px; height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.feat-icon svg { width: 17px; height: 17px; }
/* bleu existant */
.feat-icon--blue   { background: rgba(29,111,212,.2);  color: rgba(109,171,235,.95); }
/* orange existant */
.feat-icon--orange { background: rgba(249,115,22,.18); color: rgba(249,140,60,.95); }

.feat-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.feat-text strong { font-size: .875rem; font-weight: 600; color: rgba(255,255,255,.88); }
.feat-text span   { font-size: .775rem; color: rgba(255,255,255,.38); }

.feat-check {
  flex-shrink: 0;
  width: 20px; height: 20px;
  border-radius: 50%;
  background: rgba(249,115,22,.15);
  color: #f97316;
  display: flex;
  align-items: center;
  justify-content: center;
}
.feat-check svg { width: 11px; height: 11px; }

/* Stats */
.stats {
  display: flex;
  align-items: center;
  padding: 18px 22px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 16px;
  backdrop-filter: blur(8px);
}

.stat { flex: 1; text-align: center; }
.stat-n {
  display: block;
  font-size: 1.3rem;
  font-weight: 800;
  letter-spacing: -.03em;
  color: rgba(255,255,255,.9);
  line-height: 1;
  margin-bottom: 4px;
}
.stat-n em { font-style: normal; color: #f97316; font-size: 1rem; }
.stat-l {
  font-size: .7rem;
  color: rgba(255,255,255,.35);
  text-transform: uppercase;
  letter-spacing: .06em;
  font-weight: 500;
}
.stat-sep { width: 1px; height: 32px; background: rgba(255,255,255,.1); flex-shrink: 0; }

/* Footer */
.brand-footer {
  position: absolute;
  bottom: -24px;
  left: 0; right: 0;
  font-size: .7rem;
  color: rgba(255,255,255,.2);
  text-align: center;
  margin: 0;
}

/* ══════════════════════════════════════════
   RIGHT PANEL — glassmorphism
   ══════════════════════════════════════════ */
.form-panel {
  flex: 1;
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
}

.glass-card {
  position: relative;
  width: 100%;
  max-width: 426px;
  /* glassmorphism sur fond bleu existant */
  background: rgba(255,255,255,.06);
  backdrop-filter: blur(22px) saturate(1.3);
  -webkit-backdrop-filter: blur(22px) saturate(1.3);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 28px;
  padding: 44px 40px;
  box-shadow:
    inset 0 0 0 1px rgba(255,255,255,.05),
    0 32px 72px rgba(0,0,0,.45),
    0 8px 24px rgba(0,0,0,.3);
  opacity: 0;
  transform: translateY(32px) scale(.985);
  transition: opacity .55s ease, transform .7s cubic-bezier(.34,1.35,.64,1);
}
.glass-card.anim-in { opacity: 1; transform: translateY(0) scale(1); }

/* Ligne lumineuse en haut */
.glass-card::before {
  content: '';
  position: absolute;
  top: 0; left: 12%; right: 12%;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
}

/* Mobile logo */
.mobile-logo { display: flex; justify-content: center; margin-bottom: 28px; }
.mobile-logo__img {
  width: 148px; height: auto;
  filter: drop-shadow(0 0 14px rgba(29,111,212,.35));
}
@media (min-width: 1024px) { .mobile-logo { display: none; } }

/* Header */
.card-head { margin-bottom: 26px; }

.card-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 11px;
  background: rgba(249,115,22,.12);
  border: 1px solid rgba(249,115,22,.25);
  border-radius: 100px;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: #f97316;
  margin-bottom: 14px;
}
.card-badge__dot {
  width: 6px; height: 6px;
  background: #f97316;
  border-radius: 50%;
  box-shadow: 0 0 6px rgba(249,115,22,.6);
  animation: dotPulse 2.4s ease-in-out infinite;
}
@keyframes dotPulse {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.55; transform:scale(1.35); }
}

.card-title {
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -.04em;
  color: rgba(255,255,255,.95);
  margin: 0 0 6px;
}
.card-sub {
  font-size: .875rem;
  color: rgba(255,255,255,.4);
  margin: 0;
}

/* Bannière d'erreur */
.err-banner {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 11px 15px;
  background: rgba(220,38,38,.12);
  border: 1px solid rgba(220,38,38,.28);
  border-radius: 12px;
  color: #fca5a5;
  font-size: .85rem;
  font-weight: 500;
  margin-bottom: 18px;
}
.err-banner svg { flex-shrink:0; width:16px; height:16px; }

/* ── Champs ── */
.form { display: flex; flex-direction: column; gap: 18px; }
.field { display: flex; flex-direction: column; gap: 7px; }

.field__lbl {
  font-size: .75rem;
  font-weight: 600;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: rgba(255,255,255,.5);
  transition: color .18s;
}
.field--focus .field__lbl { color: rgba(249,115,22,.85); }
.field--err   .field__lbl { color: rgba(248,113,113,.85); }

.field__row {
  position: relative;
  display: flex;
  align-items: center;
}

.field__ico {
  position: absolute;
  left: 13px;
  color: rgba(255,255,255,.22);
  pointer-events: none;
  display: flex;
  transition: color .18s;
}
.field__ico svg { width: 17px; height: 17px; }
.field--focus .field__ico { color: rgba(249,115,22,.65); }
.field--err   .field__ico { color: rgba(248,113,113,.65); }

.field__inp {
  width: 100%;
  padding: 13px 13px 13px 43px;
  background: rgba(255,255,255,.07);
  border: 1.5px solid rgba(255,255,255,.1);
  border-radius: 13px;
  font-size: .9rem;
  font-family: inherit;
  color: rgba(255,255,255,.92);
  outline: none;
  transition: border-color .18s, background .18s, box-shadow .18s;
  -webkit-appearance: none;
}
.field__inp::placeholder { color: rgba(255,255,255,.2); }
/* Fix autofill couleur */
.field__inp:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 40px #0d2855 inset !important;
  -webkit-text-fill-color: rgba(255,255,255,.92) !important;
}
.field__inp:focus {
  background: rgba(255,255,255,.1);
  border-color: rgba(249,115,22,.65);
  box-shadow: 0 0 0 3px rgba(249,115,22,.14), 0 0 0 1px rgba(249,115,22,.25) inset;
}
.field--err .field__inp {
  border-color: rgba(248,113,113,.45);
  background: rgba(220,38,38,.07);
}
.field--err .field__inp:focus {
  border-color: rgba(248,113,113,.75);
  box-shadow: 0 0 0 3px rgba(220,38,38,.1);
}
.field__inp--pwd { padding-right: 44px; }

/* Toggle password */
.field__eye {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  cursor: pointer;
  color: rgba(255,255,255,.26);
  padding: 4px;
  border-radius: 7px;
  display: flex;
  align-items: center;
  transition: color .18s, background .18s;
}
.field__eye:hover {
  color: rgba(249,115,22,.8);
  background: rgba(249,115,22,.08);
}
.field__eye svg { width: 17px; height: 17px; }

/* Message d'erreur inline */
.field__msg {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: .775rem;
  color: #fca5a5;
  font-weight: 500;
  margin: 0;
}
.field__msg svg { width: 13px; height: 13px; flex-shrink: 0; }

/* Se souvenir */
.remember {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  cursor: pointer;
  user-select: none;
}
.remember__native { position: absolute; opacity: 0; width: 0; height: 0; }
.remember__box {
  flex-shrink: 0;
  width: 18px; height: 18px;
  border-radius: 5px;
  border: 1.5px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.07);
  display: flex; align-items: center; justify-content: center;
  transition: all .18s;
}
.remember__box--on {
  background: #f97316;
  border-color: #f97316;
  box-shadow: 0 0 8px rgba(249,115,22,.35);
}
.remember__box svg { width: 10px; height: 10px; color: #fff; }
.remember__lbl { font-size: .85rem; color: rgba(255,255,255,.42); }

/* ── Bouton — couleur existante #f97316→#ea580c ── */
.cta-btn {
  width: 100%;
  padding: 14px;
  margin-top: 4px;
  border: none;
  border-radius: 13px;
  cursor: pointer;
  font-family: inherit;
  font-size: .95rem;
  font-weight: 700;
  letter-spacing: .01em;
  color: #fff;
  /* gradient orange existant */
  background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.18),
    0 4px 16px rgba(249,115,22,.32),
    0 1px 3px rgba(0,0,0,.2);
  transition: transform .14s ease, box-shadow .18s ease, filter .18s;
  position: relative;
  overflow: hidden;
}
/* Brillance interne */
.cta-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255,255,255,.14) 0%, transparent 55%);
  border-radius: inherit;
  pointer-events: none;
}
.cta-btn:hover:not(:disabled) {
  transform: translateY(-2px) scale(1.008);
  filter: brightness(1.06);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.2),
    0 8px 28px rgba(249,115,22,.42),
    0 2px 6px rgba(0,0,0,.25);
}
.cta-btn:active:not(:disabled) {
  transform: translateY(0) scale(.995);
  box-shadow: 0 2px 8px rgba(249,115,22,.25);
}
.cta-btn:disabled { opacity: .65; cursor: not-allowed; }

.cta-btn__inner,
.cta-btn__spin {
  display: flex; align-items: center; justify-content: center; gap: 9px;
}
.cta-btn__inner svg { width: 17px; height: 17px; }
.cta-btn__spin svg  {
  width: 18px; height: 18px;
  animation: spin .7s linear infinite;
  stroke: white;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Restauration session */
.restoring {
  display: flex;
  align-items: center;
  gap: 10px;
  justify-content: center;
  padding: 14px;
  color: rgba(255,255,255,.6);
  font-size: .875rem;
  margin-bottom: 12px;
}
.restoring svg {
  width: 18px; height: 18px;
  animation: spin .7s linear infinite;
  stroke: rgba(249,115,22,.9);
  flex-shrink: 0;
}

/* Footer */
.card-footer {
  text-align: center;
  font-size: .71rem;
  color: rgba(255,255,255,.18);
  margin: 26px 0 0;
}

/* ══════════════════════════════════════════
   TRANSITIONS
   ══════════════════════════════════════════ */
.msg-enter-active { transition: all .22s ease; }
.msg-leave-active { transition: all .16s ease; }
.msg-enter-from  { opacity: 0; transform: translateY(-5px); }
.msg-leave-to    { opacity: 0; }

.shake-enter-active { animation: shake .45s cubic-bezier(.36,.07,.19,.97) both; }
.shake-leave-active { transition: opacity .18s; }
.shake-leave-to     { opacity: 0; }
@keyframes shake {
  0%,100% { transform: translateX(0); }
  20%,60% { transform: translateX(-5px); }
  40%,80% { transform: translateX(5px); }
}

/* ══════════════════════════════════════════
   RESPONSIVE
   ══════════════════════════════════════════ */
@media (max-width: 500px) {
  .glass-card { padding: 30px 20px; border-radius: 22px; }
  .card-title  { font-size: 1.5rem; }
}
</style>
