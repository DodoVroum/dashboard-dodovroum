<template>
  <div class="home-root">
    <div class="bg-base" />
    <div class="bg-blob  bg-blob--1" />
    <div class="bg-blob  bg-blob--2" />
    <div class="bg-blob  bg-blob--3" />
    <div class="bg-grid" />

    <main class="hero">
      <div class="logo-wrap" :class="{ 'anim-in': mounted }">
        <div class="logo-halo" />
        <div class="logo-card">
          <img :src="logoUrl" alt="DodoVroum" class="logo-img" />
        </div>
      </div>

      <div class="hero-copy" :class="{ 'anim-in': mounted }">
        <p class="eyebrow">Location de résidences &amp; véhicules en Côte d'Ivoire</p>
        <h1 class="title">
          Mettez vos véhicules et résidences<br class="br-desktop" />
          en location sur <span class="title__hl">DodoVroum</span>
        </h1>
      </div>

      <div class="cta-row" :class="{ 'anim-in': mounted }">
        <a href="/inscription-proprietaire" class="cta-btn cta-btn--primary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Devenir propriétaire
        </a>
        <a href="/login" class="cta-btn cta-btn--ghost">
          Se connecter
        </a>
      </div>

      <ul class="perks" :class="{ 'anim-in': mounted }">
        <li class="perk">
          <span class="perk-icon perk-icon--orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3 6.5 7 1-5 5 1.2 7L12 18l-6.2 3.5L7 14.5l-5-5 7-1z"/></svg>
          </span>
          <span class="perk-text">
            <strong>Paiement sécurisé</strong>
            <span>Encaissé en ligne, reversé après la remise du bien</span>
          </span>
        </li>
        <li class="perk">
          <span class="perk-icon perk-icon--blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 6h-2l-3 7H3l1 4h14l1-4h-3l-3-7z"/></svg>
          </span>
          <span class="perk-text">
            <strong>Gestion simple</strong>
            <span>Résidences, véhicules et réservations, en un seul espace</span>
          </span>
        </li>
        <li class="perk">
          <span class="perk-icon perk-icon--orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
          <span class="perk-text">
            <strong>Publication immédiate</strong>
            <span>Vos annonces sont visibles dès leur création</span>
          </span>
        </li>
      </ul>

      <p class="footer">© {{ new Date().getFullYear() }} DodoVroum — Tous droits réservés</p>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import AuthLayout from '../Components/Layouts/AuthLayout.vue';
import logoUrl from '../assets/logo.png';

defineOptions({ layout: AuthLayout });

const mounted = ref(false);
onMounted(() => { setTimeout(() => { mounted.value = true; }, 60); });
</script>

<style scoped>
.home-root { position: relative; display: flex; min-height: 100dvh; font-family: 'Inter', system-ui, -apple-system, sans-serif; overflow: hidden; }

