<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DodoVroumApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Auto-inscription propriétaire (route publique). Le rôle PROPRIETAIRE est
 * forcé côté API NestJS (POST /auth/register/proprietaire) — ce contrôleur
 * n'envoie jamais de champ "role" au payload, il n'y a donc aucun moyen pour
 * un attaquant de se créer un compte admin via ce formulaire.
 */
class RegisterProprietaireController extends Controller
{
    public function __construct(
        protected DodoVroumApiService $apiService
    ) {
    }

    /** Version actuelle du contrat, doit correspondre à CURRENT_PARTNER_CONTRACT_VERSION côté API. */
    protected const CONTRACT_VERSION = 'v10';

    public function show(): Response|\Illuminate\Http\RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('owner.dashboard');
        }

        return Inertia::render('RegisterOwner', [
            'contractVersion' => self::CONTRACT_VERSION,
            'contractUrl' => route('legal.partner-contract'),
            'termsUrl' => route('legal.terms'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'regex:/^(?:\+225|00225)?0?\d{10}$/'],
            'password' => ['required', 'string', 'min:8', 'regex:/(?=.*[A-Z])(?=.*\d)/', 'confirmed'],
            'contractAccepted' => ['accepted'],
        ], [
            'phone.regex' => 'Le numéro de téléphone doit être un numéro ivoirien valide (ex. 0102030405).',
            'password.regex' => 'Le mot de passe doit contenir au moins une majuscule et un chiffre.',
            'contractAccepted.accepted' => 'Vous devez accepter le contrat de partenariat pour vous inscrire.',
        ]);

        try {
            $result = $this->apiService->registerProprietaire([
                'firstName' => $data['firstName'],
                'lastName' => $data['lastName'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'passwordConfirmation' => $request->input('password_confirmation'),
                'contractAccepted' => true,
                'contractVersion' => self::CONTRACT_VERSION,
            ]);
        } catch (\Exception $e) {
            Log::warning('Échec inscription propriétaire (dashboard)', ['email' => $data['email'], 'error' => $e->getMessage()]);

            return back()->withErrors(['email' => $e->getMessage()])->onlyInput('firstName', 'lastName', 'email', 'phone');
        }

        $apiUser = $result['user'] ?? [];
        $token = $result['access_token'] ?? null;

        if (!$token || empty($apiUser)) {
            return back()->withErrors(['email' => 'Inscription réussie mais connexion automatique impossible. Merci de vous connecter manuellement.']);
        }

        // Auto-login : même construction de session que LoginController::store()
        $userData = [
            'id' => $apiUser['id'] ?? null,
            'email' => $apiUser['email'] ?? $data['email'],
            'name' => trim(($apiUser['firstName'] ?? $data['firstName']).' '.($apiUser['lastName'] ?? $data['lastName'])),
            'firstName' => $apiUser['firstName'] ?? $data['firstName'],
            'lastName' => $apiUser['lastName'] ?? $data['lastName'],
            'role' => 'owner',
            'token' => $token,
            'raw' => $apiUser,
        ];

        $user = new \App\Models\ApiUser($userData);
        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->put('api_token', $token);
        $request->session()->put('nest_jwt_token', $token);
        $request->session()->put('api_user', $userData);
        $request->session()->save();

        $request->session()->flash('flash_token', $token);
        $request->session()->flash('flash_user', json_encode([
            'id' => $userData['id'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'name' => $userData['name'],
        ]));

        Log::info('Auto-inscription propriétaire réussie', ['user_id' => $userData['id'], 'email' => $userData['email']]);

        return redirect()->route('owner.dashboard');
    }
}
