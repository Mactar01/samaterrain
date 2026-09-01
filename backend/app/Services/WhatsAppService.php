<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Envoyer un message WhatsApp via une API tierce (ex: Twilio, UltraMsg, Evolution API, etc.)
     * 
     * @param string $phone Numéro de téléphone du destinataire (format international, ex: +221770000000)
     * @param string $message Le texte du message à envoyer
     * @return bool
     */
    public function sendMessage(string $phone, string $message): bool
    {
        // En mode développement ou si l'API n'est pas encore configurée,
        // on loggue simplement le message pour simuler l'envoi.
        Log::info("=== WHATSAPP MESSAGE SENT ===");
        Log::info("TO: " . $phone);
        Log::info("MESSAGE:\n" . $message);
        Log::info("=============================");

        // Identifiants Twilio récupérés depuis le fichier .env
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER'); // ex: 'whatsapp:+14155238886'

        // S'assurer que le numéro commence par un +
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        // Si Twilio n'est pas configuré, on ne tente pas l'envoi HTTP
        if (!$sid || !$token || !$twilioWhatsAppNumber) {
            Log::warning("TWILIO_SID, TWILIO_AUTH_TOKEN ou TWILIO_WHATSAPP_NUMBER manquant dans le .env.");
            return false;
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json", [
                    'From' => $twilioWhatsAppNumber,
                    'To' => 'whatsapp:' . $phone,
                    'Body' => $message,
                ]);
                
            if (!$response->successful()) {
                Log::error("Erreur Twilio API: " . $response->body());
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Exception lors de l'envoi WhatsApp (Twilio): " . $e->getMessage());
            return false;
        }

        return true;
    }
}
