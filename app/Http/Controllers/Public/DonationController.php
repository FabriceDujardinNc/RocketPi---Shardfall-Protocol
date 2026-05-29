<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Page de dons : affiche le lien PayPal + l'adresse crypto wallet
 * configurés par l'admin dans Setting.
 *
 * Aucune intégration payment côté serveur — on ne fait que rendre une page
 * statique avec les pointeurs externes. La validation des dons crypto se
 * fait off-chain (vérification manuelle).
 */
class DonationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Dons', [
            'paypalUrl'           => Setting::value('donations.paypal_url', ''),
            'cryptoWalletAddress' => Setting::value('donations.crypto_wallet_address', ''),
            'cryptoNetwork'       => Setting::value('donations.crypto_network', 'BTC'),
            'thankYouMessage'     => Setting::value('donations.thank_you_message',
                "Merci de soutenir le projet ! Chaque don sert directement au développement et à l'hébergement."),
        ]);
    }
}