.bg-base { position: fixed; inset: 0; z-index: 0; background: linear-gradient(135deg, #0a1628 0%, #0d2855 45%, #1a4a9e 100%); background-size: 250% 250%; animation: bgDrift 20s ease infinite; }
@keyframes bgDrift { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
.bg-blob { position: fixed; border-radius: 50%; filter: blur(88px); pointer-events: none; z-index: 1; }
.bg-blob--1 { width: 560px; height: 560px; background: radial-gradient(circle, rgba(29,111,212,.5) 0%, transparent 70%); top: -120px; left: -100px; animation: blob1 15s ease-in-out infinite; }
.bg-blob--2 { width: 420px; height: 420px; background: radial-gradient(circle, rgba(249,115,22,.28) 0%, transparent 70%); bottom: -60px; right: -80px; animation: blob2 11s ease-in-out infinite; }
.bg-blob--3 { width: 320px; height: 320px; background: radial-gradient(circle, rgba(29,111,212,.22) 0%, transparent 70%); top: 40%; left: 60%; animation: blob3 18s ease-in-out infinite; }
@keyframes blob1 { 0%,100%{transform:translate(0,0)}40%{transform:translate(28px,-22px)}70%{transform:translate(-12px,18px)} }
@keyframes blob2 { 0%,100%{transform:translate(0,0)}50%{transform:translate(-20px,24px)} }
@keyframes blob3 { 0%,100%{transform:translate(0,0)}45%{transform:translate(18px,-14px)} }
.bg-grid { position: fixed; inset: 0; z-index: 2; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px); background-size: 52px 52px; }

.hero { position: relative; z-index: 10; width: 100%; max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; text-align: center; padding: 64px 24px 40px; }

.logo-wrap, .hero-copy, .cta-row, .perks { opacity: 0; transform: translateY(20px); transition: opacity .6s ease, transform .65s cubic-bezier(.34,1.4,.64,1); }
.hero-copy { transition-delay: .08s; }
.cta-row   { transition-delay: .16s; }
.perks     { transition-delay: .24s; }
.anim-in   { opacity: 1 !important; transform: translateY(0) !important; }

.logo-wrap { position: relative; margin-bottom: 40px; }
.logo-halo { position: absolute; inset: -26px; background: radial-gradient(ellipse, rgba(29,111,212,.35) 0%, rgba(249,115,22,.12) 50%, transparent 70%); filter: blur(22px); border-radius: 50%; pointer-events: none; }
.logo-card { position: relative; background: rgba(255,255,255,.97); border-radius: 22px; padding: 22px 34px; box-shadow: 0 0 0 1px rgba(255,255,255,.12), 0 12px 40px rgba(0,0,0,.35), 0 0 56px rgba(29,111,212,.22); }
.logo-img { display: block; width: 200px; height: auto; }

.eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: .72rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase; color: #f97316; margin: 0 0 16px; }
.title { font-size: clamp(1.9rem, 4.2vw, 2.7rem); font-weight: 800; line-height: 1.22; letter-spacing: -.035em; color: rgba(255,255,255,.96); margin: 0 0 40px; }
.title__hl { background: linear-gradient(90deg, #f97316 20%, #1d6fd4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.br-desktop { display: none; }
@media (min-width: 640px) { .br-desktop { display: block; } }

.cta-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 16px; margin-bottom: 56px; }
.cta-btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 15px 28px; border-radius: 13px; font-size: .95rem; font-weight: 700; letter-spacing: .01em; text-decoration: none; transition: transform .14s ease, box-shadow .18s ease, filter .18s, background .18s; }
.cta-btn svg { width: 18px; height: 18px; }
.cta-btn--primary { color: #fff; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 4px 16px rgba(249,115,22,.32), 0 1px 3px rgba(0,0,0,.2); }
.cta-btn--primary:hover { transform: translateY(-2px) scale(1.01); filter: brightness(1.06); }
.cta-btn--ghost { color: rgba(255,255,255,.9); background: rgba(255,255,255,.08); border: 1.5px solid rgba(255,255,255,.18); backdrop-filter: blur(8px); }
.cta-btn--ghost:hover { background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.3); transform: translateY(-2px); }

.perks { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; width: 100%; }
.perk { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px 14px; border-radius: 16px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); text-align: center; }
.perk-icon { flex-shrink: 0; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.perk-icon svg { width: 19px; height: 19px; }
.perk-icon--blue   { background: rgba(29,111,212,.2);  color: rgba(109,171,235,.95); }
.perk-icon--orange { background: rgba(249,115,22,.18); color: rgba(249,140,60,.95); }
.perk-text { display: flex; flex-direction: column; gap: 3px; }
.perk-text strong { font-size: .875rem; font-weight: 700; color: rgba(255,255,255,.92); }
.perk-text span   { font-size: .78rem; line-height: 1.4; color: rgba(255,255,255,.42); }

.footer { margin-top: 48px; font-size: .72rem; color: rgba(255,255,255,.22); }

@media (max-width: 560px) {
  .perks { grid-template-columns: 1fr; }
  .cta-row { flex-direction: column; width: 100%; }
  .cta-btn { width: 100%; }
}
</style>
