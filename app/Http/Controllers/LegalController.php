<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Conditions générales d'utilisation — contenu PLACEHOLDER, à faire
     * valider par un juriste avant mise en production (aucun texte source
     * n'a été fourni au moment de l'implémentation, contrairement au Contrat
     * de partenariat propriétaire qui est le document réel signé).
     */
    public function terms(): Response
    {
        return Inertia::render('LegalTerms');
    }
}
