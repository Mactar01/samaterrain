<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TimeSlot;
use App\Models\Owner;
use App\Http\Requests\StoreReservationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    /**
     * Liste des réservations du joueur connecté
     */
    public function index(Request $request)
    {
        $reservations = $request->user()->reservations()
            ->with(['field', 'timeSlot'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reservations);
    }

    /**
     * Liste des réservations pour le loueur connecté
     */
    public function ownerIndex(Request $request)
    {
        $owner = Owner::where('user_id', $request->user()->id)->first();
        if (!$owner) return response()->json([]);

        $reservations = Reservation::whereHas('field', function($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->with(['user', 'field', 'timeSlot'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reservations);
    }

    /**
     * Détails d'une réservation
     */
    public function show(Request $request, $id)
    {
        $reservation = Reservation::with(['field', 'timeSlot', 'payment'])->findOrFail($id);
        $this->authorize('view', $reservation);

        return response()->json($reservation);
    }

    /**
     * Créer une réservation (Anti double-booking via transactions & locks)
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = DB::transaction(function () use ($request) {
                // VERROUILLAGE PESSIMISTE : on verrouille la ligne du créneau en BDD
                // Personne d'autre ne peut lire/modifier ce créneau jusqu'à la fin de la transaction.
                $slot = TimeSlot::where('id', $request->time_slot_id)->lockForUpdate()->firstOrFail();

                if (!$slot->isAvailable()) {
                    // Annulation de la transaction, le créneau est déjà pris !
                    abort(409, 'Ce créneau n\'est plus disponible.');
                }

                $user = $request->user();
                $field = $slot->field;
                $price = $slot->effectivePrice();
                
                // Calcul de la commission du loueur (ex: 10%)
                $owner = $field->owner;
                $commission = $price * ($owner->commission_rate / 100);

                // 1. Créer la réservation en 'pending'
                $reservation = Reservation::create([
                    'user_id'      => $user->id,
                    'field_id'     => $field->id,
                    'time_slot_id' => $slot->id,
                    'status'       => 'pending',
                    'total_price'  => $price,
                    'commission'   => $commission,
                    'notes'        => $request->notes,
                ]);

                // On NE met PAS le créneau en 'reserved' ici car il n'a pas encore payé l'acompte.
                // Il restera 'available' jusqu'au paiement.

                return $reservation;
            });

            return response()->json([
                'message'     => 'Réservation mise en attente de paiement.',
                'reservation' => $reservation->load(['field', 'timeSlot'])
            ], 201);

        } catch (QueryException $e) {
            // Sécurité niveau 2 : La base de données a rejeté l'insertion (Unique Index)
            return response()->json([
                'message' => 'Conflit de réservation. Le créneau a été réservé à la même milliseconde par un autre joueur.'
            ], 409);
        } catch (\Exception $e) {
            // Récupère l'abort(409)
            if ($e->getCode() == 409 || $e->getMessage() == 'Ce créneau n\'est plus disponible.') {
                return response()->json(['message' => 'Ce créneau n\'est plus disponible.'], 409);
            }
            throw $e;
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('cancel', $reservation);

        if (!$reservation->canBeCancelled()) {
            return response()->json(['message' => 'Cette réservation ne peut plus être annulée.'], 400);
        }

        DB::transaction(function () use ($reservation) {
            $reservation->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => 'Annulée par le joueur'
            ]);

            // Libérer le créneau
            $reservation->timeSlot->update(['status' => 'available']);
        });

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }

    /**
     * Confirmer une réservation (Action du Loueur)
     */
    public function confirm(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('confirm', $reservation);

        if (!$reservation->isPending()) {
            return response()->json(['message' => 'La réservation n\'est pas en attente.'], 400);
        }

        $reservation->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'Réservation confirmée.',
            'reservation' => $reservation
        ]);
    }
}

