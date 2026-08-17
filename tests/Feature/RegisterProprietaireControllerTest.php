<?php

namespace Tests\Feature;

use App\Services\DodoVroumApiService;
use Tests\TestCase;

/**
 * Cloisonnement des rôles à l'auto-inscription : le rôle PROPRIETAIRE est
 * fixé côté API (jamais via un champ envoyé par le formulaire), la case
 * d'acceptation du contrat est obligatoire, et le mot de passe doit être
 * confirmé.
 */
class RegisterProprietaireControllerTest extends TestCase
{
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Kouassi',
            'lastName' => 'Yao',
            'email' => 'proprietaire@example.com',
            'phone' => '0102030405',
            'password' => 'MotDePasse123',
            'password_confirmation' => 'MotDePasse123',
            'contractAccepted' => '1',
        ], $overrides);
    }

    public function test_inscription_reussie_envoie_un_payload_sans_champ_role_meme_si_injecte(): void
    {
        $this->mock(DodoVroumApiService::class, function ($mock) {
            $mock->shouldReceive('registerProprietaire')
                ->once()
                ->withArgs(function (array $payload) {
                    // Le payload transmis à l'API ne doit JAMAIS contenir un
                    // champ role — la route publique ne peut donc jamais
                    // demander la création d'un compte ADMIN.
                    return !array_key_exists('role', $payload)
                        && $payload['email'] === 'proprietaire@example.com'
                        && $payload['contractAccepted'] === true;
                })
                ->andReturn([
                    'access_token' => 'fake-jwt',
                    'refresh_token' => 'fake-refresh',
                    'user' => ['id' => 'new-owner-1', 'email' => 'proprietaire@example.com', 'firstName' => 'Kouassi', 'lastName' => 'Yao'],
                ]);
        });

        // Même si un attaquant ajoute role=ADMIN dans le body du formulaire,
        // ce champ n'est lu nulle part par le contrôleur.
        $response = $this->post('/inscription-proprietaire', $this->validPayload(['role' => 'ADMIN']));

        // Redirige vers l'étape 2 (pièce d'identité), pas directement le dashboard.
        $response->assertRedirect(route('register.owner.documents'));
    }

    public function test_etape_documents_accessible_apres_inscription_mais_pas_par_un_visiteur(): void
    {
        $response = $this->get('/inscription-proprietaire/documents');

        // Middleware 'auth' : un visiteur non connecté est renvoyé au login,
        // il ne peut pas atteindre cette étape sans être passé par store().
        $response->assertRedirect('/login');
    }

    public function test_inscription_refusee_si_la_case_du_contrat_nest_pas_cochee(): void
    {
        $response = $this->post('/inscription-proprietaire', $this->validPayload(['contractAccepted' => false]));

        $response->assertSessionHasErrors('contractAccepted');
    }

    public function test_inscription_refusee_si_confirmation_mot_de_passe_differente(): void
    {
        $response = $this->post('/inscription-proprietaire', $this->validPayload(['password_confirmation' => 'Autre123']));

        $response->assertSessionHasErrors('password');
    }

    public function test_inscription_refusee_pour_un_telephone_non_ivoirien(): void
    {
        $response = $this->post('/inscription-proprietaire', $this->validPayload(['phone' => '0033123456789']));

        $response->assertSessionHasErrors('phone');
    }
}
