<template>
  <div class="auth-root">
    <div class="bg-base" />
    <div class="bg-blob  bg-blob--1" />
    <div class="bg-blob  bg-blob--2" />
    <div class="bg-blob  bg-blob--3" />
    <div class="bg-grid" />

    <aside class="brand-panel">
      <div class="brand-inner">
        <div class="logo-wrap" :class="{ 'anim-in': mounted }">
          <div class="logo-halo" />
          <div class="logo-card">
            <img :src="logoUrl" alt="DodoVroum" class="logo-img" />
          </div>
        </div>

        <div class="brand-copy" :class="{ 'anim-in': mounted }">
          <p class="brand-eyebrow">Espace propriétaire</p>
          <h1 class="brand-title">
            Publiez vos biens<br/>
            et vos véhicules<br/>
            <span class="brand-title__hl">en quelques minutes.</span>
          </h1>
          <p class="brand-desc">
            Créez votre compte, acceptez le contrat de partenariat, et commencez à recevoir des réservations. Aucune validation préalable ne bloque la publication de vos annonces.
          </p>
        </div>

        <ul class="feat-list" :class="{ 'anim-in': mounted }">
          <li class="feat-item">
            <span class="feat-icon feat-icon--blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-3.5 0-6.5 2-8 5 1.5 3 4.5 5 8 5s6.5-2 8-5c-1.5-3-4.5-5-8-5z"/><circle cx="12" cy="13" r="2.5"/></svg>
            </span>
            <span class="feat-text">
              <strong>Publication directe</strong>
              <span>Vos annonces sont visibles immédiatement</span>
            </span>
          </li>
          <li class="feat-item">
            <span class="feat-icon feat-icon--orange">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3 6.5 7 1-5 5 1.2 7L12 18l-6.2 3.5L7 14.5l-5-5 7-1z"/></svg>
            </span>
            <span class="feat-text">
              <strong>Paiement sécurisé</strong>
              <span>Encaissement via GeniusPay, reversé après remise du bien</span>
            </span>
          </li>
        </ul>

        <p class="brand-footer">© {{ new Date().getFullYear() }} DodoVroum — Tous droits réservés</p>
      </div>
    </aside>

    <main class="form-panel">
      <div class="glass-card" :class="{ 'anim-in': mounted }">
        <div class="mobile-logo">
          <img :src="logoUrl" alt="DodoVroum" class="mobile-logo__img" />
        </div>

        <header class="card-head">
          <div class="card-badge">
            <span class="card-badge__dot" />
            Inscription propriétaire
          </div>
          <h2 class="card-title">Créez votre compte</h2>
          <p class="card-sub">Gérez vos résidences et véhicules depuis votre espace dédié</p>
        </header>

        <transition name="shake">
          <div v-if="globalError" class="err-banner" role="alert">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span>{{ globalError }}</span>
          </div>
        </transition>

        <form @submit.prevent="submit" class="form" novalidate>
          <div class="field-row">
            <div class="field" :class="{ 'field--focus': focused === 'firstName', 'field--err': form.errors.firstName }">
              <label for="firstName" class="field__lbl">Prénom</label>
              <div class="field__row">
                <input
                  id="firstName" v-model="form.firstName" name="firstName" type="text" autocomplete="given-name" required
                  placeholder="Kouassi" class="field__inp field__inp--noicon"
                  @focus="focused = 'firstName'" @blur="focused = null"
                />
              </div>
              <p v-if="form.errors.firstName" class="field__msg">{{ form.errors.firstName }}</p>
            </div>

            <div class="field" :class="{ 'field--focus': focused === 'lastName', 'field--err': form.errors.lastName }">
              <label for="lastName" class="field__lbl">Nom (ou raison sociale)</label>
              <div class="field__row">
                <input
                  id="lastName" v-model="form.lastName" name="lastName" type="text" autocomplete="family-name" required
                  placeholder="Yao" class="field__inp field__inp--noicon"
                  @focus="focused = 'lastName'" @blur="focused = null"
                />
              </div>
              <p v-if="form.errors.lastName" class="field__msg">{{ form.errors.lastName }}</p>
            </div>
          </div>

          <div class="field" :class="{ 'field--focus': focused === 'email', 'field--err': form.errors.email }">
            <label for="email" class="field__lbl">Adresse email</label>
            <div class="field__row">
              <input
                id="email" v-model="form.email" name="email" type="email" autocomplete="email" required
                placeholder="vous@example.com" class="field__inp field__inp--noicon"
                @focus="focused = 'email'" @blur="focused = null"
              />
            </div>
            <p v-if="form.errors.email" class="field__msg">{{ form.errors.email }}</p>
          </div>

          <div class="field" :class="{ 'field--focus': focused === 'phone', 'field--err': form.errors.phone }">
            <label for="phone" class="field__lbl">Téléphone</label>
            <div class="field__row">
              <input
                id="phone" v-model="form.phone" name="phone" type="tel" autocomplete="tel" required
                placeholder="0102030405" class="field__inp field__inp--noicon"
                @focus="focused = 'phone'" @blur="focused = null"
              />
            </div>
            <p v-if="form.errors.phone" class="field__msg">{{ form.errors.phone }}</p>
          </div>

          <div class="field" :class="{ 'field--focus': focused === 'password', 'field--err': form.errors.password }">
            <label for="password" class="field__lbl">Mot de passe</label>
            <div class="field__row">
              <input
                id="password" v-model="form.password" name="password" :type="showPwd ? 'text' : 'password'" autocomplete="new-password" required
                placeholder="8 caractères min., 1 majuscule, 1 chiffre" class="field__inp field__inp--noicon field__inp--pwd"
                @focus="focused = 'password'" @blur="focused = null"
              />
              <button type="button" class="field__eye" :title="showPwd ? 'Masquer' : 'Afficher'" @click="showPwd = !showPwd">
                <svg v-if="!showPwd" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
              </button>
            </div>
            <p v-if="form.errors.password" class="field__msg">{{ form.errors.password }}</p>
          </div>

          <div class="field" :class="{ 'field--focus': focused === 'password_confirmation', 'field--err': form.errors.password_confirmation }">
            <label for="password_confirmation" class="field__lbl">Confirmer le mot de passe</label>
            <div class="field__row">
              <input
                id="password_confirmation" v-model="form.password_confirmation" name="password_confirmation" :type="showPwd ? 'text' : 'password'" autocomplete="new-password" required
                placeholder="••••••••" class="field__inp field__inp--noicon"
                @focus="focused = 'password_confirmation'" @blur="focused = null"
              />
            </div>
            <p v-if="form.errors.password_confirmation" class="field__msg">{{ form.errors.password_confirmation }}</p>
          </div>

          <label class="consent" :class="{ 'consent--err': form.errors.contractAccepted }">
            <input id="contractAccepted" v-model="form.contractAccepted" name="contractAccepted" type="checkbox" class="remember__native" />
            <span class="remember__box" :class="{ 'remember__box--on': form.contractAccepted }">
              <svg v-if="form.contractAccepted" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
            </span>
            <span class="consent__txt">
              J'ai lu et j'accepte le
              <a :href="props.contractUrl" target="_blank" rel="noopener noreferrer">Contrat de partenariat propriétaire</a>
              et les
              <a :href="props.termsUrl" target="_blank" rel="noopener noreferrer">Conditions d'utilisation</a>.
            </span>
          </label>
          <p v-if="form.errors.contractAccepted" class="field__msg">{{ form.errors.contractAccepted }}</p>

          <button type="submit" :disabled="form.processing" class="cta-btn">
            <span v-if="!form.processing" class="cta-btn__inner">
              Créer mon compte propriétaire
            </span>
            <span v-else class="cta-btn__spin">
              <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="45"/></svg>
              Création du compte…
            </span>
          </button>

          <p class="switch-link">
            Déjà un compte ? <a :href="'/login'">Se connecter</a>
          </p>
        </form>

        <p class="card-footer">© {{ new Date().getFullYear() }} DodoVroum · Plateforme de gestion locative</p>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '../Components/Layouts/AuthLayout.vue';
