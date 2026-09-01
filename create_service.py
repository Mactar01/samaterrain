content = '''<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayDunyaService
{
    protected \;
    protected \;
    protected \;
    protected \;

    public function __construct()
    {
        \->masterKey = env('PAYDUNYA_MASTER_KEY', 'test_master_key');
        \->privateKey = env('PAYDUNYA_PRIVATE_KEY', 'test_private_key');
        \->token = env('PAYDUNYA_TOKEN', 'test_token');
        \->baseUrl = 'https://app.paydunya.com/api/v1/checkout-invoice/create';
    }

    public function createInvoice(\, \)
    {
        if (\->masterKey === 'test_master_key') {
            return [
                'success' => true,
                'url' => 'https://paydunya.com/sandbox/checkout/simulate_' . \->id,
                'token' => 'simulated_token_' . \->id
            ];
        }

        \ = Http::withHeaders([
            'PAYDUNYA-MASTER-KEY' => \->masterKey,
            'PAYDUNYA-PRIVATE-KEY' => \->privateKey,
            'PAYDUNYA-TOKEN' => \->token,
        ])->post(\->baseUrl, [
            'invoice' => [
                'total_amount' => \,
                'description' => "Réservation myTerrain (ID: {\->id})"
            ],
            'store' => [
                'name' => 'myTerrain',
            ],
            'actions' => [
                'cancel_url' => env('FRONTEND_URL', 'http://192.168.1.4:8080') . '/payment/cancel',
                'return_url' => env('FRONTEND_URL', 'http://192.168.1.4:8080') . '/payment/success',
                'callback_url' => env('APP_URL', 'http://192.168.1.4:8000') . '/api/v1/payments/webhook'
            ],
            'custom_data' => [
                'reservation_id' => \->id,
                'amount' => \
            ]
        ]);

        if (\->successful() && \->json('response_code') === '00') {
            return [
                'success' => true,
                'url' => \->json('response_text'),
                'token' => \->json('token')
            ];
        }

        Log::error('PayDunya Error: ' . \->body());

        return [
            'success' => false,
            'message' => 'Erreur lors de la génération du paiement.'
        ];
    }
}
'''
with open(r'backend\app\Services\PayDunyaService.php', 'w', encoding='utf-8') as f:
    f.write(content)
