<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PayDunyaService;
use App\Services\WhatsAppService;
use App\Notifications\NewReservationNotification;

class PaymentController extends Controller
{
    public function pay(Request $request, $reservationId, PayDunyaService $payDunya)
    {
        $request->validate([
            'method'       => 'required|in:wave,orange_money,free_money,card,cash',
            'phone'        => 'required_unless:method,card,cash|string',
            'payment_type' => 'required|in:full,half,custom',
            'custom_amount'=> 'required_if:payment_type,custom|numeric|min:0',
        ]);

        $reservation = Reservation::findOrFail($reservationId);

        if ($reservation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        if ($reservation->status !== 'pending') {
            return response()->json(['message' => 'Cette réservation ne peut plus être payée.'], 400);
        }

        $totalPrice = $reservation->total_price;
        $minAmount  = $totalPrice / 2;

        if ($request->payment_type === 'custom') {
            $amountToPay = (float) $request->custom_amount;
            if ($amountToPay < $minAmount) {
                return response()->json(['message' => "Le montant minimum est de {$minAmount} FCFA."], 422);
            }
            if ($amountToPay > $totalPrice) {
                $amountToPay = $totalPrice;
            }
        } elseif ($request->payment_type === 'half') {
            $amountToPay = $minAmount;
        } else {
            $amountToPay = $totalPrice;
        }

        // --- Intégration PayDunya ---
        $invoice = $payDunya->createInvoice($reservation, $amountToPay);

        if (!$invoice['success']) {
            return response()->json(['message' => $invoice['message']], 500);
        }

        // On enregistre une tentative de paiement "pending"
        \App\Models\Payment::create([
            'reservation_id'  => $reservation->id,
            'amount'          => $amountToPay,
            'method'          => $request->method,
            'status'          => 'pending', // <--- PENDING
            'transaction_ref' => $invoice['token'], // On utilise le token de la facture comme ref
            'metadata'        => json_encode(['phone' => $request->phone, 'type' => $request->payment_type])
        ]);

        return response()->json([
            'message' => 'Redirection vers la plateforme de paiement.',
            'payment_url' => $invoice['url'] // Le frontend (Flutter) va ouvrir cette URL
        ]);
    }

    /**
     * Webhook appelé par PayDunya quand le client a payé
     */
    public function webhook(Request $request)
    {
        Log::info('PayDunya Webhook Received: ', $request->all());

        $status = $request->input('status');
        $token = $request->input('token'); // ou hash selon l'API

        if ($status !== 'success' && $status !== 'completed') {
            return response()->json(['status' => 'ignored']);
        }

        $payment = Payment::where('transaction_ref', $token)->where('status', 'pending')->first();

        if (!$payment) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $reservation = $payment->reservation;

        DB::transaction(function () use ($reservation, $payment) {
            $slot = \App\Models\TimeSlot::where('id', $reservation->time_slot_id)->lockForUpdate()->first();

            $payment->update([
                'status' => 'completed',
                'paid_at' => now()
            ]);

            $reservation->update(['status' => 'confirmed']);
            if ($slot) $slot->update(['status' => 'reserved']);
        });

        // Notifications
        $ownerUser = $reservation->field->owner->user ?? null;
        if ($ownerUser) {
            $ownerUser->notify(new NewReservationNotification($reservation));
            $whatsappService = new WhatsAppService();
            
            $whatsappService->sendMessage(
                $ownerUser->phone ?? '+221000000000', 
                "✅ *NOUVELLE RÉSERVATION* \nTerrain: {$reservation->field->name}\nMontant payé: {$payment->amount} FCFA"
            );

            if ($reservation->user->phone) {
                $whatsappService->sendMessage(
                    $reservation->user->phone, 
                    "✅ *RÉSERVATION CONFIRMÉE* \nTerrain: {$reservation->field->name}\nMontant payé: {$payment->amount} FCFA"
                );
            }
        }

        return response()->json(['status' => 'success']);
    }
}