import logoUrl from '../assets/logo.png';

defineOptions({ layout: AuthLayout });

const props = defineProps<{
  contractVersion: string;
  contractUrl: string;
  termsUrl: string;
}>();

const form = useForm({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  contractAccepted: false,
  contractVersion: props.contractVersion,
});

const showPwd = ref(false);
const focused = ref<string | null>(null);
const mounted = ref(false);

const globalError = computed(() => form.errors.email && !form.email.trim() ? form.errors.email : null);

onMounted(() => { setTimeout(() => { mounted.value = true; }, 60); });

const submit = () => form.post('/inscription-proprietaire', {
  onSuccess: () => form.reset('password', 'password_confirmation'),
});
</script>

<style scoped>
/* Coquille visuelle identique à Login.vue (même système de design) */
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

.logo-wrap, .brand-copy, .feat-list { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .65s cubic-bezier(.34,1.4,.64,1); }
.brand-copy { transition-delay: .1s; }
.feat-list { transition-delay: .2s; }
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

.feat-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
.feat-item { display: flex; align-items: center; gap: 13px; padding: 13px 15px; border-radius: 14px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07); }
.feat-icon { flex-shrink: 0; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.feat-icon svg { width: 17px; height: 17px; }
.feat-icon--blue { background: rgba(29,111,212,.2); color: rgba(109,171,235,.95); }
.feat-icon--orange { background: rgba(249,115,22,.18); color: rgba(249,140,60,.95); }
.feat-text { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.feat-text strong { font-size: .875rem; font-weight: 600; color: rgba(255,255,255,.88); }
.feat-text span { font-size: .775rem; color: rgba(255,255,255,.38); }

.brand-footer { position: absolute; bottom: -24px; left: 0; right: 0; font-size: .7rem; color: rgba(255,255,255,.2); text-align: center; margin: 0; }

.form-panel { flex: 1; position: relative; z-index: 10; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
.glass-card { position: relative; width: 100%; max-width: 460px; background: rgba(255,255,255,.06); backdrop-filter: blur(22px) saturate(1.3); -webkit-backdrop-filter: blur(22px) saturate(1.3); border: 1px solid rgba(255,255,255,.1); border-radius: 28px; padding: 40px 36px; box-shadow: inset 0 0 0 1px rgba(255,255,255,.05), 0 32px 72px rgba(0,0,0,.45), 0 8px 24px rgba(0,0,0,.3); opacity: 0; transform: translateY(32px) scale(.985); transition: opacity .55s ease, transform .7s cubic-bezier(.34,1.35,.64,1); }
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

.err-banner { display: flex; align-items: center; gap: 9px; padding: 11px 15px; background: rgba(220,38,38,.12); border: 1px solid rgba(220,38,38,.28); border-radius: 12px; color: #fca5a5; font-size: .85rem; font-weight: 500; margin-bottom: 18px; }
.err-banner svg { flex-shrink:0; width:16px; height:16px; }

.form { display: flex; flex-direction: column; gap: 15px; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field { display: flex; flex-direction: column; gap: 6px; }
.field__lbl { font-size: .72rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: rgba(255,255,255,.5); transition: color .18s; }
.field--focus .field__lbl { color: rgba(249,115,22,.85); }
.field--err .field__lbl { color: rgba(248,113,113,.85); }
.field__row { position: relative; display: flex; align-items: center; }
.field__inp { width: 100%; padding: 12px 13px; background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.1); border-radius: 12px; font-size: .875rem; font-family: inherit; color: rgba(255,255,255,.92); outline: none; transition: border-color .18s, background .18s, box-shadow .18s; -webkit-appearance: none; }
.field__inp--noicon { padding-left: 13px; }
.field__inp::placeholder { color: rgba(255,255,255,.2); }
.field__inp:-webkit-autofill { -webkit-box-shadow: 0 0 0 40px #0d2855 inset !important; -webkit-text-fill-color: rgba(255,255,255,.92) !important; }
.field__inp:focus { background: rgba(255,255,255,.1); border-color: rgba(249,115,22,.65); box-shadow: 0 0 0 3px rgba(249,115,22,.14), 0 0 0 1px rgba(249,115,22,.25) inset; }
.field--err .field__inp { border-color: rgba(248,113,113,.45); background: rgba(220,38,38,.07); }
.field__inp--pwd { padding-right: 44px; }

.field__eye { position: absolute; right: 12px; background: none; border: none; cursor: pointer; color: rgba(255,255,255,.26); padding: 4px; border-radius: 7px; display: flex; align-items: center; transition: color .18s, background .18s; }
.field__eye:hover { color: rgba(249,115,22,.8); background: rgba(249,115,22,.08); }
.field__eye svg { width: 17px; height: 17px; }

.field__msg { display: flex; align-items: center; gap: 5px; font-size: .76rem; color: #fca5a5; font-weight: 500; margin: 0; }

.consent { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; user-select: none; padding-top: 2px; }
.consent--err .consent__txt { color: #fca5a5; }
.consent__txt { font-size: .8rem; line-height: 1.5; color: rgba(255,255,255,.55); }
.consent__txt a { color: #f97316; text-decoration: underline; text-underline-offset: 2px; }
.consent__txt a:hover { color: #fb923c; }

.remember__native { position: absolute; opacity: 0; width: 0; height: 0; }
.remember__box { flex-shrink: 0; width: 18px; height: 18px; margin-top: 1px; border-radius: 5px; border: 1.5px solid rgba(255,255,255,.2); background: rgba(255,255,255,.07); display: flex; align-items: center; justify-content: center; transition: all .18s; }
.remember__box--on { background: #f97316; border-color: #f97316; box-shadow: 0 0 8px rgba(249,115,22,.35); }
.remember__box svg { width: 10px; height: 10px; color: #fff; }

.cta-btn { width: 100%; padding: 14px; margin-top: 4px; border: none; border-radius: 13px; cursor: pointer; font-family: inherit; font-size: .92rem; font-weight: 700; letter-spacing: .01em; color: #fff; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 4px 16px rgba(249,115,22,.32), 0 1px 3px rgba(0,0,0,.2); transition: transform .14s ease, box-shadow .18s ease, filter .18s; position: relative; overflow: hidden; }
.cta-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(255,255,255,.14) 0%, transparent 55%); border-radius: inherit; pointer-events: none; }
.cta-btn:hover:not(:disabled) { transform: translateY(-2px) scale(1.008); filter: brightness(1.06); }
.cta-btn:active:not(:disabled) { transform: translateY(0) scale(.995); }
.cta-btn:disabled { opacity: .65; cursor: not-allowed; }
.cta-btn__inner, .cta-btn__spin { display: flex; align-items: center; justify-content: center; gap: 9px; }
.cta-btn__spin svg { width: 18px; height: 18px; animation: spin .7s linear infinite; stroke: white; }
@keyframes spin { to { transform: rotate(360deg); } }

.switch-link { text-align: center; font-size: .82rem; color: rgba(255,255,255,.4); margin: 2px 0 0; }
.switch-link a { color: #f97316; font-weight: 600; text-decoration: none; }
.switch-link a:hover { text-decoration: underline; }

.card-footer { text-align: center; font-size: .71rem; color: rgba(255,255,255,.18); margin: 22px 0 0; }

.shake-enter-active { animation: shake .45s cubic-bezier(.36,.07,.19,.97) both; }
.shake-leave-active { transition: opacity .18s; }
.shake-leave-to { opacity: 0; }
@keyframes shake { 0%,100% { transform: translateX(0); } 20%,60% { transform: translateX(-5px); } 40%,80% { transform: translateX(5px); } }

@media (max-width: 500px) {
  .glass-card { padding: 28px 20px; border-radius: 22px; }
  .card-title { font-size: 1.4rem; }
  .field-row { grid-template-columns: 1fr; }
}
</style>
