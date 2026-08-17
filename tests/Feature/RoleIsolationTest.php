<?php

namespace Tests\Feature;

use App\Models\ApiUser;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Cloisonnement admin/propriétaire (spec : "un compte propriétaire qui tente
 * d'accéder à une route admin doit être rejeté (403)"), et inversement.
 */
class RoleIsolationTest extends TestCase
{
    protected function ownerUser(): ApiUser
    {
        return new ApiUser([
            'id' => 'owner-1',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'token' => 'fake-owner-token',
        ]);
    }

    protected function adminUser(): ApiUser
    {
        return new ApiUser([
            'id' => 'admin-1',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'token' => 'fake-admin-token',
        ]);
    }

    /**
     * Le middleware Admin redirige délibérément un propriétaire connu vers
     * SON dashboard plutôt que d'afficher une page 403 brute (meilleure UX
     * pour un clic accidentel sur un lien admin) — mais dans les deux cas
     * aucun contenu/donnée admin n'est jamais atteint ni exposé. On vérifie
     * donc : jamais 200, jamais de contenu admin, toujours éloigné de la
     * zone admin.
     */
    public function test_un_proprietaire_est_rejete_sur_le_dashboard_admin(): void
    {
        Auth::login($this->ownerUser());

        $response = $this->get('/admin/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_un_proprietaire_est_rejete_sur_la_gestion_des_utilisateurs_admin(): void
    {
        Auth::login($this->ownerUser());

        $response = $this->get('/admin/users');

        $response->assertStatus(302);
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_un_proprietaire_est_rejete_sur_les_reglages_admin(): void
    {
        Auth::login($this->ownerUser());

        $response = $this->get('/admin/settings');

        $response->assertStatus(302);
        $response->assertRedirect(route('owner.dashboard'));
    }

    public function test_un_role_non_reconnu_recoit_un_403_brut_sur_une_route_admin(): void
    {
        // Cas où le rôle ne résout ni à owner ni à admin (ex. anomalie de
        // session) : pas de redirection "gentille" possible, rejet 403 dur.
        Auth::login(new ApiUser(['id' => 'weird-1', 'email' => 'weird@example.com', 'role' => 'unknown']));

        $response = $this->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_un_admin_est_rejete_sur_lespace_proprietaire(): void
    {
        Auth::login($this->adminUser());

        $response = $this->get('/owner/dashboard');

        $response->assertForbidden();
    }

    public function test_un_visiteur_non_authentifie_est_redirige_vers_login_pour_ladmin(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_un_visiteur_non_authentifie_est_redirige_vers_login_pour_lespace_owner(): void
    {
        $response = $this->get('/owner/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_un_proprietaire_ne_peut_pas_moderer_une_annonce(): void
    {
        Auth::login($this->ownerUser());

        $response = $this->patch('/admin/vehicles/some-id/moderate', ['status' => 'HIDDEN']);

        $response->assertStatus(302);
        $response->assertRedirect(route('owner.dashboard'));
    }
}
