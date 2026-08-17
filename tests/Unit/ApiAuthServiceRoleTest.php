<?php

namespace Tests\Unit;

use App\Services\DodoVroumApi\ApiAuthService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Cloisonnement des rôles à la connexion : ce dashboard est réservé aux
 * admins et propriétaires. Un compte CLIENT (app mobile) qui entre des
 * identifiants valides ne doit JAMAIS obtenir de session — avant correctif,
 * ApiAuthService::normalizeRole() défaut-accordait le rôle "owner" à tout
 * rôle non reconnu.
 */
class ApiAuthServiceRoleTest extends TestCase
{
    protected function fakeLogin(string $apiRole, string $email = 'user@example.com'): void
    {
        Http::fake([
            '*/auth/login' => Http::response(['access_token' => 'fake-jwt-token'], 200),
            '*/auth/me' => Http::response([
                'id' => 'user-1',
                'email' => $email,
                'role' => $apiRole,
                'firstName' => 'Test',
                'lastName' => 'User',
            ], 200),
        ]);
    }

    public function test_un_compte_client_ne_peut_pas_se_connecter_au_dashboard(): void
    {
        $this->fakeLogin('CLIENT');

        $service = new ApiAuthService();
        $result = $service->authenticate('client@example.com', 'password123');

        $this->assertNull($result, 'Un compte CLIENT ne doit jamais obtenir de session sur ce dashboard.');
    }

    public function test_un_role_totalement_inconnu_ne_peut_pas_se_connecter(): void
    {
        $this->fakeLogin('SOME_FUTURE_ROLE');

        $service = new ApiAuthService();
        $result = $service->authenticate('future@example.com', 'password123');

        $this->assertNull($result);
    }

    public function test_un_compte_proprietaire_peut_se_connecter_avec_le_role_owner(): void
    {
        $this->fakeLogin('PROPRIETAIRE');

        $service = new ApiAuthService();
        $result = $service->authenticate('proprietaire@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertSame('owner', $result['role']);
    }

    public function test_un_compte_admin_peut_se_connecter_avec_le_role_admin(): void
    {
        $this->fakeLogin('ADMIN');

        $service = new ApiAuthService();
        $result = $service->authenticate('admin@example.com', 'password123');

        $this->assertNotNull($result);
        $this->assertSame('admin', $result['role']);
    }
}
