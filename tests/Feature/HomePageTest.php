<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Page d'accueil publique à la racine (/) : un visiteur non connecté voit la
 * page d'accueil (plus de redirection directe vers /login) ; un utilisateur
 * déjà connecté est toujours redirigé vers son espace sans jamais voir cette
 * page — la logique de routage par rôle existante n'est pas modifiée.
 */
class HomePageTest extends TestCase
{
    public function test_un_visiteur_non_connecte_voit_la_page_daccueil(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // false : ne pas vérifier l'existence du fichier via le résolveur Inertia
        // par défaut (resources/js/Pages) — ce projet sert ses pages depuis
        // resources/js/admin/Pages (voir AppAdmin.ts), non configuré dans le
        // testing view-finder du package. Le nom du composant est bien vérifié.
        $response->assertInertia(fn ($page) => $page->component('Home', false));
    }

    public function test_un_proprietaire_connecte_est_redirige_vers_son_dashboard_sans_voir_laccueil(): void
    {
        Auth::login(new ApiUser(['id' => 'owner-1', 'email' => 'owner@example.com', 'role' => 'owner']));

        $response = $this->get('/');

        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_un_admin_connecte_est_redirige_vers_son_dashboard_sans_voir_laccueil(): void
    {
        Auth::login(new ApiUser(['id' => 'admin-1', 'email' => 'admin@example.com', 'role' => 'admin']));

        $response = $this->get('/');

        $response->assertRedirect(route('admin.dashboard'));
    }
}
