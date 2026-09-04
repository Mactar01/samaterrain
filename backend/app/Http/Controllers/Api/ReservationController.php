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
     * Liste des rÃƒÆ’Ã‚Â©servations du joueur connectÃƒÆ’Ã‚Â©
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
     * Liste des rÃƒÆ’Ã‚Â©servations pour le loueur connectÃƒÆ’Ã‚Â©
     */
    public function ownerIndex(Request $request)
    {
        $owner = Owner::where('user_id', $request->user()->id)->first();
        if (!$owner) return response()->json([]);

        $reservations = Reservation::whereHas('field', function($q) use ($owner) {
                $q->where('owner_id', $owner->id);
            })
            ->with(['user', 'field', 'timeSlot', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($reservations);
    }

    /**
     * DÃƒÆ’Ã‚Â©tails d'une rÃƒÆ’Ã‚Â©servation
     */
    public function show(Request $request, $id)
    {
        $reservation = Reservation::with(['field', 'timeSlot', 'payment'])->findOrFail($id);
        $this->authorize('view', $reservation);

        return response()->json($reservation);
    }

    /**
     * CrÃƒÆ’Ã‚Â©er une rÃƒÆ’Ã‚Â©servation (Anti double-booking via transactions & locks)
     */
    public function store(StoreReservationRequest $request)
    {
        try {
            $reservation = DB::transaction(function () use ($request) {
                // VERROUILLAGE PESSIMISTE : on verrouille la ligne du crÃƒÆ’Ã‚Â©neau en BDD
                // Personne d'autre ne peut lire/modifier ce crÃƒÆ’Ã‚Â©neau jusqu'ÃƒÆ’Ã‚Â  la fin de la transaction.
                $slot = TimeSlot::where('id', $request->time_slot_id)->lockForUpdate()->firstOrFail();

                if (!$slot->isAvailable()) {
                    // Annulation de la transaction, le crÃƒÆ’Ã‚Â©neau est dÃƒÆ’Ã‚Â©jÃƒÆ’Ã‚Â  pris !
                    abort(409, 'Ce crÃƒÆ’Ã‚Â©neau n\'est plus disponible.');
                }

                $user = $request->user();
                $field = $slot->field;
                $price = $slot->effectivePrice();
                
                // Calcul de la commission du loueur (ex: 10%)
                $owner = $field->owner;
                $commission = $price * ($owner->commission_rate / 100);

                // 1. CrÃƒÆ’Ã‚Â©er la rÃƒÆ’Ã‚Â©servation en 'pending'
                $reservation = Reservation::create([
                    'user_id'      => $user->id,
                    'field_id'     => $field->id,
                    'time_slot_id' => $slot->id,
                    'status'       => 'pending',
                    'total_price'  => $price,
                    'commission'   => $commission,
                    'notes'        => $request->notes,
                ]);

                // On NE met PAS le crÃƒÆ’Ã‚Â©neau en 'reserved' ici car il n'a pas encore payÃƒÆ’Ã‚Â© l'acompte.
                // Il restera 'available' jusqu'au paiement.

                return $reservation;
            });

            return response()->json([
                'message'     => 'RÃƒÆ’Ã‚Â©servation mise en attente de paiement.',
                'reservation' => $reservation->load(['field', 'timeSlot'])
            ], 201);

        } catch (QueryException $e) {
            // SÃƒÆ’Ã‚Â©curitÃƒÆ’Ã‚Â© niveau 2 : La base de donnÃƒÆ’Ã‚Â©es a rejetÃƒÆ’Ã‚Â© l'insertion (Unique Index)
            return response()->json([
                'message' => 'Conflit de rÃƒÆ’Ã‚Â©servation. Le crÃƒÆ’Ã‚Â©neau a ÃƒÆ’Ã‚Â©tÃƒÆ’Ã‚Â© rÃƒÆ’Ã‚Â©servÃƒÆ’Ã‚Â© ÃƒÆ’Ã‚Â  la mÃƒÆ’Ã‚Âªme milliseconde par un autre joueur.'
            ], 409);
        } catch (\Exception $e) {
            // RÃƒÆ’Ã‚Â©cupÃƒÆ’Ã‚Â¨re l'abort(409)
            if ($e->getCode() == 409 || $e->getMessage() == 'Ce crÃƒÆ’Ã‚Â©neau n\'est plus disponible.') {
                return response()->json(['message' => 'Ce crÃƒÆ’Ã‚Â©neau n\'est plus disponible.'], 409);
            }
            throw $e;
        }
    }

    /**
     * Annuler une rÃƒÆ’Ã‚Â©servation
     */
    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('cancel', $reservation);

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'Cette réservation est déjà annulée.'], 400);
        }

        DB::transaction(function () use ($reservation, $request) {
            $isOwner = $request->user()->isOwner();
            $reason = $isOwner ? 'Annulée par le gérant' : 'Annulée par le joueur';

            $reservation->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => $reason
            ]);

            if ($reservation->timeSlot) {
                $reservation->timeSlot->update(['status' => 'available']);
            }
        });

        return response()->json(['message' => 'Réservation annulée avec succès.']);
    }
    public function confirm(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        $this->authorize('confirm', $reservation);

        if (!$reservation->isPending()) {
            return response()->json(['message' => 'La rÃƒÆ’Ã‚Â©servation n\'est pas en attente.'], 400);
        }

        $reservation->update(['status' => 'confirmed']);

        return response()->json([
            'message' => 'RÃƒÆ’Ã‚Â©servation confirmÃƒÆ’Ã‚Â©e.',
            'reservation' => $reservation
        ]);
    }
}




