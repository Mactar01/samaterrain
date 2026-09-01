<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Simulation de paiement Mobile Money
     */
    public function pay(Request $request, $reservationId)
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
        $minAmount  = $totalPrice / 2; // Minimum obligatoire : 50%

        if ($request->payment_type === 'custom') {
            $amountToPay = (float) $request->custom_amount;
            if ($amountToPay < $minAmount) {
                return response()->json([
                    'message' => "Le montant minimum est de {$minAmount} FCFA (50% du total)."
                ], 422);
            }
            if ($amountToPay > $totalPrice) {
                $amountToPay = $totalPrice;
            }
        } elseif ($request->payment_type === 'half') {
            $amountToPay = $minAmount;
        } else {
            $amountToPay = $totalPrice;
        }

        // SIMULATION : On considère que le paiement réussit toujours.
        // Dans la vraie vie, on appellerait l'API Wave/Orange Money ici, 
        // puis on redirigerait ou on attendrait le webhook.

        DB::transaction(function () use ($reservation, $request, $amountToPay) {
            // Verrouiller le créneau pour éviter les conflits au moment du paiement
            $slot = \App\Models\TimeSlot::where('id', $reservation->time_slot_id)->lockForUpdate()->firstOrFail();

            if (!$slot->isAvailable()) {
                abort(409, "Désolé, ce créneau vient tout juste d'être payé et réservé par un autre joueur.");
            }

            // 1. Créer la trace du paiement
            \App\Models\Payment::create([
                'reservation_id'  => $reservation->id,
                'amount'          => $amountToPay,
                'method'          => $request->method,
                'status'          => 'completed',
                'transaction_ref' => 'TXN_' . strtoupper(\Illuminate\Support\Str::random(10)),
                'paid_at'         => now(),
                'metadata'        => json_encode(['phone' => $request->phone, 'type' => $request->payment_type])
            ]);

            // 2. Confirmer la réservation (l'acompte de 50% suffit pour confirmer)
            $reservation->update(['status' => 'confirmed']);
            
            // 3. Bloquer le créneau pour les autres
            $slot->update(['status' => 'reserved']);
        });

        // --- NOTIFICATIONS ---
        // On récupère le gérant du terrain (owner)
        $ownerUser = $reservation->field->owner->user;
        if ($ownerUser) {
            // 1. Notification in-app (base de données)
            $ownerUser->notify(new \App\Notifications\NewReservationNotification($reservation));

            // 2. Notification WhatsApp
            $whatsappService = new \App\Services\WhatsAppService();
            $ownerPhone = $ownerUser->phone ?? '+221000000000'; // Fallback si pas renseigné
            
            $playerName = $reservation->user->name;
            $fieldName = $reservation->field->name;
            $date = \Carbon\Carbon::parse($reservation->timeSlot->date)->format('d/m/Y');
            $time = $reservation->timeSlot->start_time . ' - ' . $reservation->timeSlot->end_time;
            $advance = $amountToPay;
            $total = $reservation->total_price;
            $remainder = $total - $advance;
            
            $whatsappMsg = "✅ *NOUVELLE RÉSERVATION CONFIRMÉE*\n\n"
                         . "👤 *Joueur :* $playerName\n"
                         . "🏟️ *Terrain :* $fieldName\n"
                         . "📅 *Date :* $date\n"
                         . "⏰ *Heure :* $time\n"
                         . "💰 *Acompte payé :* $advance FCFA\n"
                         . "🧾 *Reste à payer sur place :* $remainder FCFA\n\n"
                         . "Connectez-vous à votre Espace Loueur pour plus de détails.";
            
            $whatsappService->sendMessage($ownerPhone, $whatsappMsg);
        }

        return response()->json([
            'message' => 'Paiement effectué avec succès. Réservation confirmée !',
            'reservation' => $reservation->fresh(['payment'])
        ]);
    }
}
