<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed des clés de paramètres pour la page de dons. Les valeurs sont
     * vides par défaut et doivent être renseignées par l'admin via
     * /admin/settings.
     */
    public function up(): void
    {
        Setting::updateOrCreate(['key' => 'donations.paypal_url'], [
            'value' => '',
            'type'  => 'string',
            'label' => 'URL PayPal pour les dons',
            'description' => 'Lien complet vers ta page PayPal.me ou ton bouton de don. Exemple : https://paypal.me/tonpseudo',
        ]);
        Setting::updateOrCreate(['key' => 'donations.crypto_wallet_address'], [
            'value' => '',
            'type'  => 'string',
            'label' => 'Adresse du portefeuille crypto',
            'description' => 'Adresse complète où recevoir les dons en crypto.',
        ]);
        Setting::updateOrCreate(['key' => 'donations.crypto_network'], [
            'value' => 'BTC',
            'type'  => 'string',
            'label' => 'Réseau crypto utilisé',
            'description' => 'Nom court du réseau (BTC, ETH, USDT-ERC20, USDT-TRC20, etc.). Sert juste à informer les donateurs.',
        ]);
        Setting::updateOrCreate(['key' => 'donations.thank_you_message'], [
            'value' => "Merci de soutenir le projet ! Chaque don sert directement au développement et à l'hébergement.",
            'type'  => 'string',
            'label' => 'Message affiché sur la page Dons',
            'description' => 'Texte d\'introduction affiché en haut de la page /dons.',
        ]);
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'donations.paypal_url',
            'donations.crypto_wallet_address',
            'donations.crypto_network',
            'donations.thank_you_message',
        ])->delete();
    }
};
